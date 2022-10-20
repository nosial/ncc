<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\InvalidProjectNameException;
    use ncc\Exceptions\ProjectAlreadyExistsException;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Utilities\Console;

    class ProjectMenu
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
         * @param $args
         * @return void
         */
        public static function createProject($args): void
        {
            // First determine the source directory of the project
            $current_directory = getcwd();
            $real_src = $current_directory;
            if(isset($args['src']))
            {
                // Make sure directory separators are corrected
                $args['src'] = str_ireplace('/', DIRECTORY_SEPARATOR, $args['src']);
                $args['src'] = str_ireplace('\\', DIRECTORY_SEPARATOR, $args['src']);

                // Remove the trailing slash
                if(substr($args['src'], -1) == DIRECTORY_SEPARATOR)
                {
                    $args['src'] = substr($args['src'], 0, -1);
                }

                $full_path = getcwd() . DIRECTORY_SEPARATOR . $args['src'];

                if(file_exists($full_path) && is_dir($full_path))
                {
                    $real_src = getcwd() . DIRECTORY_SEPARATOR . $args['src'];
                }
                else
                {
                    Console::outError('The selected source directory \'' . $full_path . '\' was not found or is not a directory', true, 1);
                }
            }

            // Remove basename from real_src
            $real_src = \ncc\Utilities\Functions::removeBasename($real_src, $current_directory);

            // Fetch the rest of the information needed for the project
            //$compiler_extension = Console::getOptionInput($args, 'ce', 'Compiler Extension (php, java): ');
            $compiler_extension = 'php'; // Always php, for now.
            $package_name = Console::getOptionInput($args, 'package', 'Package Name (com.example.foo): ');
            $project_name = Console::getOptionInput($args, 'name', 'Project Name (Foo Bar Library): ');

            // Create the compiler configuration
            $Compiler = new Compiler();
            $Compiler->Extension = $compiler_extension;
            $Compiler->MaximumVersion = '8.1';
            $Compiler->MinimumVersion = '7.4';

            // Now create the project
            $ProjectManager = new ProjectManager($current_directory);

            try
            {
                $ProjectManager->initializeProject($Compiler, $project_name, $package_name, $real_src);
            }
            catch (InvalidPackageNameException $e)
            {
                Console::outException('The given package name is invalid, the value must follow the standard package naming convention', $e, 1);
            }
            catch (InvalidProjectNameException $e)
            {
                Console::outException('The given project name is invalid, cannot be empty or larger than 126 characters', $e, 1);
            }
            catch (ProjectAlreadyExistsException $e)
            {
                Console::outException('A project has already been initialized in \'' . $current_directory . '\'', $e, 1);
            }
            catch(Exception $e)
            {
                Console::outException('There was an unexpected error while trying to initialize the project', $e, 1);
            }

            Console::out('Project successfully created');
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
                new CliHelpSection(['create', '--src', '--package', '--name'], 'Creates a new NCC project'),
                new CliHelpSection(['create', '--makefile'], 'Generates a Makefile for the project'),
            ];

            $options_padding = \ncc\Utilities\Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc project {command} [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }
        }
    }