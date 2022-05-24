<?php

    namespace ncc\CLI;

    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Resolver;

    class CredentialMenu
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
            if(isset($args['add']))
            {
                self::addCredential($args);
            }

            self::displayOptions();
            exit(0);

        }

        /**
         * @param $args
         * @return void
         * @throws AccessDeniedException
         */
        public static function addCredential($args): void
        {
            $ResolvedScope = Resolver::resolveScope();

            if($ResolvedScope !== Scopes::System)
            {
                throw new AccessDeniedException('Root permissions are required to manage the vault');
            }

            print('end' . PHP_EOL);
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
                new CliHelpSection(['add'], 'Adds a new credential to the vault'),
            ];

            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            print('Usage: ncc vault {command} [options]' . PHP_EOL);
            print('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                print('   ' . $option->toString($options_padding) . PHP_EOL);
            }
            print(PHP_EOL);
        }
    }