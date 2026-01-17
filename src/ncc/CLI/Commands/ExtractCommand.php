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
    use ncc\Libraries\fslib\IO;
    use ncc\Classes\PackageReader;

    class ExtractCommand extends AbstractCommandHandler
    {
        /**
         * Prints out the help menu for the extract command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc extract --path=<package-file> [--output=<directory>]' . PHP_EOL);
            Console::out('Extracts the contents of an ncc package to a directory.' . PHP_EOL);
            Console::out('The extracted files can be used as a working PHP component.');
            Console::out('If no output directory is specified, the package is extracted');
            Console::out('to the current directory using the package name.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --path=<file>     (Required) Path to the .ncc package file to extract');
            Console::out('  --output=<dir>    Output directory for extracted files');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc extract --path=package.ncc');
            Console::out('  ncc extract --path=package.ncc --output=/path/to/output');
        }

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

            if(isset($argv['path']))
            {
                $packagePath = realpath($argv['path']);
            }
            else
            {
                Console::error('No package path specified, use --path to specify a package file to extract');
                return 1;
            }

            if(!IO::exists($packagePath) || !IO::isFile($packagePath))
            {
                Console::error('The given path must be a ncc package file');
                return 1;
            }

            $outputPath = null;
            if(isset($argv['output']))
            {
                $outputPath = realpath($argv['output']);
            }

            try
            {
                $packageReader = new PackageReader($packagePath);
                if($outputPath === null)
                {
                    // If not output directory is specified, extract to current working directory with package name
                    $outputPath = getcwd() . DIRECTORY_SEPARATOR . $packageReader->getAssembly()->getPackage();
                }

                $packageReader->extract($outputPath);
            }
            catch(Exception $e)
            {
                Console::error($e->getMessage());
                return 1;
            }

            Console::out('Package extracted successfully to ' . $outputPath);
            return 0;
        }
    }