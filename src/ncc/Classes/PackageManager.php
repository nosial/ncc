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

    use InvalidArgumentException;
    use JsonException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PackageException;
    use ncc\Objects\PackageLockEntry;
    use ncc\Objects\PackageSource;
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
         * @throws PackageException If the lock file contains invalid data
         */
        public function __construct(string $dataDirectoryPath, bool $autoSave=true)
        {
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
            if(file_exists($this->packageLockPath))
            {
                return !is_writable($this->packageLockPath);
            }

            // Check if the directory is writable (for creating new lock file)
            if(file_exists($this->dataDirectoryPath))
            {
                return !is_writable($this->dataDirectoryPath);
            }

            // Directory doesn't exist, check parent directory
            $this->readOnly = !is_writable(dirname($this->dataDirectoryPath));

            if(file_exists($this->packageLockPath))
            {
                $this->loadLockFile();
            }
        }

        /**
         * Load and parse the package lock file
         *
         * @throws IOException If the lock file cannot be read
         * @throws PackageException If the lock file contains invalid data
         */
        private function loadLockFile(): void
        {
            $content = @file_get_contents($this->packageLockPath);
            if($content === false)
            {
                throw new IOException(sprintf('Failed to read lock file: %s', $this->packageLockPath));
            }

            if(empty($content))
            {
                return; // Empty file is valid (no packages)
            }

            try
            {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                throw new PackageException(sprintf('Invalid JSON in lock file %s: %s', $this->packageLockPath, $e->getMessage()), 0, $e);
            }

            if(!is_array($data))
            {
                throw new PackageException('Lock file must contain a JSON array');
            }

            foreach($data as $index => $entryData)
            {
                if(!is_array($entryData))
                {
                    throw new PackageException(sprintf('Invalid entry at index %d: expected array', $index));
                }

                try
                {
                    $entry = PackageLockEntry::fromArray($entryData);
                    $this->entries[(string)$entry] = $entry;
                }
                catch(InvalidArgumentException $e)
                {
                    throw new PackageException(sprintf('Invalid entry at index %d: %s', $index, $e->getMessage()), 0, $e);
                }
            }
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

            return $this->entries[$packageName . '=' . $version] ?? null;
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

            if(!file_exists($packagePath))
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
            $entry = new PackageLockEntry([
                'package' => $reader->getAssembly()->getPackage(),
                'version' => $reader->getAssembly()->getVersion(),
                'dependencies' => $reader->getHeader()->getDependencyReferences()
            ]);

            $this->entries[(string)$entry] = $entry;
            $this->modified = true;

            if($this->autoSave && $this->modified && !$this->readOnly)
            {
                $this->save();
            }
        }

        /**
         * Remove a package entry
         *
         * @param string $packageName The name of the package
         * @param string $version The version of the package
         * @return bool True if the entry was removed, false if it didn't exist
         * @throws IOException If auto-save is enabled and save fails
         */
        private function removeEntry(string $packageName, string $version): bool
        {
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
            // If the 'reinstall' option isn't set, we check if the pcakage is already installed
            if(!isset($options['reinstall']) && Runtime::packageInstalled($packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion()))
            {
                throw new OperationException(sprintf('Cannot install "%s" because the package is already installed', $packageReader->getAssembly()->getPackage()));
            }

            if(
                !file_exists($this->getPackagePathFromEntry()) &&
                !mkdir($this->getPackagePathFromEntry(), 0755, true) &&
                !is_dir($this->getPackagePathFromEntry())
            )
            {
                throw new IOException(sprintf('Directory "%s" was not created', $this->getPackagePathFromEntry()));
            }

            $packageInstallationPath = $this->getPackagePathFromEntry() . DIRECTORY_SEPARATOR .
                sprintf("%s=%s", $packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());

            // Remove the orphaned package if it already exists
            if(file_exists($packageInstallationPath) && !unlink($packageInstallationPath))
            {
                throw new IOException(sprintf('Cannot remove orphaned package from "%s"', $packageInstallationPath));
            }

            // Copy over the package to the package installation path
            if(!copy($packageReader->getFilePath(), $packageInstallationPath))
            {
                throw new IOException(sprintf('Cannot copy package from "%s" to "%s"', $packageReader->getFilePath(), $packageInstallationPath));
            }

            // Finally add the entry to the package manager
            $this->addEntry($packageReader);
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
            if(!is_dir($directory))
            {
                if(!@mkdir($directory, 0755, true))
                {
                    throw new IOException(sprintf('Failed to create directory: %s', $directory));
                }
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
            if(@file_put_contents($tempFile, $json, LOCK_EX) === false)
            {
                throw new IOException(sprintf('Failed to write temporary lock file: %s', $tempFile));
            }

            if(!@rename($tempFile, $this->packageLockPath))
            {
                @unlink($tempFile);
                throw new IOException(sprintf('Failed to save lock file: %s', $this->packageLockPath));
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