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
                    if(isset($args['basic-ascii']))
                    {
                        $banner = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'banner_basic');
                    }
                    else
                    {
                        $banner = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'banner_extended');
                    }

                    $banner_version = NCC_VERSION_BRANCH . ' ' . NCC_VERSION_NUMBER;
                    $banner_version = str_pad($banner_version, 21);
    
                    $banner_copyright = 'Copyright (c) 2022-2022 Nosial';
                    $banner_copyright = str_pad($banner_copyright, 30);
    
                    $banner = str_ireplace('%A', $banner_version, $banner);
                    $banner = str_ireplace('%B', $banner_copyright, $banner);
    
                    print($banner . PHP_EOL);
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