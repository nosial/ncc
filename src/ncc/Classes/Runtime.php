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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes;

    use Exception;
    use InvalidArgumentException;
    use ncc\Enums\FileDescriptor;
    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\ImportException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Managers\PackageManager;
    use ncc\Objects\Package\Metadata;
    use ncc\Utilities\IO;
    use RuntimeException;

    class Runtime
    {
        /**
         * @var array
         */
        private static $imported_packages = [];

        /**
         * @var array
         */
        private static $class_map = [];

        /**
         * @var PackageManager|null
         */
        private static $package_manager;

        /**
         * Executes the main execution point of an imported package and returns the evaluated result
         * This method may exit the program without returning a value
         *
         * @param string $package
         * @return mixed
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         * @throws OperationException
         */
        public static function execute(string $package): int
        {
            if(!self::isImported($package))
            {
                throw new InvalidArgumentException(sprintf('Package %s is not imported', $package));
            }

            if(self::$imported_packages[$package] instanceof PackageReader)
            {
                return ExecutionUnitRunner::executeFromPackage(
                    self::$imported_packages[$package],
                    self::$imported_packages[$package]->getMetadata()->getMainExecutionPolicy()
                );
            }

            if(is_string(self::$imported_packages[$package]))
            {
                $metadata_path = self::$imported_packages[$package] . DIRECTORY_SEPARATOR . FileDescriptor::METADATA;

                if(!is_file($metadata_path))
                {
                    throw new RuntimeException(sprintf('The package %s does not have a metadata file (is it corrupted?)', $package));
                }

                return ExecutionUnitRunner::executeFromSystem(
                    self::$imported_packages[$package],
                    Metadata::fromArray(ZiProto::decode(IO::fread($metadata_path)))->getMainExecutionPolicy()
                );
            }

            throw new RuntimeException('Unable to execute the main execution point of the package, this is probably a bug');
        }

        /**
         * @param string $package
         * @param string $version
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws ImportException
         * @throws PathNotFoundException
         */
        public static function import(string $package, string $version=Versions::LATEST): string
        {
            if(self::isImported($package))
            {
                return $package;
            }

            if(is_file($package))
            {
                return self::importFromPackage(realpath($package));
            }

            if(self::getPackageManager()->getPackageLock()->entryExists($package))
            {
                return self::importFromSystem($package, $version);
            }

            throw new RuntimeException('Importing from a package name is not supported yet');
        }

        /**
         * @param string $package
         * @param string $version
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws ImportException
         * @throws PathNotFoundException
         */
        private static function importFromSystem(string $package, string $version=Versions::LATEST): string
        {
            $entry = self::getPackageManager()->getPackageLock()->getEntry($package);

            foreach($entry->getClassMap($version) as $class => $component_name)
            {
                $component_path = $entry->getPath($version) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $component_name;
                self::$class_map[strtolower($class)] = $component_path;
            }

            self::$imported_packages[$package] = $entry->getPath($version);

            // Import dependencies recursively
            foreach($entry->getVersion($version)->getDependencies() as $dependency)
            {
                /** @noinspection UnusedFunctionResultInspection */
                self::import($dependency->getName(), $dependency->getVersion());
            }

            // TODO: Import required files if any (see options)

            return $package;
        }

        /**
         * Imports a package from a package file
         *
         * @param string $package_path
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws ImportException
         * @throws PathNotFoundException
         */
        private static function importFromPackage(string $package_path): string
        {
            try
            {
                $package_reader = new PackageReader($package_path);
            }
            catch(Exception $e)
            {
                throw new RuntimeException(sprintf('Failed to import package from file "%s" due to an exception: %s', $package_path, $e->getMessage()), 0, $e);
            }

            // Check if the package is already imported
            if(in_array($package_reader->getAssembly()->getPackage(), self::$imported_packages, true))
            {
                $package_name = $package_reader->getAssembly()->getPackage();
                unset($package_reader);
                return $package_name;
            }

            // Import the package
            $package_name = $package_reader->getAssembly()->getPackage();
            self::$imported_packages[$package_name] = $package_reader;

            // Register the autoloader
            foreach($package_reader->getClassMap() as $value)
            {
                self::$class_map[strtolower($value)] = static function() use ($value, $package_name)
                {
                    return self::$imported_packages[$package_name]->getComponentByClass($value)->getData();
                };
            }

            // Import dependencies recursively
            if(!$package_reader->getFlag(PackageFlags::STATIC_DEPENDENCIES))
            {
                foreach($package_reader->getDependencies() as $dependency)
                {
                    $dependency = $package_reader->getDependency($dependency);

                    /** @noinspection UnusedFunctionResultInspection */
                    self::import($dependency->getName(), $dependency->getVersion());
                }
            }

            // TODO: Import required files if any (see options)

            return $package_reader->getAssembly()->getPackage();
        }

        /**
         * Determines if the package is already imported
         *
         * @param string $package
         * @return bool
         */
        public static function isImported(string $package): bool
        {
            return isset(self::$imported_packages[$package]);
        }

        /**
         * Returns an array of all the packages that is currently imported
         *
         * @return array
         */
        public static function getImportedPackages(): array
        {
            return array_keys(self::$imported_packages);
        }

        /**
         * @param string $class
         * @return void
         */
        public static function autoloadHandler(string $class): void
        {
            $class = strtolower($class);

            if(!isset(self::$class_map[$class]))
            {
                return;
            }

            if(is_callable(self::$class_map[$class]))
            {
                eval(self::$class_map[$class]());
                return;
            }

            if(is_string(self::$class_map[$class]) && is_file(self::$class_map[$class]))
            {
                require_once self::$class_map[$class];
                return;
            }
        }

        /**
         * @return PackageManager
         */
        private static function getPackageManager(): PackageManager
        {
            if(self::$package_manager === null)
            {
                self::$package_manager = new PackageManager();
            }

            return self::$package_manager;
        }
    }