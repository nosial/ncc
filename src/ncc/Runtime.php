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
    namespace ncc;

    use ncc\Classes\PackageReader;
    use ncc\Classes\StreamWrapper;
    use ncc\Exceptions\ImportException;

    class Runtime
    {
        /**
         * @var array
         */
        private static array $importedPackages = [];

        /**
         * @var bool Flag to track if StreamWrapper has been initialized
         */
        private static bool $streamWrapperInitialized = false;

        /**
         * Imports a package into the runtime.
         *
         * @param string $packagePath The path to the package file. Can be a .ncc file or a file containing a package.
         * @throws ImportException If the package cannot be imported.
         */
        public static function import(string $packagePath): void
        {
            // Initialize the StreamWrapper on first import
            self::initializeStreamWrapper();

            $packagePath = realpath($packagePath);
            if(!file_exists($packagePath))
            {
                throw new ImportException('Package not found: ' . $packagePath);
            }

            if(!is_file($packagePath))
            {
                throw new ImportException('Package path is not a file: ' . $packagePath);
            }

            if(!is_readable($packagePath))
            {
                throw new ImportException('Package file is not readable: ' . $packagePath);
            }

            $packageReader = new PackageReader($packagePath);
            self::$importedPackages[$packageReader->getAssembly()->getPackage()] = $packageReader;
        }

        /**
         * Gets the list of imported packages.
         *
         * @return array An array of imported packages.
         */
        public static function getImportedPackages(): array
        {
            return self::$importedPackages;
        }

        /**
         * Checks if a package is imported.
         *
         * @param string $packageName The name of the package.
         * @return bool True if the package is imported, false otherwise.
         */
        public static function isImported(string $packageName): bool
        {
            return isset(self::$importedPackages[$packageName]);
        }

        /**
         * Initializes the StreamWrapper if not already initialized.
         * This is called automatically on the first package import.
         *
         * @return void
         */
        private static function initializeStreamWrapper(): void
        {
            if (self::$streamWrapperInitialized)
            {
                return;
            }

            StreamWrapper::register();
            self::$streamWrapperInitialized = true;
        }
    }