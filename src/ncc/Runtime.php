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
    use ncc\Classes\Logger;
    use ncc\Classes\PackageManager;
    use ncc\Classes\PackageReader;
    use ncc\Classes\PathResolver;
    use ncc\Classes\RepositoryManager;
    use ncc\Classes\StreamWrapper;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\semver\Semver;
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
         * a specific version of the package is needed. Otherwise, use `latest` to only import the latest
         * version of the installed package.
         *
         * @param string|PackageReader $package The path to the package or the package name
         * @param string $version The version of the package to import if importing from a package manager
         * @throws OperationException Thrown if there is an error during the import process
         */
        public static function import(string|PackageReader $package, string $version='latest'): void
        {
            Logger::getLogger()->debug(sprintf('Import requested: %s@%s', $package, $version));
            self::initializeStreamWrapper();

            if($package instanceof PackageReader)
            {
                $packageName = $package->getAssembly()->getPackage();

                // Check if package is already imported before attempting to import
                if(isset(self::$importedPackages[$packageName]))
                {
                    Logger::getLogger()->debug(sprintf('Package already imported: %s', $packageName));
                    return; // Package already imported, skip
                }

                Logger::getLogger()->verbose(sprintf('Importing from PackageReader instance: %s', $packageName));
                $packageReader = $package;

                // Import the package as a reference
                $referenceId = uniqid();
                Logger::getLogger()->verbose(sprintf('Registering package: %s=%s', $packageName, $packageReader->getAssembly()->getVersion()));
                self::$packageReaderReferences[$referenceId] = $packageReader;
                self::$importedPackages[$packageName] = $referenceId;
                // Register the autoloader for this package

                Logger::getLogger()->debug(sprintf('Registering autoloader for: %s', $packageName));
                self::registerAutoloader($packageReader);
                Logger::getLogger()->verbose(sprintf('Package import completed: %s', $packageName));

                return;
            }

            try
            {
                if(IO::isFile($package))
                {
                    Logger::getLogger()->verbose(sprintf('Importing from file: %s', $package));
                    $packageReader = self::importFromFile($package);
                }
                else
                {
                    // Check if package is already imported before attempting to import
                    if(isset(self::$importedPackages[$package]))
                    {
                        Logger::getLogger()->debug(sprintf('Package already imported: %s', $package));
                        return; // Package already imported, skip
                    }
                    
                    Logger::getLogger()->verbose(sprintf('Importing from package manager: %s@%s', $package, $version));
                    $packageReader = self::importFromPackageManager($package, $version);
                }

                $packageName = $packageReader->getAssembly()->getPackage();
            }
            catch(IOException $e)
            {
                throw new OperationException('Fatal error while read the package: ' . $package, $e->getCode(), $e);
            }

            // Check again with the actual package name (in case a file path was used)
            if(isset(self::$importedPackages[$packageName]))
            {
                Logger::getLogger()->debug(sprintf('Package already imported (by actual name): %s', $packageName));
                return; // Package already imported, skip
            }
            
            Logger::getLogger()->verbose(sprintf('Registering package: %s=%s', $packageName, $packageReader->getAssembly()->getVersion()));

            // Import the package as a reference
            $referenceId = uniqid();
            self::$packageReaderReferences[$referenceId] = $packageReader;
            self::$importedPackages[$packageName] = $referenceId;

            // If the package is statically linked, mark dependencies as imported BEFORE registering autoloader
            // This prevents the autoloader from trying to import dependencies that are already embedded
            if($packageReader->getHeader()->isStaticallyLinked())
            {
                Logger::getLogger()->debug(sprintf('Package is statically linked, marking %d dependencies as imported', count($packageReader->getHeader()->getDependencyReferences())));
                foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
                {
                    self::$importedPackages[$reference->getPackage()] = $referenceId;
                    Logger::getLogger()->verbose(sprintf('Marked dependency as imported: %s', $reference->getPackage()));
                }
            }

            // Register the autoloader for this package
            Logger::getLogger()->debug(sprintf('Registering autoloader for: %s', $packageName));
            self::registerAutoloader($packageReader);

            // For non-statically linked packages, import dependencies separately
            if(!$packageReader->getHeader()->isStaticallyLinked() && count($packageReader->getHeader()->getDependencyReferences()) > 0)
            {
                Logger::getLogger()->verbose(sprintf('Importing %d dependencies for: %s', count($packageReader->getHeader()->getDependencyReferences()), $packageName));
                foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
                {
                    Logger::getLogger()->debug(sprintf('Importing dependency: %s@%s', $reference->getPackage(), $reference->getVersion()));
                    self::import($reference->getPackage(), $reference->getVersion());
                }
            }
            
            Logger::getLogger()->verbose(sprintf('Package import completed: %s', $packageName));
        }

        /**
         * Gets the list of imported packages.
         *
         * @return array An array of imported packages.
         */
        public static function getImportedPackages(): array
        {
            return array_map(function ($referenceId)
            {
                return self::$packageReaderReferences[$referenceId];
            }, self::$importedPackages);
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
         * Returns True if the specified package is installed in either package manager.
         *
         * @param string $package The package name
         * @param string|null $version The version of the package (use 'latest' for the most recent version)
         * @return bool True if the package is installed, false otherwise
         * @throws IOException Thrown if there is an error accessing the package managers
         */
        public static function packageInstalled(string $package, ?string $version='latest'): bool
        {
            if(self::getSystemPackageManager()->entryExists($package, $version))
            {
                return true;
            }

            return self::getUserPackageManager()?->entryExists($package, $version) ?? false;
        }

        /**
         * Returns the PackageLockEntry for the specified package from either package manager.
         *
         * @param string $package The package name
         * @param string $version The version of the package (use 'latest' for the most recent version)
         * @return PackageLockEntry|null The PackageLockEntry if found, null otherwise
         * @throws IOException Thrown if there is an error accessing the package managers
         */
        public static function getPackageEntry(string $package, string $version='latest'): ?PackageLockEntry
        {
            $systemPackageEntry = self::getSystemPackageManager()->getEntry($package, $version);
            if($systemPackageEntry === null)
            {
                return self::getUserPackageManager()?->getEntry($package, $version);
            }

            return $systemPackageEntry;
        }

        /**
         * Returns all PackageLockEntries for the specified package from both package managers.
         * If no package is specified, returns all installed packages from both package managers.
         *
         * @param string|null $package The package name, or null to get all packages
         * @return PackageLockEntry[] An array of PackageLockEntries
         * @throws IOException Thrown if there is an error accessing the package managers
         */
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

        /**
         * Returns True if the specified package is installed in the system package manager.
         *
         * @param string $package The package name
         * @param string|null $version The version of the package (use 'latest' for the most recent version)
         * @return bool True if the package is installed in the system package manager, false otherwise
         * @throws IOException Thrown if there is an error accessing the system package manager
         */
        public static function isSystemPackage(string $package, ?string $version='latest'): bool
        {
            return self::getSystemPackageManager()->entryExists($package, $version);
        }

        /**
         * Returns the installation path of the specified package from either package manager.
         *
         * @param string $package The package name
         * @param string $version The version of the package (use 'latest' for the most recent version)
         * @return string|null The installation path if found, null otherwise
         * @throws IOException Thrown if there is an error accessing the package managers
         */
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
         * Returns an array of PackageLockEntries for packages that were uninstalled from either package manager.
         *
         * @param string $package The package name
         * @param string|null $version The version of the package (use 'latest' for the most recent version)
         * @return PackageLockEntry[] An array of PackageLockEntries for uninstalled packages
         * @throws IOException Thrown if there is an error accessing the package managers
         */
        public static function uninstallPackage(string $package, ?string $version='latest'): array
        {
            return array_merge(
                self::getSystemPackageManager()->uninstall($package, $version),
                self::getUserPackageManager()?->uninstall($package, $version) ?? []
            );
        }

        /**
         * Gets the user-level PackageManager instance, initializing it if necessary.
         * Returns null when running as root/system user.
         *
         * @return PackageManager|null The user-level PackageManager instance, or null if running as system user.
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
         * @throws IOException Thrown if there is an error creating the package manager directory.
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
         * @throws IOException Thrown if there is an error creating the package manager directory.
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

        /**
         * Gets the user-level RepositoryManager instance, initializing it if necessary.
         * Returns null when running as root/system user.
         *
         * @return RepositoryManager|null The user-level RepositoryManager instance, or null if running as system user.
         * @throws IOException Thrown if there is an error creating the repository manager directory.
         */
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

        /**
         * Gets the system-level RepositoryManager instance, initializing it if necessary.
         * This always returns a valid RepositoryManager instance.
         *
         * @return RepositoryManager The system-level RepositoryManager instance.
         * @throws IOException Thrown if there is an error creating the repository manager directory.
         */
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

        /**
         * Gets the primary (writable) RepositoryManager instance.
         * Returns user-level repository manager for regular users,
         * and system-level repository manager for system users (root).
         *
         * @return RepositoryManager The primary RepositoryManager instance.
         * @throws IOException Thrown if there is an error creating the repository manager directory.
         */
        public static function getRepositoryManager(): RepositoryManager
        {
            $userManager = self::getUserRepositoryManager();
            if($userManager !== null)
            {
                return $userManager;
            }

            return self::getSystemRepositoryManager();
        }

        /**
         * Gets the user-level AuthenticationManager instance, initializing it if necessary.
         * Returns null when running as root/system user.
         *
         * @return AuthenticationManager|null The user-level AuthenticationManager instance, or null if running as system user.
         * @throws IOException Thrown if there is an error creating the authentication manager directory.
         */
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

        /**
         * Gets the system-level AuthenticationManager instance, initializing it if necessary.
         * This always returns a valid AuthenticationManager instance.
         *
         * @return AuthenticationManager The system-level AuthenticationManager instance.
         * @throws IOException Thrown if there is an error creating the authentication manager directory.
         */
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

        /**
         * Gets the primary (writable) AuthenticationManager instance.
         * Returns user-level authentication manager for regular users,
         * and system-level authentication manager for system users (root).
         *
         * @return AuthenticationManager The primary AuthenticationManager instance.
         * @throws IOException Thrown if there is an error creating the authentication manager directory.
         */
        public static function getAuthenticationManager(): AuthenticationManager
        {
            $userManager = self::getUserAuthenticationManager();
            if($userManager !== null)
            {
                return $userManager;
            }

            return self::getSystemAuthenticationManager();
        }

        /**
         * Gets a repository configuration by name from either repository manager.
         *
         * @param string $name The name of the repository
         * @return RepositoryConfiguration|null The RepositoryConfiguration if found, null otherwise
         * @throws IOException Thrown if there is an error accessing the repository managers
         */
        public static function getRepository(string $name): ?RepositoryConfiguration
        {
            return self::getSystemRepositoryManager()->getRepository($name) ?? self::getUserRepositoryManager()?->getRepository($name);
        }

        /**
         * Checks if a repository exists in either repository manager.
         *
         * @param string $name The name of the repository
         * @return bool True if the repository exists, false otherwise
         * @throws IOException Thrown if there is an error accessing the repository managers
         */
        public static function repositoryExists(string $name): bool
        {
            return self::getSystemRepositoryManager()->repositoryExists($name) || (self::getUserRepositoryManager()?->repositoryExists($name) ?? false);
        }

        /**
         * Gets all repository configurations from both repository managers.
         *
         * @return RepositoryConfiguration[] An array of RepositoryConfigurations
         * @throws IOException Thrown if there is an error accessing the repository managers
         */
        public static function getRepositories(): array
        {
            return array_merge(
                self::getSystemRepositoryManager()->getEntries(),
                self::getUserRepositoryManager()?->getEntries() ?? []
            );
        }

        /**
         * Executes a package from either a file path or package manager.
         *
         * This method supports two ways of executing a package:
         *  1. From a file directly (package parameter is a file path)
         *  2. From the package manager (package parameter is a package name)
         *
         * @param string $package The path to the package file or the package name
         * @param string $version The version of the package to execute (ignored for file paths, default 'latest')
         * @param string|null $executionUnit The specific execution unit to run (null for default)
         * @param array $arguments Arguments to pass to the executed package
         * @return mixed The result from the package execution
         * @throws IOException If the package file cannot be accessed
         * @throws OperationException If the package or version is not found in the package manager
         */
        public static function execute(string $package, string $version='latest', ?string $executionUnit=null, array $arguments=[]): mixed
        {
            Logger::getLogger()->debug(sprintf('Execute requested: %s@%s, unit=%s', $package, $version, $executionUnit ?? 'default'));
            self::initializeStreamWrapper();

            // Determine if package is a file path or package name
            if(is_file($package))
            {
                $packagePath = realpath($package);
                if($packagePath === false)
                {
                    throw new IOException('The specified package file does not exist.');
                }

                Logger::getLogger()->verbose(sprintf('Executing package from file: %s', $packagePath));
            }
            else
            {
                // Look up package in package manager
                $packagePath = self::getPackagePath($package, $version);
                if($packagePath === null)
                {
                    throw new OperationException(sprintf('Package "%s" version "%s" not found in package managers', $package, $version));
                }

                Logger::getLogger()->verbose(sprintf('Executing package from package manager: %s@%s', $package, $version));
            }

            // Create package reader with cache for faster loading
            $packageReader = new PackageReader($packagePath, true);
            Logger::getLogger()->verbose(sprintf('Executing package: %s, unit=%s, args=%d', $packageReader->getAssembly()->getPackage(), $executionUnit ?? 'default', count($arguments)));

            // Execute the package
            return $packageReader->execute($executionUnit, $arguments);
        }

        /**
         * Checks if the current user is a system user (root on Unix, Administrator on Windows).
         *
         * @return bool True if running as system user, false otherwise
         */
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
         * Imports a package from the package manager
         *
         * @param string $package The package name
         * @param string $version The version of the package (use 'latest' for the most recent version)
         * @return PackageReader Returns the PackageReader
         * @throws IOException Thrown if there is an error reading the package file
         * @throws OperationException Thrown if the package or version is not found
         */
        private static function importFromPackageManager(string $package, string $version='latest'): PackageReader
        {
            Logger::getLogger()->debug(sprintf('Looking up package in package managers: %s@%s', $package, $version));

            // Try user package manager first
            $userManager = self::getUserPackageManager();
            if($userManager !== null && $userManager->entryExists($package, $version))
            {
                Logger::getLogger()->verbose(sprintf('Package found in user manager: %s@%s', $package, $version));
                $packagePath = $userManager->getPackagePath($package, $version);
                return self::importFromFileWithCache($packagePath);
            }

            // Try system package manager
            $systemManager = self::getSystemPackageManager();
            if($systemManager->entryExists($package, $version))
            {
                Logger::getLogger()->verbose(sprintf('Package found in system manager: %s@%s', $package, $version));
                $packagePath = $systemManager->getPackagePath($package, $version);
                return self::importFromFileWithCache($packagePath);
            }

            // If exact version not found and not 'latest', try semver matching
            if($version !== 'latest')
            {
                Logger::getLogger()->debug(sprintf('Exact version not found, trying semver matching for: %s@%s', $package, $version));
                $satisfyingVersion = self::findSatisfyingVersion($package, $version, $userManager, $systemManager);
                if($satisfyingVersion !== null)
                {
                    Logger::getLogger()->verbose(sprintf('Found satisfying version: %s@%s', $package, $satisfyingVersion));
                    // Check user manager first
                    if($userManager !== null && $userManager->entryExists($package, $satisfyingVersion))
                    {
                        $packagePath = $userManager->getPackagePath($package, $satisfyingVersion);
                        return self::importFromFileWithCache($packagePath);
                    }

                    // Check system manager
                    if($systemManager->entryExists($package, $satisfyingVersion))
                    {
                        $packagePath = $systemManager->getPackagePath($package, $satisfyingVersion);
                        return self::importFromFileWithCache($packagePath);
                    }
                }
                else
                {
                    Logger::getLogger()->debug(sprintf('No satisfying version found for: %s@%s', $package, $version));
                }
            }

            throw new OperationException(sprintf('Package "%s" version "%s" not found in package managers', $package, $version));
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
         * @noinspection PhpRedundantOptionalArgumentInspection
         * @noinspection PhpConditionCheckedByNextConditionInspection
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

        /**
         * Finds a satisfying version for a package using semver matching.
         *
         * @param string $package The package name
         * @param string $requestedVersion The requested version constraint
         * @param PackageManager|null $userManager User package manager
         * @param PackageManager $systemManager System package manager
         * @return string|null The satisfying version or null if none found
         */
        private static function findSatisfyingVersion(string $package, string $requestedVersion, ?PackageManager $userManager, PackageManager $systemManager): ?string
        {
            // Collect all available versions for this package
            $availableVersions = [];
            
            // Get versions from user manager
            if($userManager !== null)
            {
                $userEntries = $userManager->getEntries();
                foreach($userEntries as $entry)
                {
                    if($entry->getPackage() === $package)
                    {
                        $availableVersions[] = $entry->getVersion();
                    }
                }
            }
            
            // Get versions from system manager
            $systemEntries = $systemManager->getEntries();
            foreach($systemEntries as $entry)
            {
                if($entry->getPackage() === $package)
                {
                    $availableVersions[] = $entry->getVersion();
                }
            }
            
            if(empty($availableVersions))
            {
                return null;
            }
            
            // Remove duplicates
            $availableVersions = array_unique($availableVersions);
            
            try
            {
                // Try to find a satisfying version using semver
                // First, try as-is (in case it's already a constraint like ^1.0 or ~2.3)
                $satisfying = Semver::satisfiedBy($availableVersions, $requestedVersion);
                
                if(!empty($satisfying))
                {
                    // Return the highest satisfying version
                    return Semver::rsort($satisfying)[0];
                }
                
                // If no match found with exact version, try normalizing trailing .0 segments
                // (e.g., 1.33.0.0 -> 1.33.0)
                $normalized = preg_replace('/\.0+$/', '', $requestedVersion);
                if($normalized !== $requestedVersion)
                {
                    $satisfying = Semver::satisfiedBy($availableVersions, $normalized);
                    if(!empty($satisfying))
                    {
                        return Semver::rsort($satisfying)[0];
                    }
                }
                
                // If still no match and the version looks like a specific version (e.g., "7.4.0"),
                // try with caret constraint (^) for compatible versions
                if(preg_match('/^\d+\.\d+(\.\d+)?$/', $requestedVersion))
                {
                    $satisfying = Semver::satisfiedBy($availableVersions, '^' . $requestedVersion);
                    if(!empty($satisfying))
                    {
                        return Semver::rsort($satisfying)[0];
                    }
                }
            }
            catch(Exception $e)
            {
                // Semver matching failed, return null
                return null;
            }
            
            return null;
        }
    }
