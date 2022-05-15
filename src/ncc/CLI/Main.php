<?php

    namespace ncc\CLI;

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
                \ncc\ncc::initialize();

                if(isset($args['no-banner']) == false)
                {
                    $basic_ascii = false;

                    if(isset($args['basic-ascii']))
                    {
                        $basic_ascii = true;
                    }

                    // TODO: Make copyright not hard-coded.
                    print(\ncc\Utilities\Functions::getBanner(NCC_VERSION_BRANCH . ' ' . NCC_VERSION_NUMBER, 'Copyright (c) 2022-2022 Nosial', $basic_ascii) . PHP_EOL);
                }

                switch(strtolower($args['ncc-cli']))
                {
                    default:
                        print('Unknown command ' . strtolower($args['ncc-cli']) . PHP_EOL);
                        exit(1);

                    case '1':
                    case 'help':
                        HelpMenu::start($argv);
                        exit(0);
                }
            }
        }

    }