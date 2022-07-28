<?php

    namespace ncc\CLI;

    use Exception;
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
                ncc::initialize();

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