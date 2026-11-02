<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

    namespace ncc\Objects\Signing;

    use ncc\Classes\SignatureVerifier;
    use ncc\Enums\SignerType;
    use ncc\Exceptions\OperationException;
    use ncc\Objects\Package\PackageSignature;
    use SodiumException;
    use Throwable;
    use function msgpack_pack;
    use function msgpack_unpack;

    class SigningKey
    {
        public const string EXPORT_PREFIX = 'ncc-sk1:';

        private SignerType $signerType;
        private string $signer;
        private string $publicKey;
        private ?string $secretKey;

        /**
         * Public constructor
         *
         * @param SignerType $signerType The identity type the key signs as
         * @param string $signer The organization or author name the key signs as
         * @param string $secretKey The raw Ed25519 secret key
         * @throws OperationException If the secret key is invalid
         */
        public function __construct(SignerType $signerType, string $signer, string $secretKey)
        {
            SignatureVerifier::requireSodium();
            if(strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES)
            {
                throw new OperationException('The signing key is not a valid Ed25519 secret key');
            }

            if(trim($signer) === '')
            {
                throw new OperationException('The signer of a signing key cannot be empty');
            }

            $this->signerType = $signerType;
            $this->signer = $signer;
            $this->secretKey = $secretKey;

            try
            {
                $this->publicKey = sodium_crypto_sign_publickey_from_secretkey($secretKey);
            }
            catch(SodiumException $e)
            {
                throw new OperationException('The signing key is not a valid Ed25519 secret key', 0, $e);
            }
        }

        /**
         * Generates a new signing key
         *
         * @param SignerType $signerType The identity type the key signs as
         * @param string $signer The organization or author name the key signs as
         * @return SigningKey The generated signing key
         * @throws OperationException If ext-sodium is not available or a SodiumException occurs
         */
        public static function generate(SignerType $signerType, string $signer): SigningKey
        {
            try
            {
                SignatureVerifier::requireSodium();
                $keyPair = sodium_crypto_sign_keypair();
                $key = new SigningKey($signerType, $signer, sodium_crypto_sign_secretkey($keyPair));
                sodium_memzero($keyPair);
            }
            catch (SodiumException $e)
            {
                throw new OperationException('Failed to generate signing key', 0, $e);
            }

            return $key;
        }

        /**
         * Retrieves the type of the signer.
         *
         * @return SignerType The type of the signer.
         */
        public function getSignerType(): SignerType
        {
            return $this->signerType;
        }

        /**
         * Retrieves the signer identifier.
         *
         * @return string The identifier of the signer.
         */
        public function getSigner(): string
        {
            return $this->signer;
        }

        /**
         * Retrieves the public key associated with the signing key.
         *
         * @return string The public key as a string.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Computes and returns the unique identifier for the key.
         *
         * @return string The computed key identifier.
         */
        public function getKeyId(): string
        {
            return PackageSignature::computeKeyId($this->publicKey);
        }

        /**
         * Retrieves the formatted fingerprint of the key.
         *
         * @return string The formatted fingerprint string.
         */
        public function getFingerprint(): string
        {
            return PackageSignature::formatFingerprint($this->getKeyId());
        }

        /**
         * Returns a description of the identity of the key, eg; organization "Nosial"
         *
         * @return string The description of the identity
         */
        public function describeSigner(): string
        {
            return sprintf('%s "%s"', $this->signerType->value, $this->signer);
        }

        /**
         * Signs a message with the secret key
         *
         * @param string $message The message to sign
         * @return string The raw detached signature
         * @throws OperationException If the key was wiped or signing failed
         */
        public function sign(string $message): string
        {
            if($this->secretKey === null)
            {
                throw new OperationException('The signing key is no longer available');
            }

            try
            {
                return sodium_crypto_sign_detached($message, $this->secretKey);
            }
            catch(SodiumException $e)
            {
                throw new OperationException('Failed to sign the message: ' . $e->getMessage(), 0, $e);
            }
        }

        /**
         * Exports the signing key in its encrypted string form
         *
         * @param string $passphrase The passphrase to encrypt the secret key with, cannot be empty
         * @return string The encrypted key, `ncc-sk1:<base64>`
         * @throws OperationException If the passphrase is empty or encryption failed
         */
        public function export(string $passphrase): string
        {
            if($passphrase === '')
            {
                throw new OperationException('The passphrase of a signing key cannot be empty');
            }

            if($this->secretKey === null)
            {
                throw new OperationException('The signing key is no longer available');
            }

            try
            {
                $opsLimit = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
                $memLimit = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
                $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $encryptionKey = self::deriveKey($passphrase, $salt, $opsLimit, $memLimit);
                $ciphertext = sodium_crypto_secretbox($this->secretKey, $nonce, $encryptionKey);
                sodium_memzero($encryptionKey);
            }
            catch(Throwable $e)
            {
                throw new OperationException('Failed to encrypt the signing key: ' . $e->getMessage(), 0, $e);
            }

            return self::EXPORT_PREFIX . base64_encode(msgpack_pack([
                'version' => 1,
                'type' => $this->signerType->value,
                'signer' => $this->signer,
                'public_key' => $this->publicKey,
                'kdf' => 'argon2id13',
                'opslimit' => $opsLimit,
                'memlimit' => $memLimit,
                'salt' => $salt,
                'nonce' => $nonce,
                'ciphertext' => $ciphertext
            ]));
        }

        /**
         * Imports a signing key from its encrypted string form
         *
         * @param string $exported The encrypted key, `ncc-sk1:<base64>`
         * @param string $passphrase The passphrase the key was encrypted with
         * @return SigningKey The decrypted signing key
         * @throws OperationException If the key is malformed or the passphrase is incorrect
         */
        public static function import(string $exported, string $passphrase): SigningKey
        {
            SignatureVerifier::requireSodium();
            $data = self::decodeExported($exported);

            try
            {
                $encryptionKey = self::deriveKey($passphrase, $data['salt'], $data['opslimit'], $data['memlimit']);
                $secretKey = sodium_crypto_secretbox_open($data['ciphertext'], $data['nonce'], $encryptionKey);
                sodium_memzero($encryptionKey);
            }
            catch(Throwable $e)
            {
                throw new OperationException('Failed to decrypt the signing key: ' . $e->getMessage(), 0, $e);
            }

            if($secretKey === false)
            {
                throw new OperationException('Failed to decrypt the signing key, the passphrase is incorrect');
            }

            $key = new SigningKey($data['type'], $data['signer'], $secretKey);

            try
            {
                sodium_memzero($secretKey);
            }
            catch (SodiumException $e)
            {
                throw new OperationException('Failed to zeroize the signing key: ' . $e->getMessage(), 0, $e);
            }

            if(!hash_equals($data['public_key'], $key->getPublicKey()))
            {
                throw new OperationException('The signing key is corrupted, its public key does not match its secret key');
            }

            return $key;
        }

        /**
         * Reads the public information of an encrypted signing key without decrypting it
         *
         * @param string $exported The encrypted key, `ncc-sk1:<base64>`
         * @return array{type: SignerType, signer: string, public_key: string} The identity and public key
         * @throws OperationException If the key is malformed
         */
        public static function inspect(string $exported): array
        {
            $data = self::decodeExported($exported);
            return [
                'type' => $data['type'],
                'signer' => $data['signer'],
                'public_key' => $data['public_key']
            ];
        }

        /**
         * Returns whether the given string looks like an encrypted signing key
         *
         * @param string $value The value to check
         * @return bool True if the value starts with the export prefix
         */
        public static function isExportedKey(string $value): bool
        {
            return str_starts_with(trim($value), self::EXPORT_PREFIX);
        }

        /**
         * Decodes and validates the encrypted string form of a signing key
         *
         * @param string $exported The encrypted key
         * @return array The decoded key data, with 'type' converted to a SignerType
         * @throws OperationException If the key is malformed
         */
        private static function decodeExported(string $exported): array
        {
            $exported = trim($exported);
            if(!str_starts_with($exported, self::EXPORT_PREFIX))
            {
                throw new OperationException(sprintf('The signing key is not in the expected format (expected it to start with "%s")', self::EXPORT_PREFIX));
            }

            $binary = base64_decode(substr($exported, strlen(self::EXPORT_PREFIX)), true);
            $data = $binary === false ? null : @msgpack_unpack($binary);
            if(!is_array($data) || ($data['version'] ?? null) !== 1)
            {
                throw new OperationException('The signing key is corrupted or uses an unsupported format');
            }

            foreach(['type', 'signer', 'public_key', 'kdf', 'salt', 'nonce', 'ciphertext'] as $field)
            {
                if(!isset($data[$field]) || !is_string($data[$field]))
                {
                    throw new OperationException(sprintf('The signing key is corrupted, the property "%s" is missing', $field));
                }
            }

            if(!isset($data['opslimit'], $data['memlimit']) || !is_int($data['opslimit']) || !is_int($data['memlimit']))
            {
                throw new OperationException('The signing key is corrupted, the key derivation parameters are missing');
            }

            if($data['kdf'] !== 'argon2id13')
            {
                throw new OperationException(sprintf('The signing key uses an unsupported key derivation function "%s"', $data['kdf']));
            }

            $signerType = SignerType::tryFrom($data['type']);
            if($signerType === null)
            {
                throw new OperationException(sprintf('The signing key has an unknown signer type "%s"', $data['type']));
            }

            $data['type'] = $signerType;
            return $data;
        }

        /**
         * Derives an encryption key using a passphrase and salt with the Argon2id algorithm.
         *
         * @param string $passphrase The passphrase used to derive the key.
         * @param string $salt A cryptographic salt to ensure unique key derivation.
         * @param int $opsLimit The computational cost of the key derivation function.
         * @param int $memLimit The memory usage limit for the key derivation function.
         * @return string The derived encryption key.
         * @throws OperationException If the key derivation process fails.
         */
        private static function deriveKey(string $passphrase, string $salt, int $opsLimit, int $memLimit): string
        {
            try
            {
                return sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, $passphrase, $salt, $opsLimit, $memLimit, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
            }
            catch (SodiumException $e)
            {
                throw new OperationException('Failed to derive encryption key from passphrase and salt.', $e->getCode(), $e);
            }
        }

        /**
         * Wipes the secret key from memory
         */
        public function __destruct()
        {
            if($this->secretKey !== null)
            {
                sodium_memzero($this->secretKey);
                $this->secretKey = null;
            }
        }
    }
