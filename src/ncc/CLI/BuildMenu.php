<?php

    namespace ncc\CLI;

    use Exception;
    use JetBrains\PhpStorm\NoReturn;
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Classes\PhpExtension\Compiler;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Utilities\Console;

    class BuildMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         */
        #[NoReturn] public static function start($args): void
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

                // Append trailing slash
                if(substr($path_arg, -1) !== DIRECTORY_SEPARATOR)
                {
                    $path_arg .= DIRECTORY_SEPARATOR;
                }

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

            try
            {
                $ProjectConfiguration = ProjectConfiguration::fromFile($project_path . DIRECTORY_SEPARATOR . 'project.json');
            }
            catch (FileNotFoundException $e)
            {
                Console::outException('Cannot find the file \'project.json\'', $e, 1);
                return;
            }
            catch (MalformedJsonException $e)
            {
                Console::outException('The file \'project.json\' contains malformed json data, please verify the syntax of the file', $e, 1);
                return;
            }


            // Select the correct compiler for the specified extension
            switch(strtolower($ProjectConfiguration->Project->Compiler->Extension))
            {
                case CompilerExtensions::PHP:
                    /** @var CompilerInterface $Compiler */
                    $Compiler = new Compiler($ProjectConfiguration);
                    break;

                default:
                    Console::outError('The extension '. $ProjectConfiguration->Project->Compiler->Extension . ' is not supported', true, 1);
                    return;
            }

            $build_configuration = BuildConfigurationValues::DefaultConfiguration;

            if(isset($args['config']))
            {
                $build_configuration = $args['config'];
            }

            // Auto-resolve to the default configuration if `default` is used or not specified
            if($build_configuration == BuildConfigurationValues::DefaultConfiguration)
            {
                $build_configuration = $ProjectConfiguration->Build->DefaultConfiguration;
            }

            try
            {
                $ProjectConfiguration->Build->getBuildConfiguration($build_configuration);
            }
            catch (BuildConfigurationNotFoundException $e)
            {
                Console::outException('The build configuration \'' . $build_configuration . '\' does not exist in the project configuration', $e, 1);
                return;
            }

            Console::out(
                ' ===== BUILD INFO ===== ' . PHP_EOL .
                ' Package Name: ' .  $ProjectConfiguration->Assembly->Package . PHP_EOL .
                ' Version: ' .  $ProjectConfiguration->Assembly->Version . PHP_EOL .
                ' Compiler Extension: ' .  $ProjectConfiguration->Project->Compiler->Extension . PHP_EOL .
                ' Compiler Version: ' .  $ProjectConfiguration->Project->Compiler->MaximumVersion . ' - ' . $ProjectConfiguration->Project->Compiler->MinimumVersion . PHP_EOL .
                ' Build Configuration: ' .  $build_configuration . PHP_EOL
            );

            Console::out('Preparing package');

            try
            {
                $Compiler->prepare($project_path, $build_configuration);
            }
            catch (Exception $e)
            {
                Console::outException('The package preparation process failed', $e, 1);
                return;
            }

            Console::out('Compiling package');

            try
            {
                $Compiler->build($project_path);
            }
            catch (Exception $e)
            {
                Console::outException('Build Failed', $e, 1);
                return;
            }

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