<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\Abstracts\NccBuildFlags;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\RuntimeException;
    use ncc\ncc;
    use ncc\Utilities\Console;
    use ncc\Utilities\Resolver;

    class Main
    {
        /**
         * Executes the main CLI process
         *
         * @param $argv
         * @return void
         */
        public static function start($argv): void
        {
            $args = Resolver::parseArguments(implode(' ', $argv));

            if(isset($args['ncc-cli']))
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

                if(in_array(NccBuildFlags::Unstable, NCC_VERSION_FLAGS))
                {
                    Console::outWarning('This is an unstable build of NCC, expect some features to not work as expected');
                }

                try
                {
                    switch(strtolower($args['ncc-cli']))
                    {
                        default:
                            Console::out('Unknown command ' . strtolower($args['ncc-cli']));
                            exit(1);

                        case 'project':
                            ProjectMenu::start($args);
                            exit(0);

                        case 'build':
                            BuildMenu::start($args);
                            exit(0);

                        case 'credential':
                            CredentialMenu::start($args);
                            exit(0);

                        case '1':
                        case 'help':
                            HelpMenu::start($args);
                            exit(0);
                    }
                }
                catch(Exception $e)
                {
                    Console::out('Error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')');
                    exit(1);
                }

            }
        }

    }