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

    if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php'))
    {
        throw new Exception('Autoloader.php not found, is this project built correctly?');
    }

    require 'Autoloader.php';
    define('__NCC_DIR__', __DIR__);

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
            define('__NCC_CLI__', true);
            exit(Main::main(array_slice($argv, array_search('--ncc-cli', $argv, true) + 1)));
        }
    }
    else
    {
        define('__NCC_CLI__', false);
    }
