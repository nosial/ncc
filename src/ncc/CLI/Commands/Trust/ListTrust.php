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
    use ncc\Objects\Signing\TrustedKey;
    use ncc\Runtime;

    class ListTrust extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $groups = ['built-in' => BuiltinTrustedKeys::getKeys()];
            foreach(Runtime::getTrustStores() as $trustStore)
            {
                $groups[$trustStore->getTrustStorePath()] = $trustStore->getEntries();
            }

            $total = 0;
            foreach($groups as $label => $keys)
            {
                if(count($keys) === 0)
                {
                    continue;
                }

                Console::out(sprintf('Trusted keys (%s):', $label));
                /** @var TrustedKey $key */
                foreach($keys as $key)
                {
                    Console::out(sprintf('  %s%s', $key->describeSigner(), $key->isRevoked() ? ' [REVOKED]' : ''));
                    Console::out(sprintf('    Fingerprint: %s', $key->getFingerprint()));
                    Console::out(sprintf('    Public key:  %s', base64_encode($key->getPublicKey())));
                    if($key->getSource() !== TrustedKey::SOURCE_BUILTIN)
                    {
                        Console::out(sprintf('    Added:       %s (%s)', date('Y-m-d H:i:s', $key->getAdded()), $key->getSource()));
                    }
                    $total++;
                }
            }

            if($total === 0)
            {
                Console::out('No trusted keys, keys are trusted when installing signed packages or with "ncc trust add"');
            }

            return 0;
        }
    }
