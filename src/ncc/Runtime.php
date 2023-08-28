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

    namespace ncc;

    use Exception;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\Versions;
    use ncc\Classes\PhpExtension\PhpRuntime;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\ImportException;
    use ncc\Exceptions\IntegrityException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageException;
    use ncc\Managers\PackageManager;
    use ncc\Objects\PackageLock\VersionEntry;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Runtime\Constants;

    class Runtime
    {
        /**
         * @var PackageManager
         */
        private static $package_manager;

        /**
         * @var array
         */
        private static $imported_packages;

        /**
         * Determines if the package is already imported
         *
         * @param string $package
         * @param string $version
         * @return bool
         * @throws IOException
         */
        private static function isImported(string $package, string $version=Versions::LATEST): bool
        {
            if($version === Versions::LATEST)
            {
                $version = self::getPackageManager()->getPackage($package)->getLatestVersion();
            }

            $entry = "$package=$version";

            return isset(self::$imported_packages[$entry]);
        }

        /**
         * Adds a package to the imported packages list
         *
         * @param string $package
         * @param string $version
         * @return void
         */
        private static function addImport(string $package, string $version): void
        {
            $entry = "$package=$version";
            self::$imported_packages[$entry] = true;
        }


        /**
         * @param string $package
         * @param string $version
         * @param array $options
         * @return void
         * @throws IOException
         * @throws ImportException
         */
        public static function import(string $package, string $version=Versions::LATEST, array $options=[]): void
        {
            try
            {
                $package_entry = self::getPackageManager()->getPackage($package);
            }
            catch (IOException $e)
            {
                throw new ImportException(sprintf('Failed to import package "%s" due to a package lock exception: %s', $package, $e->getMessage()), $e);
            }

            if($package_entry === null)
            {
                throw new ImportException(sprintf("Package '%s' not found", $package));
            }

            if($version === Versions::LATEST)
            {
                $version = $package_entry->getLatestVersion();
            }

            try
            {
                /** @var VersionEntry $version_entry */
                $version_entry = $package_entry->getVersion($version);

                if($version_entry === null)
                {
                    throw new ImportException(sprintf('Version %s of %s is not installed', $version, $package));
                }
            }
            catch (IOException $e)
            {
                throw new ImportException(sprintf('Version %s of %s is not installed', $version, $package), $e);
            }

            try
            {
                if (self::isImported($package, $version))
                {
                    return;
                }
            }
            catch (IOException $e)
            {
                throw new ImportException(sprintf('Failed to check if package %s is imported', $package), $e);
            }

            if(count($version_entry->getDependencies()) > 0)
            {
                // Import all dependencies first
                /** @var Dependency $dependency */
                foreach($version_entry->getDependencies() as $dependency)
                {
                    self::import($dependency->getPackageName(), $dependency->getVersion(), $options);
                }
            }

            try
            {
                switch($version_entry->getCompiler()->getExtension())
                {
                    case CompilerExtensions::PHP:
                        PhpRuntime::import($version_entry, $options);
                        break;

                    default:
                        throw new ImportException(sprintf('Compiler extension %s is not supported in this runtime', $version_entry->getCompiler()->getExtension()));
                }
            }
            catch(Exception $e)
            {
                throw new ImportException(sprintf('Failed to import package %s', $package), $e);
            }

            self::addImport($package, $version);
        }

        /**
         * Returns the data path of the package
         *
         * @param string $package
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws PackageException
         */
        public static function getDataPath(string $package): string
        {
            $package = self::getPackageManager()->getPackage($package);

            if($package === null)
            {
                throw new PackageException('Package not found (null entry error, possible bug)');
            }

            return $package->getDataPath();
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
         * Returns a registered constant
         *
         * @param string $package
         * @param string $name
         * @return string|null
         */
        public static function getConstant(string $package, string $name): ?string
        {
            return Constants::get($package, $name);
        }

        /**
         * Registers a new constant
         *
         * @param string $package
         * @param string $name
         * @param string $value
         * @return void
         * @throws IntegrityException
         */
        public static function setConstant(string $package, string $name, string $value): void
        {
            Constants::register($package, $name, $value);
        }
    }