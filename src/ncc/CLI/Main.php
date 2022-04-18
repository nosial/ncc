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
                $banner = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'banner_extended');

                $banner_version = 'Master 1.0.0';
                $banner_version = str_pad($banner_version, 21);

                $banner_copyright = 'Copyright (c) 2022-2022 Nosial';
                $banner_copyright = str_pad($banner_copyright, 30);

                $banner = str_ireplace('%A', $banner_version, $banner);
                $banner = str_ireplace('%B', $banner_copyright, $banner);

                print($banner . PHP_EOL);

                switch(strtolower($args['ncc-cli']))
                {
                    default:
                    case 'help':
                        HelpMenu::start($argv);
                        exit(0);
                }
            }
        }

    }