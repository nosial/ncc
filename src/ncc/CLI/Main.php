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

    use ncc\Classes\Console;
    use ncc\Classes\IO;
    use ncc\CLI\Commands\AuthenticationCommand;
    use ncc\CLI\Commands\BuildCommand;
    use ncc\CLI\Commands\ExecuteCommand;
    use ncc\CLI\Commands\ExtractCommand;
    use ncc\CLI\Commands\InspectCommand;
    use ncc\CLI\Commands\InstallCommand;
    use ncc\CLI\Commands\ListPackagesCommand;
    use ncc\CLI\Commands\ProjectCommand;
    use ncc\CLI\Commands\RepositoryCommand;
    use ncc\CLI\Commands\UninstallCommand;
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
            // Note, while this is the main execution pointer for the command-line interface, we should never use
            // any exit calls here. Instead, we return exit codes to the caller so they can handle it appropriately.

            // If the CLI definition isn't created, we assume we're not in CLI mode.
            if(!defined('__NCC_CLI__'))
            {
                return 0;
            }

            // Check for a CLI environment
            if(php_sapi_name() !== 'cli')
            {
                Console::out('This program can only be run from the command line.' . PHP_EOL);
                return 1;
            }

            ShutdownHandler::register();
            $argv = Parse::parseArgument($argv);

            if(isset($argv['project']))
            {
                return ProjectCommand::handle($argv);
            }
            elseif(isset($argv['build']))
            {
                return BuildCommand::handle($argv);
            }
            elseif(isset($argv['authenticate']) || isset($argv['auth']))
            {
                return AuthenticationCommand::handle($argv);
            }
            elseif(isset($argv['inspect']) || isset($argv['ins']))
            {
                return InspectCommand::handle($argv);
            }
            elseif(isset($argv['install']))
            {
                return InstallCommand::handle($argv);
            }
            elseif(isset($argv['uninstall']))
            {
                return UninstallCommand::handle($argv);
            }
            elseif(isset($argv['extract']) || isset($argv['ext']))
            {
                return ExtractCommand::handle($argv);
            }
            elseif(isset($argv['execute']) || isset($argv['exec']) || isset($argv['exe']))
            {
                return ExecuteCommand::handle($argv);
            }
            elseif(isset($argv['repository']) || isset($argv['repo']))
            {
                return RepositoryCommand::handle($argv);
            }
            elseif(isset($argv['list']) || isset($argv['ls']))
            {
                return ListPackagesCommand::handle($argv);
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

        /**
         * Prints out the version information for ncc
         *
         * @return void
         */
        private static function version(): void
        {
            Console::out(sprintf("ncc v%s build %s", __NCC_VERSION__, __NCC_BUILD__));
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
                Console::out(sprintf('ncc - Nosial Code Compiler v%s', __NCC_VERSION__));
                Console::out('Usage: ncc [command] [options]' . PHP_EOL);
                Console::out('Commands:');
                Console::out('  project           Manage ncc projects (create, validate, convert, apply templates)');
                Console::out('  build             Build a project into an ncc package');
                Console::out('  install           Install an ncc package from file or repository');
                Console::out('  uninstall         Uninstall an installed ncc package');
                Console::out('  execute           Execute an installed ncc package');
                Console::out('  inspect           Display information about an ncc package');
                Console::out('  extract           Extract package contents to a directory');
                Console::out('  authenticate      Manage authentication entries for repositories');
                Console::out('  repository        Manage package repositories');
                Console::out('  list              List all installed ncc packages');
                Console::out(PHP_EOL . 'Options:');
                Console::out('  --version, -v     Display version information');
                Console::out('  --help, -h        Display this help message');
                Console::out(PHP_EOL . 'Use "ncc [command] --help" for more information about a command.');
                return;
            }

            switch(strtolower($command))
            {
                case 'project':
                    ProjectCommand::help();
                    break;

                case 'build':
                    BuildCommand::help();
                    break;

                case 'inspect':
                case 'ins':
                    InspectCommand::help();
                    break;

                case 'install':
                    InstallCommand::help();
                    break;

                case 'uninstall':
                    UninstallCommand::help();
                    break;

                case 'execute':
                case 'exec':
                case 'exe':
                    ExecuteCommand::help();
                    break;

                case 'extract':
                case 'ext':
                    ExtractCommand::help();
                    break;

                case 'authenticate':
                case 'auth':
                    AuthenticationCommand::help();
                    break;

                case 'repository':
                case 'repo':
                    RepositoryCommand::help();
                    break;

                case 'list':
                case 'ls':
                    ListPackagesCommand::help();
                    break;

                default:
                    Console::out('No help available for command ' . $command);
                    break;
            }
        }
    }