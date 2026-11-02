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

    namespace ncc\CLI\Commands\Key;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\KeyManager;
    use ncc\Objects\Package\PackageSignature;

    class ListKeys extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $keys = KeyManager::listKeys();
            if(count($keys) === 0)
            {
                Console::out('No signing keys found, use "ncc key generate" to create one');
                return 0;
            }

            Console::out(sprintf('Signing keys (%s):', KeyManager::getKeysDirectory()));
            foreach($keys as $name => $info)
            {
                if($info === null)
                {
                    Console::out(sprintf('  %s (unreadable)', $name));
                    continue;
                }

                Console::out(sprintf('  %s', $name));
                Console::out(sprintf('    Signer:      %s "%s"', $info['type']->value, $info['signer']));
                Console::out(sprintf('    Fingerprint: %s', PackageSignature::formatFingerprint(PackageSignature::computeKeyId($info['public_key']))));
            }

            return 0;
        }
    }
