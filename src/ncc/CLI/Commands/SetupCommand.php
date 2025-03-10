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
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class SetupCommand
    {
        /**
         * Sets up the NCC environment (usually used during installation)
         *
         * @param array $args
         * @return int
         */
        public static function start(array $args): int
        {
            if(isset($args['help']))
            {
                return self::help();
            }

            $default_repositories = [];
            if(isset($args['default-repositories']))
            {
                try
                {
                    $default_repositories = Functions::loadJsonFile($args['default-repositories'], Functions::FORCE_ARRAY);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Failed to load the default repositories from the given path: %s', $e->getMessage()), $e);
                    return 1;
                }
            }

            try
            {
                Functions::initializeFiles(null, $default_repositories);
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Failed to initialize the files: %s', $e->getMessage()), $e);
                return 1;
            }

            return 0;
        }


        /**
         * Displays the help menu for the setup command
         *
         * @return int
         */
        private static function help(): int
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the command'),
                new CliHelpSection(['--default-repositories=path'], 'Optional. The path to the default repositories file. If not specified, no default repositories will be loaded')
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc setup [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            return 0;
        }
    }