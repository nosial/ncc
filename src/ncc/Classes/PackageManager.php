<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    use Exception;
    use InvalidArgumentException;
    use JsonException;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Libraries\semver\VersionParser;
    use ncc\Objects\PackageLockEntry;
    use ncc\Runtime;

    class PackageManager
    {
        private string $dataDirectoryPath;
        private string $packageLockPath;
        /**
         * @var PackageLockEntry[]
         */
        private array $entries;
        private bool $modified;
        private bool $readOnly;
        private bool $autoSave;

        /**
         * PackageManager constructor.
         *
         * @param string $dataDirectoryPath The directory path where packages are stored
         * @param bool $autoSave If true, automatically save after each write operation (default: true)
         * @throws InvalidArgumentException If the directory path is invalid
         * @throws IOException If the lock file cannot be read
         */
        public function __construct(string $dataDirectoryPath, bool $autoSave=true)
        {
            Logger::getLogger()?->debug(sprintf('Initializing PackageManager for directory: %s', $dataDirectoryPath));
            
            if(empty($dataDirectoryPath))
            {
                throw new InvalidArgumentException('Directory path cannot be empty');
            }

            $this->dataDirectoryPath = rtrim($dataDirectoryPath, DIRECTORY_SEPARATOR);
            $this->packageLockPath = $this->dataDirectoryPath . DIRECTORY_SEPARATOR . 'lock.json';
            $this->entries = [];
            $this->modified = false;
            $this->autoSave = $autoSave;

            // Check if the lock file exists and is not writable
            if(IO::exists($this->packageLockPath))
            {
                $this->readOnly = !IO::isWritable($this->packageLockPath);
            }
            // Check if the directory is writable (for creating new lock file)
            elseif(IO::exists($this->dataDirectoryPath))
            {
                $this->readOnly = !IO::isWritable($this->dataDirectoryPath);
            }
            else
            {
                // Directory doesn't exist, check parent directory
                $this->readOnly = !IO::isWritable(dirname($this->dataDirectoryPath));
            }

            if(IO::exists($this->packageLockPath))
            {
                Logger::getLogger()?->verbose(sprintf('Loading existing lock file: %s', $this->packageLockPath));
                $this->loadLockFile();
            }
            else
            {
                Logger::getLogger()?->verbose('No existing lock file found, starting fresh');
            }
        }

        /**
         * Load and parse the package lock file
         *
         * @throws IOException If the lock file cannot be read
         */
        private function loadLockFile(): void
        {
            Logger::getLogger()?->debug('Parsing package lock file');
            $content = IO::readFile($this->packageLockPath);

            if(empty($content))
            {
                Logger::getLogger()?->verbose('Lock file is empty');
                return; // Empty file is valid (no packages)
            }

            try
            {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                throw new OperationException(sprintf('Invalid JSON in lock file %s: %s', $this->packageLockPath, $e->getMessage()), 0, $e);
            }

            if(!is_array($data))
            {
                throw new OperationException('Lock file must contain a JSON array');
            }

            foreach($data as $index => $entryData)
            {
                if(!is_array($entryData))
                {
                    throw new OperationException(sprintf('Invalid entry at index %d: expected array', $index));
                }

                try
                {
                    $entry = PackageLockEntry::fromArray($entryData);
                    $this->entries[(string)$entry] = $entry;
                    Logger::getLogger()?->debug(sprintf('Loaded package entry: %s', (string)$entry));
                }
                catch(InvalidArgumentException $e)
                {
                    throw new OperationException(sprintf('Invalid entry at index %d: %s', $index, $e->getMessage()), 0, $e);
                }
            }
            
            Logger::getLogger()?->verbose(sprintf('Loaded %d package entries from lock file', count($this->entries)));
        }

        /**
         * Get the path to the package lock file
         *
         * @return string
         */
        public function getPackageLockPath(): string
        {
            return $this->packageLockPath;
        }

        /**
         * Get the directory path of the package manager
         *
         * @return string
         */
        public function getDataDirectoryPath(): string
        {
            return $this->dataDirectoryPath;
        }

        /**
         * Get all package entries
         *
         * @return PackageLockEntry[]
         */
        public function getEntries(): array
        {
            return array_values($this->entries);
        }

        /**
         * Get the latest version of a package
         *
         * NOTE: Uses PHP's version_compare which may not handle all semantic versioning edge cases.
         * For full Composer-style version constraints, consider using composer/semver library.
         *
         * @param string $packageName The name of the package
         * @return PackageLockEntry|null The latest version entry or null if package not found
         */
        public function getLatestVersion(string $packageName): ?PackageLockEntry
        {
            Logger::getLogger()?->debug(sprintf('Looking for latest version of package: %s', $packageName));
            
            if(empty($packageName))
            {
                return null;
            }

            $latestEntry = null;

            foreach($this->entries as $entry)
            {
                if($entry->getPackage() === $packageName)
                {
                    if($latestEntry === null || version_compare($entry->getVersion(), $latestEntry->getVersion(), '>'))
                    {
                        $latestEntry = $entry;
                    }
                }
            }

            if($latestEntry !== null)
            {
                Logger::getLogger()?->verbose(sprintf('Latest version of %s is %s', $packageName, $latestEntry->getVersion()));
            }
            else
            {
                Logger::getLogger()?->verbose(sprintf('No version found for package: %s', $packageName));
            }
            
            return $latestEntry;
        }

        /**
         * Check if an entry exists for a package
         *
         * @param string $packageName The name of the package
         * @param string|null $version The specific version, or null to check if any version exists
         * @return bool True if the entry exists, false otherwise
         */
        public function entryExists(string $packageName, ?string $version = null): bool
        {
            if(empty($packageName))
            {
                return false;
            }

            if($version === null)
            {
                return $this->getLatestVersion($packageName) !== null;
            }

            if($version === 'latest')
            {
                return $this->getLatestVersion($packageName) !== null;
            }

            return isset($this->entries[$packageName . '=' . $version]);
        }

        /**
         * Get a specific package entry
         *
         * @param string $packageName The name of the package
         * @param string $version The version of the package, or 'latest' for the latest version
         * @return PackageLockEntry|null The package entry or null if not found
         */
        public function getEntry(string $packageName, string $version): ?PackageLockEntry
        {
            if(empty($packageName) || empty($version))
            {
                return null;
            }

            if($version === 'latest')
            {
                return $this->getLatestVersion($packageName);
            }

            // Try exact match first
            $entry = $this->entries[$packageName . '=' . $version] ?? null;
            if($entry !== null)
            {
                return $entry;
            }

            // Use semver to find matching version
            // Normalize the requested version and compare with all installed versions
            try
            {
                $versionParser = new VersionParser();
                $normalizedRequestedVersion = $versionParser->normalize($version);
                
                // Check all versions of this package
                foreach($this->entries as $key => $packageEntry)
                {
                    if($packageEntry->getPackage() === $packageName)
                    {
                        try
                        {
                            $normalizedInstalledVersion = $versionParser->normalize($packageEntry->getVersion());
                            
                            // Compare normalized versions
                            if($normalizedRequestedVersion === $normalizedInstalledVersion)
                            {
                                return $packageEntry;
                            }
                        }
                        catch(Exception $e)
                        {
                            // Skip invalid versions
                            continue;
                        }
                    }
                }
            }
            catch(Exception $e)
            {
                // If normalization fails, try fallback methods
            }

            return null;
        }

        /**
         * Get all versions of a package
         *
         * @param string $packageName The name of the package
         * @return PackageLockEntry[] Array of all versions of the package
         */
        public function getAllVersions(string $packageName): array
        {
            if(empty($packageName))
            {
                return [];
            }

            $results = [];

            foreach($this->entries as $entry)
            {
                if($entry->getPackage() === $packageName)
                {
                    $results[] = $entry;
                }
            }

            return $results;
        }

        /**
         * Get the filesystem path to a specific package file
         *
         * @param string $packageName The name of the package
         * @param string $version The version of the package, or 'latest' for the latest version
         * @return string|null The absolute path to the package file or null if not found
         */
        public function getPackagePath(string $packageName, string $version='latest'): ?string
        {
            $entry = $this->getEntry($packageName, $version);
            if($entry === null)
            {
                return null;
            }

            $packagePath = $this->getDataDirectoryPath() . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR .
                $packageName . '=' . $entry->getVersion();

            if(!IO::exists($packagePath))
            {
                return null;
            }

            return $packagePath;
        }

        /**
         * Get the filesystem location of a package
         *
         * @param PackageLockEntry $entry The package lock entry, if null only the packages directory is returned
         * @return string The absolute path to the package directory
         */
        public function getPackagePathFromEntry(?PackageLockEntry $entry=null): string
        {
            if($entry === null)
            {
                return $this->getDataDirectoryPath() . DIRECTORY_SEPARATOR . 'packages';
            }

            return $this->getDataDirectoryPath() . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . $entry;
        }

        /**
         * Add a new package entry from a PackageReader
         *
         * @param PackageReader $reader The package reader containing package information
         * @throws InvalidArgumentException If the package reader contains invalid data
         * @throws IOException If auto-save is enabled and save fails
         */
        private function addEntry(PackageReader $reader): void
        {
            Logger::getLogger()?->debug(sprintf('Adding package entry: %s=%s', $reader->getAssembly()->getPackage(), $reader->getAssembly()->getVersion()));
            
            $entry = new PackageLockEntry([
                'package' => $reader->getAssembly()->getPackage(),
                'version' => $reader->getAssembly()->getVersion(),
                'dependencies' => $reader->getHeader()->getDependencyReferences()
            ]);

            $this->entries[(string)$entry] = $entry;
            $this->modified = true;
            
            Logger::getLogger()?->verbose(sprintf('Package entry added: %s', (string)$entry));

            if($this->autoSave && $this->modified && !$this->readOnly)
            {
                $this->save();
            }
        }

        /**
         * Remove a package entry
         *
         * @param string $packageName The name of the package
         * @param string|null $version The version of the package to remove, or null to remove all versions
         * @return bool True if the entry was removed, false if it didn't exist
         * @throws IOException If auto-save is enabled and save fails
         */
        private function removeEntry(string $packageName, ?string $version=null): bool
        {
            if($version === null)
            {
                foreach($this->getAllVersions($packageName) as $entry)
                {
                    $this->removeEntry($packageName, $entry->getVersion());
                }
            }

            $key = $packageName . '=' . $version;

            if(isset($this->entries[$key]))
            {
                unset($this->entries[$key]);
                $this->modified = true;
                if($this->autoSave && $this->modified && !$this->readOnly)
                {
                    $this->save();
                }
                return true;
            }

            return false;
        }

        /**
         * Check if the package manager has unsaved changes
         *
         * @return bool True if there are unsaved modifications, false otherwise
         */
        public function isModified(): bool
        {
            return $this->modified;
        }

        /**
         * Check if the package manager is in read-only mode
         *
         * @return bool True if the lock file cannot be modified, false otherwise
         */
        public function isReadOnly(): bool
        {
            return $this->readOnly;
        }

        /**
         * Install a package using a PackageReader
         *
         * @param PackageReader $packageReader The package reader containing package information
         * @param array $options Installation options (e.g. 'reinstall' => true)
         * @throws OperationException If the package is already installed and 'reinstall' is not set
         * @throws IOException If file operations fail during installation
         */
        public function install(PackageReader $packageReader, array $options=[]): void
        {
            Logger::getLogger()?->verbose(sprintf('Installing package: %s=%s', $packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion()));
            
            // If the 'reinstall' option isn't set, we check if the pcakage is already installed
            if(!isset($options['reinstall']) && Runtime::packageInstalled($packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion()))
            {
                Logger::getLogger()?->debug('Package already installed and reinstall not requested');
                throw new OperationException(sprintf('Cannot install "%s" because the package is already installed', $packageReader->getAssembly()->getPackage()));
            }

            if(!IO::exists($this->getPackagePathFromEntry()))
            {
                Logger::getLogger()?->debug('Creating packages directory');
                IO::createDirectory($this->getPackagePathFromEntry());
            }

            $packageInstallationPath = $this->getPackagePathFromEntry() . DIRECTORY_SEPARATOR .
                sprintf("%s=%s", $packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());
            
            Logger::getLogger()?->verbose(sprintf('Installation path: %s', $packageInstallationPath));

            // Remove the orphaned package if it already exists
            if(IO::exists($packageInstallationPath))
            {
                Logger::getLogger()?->debug('Removing existing package installation');
                IO::delete($packageInstallationPath, false);
            }

            Logger::getLogger()?->verbose('Exporting package to installation path');
            $packageReader->exportPackage($packageInstallationPath); // Export (ONLY) the package to a file
            // The reason we don't copy the file directly is because the package could be embedded into another file,
            // and we need to extract just the package data.
            
            // Export cache file for faster subsequent imports
            try
            {
                Logger::getLogger()?->debug('Creating cache file for faster imports');
                $packageReader->exportCache($packageInstallationPath . '.cache');
            }
            catch(Exception $e)
            {
                Logger::getLogger()?->debug(sprintf('Failed to create cache file: %s', $e->getMessage()));
                // Cache creation is not critical, so we just log and continue
                // The package will still work, just without cache optimization
            }
            
            $this->addEntry($packageReader); // Finally add the entry to the package manager
            
            // Handle symlink creation if this is a system installation
            if (Runtime::isSystemUser() && !$options['no-symlink'])
            {
                try
                {
                    $projectName = $packageReader->getAssembly()->getName();
                    $packageName = $packageReader->getAssembly()->getPackage();
                    $forceSymlink = isset($options['force-symlink']) && $options['force-symlink'];
                    
                    // Check if symlink already exists
                    $symlinkExists = SymlinkManager::symlinkExists($projectName);
                    $isNccManaged = SymlinkManager::isNccManaged($projectName);
                    
                    // Determine if we should create/update the symlink
                    $shouldCreateSymlink = false;
                    
                    if (!$symlinkExists)
                    {
                        // No symlink exists, create one
                        $shouldCreateSymlink = true;
                        Logger::getLogger()?->verbose(sprintf('No symlink exists for %s, will create one', $projectName));
                    }
                    else if ($isNccManaged)
                    {
                        // Symlink exists and is managed by ncc, update it
                        $shouldCreateSymlink = true;
                        Logger::getLogger()?->verbose(sprintf('Updating existing ncc-managed symlink for %s', $projectName));
                    }
                    else if ($forceSymlink)
                    {
                        // Symlink exists but is not managed by ncc, force overwrite
                        $shouldCreateSymlink = true;
                        Logger::getLogger()?->verbose(sprintf('Force-overwriting existing symlink for %s', $projectName));
                    }
                    else
                    {
                        Logger::getLogger()?->warning(sprintf('Symlink for %s already exists and is not managed by ncc. Use --force-symlink to overwrite.', $projectName));
                    }
                    
                    if ($shouldCreateSymlink)
                    {
                        $symlinkPath = SymlinkManager::createSymlink($projectName, $packageName, $forceSymlink);
                        
                        if ($symlinkPath !== null)
                        {
                            Logger::getLogger()?->verbose(sprintf('Symlink created at: %s', $symlinkPath));
                            
                            // Update the package entry to mark symlink as registered
                            $entry = $this->getEntry($packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());
                            if ($entry !== null)
                            {
                                $entry->setSymlinkRegistered(true);
                                $this->modified = true;
                                if ($this->autoSave && !$this->readOnly)
                                {
                                    $this->save();
                                }
                            }
                        }
                    }
                }
                catch (Exception $e)
                {
                    // Symlink creation is not critical, log the error and continue
                    Logger::getLogger()?->warning(sprintf('Failed to create symlink: %s', $e->getMessage()));
                }
            }
            else if (!Runtime::isSystemUser())
            {
                Logger::getLogger()?->debug('Skipping symlink creation: not running as system user');
            }
            else
            {
                Logger::getLogger()?->debug('Skipping symlink creation: --no-symlink option specified');
            }
            
            Logger::getLogger()?->verbose('Package installation completed successfully');
        }

        /**
         * @param string $package
         * @param string|null $version
         * @return PackageLockEntry[]
         * @throws IOException
         */
        public function uninstall(string $package, ?string $version='latest'): array
        {
            $removedEntries = [];

            if($version === null)
            {
                // We remove all versions
                foreach($this->getAllVersions($package) as $entry)
                {
                    $removedEntries = array_merge($removedEntries, $this->uninstall($package, $entry->getVersion()));
                }
            }
            else
            {
                $entry = $this->getEntry($package, $version);
                if($entry === null)
                {
                    return []; // Nothing to uninstall
                }

                $packagePath = $this->getPackagePathFromEntry($entry);
                if(IO::exists($packagePath))
                {
                    // Handle symlink removal if this is a system installation and symlink was registered
                    if (Runtime::isSystemUser() && $entry->isSymlinkRegistered())
                    {
                        try
                        {
                            // Try to read the package to get the project name
                            $packageReader = new PackageReader($packagePath);
                            $projectName = $packageReader->getAssembly()->getName();
                            
                            // Remove the symlink
                            if (SymlinkManager::removeSymlink($projectName))
                            {
                                Logger::getLogger()?->verbose(sprintf('Removed symlink for project: %s', $projectName));
                            }
                        }
                        catch (Exception $e)
                        {
                            // Symlink removal is not critical, log the error and continue
                            Logger::getLogger()?->warning(sprintf('Failed to remove symlink: %s', $e->getMessage()));
                        }
                    }
                    
                    // Remove the package file
                    IO::delete($packagePath, false);
                }

                // Remove the entry from the lock file
                if($this->removeEntry($package, $entry->getVersion()))
                {
                    $removedEntries[] = $entry;
                }
            }

            return $removedEntries;
        }

        /**
         * Save the package lock file
         *
         * @throws IOException If the lock file cannot be written or is read-only
         */
        public function save(): void
        {
            if(!$this->modified)
            {
                return; // No changes to save
            }

            if($this->readOnly)
            {
                throw new IOException(sprintf('Cannot save lock file in read-only mode: %s', $this->packageLockPath));
            }

            $directory = dirname($this->packageLockPath);
            if(!IO::isDirectory($directory))
            {
                IO::createDirectory($directory);
            }

            $data = array_map(fn(PackageLockEntry $entry) => $entry->toArray(), array_values($this->entries));

            try
            {
                $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            catch(JsonException $e)
            {
                throw new IOException(sprintf('Failed to encode lock file data: %s', $e->getMessage()), 0, $e);
            }

            // Atomic write using temporary file
            $tempFile = $this->packageLockPath . '.tmp';
            IO::writeFile($tempFile, $json);

            try
            {
                IO::move($tempFile, $this->packageLockPath);
            }
            catch(IOException $e)
            {
                if(IO::exists($tempFile))
                {
                    IO::delete($tempFile, false);
                }
                throw new IOException(sprintf('Failed to save lock file: %s', $this->packageLockPath), 0, $e);
            }

            $this->modified = false;
        }

        /**
         * Destructor - automatically saves changes if modified
         */
        public function __destruct()
        {
            if($this->modified)
            {
                try
                {
                    $this->save();
                }
                catch(IOException $e)
                {
                    // Log error but don't throw in destructor
                    error_log(sprintf('Failed to auto-save package lock file: %s', $e->getMessage()));
                }
            }
        }
    }