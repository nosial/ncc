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

    namespace ncc\Classes;

    use ncc\Enums\SignatureStatus;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\Package\PackageSignature;
    use ncc\Objects\Signing\TrustedKey;
    use ncc\Objects\Signing\VerificationResult;
    use SodiumException;

    class SignatureVerifier
    {
        /**
         * Returns whether ext-sodium is available to sign and verify packages
         *
         * @return bool True if ext-sodium is loaded
         */
        public static function isSupported(): bool
        {
            return extension_loaded('sodium') && function_exists('sodium_crypto_sign_verify_detached');
        }

        /**
         * Throws if ext-sodium is not available
         *
         * @throws OperationException If ext-sodium is not loaded
         */
        public static function requireSodium(): void
        {
            if(!self::isSupported())
            {
                throw new OperationException('The PHP extension "sodium" is required to sign packages and manage signing keys');
            }
        }

        /**
         * Verifies the signatures of a package
         *
         * @param PackageReader $packageReader The package to verify
         * @param TrustStore[] $trustStores The trust stores to look up trusted keys in, in addition to the built-in keys
         * @return VerificationResult The verification result
         */
        public static function verify(PackageReader $packageReader, array $trustStores=[]): VerificationResult
        {
            if($packageReader->getSignatureError() !== null)
            {
                return new VerificationResult(SignatureStatus::INVALID, sprintf('The signature block of the package is corrupted (%s), the package may have been tampered with', $packageReader->getSignatureError()));
            }

            if($packageReader->isSignatureUnsupported())
            {
                return new VerificationResult(SignatureStatus::UNSUPPORTED, 'The package is signed using a signature format this version of ncc does not support');
            }

            $signatures = $packageReader->getSignatures();
            if(count($signatures) === 0)
            {
                return new VerificationResult(SignatureStatus::UNSIGNED, 'The package is not signed');
            }

            if(!self::isSupported())
            {
                return new VerificationResult(SignatureStatus::UNSUPPORTED, 'The package is signed but the PHP extension "sodium" is not available to verify it');
            }

            try
            {
                $digest = $packageReader->computeBodyDigest(PackageSignature::DIGEST_SHA512);
            }
            catch(IOException $e)
            {
                return new VerificationResult(SignatureStatus::INVALID, 'Failed to read the package to verify its signatures: ' . $e->getMessage());
            }

            $trustedKeys = BuiltinTrustedKeys::getKeys();
            foreach($trustStores as $trustStore)
            {
                array_push($trustedKeys, ...$trustStore->getEntries());
            }

            $entries = [];
            foreach($signatures as $signature)
            {
                [$status, $message] = self::verifySignature($signature, $digest, $packageReader, $trustedKeys);
                Logger::getLogger()?->debug(sprintf('Signature by %s (%s): %s', $signature->describeSigner(), $signature->getFingerprint(), $message));
                $entries[] = ['signature' => $signature, 'status' => $status, 'message' => $message];
            }

            return VerificationResult::fromEntries($entries);
        }

        /**
         * Verifies a single signature
         *
         * @param PackageSignature $signature The signature to verify
         * @param string $digest The SHA-512 digest of the package body
         * @param PackageReader $packageReader The package the signature belongs to
         * @param TrustedKey[] $trustedKeys The trusted keys
         * @return array{0: SignatureStatus, 1: string} The status and a message describing it
         */
        private static function verifySignature(PackageSignature $signature, string $digest, PackageReader $packageReader, array $trustedKeys): array
        {
            $signer = $signature->describeSigner();
            $fingerprint = $signature->getFingerprint();

            if($signature->getAlgorithm() !== PackageSignature::ALGORITHM_ED25519 || $signature->getDigestAlgorithm() !== PackageSignature::DIGEST_SHA512)
            {
                return [SignatureStatus::UNSUPPORTED, sprintf('The signature by %s uses an unsupported algorithm (%s/%s)', $signer, $signature->getAlgorithm(), $signature->getDigestAlgorithm())];
            }

            if(strlen($signature->getPublicKey()) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($signature->getSignature()) !== SODIUM_CRYPTO_SIGN_BYTES)
            {
                return [SignatureStatus::INVALID, sprintf('The signature by %s is malformed', $signer)];
            }

            // Integrity
            if(!hash_equals($signature->getDigest(), $digest))
            {
                return [SignatureStatus::INVALID, sprintf('The package contents do not match the signature by %s (%s), the package was modified after it was signed', $signer, $fingerprint)];
            }

            try
            {
                $valid = sodium_crypto_sign_verify_detached($signature->getSignature(), $signature->getSignedMessage(), $signature->getPublicKey());
            }
            catch(SodiumException)
            {
                $valid = false;
            }

            if(!$valid)
            {
                return [SignatureStatus::INVALID, sprintf('The signature by %s (%s) is not valid', $signer, $fingerprint)];
            }

            // Identity, the assembly is part of the signed body so this cannot be altered without breaking the signature
            $assemblyValue = $signature->getSignerType()->getAssemblyValue($packageReader->getAssembly());
            if($assemblyValue === null || $assemblyValue !== $signature->getSigner())
            {
                return [SignatureStatus::INVALID, sprintf('The package is signed as %s but its assembly declares %s %s', $signer,
                    $signature->getSignerType()->value, $assemblyValue === null ? 'nothing' : sprintf('"%s"', $assemblyValue))];
            }

            // Trust
            $keyId = $signature->getKeyId();
            $identityKeys = [];
            foreach($trustedKeys as $trustedKey)
            {
                if($trustedKey->getKeyId() === $keyId && $trustedKey->isRevoked())
                {
                    return [SignatureStatus::INVALID, sprintf('The package is signed by %s with the revoked key %s', $signer, $fingerprint)];
                }

                if($trustedKey->isFor($signature->getSignerType(), $signature->getSigner()) && !$trustedKey->isRevoked())
                {
                    $identityKeys[] = $trustedKey;
                }
            }

            if (array_any($identityKeys, fn($trustedKey) => $trustedKey->getKeyId() === $keyId))
            {
                return [SignatureStatus::VERIFIED, sprintf('Verified signature by %s (%s)', $signer, $fingerprint)];
            }

            if(count($identityKeys) > 0)
            {
                return [SignatureStatus::KEY_MISMATCH, sprintf('The package claims to be signed by %s with the key %s, but you trust a different key for %s (%s). The package may have been tampered with, or the publisher changed their key; if you expected this, trust the new key with "ncc trust add"',
                    $signer, $fingerprint, $signer, implode(', ', array_map(fn(TrustedKey $key) => $key->getFingerprint(), $identityKeys)))];
            }

            return [SignatureStatus::UNTRUSTED, sprintf('The package is signed by %s with the key %s, which is not trusted yet', $signer, $fingerprint)];
        }
    }
