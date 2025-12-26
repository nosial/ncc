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

    use Exception;
    use ncc\Classes\AuthenticationManager;
    use ncc\Classes\IO;
    use ncc\Classes\PackageManager;
    use ncc\Classes\PackageReader;
    use ncc\Classes\PathResolver;
    use ncc\Classes\RepositoryManager;
    use ncc\Classes\StreamWrapper;
    use ncc\Exceptions\ImportException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageException;
    use ncc\Objects\PackageLockEntry;
    use ncc\Objects\RepositoryConfiguration;

    class Runtime
    {
        private static array $importedPackages = [];
        private static array $packageReaderReferences = [];
        private static array $registeredAutoloaders = [];
        private static bool $streamWrapperInitialized = false;
        private static ?PackageManager $userPackageManager = null;
        private static ?PackageManager $systemPackageManager = null;
        private static ?RepositoryManager $userRepositoryManager = null;
        private static ?RepositoryManager $systemRepositoryManager = null;
        private static ?AuthenticationManager $systemAuthenticationManager = null;
        private static ?AuthenticationManager $userAuthenticationManager = null;

        /**
         * Imports a package into the runtime, this method supports two ways of importing a method
         *  1. From a file directly
         *  2. From the package manager
         *
         * Importing a file directly does not support the $version parameter, hence it will be ignored.
         * Importing a package from the package manager allows the user of the $version parameter if
         * a specific version of the package is needed. Otherwise use `latest` to only import the latest
         * version of the installed package.
         *
         * @param string $package The path to the package or the package name
         * @param string $version The version of the package to import if importing from a package manager
         * @throws ImportException If the package cannot be imported.
         */
        public static function import(string $package, string $version='latest'): void
        {
            self::initializeStreamWrapper();

            try
            {
                if(IO::isFile($package))
                {
                    $packageReader = self::importFromFile($package);
                    $packageName = $packageReader->getAssembly()->getPackage();
                }
                else
                {
                    // Check if package is already imported before attempting to import
                    if(isset(self::$importedPackages[$package]))
                    {
                        return; // Package already imported, skip
                    }
                    
                    $packageReader = self::importFromPackageManager($package, $version);
                    $packageName = $packageReader->getAssembly()->getPackage();
                }
            }
            catch(IOException $e)
            {
                throw new ImportException('Fatal error while read the package: ' . $package, $e->getCode(), $e);
            }

            // Check again with the actual package name (in case a file path was used)
            if(isset(self::$importedPackages[$packageName]))
            {
                return; // Package already imported, skip
            }

            // Import the package as a reference
            $referenceId = uniqid();
            self::$packageReaderReferences[$referenceId] = $packageReader;
            self::$importedPackages[$packageName] = $referenceId;

            // Register the autoloader for this package
            self::registerAutoloader($packageReader);

            // If the package is statically linked, import its dependencies as well but point to the same reference
            if($packageReader->getHeader()->isStaticallyLinked())
            {
                foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
                {
                    self::$importedPackages[$reference->getPackage()] = $referenceId;
                }
            }
            // For non-statically linked packages, import dependencies separately
            elseif(count($packageReader->getHeader()->getDependencyReferences()) > 0)
            {
                foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
                {
                    self::import($reference->getPackage(), $reference->getVersion());
                }
            }
        }

        /**
         * Imports a package from the package manager
         *
         * @param string $package The package name
         * @param string $version The version of the package (use 'latest' for the most recent version)
         * @return PackageReader Returns the PackageReader
         * @throws IOException
         * @throws ImportException If the package cannot be found or imported
         * @throws PackageException
         */
        private static function importFromPackageManager(string $package, string $version='latest'): PackageReader
        {
            // Try user package manager first
            $userManager = self::getUserPackageManager();
            if($userManager !== null && $userManager->entryExists($package, $version))
            {
                $packagePath = $userManager->getPackagePath($package, $version);
                return self::importFromFileWithCache($packagePath);
            }

            // Try system package manager
            $systemManager = self::getSystemPackageManager();
            if($systemManager->entryExists($package, $version))
            {
                $packagePath = $systemManager->getPackagePath($package, $version);
                return self::importFromFileWithCache($packagePath);
            }

            throw new ImportException(sprintf('Package "%s" version "%s" not found in package managers', $package, $version));
        }

        /**
         * Constructs a PackageReader instance based off a package file on disk
         *
         * @param string $packagePath The file path to the package
         * @return PackageReader Returns the PackageReader
         * @throws IOException Thrown if the file cannot be read/found
         */
        private static function importFromFile(string $packagePath): PackageReader
        {
            // Initialize the StreamWrapper on first import
            $packagePath = realpath($packagePath);
            if(!IO::exists($packagePath))
            {
                throw new IOException('Package not found: ' . $packagePath);
            }

            if(!IO::isFile($packagePath))
            {
                throw new IOException('Package path is not a file: ' . $packagePath);
            }

            if(!IO::isReadable($packagePath))
            {
                throw new IOException('Package file is not readable: ' . $packagePath);
            }

            return new PackageReader($packagePath);
        }

        /**
         * Constructs a PackageReader instance with cache support for faster loading
         *
         * @param string $packagePath The file path to the package
         * @return PackageReader Returns the PackageReader
         * @throws IOException Thrown if the file cannot be read/found
         */
        private static function importFromFileWithCache(string $packagePath): PackageReader
        {
            // Initialize the StreamWrapper on first import
            $packagePath = realpath($packagePath);
            if(!IO::exists($packagePath))
            {
                throw new IOException('Package not found: ' . $packagePath);
            }

            if(!IO::isFile($packagePath))
            {
                throw new IOException('Package path is not a file: ' . $packagePath);
            }

            if(!IO::isReadable($packagePath))
            {
                throw new IOException('Package file is not readable: ' . $packagePath);
            }

            // Try to use cache for faster loading
            return new PackageReader($packagePath, true);
        }

        /**
         * Gets the list of imported packages.
         *
         * @return array An array of imported packages.
         */
        public static function getImportedPackages(): array
        {
            $results = [];
            foreach(self::$importedPackages as $packageName => $referenceId)
            {
                $results[$packageName] = self::$packageReaderReferences[$referenceId];
            }

            return $results;
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
         * Gets the user-level PackageManager instance, initializing it if necessary.
         * Returns null when running as root/system user.
         *
         * @return PackageManager|null The user-level PackageManager instance, or null if running as system user.
         * @throws PackageException Thrown if there is an error initializing the package manager.
         * @throws IOException Thrown if there is an error creating the package manager directory.
         */
        public static function getUserPackageManager(): ?PackageManager
        {
            if(self::$userPackageManager === null)
            {
                $userLocation = PathResolver::getUserLocation();
                if($userLocation === null)
                {
                    return null;
                }

                if(!IO::exists($userLocation))
                {
                    IO::mkdir($userLocation);
                }

                self::$userPackageManager = new PackageManager($userLocation);
            }

            return self::$userPackageManager;
        }

        /**
         * Gets the system-level PackageManager instance, initializing it if necessary.
         * This always returns a valid PackageManager instance.
         *
         * @return PackageManager The system-level PackageManager instance.
         * @throws IOException
         * @throws PackageException
         */
        public static function getSystemPackageManager(): PackageManager
        {
            if(self::$systemPackageManager === null)
            {
                $systemLocation = PathResolver::getSystemLocation();
                $hasWriteAccess = IO::isWritable(dirname($systemLocation)) || (IO::exists($systemLocation) && IO::isWritable($systemLocation));
                if($hasWriteAccess && !IO::exists($systemLocation))
                {
                    IO::mkdir($systemLocation);
                }

                self::$systemPackageManager = new PackageManager($systemLocation);
            }

            return self::$systemPackageManager;
        }

        /**
         * Gets the primary (writable) PackageManager instance.
         * Returns user-level package manager for regular users,
         * and system-level package manager for system users (root).
         *
         * @return PackageManager The primary PackageManager instance.
         * @throws IOException
         * @throws PackageException
         */
        public static function getPackageManager(): PackageManager
        {
            $userManager = self::getUserPackageManager();
            if($userManager !== null)
            {
                return $userManager;
            }

            return self::getSystemPackageManager();
        }

        public static function getUserRepositoryManager(): ?RepositoryManager
        {
            if(self::$userRepositoryManager === null)
            {
                $userLocation = PathResolver::getUserLocation();
                if($userLocation === null)
                {
                    return null;
                }

                if(!IO::exists($userLocation))
                {
                    IO::mkdir($userLocation);
                }

                self::$userRepositoryManager = new RepositoryManager($userLocation);
            }

            return self::$userRepositoryManager;
        }

        public static function getSystemRepositoryManager(): RepositoryManager
        {
            if(self::$systemRepositoryManager === null)
            {
                $systemLocation = PathResolver::getSystemLocation();
                $hasWriteAccess = IO::isWritable(dirname($systemLocation)) || (IO::exists($systemLocation) && IO::isWritable($systemLocation));
                if($hasWriteAccess && !IO::exists($systemLocation))
                {
                    IO::mkdir($systemLocation);
                }

                self::$systemRepositoryManager = new RepositoryManager($systemLocation);
            }

            return self::$systemRepositoryManager;
        }

        public static function getRepositoryManager(): RepositoryManager
        {
            $userManager = self::getUserRepositoryManager();
            if($userManager !== null)
            {
                return $userManager;
            }

            return self::getSystemRepositoryManager();
        }

        public static function getUserAuthenticationManager(): ?AuthenticationManager
        {
            if(self::$userAuthenticationManager === null)
            {
                $userLocation = PathResolver::getUserLocation();
                if($userLocation === null)
                {
                    return null;
                }

                if(!IO::exists($userLocation))
                {
                    IO::mkdir($userLocation);
                }

                self::$userAuthenticationManager = new AuthenticationManager($userLocation);
            }

            return self::$userAuthenticationManager;
        }

        public static function getSystemAuthenticationManager(): AuthenticationManager
        {
            if(self::$systemAuthenticationManager === null)
            {
                $systemLocation = PathResolver::getSystemLocation();
                $hasWriteAccess = IO::isWritable(dirname($systemLocation) || (IO::exists($systemLocation) && IO::isWritable($systemLocation)));
                if($hasWriteAccess && !IO::exists($systemLocation))
                {
                    IO::mkdir($systemLocation);
                }

                self::$systemAuthenticationManager = new AuthenticationManager($systemLocation);
            }

            return self::$systemAuthenticationManager;
        }

        public static function packageInstalled(string $package, ?string $version='latest'): bool
        {
            if(self::getSystemPackageManager()->entryExists($package, $version))
            {
                return true;
            }

            return self::getUserPackageManager()?->entryExists($package, $version) ?? false;
        }

        public static function getPackageEntry(string $package, string $version='latest'): ?PackageLockEntry
        {
            $systemPackageEntry = self::getSystemPackageManager()->getEntry($package, $version);
            if($systemPackageEntry === null)
            {
                return self::getUserPackageManager()?->getEntry($package, $version);
            }

            return $systemPackageEntry;
        }

        public static function getPackageEntries(?string $package=null): array
        {

            $entries = [];

            if($package === null)
            {
                $entries = array_merge($entries, self::getSystemPackageManager()->getEntries());
                if(self::getUserPackageManager() !== null)
                {
                    $entries = array_merge($entries, self::getUserPackageManager()->getEntries());
                }
            }
            else
            {
                $entries = array_merge($entries, self::getSystemPackageManager()->getAllVersions($package));
                if(self::getUserPackageManager() !== null)
                {
                    $entries = array_merge($entries, self::getUserPackageManager()->getAllVersions($package));
                }
            }

            return $entries;
        }

        public static function isSystemPackage(string $package, ?string $version='latest'): bool
        {
            return self::getSystemPackageManager()->entryExists($package, $version);
        }

        public static function getPackagePath(string $package, string $version='latest'): ?string
        {
            $systemPackagePath = self::getSystemPackageManager()->getPackagePath($package, $version);
            if($systemPackagePath === null)
            {
                return self::getUserPackageManager()->getPackagePath($package, $version);
            }

            return $systemPackagePath;
        }

        /**
         * @param string $package
         * @param string|null $version
         * @return PackageLockEntry[]
         * @throws IOException
         * @throws PackageException
         */
        public static function uninstallPackage(string $package, ?string $version='latest'): array
        {
            return array_merge(
                self::getSystemPackageManager()->uninstall($package, $version),
                self::getUserPackageManager()?->uninstall($package, $version) ?? []
            );
        }

        public static function getRepository(string $name): ?RepositoryConfiguration
        {
            return self::getSystemRepositoryManager()->getRepository($name) ?? self::getUserRepositoryManager()?->getRepository($name);
        }

        public static function repositoryExists(string $name): bool
        {
            if(self::getSystemRepositoryManager()->repositoryExists($name))
            {
                return true;
            }

            return self::getUserRepositoryManager()?->repositoryExists($name) ?? false;
        }

        public static function getRepositories(): array
        {
            return array_merge(
                self::getSystemRepositoryManager()->getEntries(),
                self::getUserRepositoryManager()?->getEntries() ?? []
            );
        }

        public static function deleteRepository(string $name): bool
        {
            return self::getSystemRepositoryManager()->removeRepository($name) || self::getUserRepositoryManager()?->removeRepository($name) ?? false;
        }

        public static function isSystemUser(): bool
        {
            // Check if running as root on Unix-like systems
            if (function_exists('posix_geteuid') && posix_geteuid() === 0)
            {
                return true;
            }

            // Check if running with elevated privileges on Windows
            if (PHP_OS_FAMILY === 'Windows')
            {
                $identity = shell_exec('whoami /groups 2>nul | findstr /i "S-1-16-12288" 2>nul');
                if ($identity !== null && trim($identity) !== '')
                {
                    return true;
                }
            }

            return false;
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

        /**
         * Registers an autoloader for the given package.
         * 
         * Extracts the autoloader mapping from the package header and registers
         * a custom autoloader function that maps class names to ncc:// protocol paths.
         *
         * @param PackageReader $packageReader The package reader to register an autoloader for
         * @return void
         */
        private static function registerAutoloader(PackageReader $packageReader): void
        {
            $packageName = $packageReader->getAssembly()->getPackage();
            
            // Check if autoloader already registered for this package
            if(isset(self::$registeredAutoloaders[$packageName]))
            {
                return;
            }
            
            $autoloaderMapping = $packageReader->getHeader()->getAutoloader();
            
            // If no autoloader mapping, nothing to register
            if($autoloaderMapping === null || empty($autoloaderMapping))
            {
                return;
            }
            
            // Create the autoloader closure
            $autoloader = function(string $className) use ($autoloaderMapping, $packageName): bool
            {
                // Try case-sensitive match first
                if(isset($autoloaderMapping[$className]))
                {
                    $filePath = $autoloaderMapping[$className];
                    
                    try
                    {
                        require_once $filePath;
                        return true;
                    }
                    catch(Exception $e)
                    {
                        trigger_error(sprintf('NCC Autoloader: Failed to load "%s" from "%s": %s', $className, $filePath, $e->getMessage()), E_USER_WARNING);
                        return false;
                    }
                }
                
                // Try case-insensitive match as fallback
                foreach($autoloaderMapping as $mappedClass => $filePath)
                {
                    if(strcasecmp($mappedClass, $className) === 0)
                    {
                        try
                        {
                            require_once $filePath;
                            return true;
                        }
                        catch(Exception $e)
                        {
                            trigger_error(sprintf('NCC Autoloader: Failed to load "%s" from "%s": %s', $className, $filePath, $e->getMessage()), E_USER_WARNING);
                            return false;
                        }
                    }
                }
                
                return false;
            };
            
            // Register the autoloader
            $registered = spl_autoload_register($autoloader, true, false);
            
            if($registered)
            {
                self::$registeredAutoloaders[$packageName] = $autoloader;
            }
            else
            {
                trigger_error(sprintf('NCC Autoloader: Failed to register autoloader for package "%s"', $packageName), E_USER_WARNING);
            }
        }
    }
