<?php
    /*
 * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\CLI;

    use ncc\CLI\Commands\ProjectCommand;
    use ncc\Libraries\OptsLib\Parse;

    class Main
    {
        /**
         * The main execution point for ncc's command-line interface
         *
         * @param array $argv Command-line arguments to be passed on
         * @return int The exit code
         */
        public static function main(array $argv): int
        {
            // If the CLI definition isn't created, we assume we're not in CLI mode.
            if(!defined('__NCC_CLI__'))
            {
                return 0;
            }

            // Check for a CLI environment
            if(php_sapi_name() !== 'cli')
            {
                print('This program can only be run from the command line.' . PHP_EOL);
                return 1;
            }

            ShutdownHandler::register();
            $argv = Parse::parseArgument($argv);

            if(isset($argv['project']))
            {
                return ProjectCommand::handle($argv);
            }
            elseif(isset($argv['version']) || isset($argv['v']))
            {
                self::version();
                return 0;
            }
            elseif(isset($argv['help']) || isset($argv['h']))
            {
                self::help($argv['help'] ?? $argv['h'] ?? null);
                return 0;
            }

            self::help();
            return 0;
        }

        private static function version(): void
        {
            $versionFile = __NCC_DIR__ . DIRECTORY_SEPARATOR . 'VERSION';
            $buildFile = __NCC_DIR__ . DIRECTORY_SEPARATOR . 'BUILD';

            if(!file_exists($versionFile))
            {
                print('ncc version file not found!' . PHP_EOL);
                return;
            }

            if(!file_exists($buildFile))
            {
                print('ncc build file not found!' . PHP_EOL);
                return;
            }

            print(sprintf("ncc v%s build %s", file_get_contents($versionFile), file_get_contents($buildFile)));
        }

        /**
         * Prints out the help menu for the command-line interface
         *
         * @param string|true|null $command The command to get help information about
         * @return void
         */
        private static function help(string|null|true $command=null): void
        {
            if($command === null || $command === true)
            {
                print('ncc - Nosial Code Compiler' . PHP_EOL);
                print('Usage: ncc [command] [options]' . PHP_EOL . PHP_EOL);
                print('Commands:' . PHP_EOL);
                print('  project           Create and Edit ncc Projects' . PHP_EOL);
                print('  build             Build the current project' . PHP_EOL);
                print('  exec              Execution handler for ncc packages' . PHP_EOL);
                print('  inspect           Inspect a package without installing it' . PHP_EOL);
                print(PHP_EOL . 'Use "ncc [command] --help" for more information about a command.' . PHP_EOL);
                return;
            }

            switch(strtolower($command))
            {
                case 'project':
                    ProjectCommand::help();
                    break;

                default:
                    print('No help available for command ' . $command . PHP_EOL);
                    break;
            }
        }
    }