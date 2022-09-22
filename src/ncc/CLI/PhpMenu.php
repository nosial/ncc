<?php

    namespace ncc\CLI;

    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;

    class PhpMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         */
        public static function start($args): void
        {
            if(isset($args['create']))
            {
                self::createProject($args);
            }

            self::displayOptions();
            exit(0);
        }

        /**
         * Generates a new Autoloader file for the project
         *
         * @param $args
         * @return void
         */
        private static function generateAutoload($args): void
        {

        }

        /**
         * Displays the main options section
         *
         * @return void
         */
        private static function displayOptions(): void
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the PHP command'),
                new CliHelpSection(['build', '--autoload'], 'Builds a new Autoload file for the project (Development purposes only)')
            ];

            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc php {command} [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }
        }
    }