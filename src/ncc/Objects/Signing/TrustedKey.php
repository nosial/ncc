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

    use InvalidArgumentException;
    use ncc\Enums\SignerType;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\Package\PackageSignature;

    class TrustedKey implements SerializableInterface
    {
        public const string SOURCE_BUILTIN = 'builtin';
        public const string SOURCE_PROMPT = 'prompt';
        public const string SOURCE_MANUAL = 'manual';

        private SignerType $signerType;
        private string $signer;
        private string $publicKey;
        private string $source;
        private int $added;
        private bool $revoked;

        /**
         * Public constructor
         *
         * @param SignerType $signerType The identity type the key is trusted for
         * @param string $signer The organization or author name the key is trusted for
         * @param string $publicKey The raw Ed25519 public key
         * @param string $source How the key was trusted, one of the SOURCE_* constants
         * @param int|null $added Unix timestamp of when the key was trusted, defaults to now
         * @param bool $revoked Whether the key is revoked
         */
        public function __construct(SignerType $signerType, string $signer, string $publicKey, string $source=self::SOURCE_MANUAL, ?int $added=null, bool $revoked=false)
        {
            if(strlen($publicKey) !== 32)
            {
                throw new InvalidArgumentException('The trusted key is not a valid Ed25519 public key');
            }

            if(trim($signer) === '')
            {
                throw new InvalidArgumentException('The signer of a trusted key cannot be empty');
            }

            $this->signerType = $signerType;
            $this->signer = $signer;
            $this->publicKey = $publicKey;
            $this->source = $source;
            $this->added = $added ?? time();
            $this->revoked = $revoked;
        }

        /**
         * Retrieves the signer type associated with the trusted key.
         *
         * @return SignerType The identity type the key is trusted for.
         */
        public function getSignerType(): SignerType
        {
            return $this->signerType;
        }

        /**
         * Retrieves the signer associated with the current instance.
         *
         * @return string The signer as a string.
         */
        public function getSigner(): string
        {
            return $this->signer;
        }

        /**
         * Retrieves the public key associated with the current instance.
         *
         * @return string The public key as a string.
         */
        public function getPublicKey(): string
        {
            return $this->publicKey;
        }

        /**
         * Retrieves the key identifier computed from the public key.
         *
         * @return string The computed key identifier as a string.
         */
        public function getKeyId(): string
        {
            return PackageSignature::computeKeyId($this->publicKey);
        }

        /**
         * Generates and retrieves the formatted fingerprint based on the key identifier.
         *
         * @return string The formatted fingerprint as a string.
         */
        public function getFingerprint(): string
        {
            return PackageSignature::formatFingerprint($this->getKeyId());
        }

        /**
         * Retrieves the source associated with the current instance.
         *
         * @return string The source as a string.
         */
        public function getSource(): string
        {
            return $this->source;
        }

        /**
         * Retrieves the added value associated with the current instance.
         *
         * @return int The added value as an integer.
         */
        public function getAdded(): int
        {
            return $this->added;
        }

        /**
         * Checks if the current instance is revoked.
         *
         * @return bool True if the instance is revoked, false otherwise.
         */
        public function isRevoked(): bool
        {
            return $this->revoked;
        }

        /**
         * Sets the revoked status of the current instance.
         *
         * @param bool $revoked The revoked status to set.
         * @return void
         */
        public function setRevoked(bool $revoked): void
        {
            $this->revoked = $revoked;
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
         * Returns whether this key is trusted for the given identity, identities are compared case-insensitively so
         * that a lookalike identity ("nosial" vs "Nosial") is detected as a key mismatch instead of an unknown signer
         *
         * @param SignerType $signerType The identity type
         * @param string $signer The organization or author name
         * @return bool True if this key is for the given identity
         */
        public function isFor(SignerType $signerType, string $signer): bool
        {
            return $this->signerType === $signerType && self::normalizeSigner($this->signer) === self::normalizeSigner($signer);
        }

        /**
         * Normalizes a signer name for comparison
         *
         * @param string $signer The signer name
         * @return string The normalized signer name
         */
        public static function normalizeSigner(string $signer): string
        {
            return strtolower(trim($signer));
        }

        /**
         * Decodes a public key given as base64 (as printed by `ncc key export --public`) or hexadecimal
         *
         * @param string $value The encoded public key
         * @return string|null The raw public key, or null if the value is not a valid public key
         */
        public static function decodePublicKey(string $value): ?string
        {
            $value = trim($value);
            if(strlen($value) === 64 && ctype_xdigit($value))
            {
                return hex2bin($value);
            }

            $decoded = base64_decode($value, true);
            if($decoded !== false && strlen($decoded) === 32)
            {
                return $decoded;
            }

            return null;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'type' => $this->signerType->value,
                'signer' => $this->signer,
                'public_key' => base64_encode($this->publicKey),
                'key_id' => $this->getKeyId(),
                'source' => $this->source,
                'added' => $this->added,
                'revoked' => $this->revoked
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): TrustedKey
        {
            $signerType = SignerType::tryFrom($data['type'] ?? '');
            if($signerType === null)
            {
                throw new InvalidArgumentException('The trusted key has an unknown signer type');
            }

            $publicKey = is_string($data['public_key'] ?? null) ? self::decodePublicKey($data['public_key']) : null;
            if($publicKey === null)
            {
                throw new InvalidArgumentException('The trusted key has an invalid public key');
            }

            if(!is_string($data['signer'] ?? null))
            {
                throw new InvalidArgumentException('The trusted key has no signer');
            }

            return new TrustedKey(
                $signerType,
                $data['signer'],
                $publicKey,
                is_string($data['source'] ?? null) ? $data['source'] : self::SOURCE_MANUAL,
                is_int($data['added'] ?? null) ? $data['added'] : null,
                (bool)($data['revoked'] ?? false)
            );
        }
    }
