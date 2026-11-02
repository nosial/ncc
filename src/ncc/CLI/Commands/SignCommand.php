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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\KeyManager;
    use ncc\Classes\PackageSigner;
    use ncc\Libraries\fslib\IO;

    class SignCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $packagePath = isset($argv['path']) && is_string($argv['path']) ? Helper::resolvePath($argv['path']) : null;
            if($packagePath === null || !IO::isFile($packagePath))
            {
                Console::error('No package specified, use --path to specify the package file to sign');
                return 1;
            }

            $keyName = $argv['key'] ?? null;
            if($keyName !== null && !is_string($keyName))
            {
                Console::error('No key name specified, use --key=<name>');
                return 1;
            }

            try
            {
                $signingKey = KeyManager::resolve($keyName);
                if($signingKey === null)
                {
                    Console::error(sprintf('No signing key specified, use --key=<name> or set %s', KeyManager::ENV_SIGNING_KEY));
                    return 1;
                }

                $signature = PackageSigner::sign($packagePath, $signingKey);
                Console::out(sprintf('Signed %s as %s (%s)', $packagePath, $signature->describeSigner(), $signature->getFingerprint()));
            }
            catch(Exception $e)
            {
                Console::error('Failed to sign the package: ' . $e->getMessage());
                return 1;
            }

            return 0;
        }

        /**
         * Prints out the help menu for the sign command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc sign --path=<package> [--key=<name>]' . PHP_EOL);
            Console::out('Adds a signature to an ncc package (or an executable built with the php build type).' . PHP_EOL);
            Console::out('A package can carry several signatures, eg; one by its author and one by its organization,');
            Console::out('signing a package again with the same key replaces that key\'s signature. The key must sign');
            Console::out('as the organization or author declared in the package\'s assembly.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --path=<file>     (Required) The package to sign');
            Console::out('  --key=<name>      The stored signing key to use (see "ncc key"), defaults to NCC_SIGNING_KEY');
            Console::out(PHP_EOL . 'Environment:');
            Console::out('  NCC_SIGNING_KEY             The signing key (output of "ncc key export") or a path to a file containing it');
            Console::out('  NCC_SIGNING_KEY_PASSPHRASE  The passphrase of the signing key');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc sign --path=target/release/com.example.package.ncc --key=nosial');
            Console::out('  NCC_SIGNING_KEY=... NCC_SIGNING_KEY_PASSPHRASE=... ncc sign --path=package.ncc');
        }
    }
