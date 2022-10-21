<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\Abstracts\CompilerExtensionDefaultVersions;
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\CompilerExtensionSupportedVersions;
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
            else
            {
                $real_src = getcwd() . DIRECTORY_SEPARATOR . 'src';
            }

            // Remove basename from real_src
            $real_src = \ncc\Utilities\Functions::removeBasename($real_src, $current_directory);

            // Fetch the rest of the information needed for the project
            //$compiler_extension = Console::getOptionInput($args, 'ce', 'Compiler Extension (php, java): ');
            $package_name = Console::getOptionInput($args, 'package', 'Package Name (com.example.foo): ');
            $project_name = Console::getOptionInput($args, 'name', 'Project Name (Foo Bar Library): ');
            $Compiler = new Compiler();

            // Detect the specified compiler extension
            if(isset($args['ext']) || isset($args['extension']))
            {
                $compiler_extension = strtolower(($args['extension'] ?? $args['ext']));

                if(in_array($compiler_extension, CompilerExtensions::All))
                {
                    $Compiler->Extension = $compiler_extension;
                }
                else
                {
                    Console::outError('Unsupported extension: ' . $compiler_extension, true, 1);
                    return;
                }
            }
            else
            {
                // Default PHP Extension
                $Compiler->Extension = CompilerExtensions::PHP;
            }

            // If a minimum and maximum version is specified
            if(
                (isset($args['max-version']) || isset($args['max-ver'])) &&
                (isset($args['min-version']) || isset($args['min-ver']))
            )
            {
                $max_version = strtolower($args['max-version'] ?? $args['max-ver']);
                $min_version = strtolower($args['min-version'] ?? $args['min-ver']);

                switch($Compiler->Extension)
                {
                    case CompilerExtensions::PHP:

                        if(!in_array($max_version, CompilerExtensionSupportedVersions::PHP))
                        {
                            Console::outError('The extension \'' . $Compiler->Extension . '\' does not support version ' . $max_version, true, 1);
                            return;
                        }
                        if(!in_array($min_version, CompilerExtensionSupportedVersions::PHP))
                        {
                            Console::outError('The extension \'' . $Compiler->Extension . '\' does not support version ' . $min_version, true, 1);
                            return;
                        }

                        $Compiler->MaximumVersion = $max_version;
                        $Compiler->MinimumVersion = $min_version;

                        break;

                    default:
                        Console::outError('Unsupported extension: ' . $Compiler->Extension, true, 1);
                        return;
                }
            }
            // If a single version is specified
            elseif(isset($args['version']) || isset($args['ver']))
            {
                $version = strtolower($args['version'] ?? $args['ver']);
                switch($Compiler->Extension)
                {
                    case CompilerExtensions::PHP:
                        if(!in_array($version, CompilerExtensionSupportedVersions::PHP))
                        {
                            Console::outError('The extension \'' . $Compiler->Extension . '\' does not support version ' . $version, true, 1);
                            return;
                        }

                        $Compiler->MaximumVersion = $version;
                        $Compiler->MinimumVersion = $version;

                        break;

                    default:
                        Console::outError('Unsupported extension: ' . $Compiler->Extension, true, 1);
                        return;
                }
            }
            // If no version is specified, use the default version
            else
            {
                switch($Compiler->Extension)
                {
                    case CompilerExtensions::PHP:
                        $Compiler->MinimumVersion = CompilerExtensionDefaultVersions::PHP[1];
                        $Compiler->MaximumVersion = CompilerExtensionDefaultVersions::PHP[0];
                        break;

                    default:
                        Console::outError('Unsupported extension: ' . $Compiler->Extension, true, 1);
                        return;
                }
            }

            // Now create the project
            $ProjectManager = new ProjectManager($current_directory);

            try
            {
                $ProjectManager->initializeProject($Compiler, $project_name, $package_name, $real_src);
            }
            catch (InvalidPackageNameException $e)
            {
                Console::outException('The given package name is invalid, the value must follow the standard package naming convention', $e, 1);
                return;
            }
            catch (InvalidProjectNameException $e)
            {
                Console::outException('The given project name is invalid, cannot be empty or larger than 126 characters', $e, 1);
                return;
            }
            catch (ProjectAlreadyExistsException $e)
            {
                Console::outException('A project has already been initialized in \'' . $current_directory . '\'', $e, 1);
                return;
            }
            catch(Exception $e)
            {
                Console::outException('There was an unexpected error while trying to initialize the project', $e, 1);
                return;
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
                new CliHelpSection(['create', '--ext'], 'Specifies the compiler extension'),
                new CliHelpSection(['create', '--min-version', '--min-ver', '--maximum-ver', '-max-ver'], 'Specifies the compiler extension version'),
                new CliHelpSection(['create-makefile'], 'Generates a Makefile for the project'),
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