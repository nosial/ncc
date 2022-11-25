<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;

    class BuildMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         * @noinspection PhpNoReturnAttributeCanBeAddedInspection
         */
        public static function start($args): void
        {
            if(isset($args['help']))
            {
                self::displayOptions();
                exit(0);
            }

            self::build($args);
            exit(0);
        }

        /**
         * Builds the current project
         *
         * @param $args
         * @return void
         */
        private static function build($args): void
        {
            // Determine the path of the project
            if(isset($args['path']) || isset($args['p']))
            {
                $path_arg = ($args['path'] ?? $args['p']);

                // Check if the path exists
                if(!file_exists($path_arg) || !is_dir($path_arg))
                {
                    Console::outError('The given path \'' . $path_arg . '\' is does not exist or is not a directory', true, 1);
                }

                $project_path = $path_arg;
            }
            else
            {
                $project_path = getcwd();
            }

            // Load the project
            try
            {
                $ProjectManager = new ProjectManager($project_path);
                $ProjectManager->load();
            }
            catch (Exception $e)
            {
                Console::outException('Failed to load Project Configuration (project.json)', $e, 1);
                return;
            }

            // Build the project
            try
            {
                $build_configuration = BuildConfigurationValues::DefaultConfiguration;
                if(isset($args['config']))
                {
                    $build_configuration = $args['config'];
                }

                $output = $ProjectManager->build($build_configuration);

                Console::out('Successfully built ' . $output);
                exit(0);
            }
            catch (Exception $e)
            {
                Console::outException('Failed to build project', $e, 1);
                return;
            }

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
                new CliHelpSection(['build'], 'Builds the current project using the default build configuration'),
                new CliHelpSection(['build', '--path', '-p'], 'Builds the project in the specified path that contains project.json'),
                new CliHelpSection(['build', '--config'], 'Builds the current project with a specified build configuration')
            ];

            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc build [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }
        }
    }