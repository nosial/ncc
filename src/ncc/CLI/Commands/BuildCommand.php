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
    use ncc\Enums\Options\BuildConfigurationValues;
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
            if(isset($args['path']) || isset($args['p']))
            {
                $project_path = $args['path'] ?? $args['p'];
            }
            elseif(is_file(getcwd() . DIRECTORY_SEPARATOR . 'project.json'))
            {
                $project_path = getcwd();
            }
            else
            {
                Console::outError('Missing option: --path|-p, please specify the path to the project', true, 1);
                return;
            }

            // Load the project
            try
            {
                $project_manager = new ProjectManager($project_path);
            }
            catch (Exception $e)
            {
                Console::outException('There was an error loading the project', $e, 1);
                return;
            }

            // Build the project
            try
            {
                $build_configuration = BuildConfigurationValues::DEFAULT;
                if(isset($args['config']))
                {
                    $build_configuration = $args['config'];
                }

                $output = $project_manager->build($build_configuration);
            }
            catch (Exception $e)
            {
                Console::outException('Failed to build project', $e, 1);
                return;
            }

            Console::out($output);
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