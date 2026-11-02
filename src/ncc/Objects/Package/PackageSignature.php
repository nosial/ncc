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

    namespace ncc\Objects\Package;

    use InvalidArgumentException;
    use ncc\Enums\SignerType;
    use ncc\Interfaces\SerializableInterface;
    use function msgpack_pack;

    class PackageSignature implements SerializableInterface
    {
        /** Domain separation prefix of the signed message */
        public const string MESSAGE_PREFIX = "NCC-PACKAGE-SIGNATURE-V1\0";
        public const string ALGORITHM_ED25519 = 'ed25519';
        public const string DIGEST_SHA512 = 'sha512';

        private SignerType $signerType;
        private string $signer;
        private string $algorithm;
        private string $publicKey;
        private string $digestAlgorithm;
        private string $digest;
        private int $signedAt;
        private string $signature;

        /**
         * Public constructor
         *
         * @param SignerType $signerType The identity type the signature is made on behalf of
         * @param string $signer The organization or author name, must match the package's assembly
         * @param string $algorithm The signature algorithm
         * @param string $publicKey The raw public key of the signer
         * @param string $digestAlgorithm The algorithm of the package body digest
         * @param string $digest The raw digest of the package body
         * @param int $signedAt Unix timestamp of when the signature was made (informational, asserted by the signer)
         * @param string $signature The raw signature of the signed message
         */
        public function __construct(SignerType $signerType, string $signer, string $algorithm, string $publicKey,
            string $digestAlgorithm, string $digest, int $signedAt, string $signature)
        {
            $this->signerType = $signerType;
            $this->signer = $signer;
            $this->algorithm = $algorithm;
            $this->publicKey = $publicKey;
            $this->digestAlgorithm = $digestAlgorithm;
            $this->digest = $digest;
            $this->signedAt = $signedAt;
            $this->signature = $signature;
        }

        /**
         * Retrieves the signer type associated with the signature.
         *
         * @return SignerType The identity type the signature is made on behalf of.
         */
        public function getSignerType(): SignerType
        {
            return $this->signerType;
        }

        /**
         * Retrieves the signer.
         *
         * @return string The signer value.
         */
        public function getSigner(): string
        {
            return $this->signer;
        }

        /**
         * Retrieves the algorithm.
         *
         * @return string The algorithm value.
         */
        public function getAlgorithm(): string
        {
            return $this->algorithm;
        }

        /**
         * Retrieves the public key.
         *
         * @return string The public key value.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the digest algorithm.
         *
         * @return string The digest algorithm value.
         */
        public function getDigestAlgorithm(): string
        {
            return $this->digestAlgorithm;
        }

        /**
         * Retrieves the digest.
         *
         * @return string The digest value.
         */
        public function getDigest(): string
        {
            return $this->digest;
        }

        /**
         * Retrieves the signed timestamp.
         *
         * @return int The signed timestamp value.
         */
        public function getSignedAt(): int
        {
            return $this->signedAt;
        }

        /**
         * Retrieves the signature.
         *
         * @return string The signature value.
         */
        public function getSignature(): string
        {
            return $this->signature;
        }

        /**
         * Retrieves the key ID.
         *
         * @return string The computed key ID value.
         */
        public function getKeyId(): string
        {
            return self::computeKeyId($this->publicKey);
        }

        /**
         * Retrieves the fingerprint.
         *
         * @return string The formatted fingerprint value.
         */
        public function getFingerprint(): string
        {
            return self::formatFingerprint($this->getKeyId());
        }

        /**
         * Returns a description of the signer, eg; organization "Nosial"
         *
         * @return string The description of the signer
         */
        public function describeSigner(): string
        {
            return sprintf('%s "%s"', $this->signerType->value, $this->signer);
        }

        /**
         * Returns the message that is signed by this signature
         *
         * @return string The signed message
         */
        public function getSignedMessage(): string
        {
            return self::createSignedMessage($this->signerType, $this->signer, $this->publicKey, $this->digestAlgorithm, $this->digest, $this->signedAt);
        }

        /**
         * Builds the message that is signed for a package signature
         *
         * @param SignerType $signerType The identity type of the signer
         * @param string $signer The identity of the signer
         * @param string $publicKey The raw public key of the signer
         * @param string $digestAlgorithm The digest algorithm
         * @param string $digest The raw digest of the package body
         * @param int $signedAt The timestamp of the signature
         * @return string The message to sign
         */
        public static function createSignedMessage(SignerType $signerType, string $signer, string $publicKey, string $digestAlgorithm, string $digest, int $signedAt): string
        {
            return self::MESSAGE_PREFIX . msgpack_pack([$signerType->value, $signer, $publicKey, $digestAlgorithm, $digest, $signedAt]);
        }

        /**
         * Computes the key ID of a public key, the first 16 bytes of its SHA-256 hash in hexadecimal
         *
         * @param string $publicKey The raw public key
         * @return string The key ID
         */
        public static function computeKeyId(string $publicKey): string
        {
            return substr(hash('sha256', $publicKey), 0, 32);
        }

        /**
         * Formats a key ID as a human-readable fingerprint, eg; 1A2B 3C4D ...
         *
         * @param string $keyId The key ID
         * @return string The fingerprint
         */
        public static function formatFingerprint(string $keyId): string
        {
            return implode(' ', str_split(strtoupper($keyId), 4));
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'type' => $this->signerType->value,
                'signer' => $this->signer,
                'alg' => $this->algorithm,
                'public_key' => $this->publicKey,
                'digest_alg' => $this->digestAlgorithm,
                'digest' => $this->digest,
                'signed_at' => $this->signedAt,
                'signature' => $this->signature
            ];
        }

        /**
         * @inheritDoc
         * @throws InvalidArgumentException If the array is not a valid signature
         */
        public static function fromArray(array $data): PackageSignature
        {
            foreach(['type', 'signer', 'alg', 'public_key', 'digest_alg', 'digest', 'signature'] as $field)
            {
                if(!isset($data[$field]) || !is_string($data[$field]))
                {
                    throw new InvalidArgumentException(sprintf('The signature property "%s" is missing or not a string', $field));
                }
            }

            if(!isset($data['signed_at']) || !is_int($data['signed_at']))
            {
                throw new InvalidArgumentException('The signature property "signed_at" is missing or not an integer');
            }

            $signerType = SignerType::tryFrom($data['type']);
            if($signerType === null)
            {
                throw new InvalidArgumentException(sprintf('Unknown signer type "%s"', $data['type']));
            }

            return new PackageSignature($signerType, $data['signer'], $data['alg'], $data['public_key'], $data['digest_alg'],
                $data['digest'], $data['signed_at'], $data['signature']);
        }
    }
