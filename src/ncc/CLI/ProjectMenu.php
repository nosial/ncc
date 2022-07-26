<?php

    namespace ncc\CLI;

    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Resolver;

    class ProjectMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         * @throws AccessDeniedException
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
         * @param $args
         * @return void
         * @throws AccessDeniedException
         */
        public static function createProject($args): void
        {
            Console::out('end' . PHP_EOL);
            exit(0);
        }

        /**
         * Displays the main options section
         *
         * @return void
         */
        private static function displayOptions(): void
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['create'], 'Creates a new NCC project'),
            ];

            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc project {command} [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding) . PHP_EOL);
            }
            print(PHP_EOL);
        }
    }