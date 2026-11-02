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

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\KeyManager;
    use ncc\Libraries\fslib\IO;
    use ncc\Objects\Signing\SigningKey;

    class ImportKey extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $name = $argv['name'] ?? $argv['n'] ?? null;
            $input = $argv['input'] ?? $argv['i'] ?? null;
            $value = $argv['value'] ?? null;

            if(!is_string($name) || $name === '')
            {
                Console::error('A key name is required, use --name to specify it');
                return 1;
            }

            try
            {
                if(is_string($input))
                {
                    $value = IO::readFile($input);
                }

                if(!is_string($value) || trim($value) === '')
                {
                    Console::error('No key given, use --input=<file> or --value=<key>');
                    return 1;
                }

                KeyManager::validateName($name);
                if(!isset($argv['overwrite']) && KeyManager::exists($name))
                {
                    Console::error(sprintf('The signing key "%s" already exists, use --overwrite to replace it', $name));
                    return 1;
                }

                // Decrypt the key once to make sure it is intact and the passphrase is known
                $info = SigningKey::inspect($value);
                $signingKey = SigningKey::import($value, KeyManager::getPassphrase(sprintf('Enter the passphrase of the signing key (%s "%s"): ', $info['type']->value, $info['signer'])));
                KeyManager::save($name, $value, isset($argv['overwrite']));
            }
            catch(Exception $e)
            {
                Console::error('Failed to import the signing key: ' . $e->getMessage());
                return 1;
            }

            Console::out(sprintf('Imported the signing key "%s"', $name));
            Console::out(sprintf('  Signer:      %s', $signingKey->describeSigner()));
            Console::out(sprintf('  Fingerprint: %s', $signingKey->getFingerprint()));
            return 0;
        }
    }
