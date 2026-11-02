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
    use ncc\Runtime;

    class RemoveTrust extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $keyId = $argv['key-id'] ?? null;
            if(!is_string($keyId) || trim($keyId) === '')
            {
                Console::error('A key ID is required, use --key-id with the key ID or fingerprint shown by "ncc trust list"');
                return 1;
            }

            $type = $argv['type'] ?? $argv['t'] ?? null;
            $signer = $argv['signer'] ?? null;
            $signerType = is_string($type) ? SignerType::tryFrom(strtolower($type)) : null;
            if(($type !== null || $signer !== null) && ($signerType === null || !is_string($signer)))
            {
                Console::error('Both --type and --signer are required to remove a key for a single signer');
                return 1;
            }

            $removed = 0;
            foreach(Runtime::getTrustStores() as $trustStore)
            {
                $count = $trustStore->remove($keyId, $signerType, is_string($signer) ? $signer : null);
                if($count === 0)
                {
                    continue;
                }

                if(!$trustStore->save())
                {
                    Console::error(sprintf('Failed to save the trust store %s, check the permissions', $trustStore->getTrustStorePath()));
                    return 1;
                }

                $removed += $count;
            }

            if($removed === 0)
            {
                Console::error('No trusted key matches the given key ID, built-in keys cannot be removed but can be revoked');
                return 1;
            }

            Console::out(sprintf('Removed %d trusted key(s)', $removed));
            return 0;
        }
    }
