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
    use ncc\Classes\Runtime;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class ExecCommand
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         */
        public static function start($args): void
        {
            $package = $args['package'] ?? null;
            $version = $args['exec-version'] ?? 'latest';

            if($package == null)
            {
                self::displayOptions();
                exit(0);
            }

            try
            {
                $package_name = Runtime::import($package, $version);
            }
            catch(Exception $e)
            {
                Console::outException('Cannot import package ' . $package, $e, 1);
                return;
            }

            try
            {
                exit(Runtime::execute($package_name));
            }
            catch(Exception $e)
            {
                Console::outException($e->getMessage(), $e, 1);
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
                new CliHelpSection(['exec', '--package'], '(Required) The package to execute'),
                new CliHelpSection(['--exec-version'], '(default: latest) The version of the package to execute'),
                new CliHelpSection(['--exec-args'], '(optional) Anything past this point will be passed to the execution unit'),
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc exec --package <package> [options] [arguments]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            Console::out(PHP_EOL . 'Arguments:' . PHP_EOL);
            Console::out('   <arguments>   The arguments to pass to the program');
            Console::out(PHP_EOL . 'Example Usage:' . PHP_EOL);
            Console::out('   ncc exec --package com.example.program');
            Console::out('   ncc exec --package com.example.program --exec-version 1.0.0');
            Console::out('   ncc exec --package com.example.program --exec-args --foo --bar --extra=test');
        }
    }