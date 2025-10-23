<?php
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

    namespace ncc\Classes;

    use RuntimeException;

    class PathResolver
    {
        /**
         * Returns the home path of the user directory
         *
         * @return string
         */
        public static function getUserHome(): string
        {
            $home = getenv('HOME');
            if($home === false || $home === '')
            {
                $home = getenv('HOMEPATH');
                if($home === false || $home === '')
                {
                    throw new RuntimeException("Could not resolve user home directory");
                }
                $drive = getenv('HOMEDRIVE');
                if($drive !== false && $drive !== '')
                {
                    $home = $drive . $home;
                }
            }

            return rtrim($home, DIRECTORY_SEPARATOR);
        }

        /**
         * Returns the data path for ncc
         *
         * @return string
         */
        public static function getDataPath(): string
        {
            if (getenv('NCC_DATA_PATH'))
            {
                return rtrim(getenv('NCC_DATA_PATH'), DIRECTORY_SEPARATOR);
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            {
                return self::getUserHome() . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local' . DIRECTORY_SEPARATOR . 'ncc';
            }
            else
            {
                return self::getUserHome() . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'ncc';
            }
        }
    }