<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\CLI;

    use Exception;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\NccBuildFlags;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\RuntimeException;
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
         * @param $argv
         * @return void
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

                // Define CLI stuff
                define('NCC_CLI_MODE', 1);

                if(isset(self::$args['l']) || isset(self::$args['log-level']))
                {
                    switch(strtolower(self::$args['l'] ?? self::$args['log-level']))
                    {
                        case LogLevel::Silent:
                        case LogLevel::Fatal:
                        case LogLevel::Error:
                        case LogLevel::Warning:
                        case LogLevel::Info:
                        case LogLevel::Debug:
                        case LogLevel::Verbose:
                            self::$log_level = strtolower(self::$args['l'] ?? self::$args['log-level']);
                            break;

                        default:
                            Console::outWarning('Unknown log level: ' . (self::$args['l'] ?? self::$args['log-level']) . ', using \'info\'');
                            self::$log_level = LogLevel::Info;
                            break;
                    }
                }
                else
                {
                    self::$log_level = LogLevel::Info;
                }

                if(Resolver::checkLogLevel(self::$log_level, LogLevel::Debug))
                {
                    Console::outDebug('Debug logging enabled');
                    Console::outDebug(sprintf('consts: %s', json_encode(ncc::getConstants(), JSON_UNESCAPED_SLASHES)));
                    Console::outDebug(sprintf('args: %s', json_encode(self::$args, JSON_UNESCAPED_SLASHES)));
                }

                if(in_array(NccBuildFlags::Unstable, NCC_VERSION_FLAGS))
                {
                    Console::outWarning('This is an unstable build of NCC, expect some features to not work as expected');
                }

                try
                {
                    switch(strtolower(self::$args['ncc-cli']))
                    {
                        default:
                            Console::out('Unknown command ' . strtolower(self::$args['ncc-cli']));
                            exit(1);

                        case 'project':
                            ProjectMenu::start(self::$args);
                            exit(0);

                        case 'build':
                            BuildMenu::start(self::$args);
                            exit(0);

                        case 'credential':
                            CredentialMenu::start(self::$args);
                            exit(0);

                        case 'package':
                            PackageManagerMenu::start(self::$args);
                            exit(0);

                        case '1':
                        case 'help':
                            HelpMenu::start(self::$args);
                            exit(0);
                    }
                }
                catch(Exception $e)
                {
                    Console::outException($e->getMessage() . ' (Code: ' . $e->getCode() . ')', $e, 1);
                    exit(1);
                }

            }
        }

        /**
         * @return mixed
         */
        public static function getArgs()
        {
            return self::$args;
        }

        /**
         * @return string
         */
        public static function getLogLevel(): string
        {
            if(self::$log_level == null)
                self::$log_level = LogLevel::Info;
            return self::$log_level;
        }

    }