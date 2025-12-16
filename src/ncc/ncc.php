<?php

    /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    use ncc\CLI\Main;

    if(defined('__NCC__'))
    {
        // NCC is already loaded, prevent re-initialization
        return;
    }

    if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php'))
    {
        throw new Exception('Autoloader.php not found, was ncc built correctly?');
    }

    // Include the autoloader
    /** @noinspection PhpIncludeInspection */
    require 'Autoloader.php';

    // Define NCC constants
    define('__NCC_DIR__', __DIR__); // The directory where ncc is located
    define('__NCC__', true); // Flag to indicate that ncc is loaded

    // Register the shutdown handler
    \ncc\Classes\ShutdownHandler::register();

    // Define the core methods
    if(!function_exists('import'))
    {
        /**
         * Imports a package into the runtime environment.
         *
         * @param string $packagePath The path to the package file. Can be a .ncc file or a file containing a package.
         * @noinspection PhpUnused
         */
        function import(string $packagePath): void
        {
            try
            {
                \ncc\Runtime::import($packagePath);
            }
            catch (\ncc\Exceptions\ImportException $e)
            {
                trigger_error('Failed to import package: ' . $e->getMessage(), E_USER_ERROR);
            }
        }
    }

    if(!function_exists('get_imported'))
    {
        /**
         * Returns an array of imported package names in the runtime environment.
         *
         * @return array An array of imported package names.
         * @noinspection PhpUnused
         */
        function get_imported(): array
        {
            return \ncc\Runtime::getImportedPackages();
        }
    }

    if(!function_exists('is_imported'))
    {
        /**
         * Checks if a package is imported in the runtime environment.
         *
         * @param string $packageName The name of the package to check.
         * @return bool True if the package is imported, false otherwise.
         * @noinspection PhpUnused
         */
        function is_imported(string $packageName): bool
        {
            return \ncc\Runtime::isImported($packageName);
        }
    }

    // Ensure that ncc's CLI mode only runs when executed from the command line
    if(php_sapi_name() === 'cli')
    {
        // Check if $argv contains '--ncc-cli'
        if(!isset($argv) || !is_array($argv) || !in_array('--ncc-cli', $argv, true))
        {
            define('__NCC_CLI__', false);
        }
        else
        {
            // Check if all the extensions are available
            $required = [
                'curl',
                'json',
                'msgpack',
                'tokenizer',
                'phar',
                'ctype'
            ];

            foreach($required as $extension)
            {
                if(!extension_loaded($extension))
                {
                    fwrite(STDERR, "Required PHP extension '$extension' is not loaded. Please install/enable it to use ncc.\n");
                    exit(1);
                }
            }

            define('__NCC_CLI__', true);
            exit(Main::main(array_slice($argv, array_search('--ncc-cli', $argv, true) + 1)));
        }
    }
    else
    {
        define('__NCC_CLI__', false);
    }
