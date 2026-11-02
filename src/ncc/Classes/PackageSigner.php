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

    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\Package\PackageSignature;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Signing\SigningKey;

    class PackageSigner
    {
        /**
         * Checks that a signing key may sign a package with the given assembly
         *
         * @param SigningKey $signingKey The signing key
         * @param Assembly $assembly The assembly of the package
         * @throws OperationException If the identity of the key does not match the assembly
         */
        public static function assertSignerMatches(SigningKey $signingKey, Assembly $assembly): void
        {
            $assemblyValue = $signingKey->getSignerType()->getAssemblyValue($assembly);
            if($assemblyValue === null)
            {
                throw new OperationException(sprintf('The signing key signs as %s, but the package assembly does not declare an %s',
                    $signingKey->describeSigner(), $signingKey->getSignerType()->value));
            }

            if($assemblyValue !== $signingKey->getSigner())
            {
                throw new OperationException(sprintf('The signing key signs as %s, but the package assembly declares %s "%s"',
                    $signingKey->describeSigner(), $signingKey->getSignerType()->value, $assemblyValue));
            }
        }

        /**
         * Signs a package in place, a signature made earlier by the same key is replaced
         *
         * @param string $packagePath The path to the package (or an executable with an embedded package)
         * @param SigningKey $signingKey The key to sign the package with
         * @return PackageSignature The added signature
         * @throws OperationException If the key does not match the package or signing failed
         * @throws IOException If the package cannot be read or written
         */
        public static function sign(string $packagePath, SigningKey $signingKey): PackageSignature
        {
            SignatureVerifier::requireSodium();
            Logger::getLogger()?->verbose(sprintf('Signing %s as %s (%s)', $packagePath, $signingKey->describeSigner(), $signingKey->getFingerprint()));

            $packageReader = new PackageReader($packagePath);
            self::assertSignerMatches($signingKey, $packageReader->getAssembly());

            $signatures = [];
            if($packageReader->getSignatureError() !== null)
            {
                Logger::getLogger()?->warning(sprintf('Discarding the corrupted signature block of %s', $packagePath));
            }
            elseif($packageReader->isSignatureUnsupported())
            {
                throw new OperationException('The package is signed using a signature format this version of ncc does not support, it cannot be signed again');
            }
            else
            {
                foreach($packageReader->getSignatures() as $signature)
                {
                    if($signature->getKeyId() === $signingKey->getKeyId())
                    {
                        Logger::getLogger()?->verbose('Replacing the existing signature made by the same key');
                        continue;
                    }

                    $signatures[] = $signature;
                }
            }

            $digest = $packageReader->computeBodyDigest(PackageSignature::DIGEST_SHA512);
            $signedAt = time();
            $message = PackageSignature::createSignedMessage($signingKey->getSignerType(), $signingKey->getSigner(),
                $signingKey->getPublicKey(), PackageSignature::DIGEST_SHA512, $digest, $signedAt);

            $signature = new PackageSignature(
                $signingKey->getSignerType(),
                $signingKey->getSigner(),
                PackageSignature::ALGORITHM_ED25519,
                $signingKey->getPublicKey(),
                PackageSignature::DIGEST_SHA512,
                $digest,
                $signedAt,
                $signingKey->sign($message)
            );
            $signatures[] = $signature;

            $bodyEndOffset = $packageReader->getBodyEndOffset();
            $endOffset = $packageReader->getEndOffset();
            unset($packageReader);

            self::writeTrailer($packagePath, $bodyEndOffset, $endOffset, PackageReader::createSignatureTrailer($signatures));
            return $signature;
        }

        /**
         * Replaces the bytes between the end of the package body and the end of the package with a new signature
         * trailer, anything following the package is preserved
         *
         * @param string $packagePath The path to the package
         * @param int $bodyEndOffset The end offset of the package body
         * @param int $endOffset The end offset of the package including its current trailer
         * @param string $trailer The new trailer
         * @throws IOException If the package cannot be written
         */
        private static function writeTrailer(string $packagePath, int $bodyEndOffset, int $endOffset, string $trailer): void
        {
            $handle = fopen($packagePath, 'r+b');
            if($handle === false)
            {
                throw new IOException($packagePath, 'Could not open the package for writing');
            }

            try
            {
                fseek($handle, $endOffset);
                $remainder = stream_get_contents($handle);
                if($remainder === false)
                {
                    throw new IOException($packagePath, 'Could not read the end of the package');
                }

                if(!ftruncate($handle, $bodyEndOffset) || fseek($handle, $bodyEndOffset) !== 0)
                {
                    throw new IOException($packagePath, 'Could not truncate the package');
                }

                $data = $trailer . $remainder;
                if(fwrite($handle, $data) !== strlen($data))
                {
                    throw new IOException($packagePath, 'Could not write the signature trailer');
                }

                fflush($handle);
            }
            finally
            {
                fclose($handle);
            }

            // The metadata cache of the package is now outdated
            if(IO::exists($packagePath . '.cache'))
            {
                IO::delete($packagePath . '.cache');
            }
        }
    }
