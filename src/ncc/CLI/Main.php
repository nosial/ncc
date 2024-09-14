<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\CLI;

    use Exception;
    use ncc\Classes\ShutdownHandler;
    use ncc\CLI\Commands\BuildCommand;
    use ncc\CLI\Commands\ExecCommand;
    use ncc\CLI\Commands\PackageInspectorCommand;
    use ncc\CLI\Commands\SetupCommand;
    use ncc\CLI\Management\ConfigMenu;
    use ncc\CLI\Management\CredentialMenu;
    use ncc\CLI\Management\PackageManagerMenu;
    use ncc\CLI\Management\ProjectMenu;
    use ncc\CLI\Management\RepositoryMenu;
    use ncc\Enums\Flags\NccBuildFlags;
    use ncc\Enums\LogLevel;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\ncc;
    use ncc\Utilities\Console;
    use ncc\Utilities\Resolver;

    class Main
    {
        /**
         * @var array
         */
        private static $args;

        /**
         * @var string|null
         */
        private static $log_level;

        /**
         * Executes the main CLI process
         *
         * @param array $argv
         * @return int
         */
        public static function start(array $argv): int
        {
            self::$args = Resolver::parseArguments(implode(' ', $argv));

            if(!isset(self::$args['ncc-cli']))
            {
                Console::outError('No command specified, please verify your command and try again.', true, 1);
                return 1;
            }

            // Initialize ncc
            try
            {
                ncc::initialize();
            }
            catch (PathNotFoundException $e)
            {
                Console::outException('Cannot initialize ncc, one or more files were not found.', $e, 1);
                return 1;
            }
            catch (Exception $e)
            {
                Console::outException('Cannot initialize ncc due to an unexpected error.', $e, 1);
                return 1;
            }

            define('NCC_CLI_MODE', 1);
            register_shutdown_function([ShutdownHandler::class, 'shutdown']);

            if(isset(self::$args['l']) || isset(self::$args['log-level']))
            {
                switch(strtolower(self::$args['l'] ?? self::$args['log-level']))
                {
                    case LogLevel::SILENT->value:
                    case LogLevel::FATAL->value:
                    case LogLevel::ERROR->value:
                    case LogLevel::WARNING->value:
                    case LogLevel::INFO->value:
                    case LogLevel::DEBUG->value:
                    case LogLevel::VERBOSE->value:
                        self::$log_level = strtolower(self::$args['l'] ?? self::$args['log-level']);
                        break;

                    default:
                        Console::outWarning('Unknown log level: ' . (self::$args['l'] ?? self::$args['log-level']) . ', using \'info\'');
                        self::$log_level = LogLevel::INFO->value;
                        break;
                }
            }
            else
            {
                self::$log_level = LogLevel::INFO->value;
            }

            if(Resolver::checkLogLevel(self::$log_level, LogLevel::DEBUG->value))
            {
                Console::outDebug('Debug logging enabled');

                /** @noinspection JsonEncodingApiUsageInspection */
                Console::outDebug(sprintf('const: %s', json_encode(ncc::getConstants(), JSON_UNESCAPED_SLASHES)));

                /** @noinspection JsonEncodingApiUsageInspection */
                Console::outDebug(sprintf('args: %s', json_encode(self::$args, JSON_UNESCAPED_SLASHES)));
            }

            if(in_array(NccBuildFlags::UNSTABLE, NCC_VERSION_FLAGS, true))
            {
                Console::outWarning('This is an unstable build of ncc, expect some features to not work as expected');
            }

            if(in_array(NccBuildFlags::BETA, NCC_VERSION_FLAGS, true))
            {
                Console::outWarning('This is a beta build of ncc, expect some features to not work as expected');
            }

            if(isset(self::$args['version']))
            {
                self::displayVersion();
                return 0;
            }

            try
            {
                switch(strtolower(self::$args['ncc-cli']))
                {
                    default:
                        Console::out('Unknown command ' . strtolower(self::$args['ncc-cli']));
                        break;

                    case 'setup':
                        return SetupCommand::start(self::$args);

                    case 'project':
                        return ProjectMenu::start(self::$args);

                    case 'build':
                        return BuildCommand::start(self::$args);

                    case 'ins':
                        return PackageInspectorCommand::start(self::$args);

                    case 'exec':
                        return ExecCommand::start(self::$args);

                    case 'cred':
                        return CredentialMenu::start(self::$args);

                    case 'pkg':
                    case 'package':
                        return PackageManagerMenu::start(self::$args);

                    case 'config':
                        return ConfigMenu::start(self::$args);

                    case 'repo':
                    case 'repository':
                        return RepositoryMenu::start(self::$args);

                    case 'version':
                        return self::displayVersion();

                    case '1':
                    case 'help':
                        return HelpMenu::start(self::$args);
                }
            }
            catch(Exception $e)
            {
                Console::outException($e->getMessage(), $e, 1);
                return 1;
            }

            return 0;
        }

        /**
         * Displays the current version of ncc
         *
         * @return int
         */
        private static function displayVersion(): int
        {
            Console::out(sprintf('ncc version %s (%s)', NCC_VERSION_NUMBER, NCC_VERSION_BRANCH));
            return 0;
        }

        /**
         * Returns the arguments passed to ncc
         *
         * @return array
         */
        public static function getArgs(): array
        {
            if (self::$args === null)
            {
                /** @noinspection IssetArgumentExistenceInspection */
                if(isset($argv))
                {
                    self::$args = Resolver::parseArguments(implode(' ', $argv));
                }
                else
                {
                    self::$args = [];
                }
            }

            return self::$args;
        }

        /**
         * @return string
         */
        public static function getLogLevel(): string
        {
            if(self::$log_level === null)
            {
                self::$log_level = LogLevel::INFO->value;
            }

            return self::$log_level;
        }
    }