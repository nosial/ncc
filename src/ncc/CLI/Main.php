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
    use ncc\Enums\LogLevel;
    use ncc\Enums\NccBuildFlags;
    use ncc\CLI\Commands\BuildCommand;
    use ncc\CLI\Commands\ExecCommand;
    use ncc\CLI\Management\ConfigMenu;
    use ncc\CLI\Management\CredentialMenu;
    use ncc\CLI\Management\PackageManagerMenu;
    use ncc\CLI\Management\ProjectMenu;
    use ncc\CLI\Management\SourcesMenu;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\RuntimeException;
    use ncc\ncc;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\RuntimeCache;

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
         * @param $argv
         * @return void
         * @throws RuntimeException
         * @throws AccessDeniedException
         * @throws IOException
         */
        public static function start($argv): void
        {
            self::$args = Resolver::parseArguments(implode(' ', $argv));

            if(isset(self::$args['ncc-cli']))
            {
                // Initialize NCC
                try
                {
                    ncc::initialize();
                }
                catch (FileNotFoundException $e)
                {
                    Console::outException('Cannot initialize NCC, one or more files were not found.', $e, 1);
                }
                catch (RuntimeException $e)
                {
                    Console::outException('Cannot initialize NCC due to a runtime error.', $e, 1);
                }

                define('NCC_CLI_MODE', 1);
                register_shutdown_function('ncc\CLI\Main::shutdown');

                if(isset(self::$args['l']) || isset(self::$args['log-level']))
                {
                    switch(strtolower(self::$args['l'] ?? self::$args['log-level']))
                    {
                        case LogLevel::SILENT:
                        case LogLevel::FATAL:
                        case LogLevel::ERROR:
                        case LogLevel::WARNING:
                        case LogLevel::INFO:
                        case LogLevel::DEBUG:
                        case LogLevel::VERBOSE:
                            self::$log_level = strtolower(self::$args['l'] ?? self::$args['log-level']);
                            break;

                        default:
                            Console::outWarning('Unknown log level: ' . (self::$args['l'] ?? self::$args['log-level']) . ', using \'info\'');
                            self::$log_level = LogLevel::INFO;
                            break;
                    }
                }
                else
                {
                    self::$log_level = LogLevel::INFO;
                }

                if(Resolver::checkLogLevel(self::$log_level, LogLevel::DEBUG))
                {
                    Console::outDebug('Debug logging enabled');
                    Console::outDebug(sprintf('const: %s', json_encode(ncc::getConstants(), JSON_UNESCAPED_SLASHES)));
                    Console::outDebug(sprintf('args: %s', json_encode(self::$args, JSON_UNESCAPED_SLASHES)));
                }

                if(in_array(NccBuildFlags::UNSTABLE, NCC_VERSION_FLAGS))
                {
                    Console::outWarning('This is an unstable build of NCC, expect some features to not work as expected');
                }

                if(isset(self::$args['version']))
                {
                    self::displayVersion();
                    exit(0);
                }

                try
                {
                    switch(strtolower(self::$args['ncc-cli']))
                    {
                        default:
                            Console::out('Unknown command ' . strtolower(self::$args['ncc-cli']));
                            break;

                        case 'project':
                            ProjectMenu::start(self::$args);
                            break;

                        case 'build':
                            BuildCommand::start(self::$args);
                            break;

                        case 'exec':
                            ExecCommand::start(self::$args);
                            break;

                        case 'cred':
                            CredentialMenu::start(self::$args);
                            break;

                        case 'package':
                            PackageManagerMenu::start(self::$args);
                            break;

                        case 'config':
                            ConfigMenu::start(self::$args);
                            break;

                        case 'source':
                            SourcesMenu::start(self::$args);
                            break;

                        case 'version':
                            self::displayVersion();
                            break;

                        case '1':
                        case 'help':
                            HelpMenu::start(self::$args);
                            break;
                    }
                }
                catch(Exception $e)
                {
                    Console::outException($e->getMessage(), $e, 1);
                    exit(1);
                }

                exit(0);
            }
        }

        private static function displayVersion()
        {
            Console::out(sprintf('NCC version %s (%s)', NCC_VERSION_NUMBER, NCC_VERSION_BRANCH));
        }

        /**
         * @return array
         */
        public static function getArgs(): array
        {
            if (self::$args == null)
            {
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
            if(self::$log_level == null)
                self::$log_level = LogLevel::INFO;
            return self::$log_level;
        }

        /**
         * @return void
         */
        public static function shutdown(): void
        {
            try
            {
                RuntimeCache::clearCache();
                Functions::finalizePermissions();
            }
            catch (Exception $e)
            {
                Console::outWarning('An error occurred while shutting down NCC, ' . $e->getMessage());
            }
        }

    }