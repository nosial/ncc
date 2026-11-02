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

    class ExportKey extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $name = $argv['name'] ?? $argv['n'] ?? null;
            $output = $argv['output'] ?? $argv['o'] ?? null;

            if(!is_string($name) || $name === '')
            {
                Console::error('A key name is required, use --name to specify it');
                return 1;
            }

            try
            {
                $exported = KeyManager::read($name);
                if(isset($argv['public']))
                {
                    $info = SigningKey::inspect($exported);
                    $value = base64_encode($info['public_key']);
                }
                else
                {
                    // The stored key is already encrypted with its passphrase
                    $value = $exported;
                }

                if(is_string($output))
                {
                    $previousUmask = umask(isset($argv['public']) ? 0022 : 0077);
                    try
                    {
                        IO::writeFile($output, $value . PHP_EOL);
                    }
                    finally
                    {
                        umask($previousUmask);
                    }

                    Console::out(sprintf('Exported the %s of "%s" to %s', isset($argv['public']) ? 'public key' : 'encrypted signing key', $name, $output));
                    return 0;
                }

                Console::out($value);
            }
            catch(Exception $e)
            {
                Console::error('Failed to export the signing key: ' . $e->getMessage());
                return 1;
            }

            return 0;
        }
    }
