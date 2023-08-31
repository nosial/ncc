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

$BuildDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'build';
    $AutoloadPath = $BuildDirectory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';

    if(!file_exists($BuildDirectory) || !is_dir($BuildDirectory))
        throw new RuntimeExcepion('Build directory does not exist, to run tests you must build the project.');

    if(!file($AutoloadPath) || !is_file($AutoloadPath))
        throw new RuntimeException('Autoload file does not exist in \'' . $BuildDirectory .'\', to run tests you must build the project.');

    require($AutoloadPath);