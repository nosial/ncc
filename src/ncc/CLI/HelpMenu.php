<?php

    namespace ncc\CLI;

    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Resolver;

    class HelpMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $argv
         * @return void
         */
        public static function start($argv)
        {
            print('Usage: ncc [options] COMMAND' . PHP_EOL . PHP_EOL);
            print('Nosial Code Compiler / Project Toolkit' . PHP_EOL . PHP_EOL);

            self::displayMainOptions();
            self::displayManagementCommands();
            self::displayMainCommands();
            self::displayExtensions();
        }

        /**
         * Displays the main options section
         *
         * @return void
         */
        private static function displayMainOptions(): void
        {
            $options = [
                new CliHelpSection(['{command} --help'], 'Displays help information about a specific command'),
                new CliHelpSection(['-v', '--version'], 'Display NCC version information'),
                new CliHelpSection(['-D', '--debug'], 'Enables debug mode'),
                new CliHelpSection(['-l', '--log-level={debug|info|warn|error|fatal}'], 'Set the logging level', 'info'),
                new CliHelpSection(['--basic-ascii'], 'Uses basic ascii characters'),
                new CliHelpSection(['--no-color'], 'Omits the use of colors'),
                new CliHelpSection(['--no-banner'], 'Omits displaying the NCC ascii banner'),
                new CliHelpSection(['--no-ui'], 'Omits displaying a user-interface for wizards/dialogs'),
                new CliHelpSection(['--require-scope={user|system}'], 'Enforces the requirement of a access scope', 'user'),
            ];
            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            print('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                print('   ' . $option->toString($options_padding) . PHP_EOL);
            }
            print(PHP_EOL);


        }

        /**
         * Displays the management commands section
         *
         * @return void
         */
        private static function displayManagementCommands(): void
        {
            $commands = [
                new CliHelpSection(['project'], 'Manages the current project'),
                new CliHelpSection(['package'], 'Manages the package system'),
                new CliHelpSection(['cache'], 'Manages the system cache'),
                new CliHelpSection(['credential'], 'Manages credentials'),
            ];
            $commands_padding = \ncc\Utilities\Functions::detectParametersPadding($commands) + 2;

            print('Management Commands:' . PHP_EOL);
            foreach($commands as $command)
            {
                print('   ' . $command->toString($commands_padding) . PHP_EOL);
            }
            print(PHP_EOL);
        }

        /**
         * Displays the main commands section
         *
         * @return void
         */
        private static function displayMainCommands(): void
        {
            $commands = [
                new CliHelpSection(['build'], 'Builds the current project'),
                new CliHelpSection(['main'], 'Executes the main entrypoint of a package')
            ];
            $commands_padding = \ncc\Utilities\Functions::detectParametersPadding($commands) + 2;

            print('Commands:' . PHP_EOL);
            foreach($commands as $command)
            {
                print('   ' . $command->toString($commands_padding) . PHP_EOL);
            }
            print(PHP_EOL);
        }

        /**
         * Displays the main commands section
         *
         * @return void
         */
        private static function displayExtensions(): void
        {
            $extensions = [
                new CliHelpSection(['exphp'], 'The PHP compiler extension')
            ];
            $extensions_padding = \ncc\Utilities\Functions::detectParametersPadding($extensions) + 2;

            print('Extensions:' . PHP_EOL);
            foreach($extensions as $command)
            {
                print('   ' . $command->toString($extensions_padding) . PHP_EOL);
            }
            print(PHP_EOL);
        }
    }