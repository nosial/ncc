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

    namespace ncc\Utilities;

    use RuntimeException;

    class PathFinder
    {
        /**
         * Returns the root path of the system
         *
         * @return string
         */
        public static function getRootPath(): string
        {
            return realpath(DIRECTORY_SEPARATOR);
        }

        /**
         * Returns the path where all NCC installation data is stored
         *
         * @return string
         */
        public static function getDataPath(): string
        {
            return self::getRootPath() . 'var' . DIRECTORY_SEPARATOR . 'ncc';
        }

        /**
         * Returns the path where packages are installed
         *
         * @return string
         */
        public static function getPackagesPath(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'packages';
        }

        /**
         * Returns the path where cache files are stored
         *
         * @return string
         */
        public static function getCachePath(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'cache';
        }

        /**
         * Returns the package lock file path
         *
         * @return string
         */
        public static function getPackageLock(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'package.lck';
        }

        /**
         * Returns the repository database file
         *
         * @return string
         */
        public static function getRepositoryDatabase(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'repository.db';
        }

        /**
         * Returns the credential storage file
         *
         * @return string
         */
        public static function getCredentialStorage(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'credentials.store';
        }

        /**
         * Returns the configuration file path (ncc.yaml)
         *
         * @return string
         */
        public static function getConfigurationFile(): string
        {
            return self::getDataPath() . DIRECTORY_SEPARATOR . 'ncc.yaml';
        }

        /**
         * Returns the path where binaries are stored
         *
         * @return string
         */
        public static function findBinPath(): string
        {
            $path_directories = explode(PATH_SEPARATOR, getenv('PATH'));

            // Check if the bin directory is in the path
            if(in_array(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin', $path_directories))
            {
                return DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin';
            }

            Console::outWarning('The /usr/bin directory is not in the PATH environment variable, an alternative will be used');

            // If not, find something appropriate
            foreach($path_directories as $path_directory)
            {
                if(is_dir($path_directory) && is_writable($path_directory))
                {
                    return $path_directory;
                }
            }

            throw new RuntimeException('Could not find a suitable bin directory in the PATH environment variable');
        }
    }