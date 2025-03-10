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
    use ncc\Enums\Options\BuildConfigurationOptions;
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
         * @param array $args
         * @return int
         */
        public static function start(array $args): int
        {
            if(isset($args['help']))
            {
                return self::displayOptions();
            }

            return self::build($args);
        }

        /**
         * Builds the current project
         *
         * @param array $args
         * @return int
         */
        private static function build(array $args): int
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
                return 1;
            }

            $output_path = $args['output'] ?? $args['o'] ?? null;
            $options = [];

            if($output_path !== null)
            {
                $options[BuildConfigurationOptions::OUTPUT_FILE->value] = $output_path;
            }

            // Load the project
            try
            {
                $project_manager = new ProjectManager($project_path);
            }
            catch (Exception $e)
            {
                Console::outException('There was an error loading the project', $e, 1);
                return 1;
            }

            // Build the project
            try
            {
                $build_configuration = $args['config'] ?? $args['c'] ?? BuildConfigurationValues::DEFAULT->value;
                $output = $project_manager->build($build_configuration, $options);
            }
            catch (Exception $e)
            {
                Console::outException('Failed to build project', $e, 1);
                return 1;
            }

            Console::out($output);
            return 0;
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            $options = [
                new CliHelpSection(['build'], 'Builds the current project'),
                new CliHelpSection(['--help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['--path', '-p'], 'Specifies the path to the project where project.json is located (or the file itself), default: current working directory'),
                new CliHelpSection(['--config', '-c'], 'Specifies the build configuration to use, default: default build configuration'),
                new CliHelpSection(['--output', '-o'], 'Specifies the output path of the build, default: build configuration specified output path'),
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc build [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            return 0;
        }
    }