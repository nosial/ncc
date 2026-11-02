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

    namespace ncc\CLI\Commands\Trust;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Enums\SignerType;
    use ncc\Objects\Signing\TrustedKey;
    use ncc\Runtime;

    class AddTrust extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $trustedKey = self::parseKey($argv, TrustedKey::SOURCE_MANUAL);
            if($trustedKey === null)
            {
                return 1;
            }

            $trustStore = isset($argv['system']) ? Runtime::getSystemTrustStore() : Runtime::getTrustStore();
            $trustStore->add($trustedKey);
            if(!$trustStore->save())
            {
                Console::error(sprintf('Failed to save the trust store %s, check the permissions', $trustStore->getTrustStorePath()));
                return 1;
            }

            Console::out(sprintf('Trusted the key %s for %s', $trustedKey->getFingerprint(), $trustedKey->describeSigner()));
            return 0;
        }

        /**
         * Parses --type, --signer and --public-key into a TrustedKey, printing an error if they are missing
         *
         * @param array $argv The command arguments
         * @param string $source The source of the key
         * @param bool $revoked Whether the key is revoked
         * @return TrustedKey|null The key, or null if the arguments are invalid
         */
        public static function parseKey(array $argv, string $source, bool $revoked=false): ?TrustedKey
        {
            $type = $argv['type'] ?? $argv['t'] ?? null;
            $signer = $argv['signer'] ?? null;
            $publicKey = $argv['public-key'] ?? null;

            $signerType = is_string($type) ? SignerType::tryFrom(strtolower($type)) : null;
            if($signerType === null)
            {
                Console::error('A signer type is required, use --type=organization or --type=author');
                return null;
            }

            if(!is_string($signer) || trim($signer) === '')
            {
                Console::error('A signer is required, use --signer to specify the organization or author name');
                return null;
            }

            $decoded = is_string($publicKey) ? TrustedKey::decodePublicKey($publicKey) : null;
            if($decoded === null)
            {
                Console::error('A valid public key is required, use --public-key with the output of "ncc key export --public"');
                return null;
            }

            return new TrustedKey($signerType, $signer, $decoded, $source, null, $revoked);
        }
    }
