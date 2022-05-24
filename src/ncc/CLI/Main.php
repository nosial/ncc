<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\ncc;
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
                            print('Unknown command ' . strtolower($args['ncc-cli']) . PHP_EOL);
                            exit(1);

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
                    print('Error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . PHP_EOL);
                    exit(1);
                }

            }
        }

    }