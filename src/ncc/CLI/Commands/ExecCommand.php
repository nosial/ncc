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
    use ncc\Managers\ExecutionPointerManager;
    use ncc\Managers\PackageLockManager;
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
            $unit_name = $args['exec-unit'] ?? 'main';
            $set_args = $args['exec-args'] ?? null;

            if($package == null)
            {
                self::displayOptions();
                exit(0);
            }

            $package_lock_manager = new PackageLockManager();
            $execution_pointer_manager = new ExecutionPointerManager();

            try
            {
                $package_entry = $package_lock_manager->getPackageLock()->getPackage($package);
            }
            catch(Exception $e)
            {
                Console::outException('Package ' . $package . ' is not installed', $e, 1);
                return;
            }

            try
            {
                $version_entry = $package_entry->getVersion($version);
            }
            catch(Exception $e)
            {
                Console::outException('Version ' . $version . ' is not installed', $e, 1);
                return;
            }

            try
            {
                $units = $execution_pointer_manager->getUnits($package_entry->Name, $version_entry->Version);
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Cannot load execution units for package \'%s\'', $package), $e, 1);
                return;
            }

            if(!in_array($unit_name, $units))
            {
                Console::outError(sprintf('Unit \'%s\' is not configured for package \'%s\'', $unit_name, $package), true, 1);
                return;
            }

            $options = [];

            if($set_args != null)
            {
                global $argv;
                $args_index = array_search('--exec-args', $argv);
                $options = array_slice($argv, $args_index + 1);
            }

            try
            {
                exit($execution_pointer_manager->executeUnit($package_entry->Name, $version_entry->Version, $unit_name, $options));
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Cannot execute execution point \'%s\' in package \'%s\'', $unit_name, $package), $e, 1);
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
                new CliHelpSection(['--exec-unit'], '(default: main) The unit point of the package to execute'),
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
            Console::out('   ncc exec --package com.example.program --exec-version 1.0.0 --exec-unit setup');
            Console::out('   ncc exec --package com.example.program --exec-args --foo --bar --extra=test');
        }
    }