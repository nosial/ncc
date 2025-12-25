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
    use RuntimeException;

    class Runtime
    {
        private static array $importedPackages = [];
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
                    self::$importedPackages[$packageReader->getAssembly()->getPackage()] = $packageReader;
                }
                else
                {
                    $packageReader = self::importFromPackageManager($package, $version);
                }
            }
            catch(IOException $e)
            {
                throw new ImportException('Fatal error while read the package: ' . $package, $e->getCode(), $e);
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
        public static function importFromPackageManager(string $package, string $version='latest'): PackageReader
        {
            // First check if package is already imported
            if(isset(self::$importedPackages[$package]))
            {
                return self::$importedPackages[$package];
            }

            // Try user package manager first
            $userManager = self::getUserPackageManager();
            if($userManager !== null && $userManager->entryExists($package, $version))
            {
                $packagePath = $userManager->getPackagePath($package, $version);
                $packageReader = self::importFromFile($packagePath);
                self::$importedPackages[$package] = $packageReader;
                return $packageReader;
            }

            // Try system package manager
            $systemManager = self::getSystemPackageManager();
            if($systemManager->entryExists($package, $version))
            {
                $packagePath = $systemManager->getPackagePath($package, $version);
                $packageReader = self::importFromFile($packagePath);
                self::$importedPackages[$package] = $packageReader;
                return $packageReader;
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
    }