<?php
/*
 * Copyright (c) Nosial 2022-2023, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class BuildCommand
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

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc build [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }
        }
    }