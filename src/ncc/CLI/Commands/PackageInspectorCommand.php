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
    use ncc\Classes\PackageReader;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\CliHelpSection;
    use ncc\ThirdParty\Symfony\Yaml\Yaml;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class PackageInspectorCommand
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

            if(!isset($args['path']) && !isset($args['p']))
            {
                Console::outError('Missing required option "--path"', true, 1);
                return 1;
            }

            if(isset($args['headers']))
            {
                try
                {
                    return self::displayHeaders($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display headers: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['metadata']))
            {
                try
                {
                    return self::displayMetadata($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display metadata: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['assembly']))
            {
                try
                {
                    return self::displayAssembly($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display assembly: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['dependencies']))
            {
                try
                {
                    return self::displayDependencies($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display dependencies: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['execution_units']))
            {
                try
                {
                    return self::displayExecutionUnits($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display execution units: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            return self::displayOptions();
        }

        /**
         * Displays the headers section of the package
         *
         * @param array $args
         * @return int
         * @throws IOException
         */
        private static function displayHeaders(array $args): int
        {
            self::printArray((new PackageReader($args['path'] ?? $args['p']))->getHeaders(),
                isset($args['json']), isset($args['json-pretty'])
            );

            return 0;
        }

        /**
         * Displays the assembly section
         *
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws IOException
         */
        private static function displayAssembly(array $args): int
        {
            self::printArray((new PackageReader($args['path'] ?? $args['p']))->getAssembly()->toArray(),
                isset($args['json']), isset($args['json-pretty'])
            );

            return 0;
        }

        /**
         * Displays the metadata section
         *
         * @param array $args
         * @return int
         * @throws IOException
         * @throws ConfigurationException
         */
        private static function displayMetadata(array $args): int
        {
            self::printArray((new PackageReader($args['path'] ?? $args['p']))->getMetadata()->toArray(),
                isset($args['json']), isset($args['json-pretty'])
            );

            return 0;
        }

        /**
         * Displays the dependencies section of the package
         *
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws IOException
         */
        private static function displayDependencies(array $args): int
        {
            $package_reader = new PackageReader($args['path'] ?? $args['p']);
            $dependencies = array_map(static function($dependency) use ($package_reader)
            {
                return $package_reader->getDependency($dependency)->toArray();
            }, $package_reader->getDependencies());

            if(count($dependencies) === 0)
            {
                Console::outError('No dependencies found', true, 1);
                return 1;
            }

            self::printArray($dependencies, isset($args['json']), isset($args['json-pretty']));
            return 0;
        }

        /**
         * Displays the execution units section of the package
         *
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws IOException
         */
        private static function displayExecutionUnits(array $args): int
        {
            $package_reader = new PackageReader($args['path'] ?? $args['p']);
            $execution_units = array_map(static function($execution_unit) use ($package_reader)
            {
                return $package_reader->getExecutionUnit($execution_unit)->toArray();
            }, $package_reader->getExecutionUnits());

            if(count($execution_units) === 0)
            {
                Console::outError('No execution units found', true, 1);
                return 1;
            }

            self::printArray($execution_units, isset($args['json']), isset($args['json-pretty']));
            return 0;
        }

        /**
         * Prints out an array in a specific format
         *
         * @param array $data
         * @param bool $is_json
         * @param bool $pretty_json
         * @return void
         */
        private static function printArray(array $data, bool $is_json, bool $pretty_json): void
        {
            if($is_json)
            {
                try
                {
                    Console::out(json_encode($data, JSON_THROW_ON_ERROR));
                    return;
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display headers: %s', $e->getMessage()), $e, 1);
                    return;
                }
            }

            if($pretty_json)
            {
                try
                {
                    Console::out(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
                    return;
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to display headers: %s', $e->getMessage()), $e, 1);
                    return;
                }
            }

            Console::out(Yaml::dump($data, 4, 2), false);
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['--path', '-p'], 'Required. Specifies the path to the binary package to inspect'),
                new CliHelpSection(['--json'], 'Prints out the information of the package in JSON format'),
                new CliHelpSection(['--pretty-json'], 'Prints out the information of the package in pretty JSON format'),
                new CliHelpSection(['headers'], 'Prints out the headers of the package'),
                new CliHelpSection(['metadata'], 'Prints out the metadata of the package'),
                new CliHelpSection(['assembly'], 'Prints out the assembly information of the package'),
                new CliHelpSection(['dependencies'], 'Prints out the dependencies of the package'),
                new CliHelpSection(['execution_units'], 'Prints out the execution units of the package'),
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc ins [command] [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            return 0;
        }
    }