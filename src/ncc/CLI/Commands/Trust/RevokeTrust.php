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
    use ncc\Classes\BuiltinTrustedKeys;
    use ncc\Classes\Console;
    use ncc\Classes\TrustStore;
    use ncc\Objects\Signing\TrustedKey;
    use ncc\Runtime;

    class RevokeTrust extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $keyId = $argv['key-id'] ?? null;

            // Revoke a key that is not in the trust store by its signer and public key
            if(!is_string($keyId))
            {
                $trustedKey = AddTrust::parseKey($argv, TrustedKey::SOURCE_MANUAL, true);
                if($trustedKey === null)
                {
                    return 1;
                }

                $trustStore = Runtime::getTrustStore();
                $trustStore->add($trustedKey);
                if(!$trustStore->save())
                {
                    Console::error(sprintf('Failed to save the trust store %s, check the permissions', $trustStore->getTrustStorePath()));
                    return 1;
                }

                Console::out(sprintf('Revoked the key %s', $trustedKey->getFingerprint()));
                return 0;
            }

            $keyId = TrustStore::normalizeKeyId($keyId);
            $revoked = 0;
            foreach(Runtime::getTrustStores() as $trustStore)
            {
                $count = $trustStore->revoke($keyId);
                if($count === 0)
                {
                    continue;
                }

                if(!$trustStore->save())
                {
                    Console::error(sprintf('Failed to save the trust store %s, check the permissions', $trustStore->getTrustStorePath()));
                    return 1;
                }

                $revoked += $count;
            }

            // Built-in keys are revoked by adding a revoked copy to the trust store
            foreach(BuiltinTrustedKeys::getKeys() as $builtinKey)
            {
                if($builtinKey->getKeyId() !== $keyId)
                {
                    continue;
                }

                $trustStore = Runtime::getTrustStore();
                $trustStore->add(new TrustedKey($builtinKey->getSignerType(), $builtinKey->getSigner(), $builtinKey->getPublicKey(), TrustedKey::SOURCE_MANUAL, null, true));
                if(!$trustStore->save())
                {
                    Console::error(sprintf('Failed to save the trust store %s, check the permissions', $trustStore->getTrustStorePath()));
                    return 1;
                }

                $revoked++;
            }

            if($revoked === 0)
            {
                Console::error('No trusted key matches the given key ID, use --type, --signer and --public-key to revoke an unknown key');
                return 1;
            }

            Console::out(sprintf('Revoked the key %s', strtoupper($keyId)));
            return 0;
        }
    }
