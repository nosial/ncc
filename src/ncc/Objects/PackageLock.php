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

    namespace ncc\Objects;

    use ncc\Enums\Versions;
    use ncc\Exceptions\IOException;
    use ncc\Objects\PackageLock\PackageEntry;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class PackageLock
    {
        /**
         * The version of package lock file structure
         *
         * @var string
         */
        public $PackageLockVersion;

        /**
         * The Unix Timestamp for when this package lock file was last updated
         *
         * @var int
         */
        public $LastUpdatedTimestamp;

        /**
         * An array of installed packages in the PackageLock file
         *
         * @var PackageEntry[]
         */
        public $Packages;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->PackageLockVersion = Versions::PACKAGE_LOCK_VERSION;
            $this->Packages = [];
        }

        /**
         * Updates the version and timestamp
         *
         * @return void
         */
        private function update(): void
        {
            $this->PackageLockVersion = Versions::PACKAGE_LOCK_VERSION;
            $this->LastUpdatedTimestamp = time();
        }

        /**
         * @param Package $package
         * @param string $install_path
         * @return void
         */
        public function addPackage(Package $package, string $install_path): void
        {
            Console::outVerbose("Adding package {$package->assembly->package} to package lock file");

            if(!isset($this->Packages[$package->assembly->package]))
            {
                $package_entry = new PackageEntry();
                $package_entry->addVersion($package, $install_path, true);
                $package_entry->Name = $package->assembly->package;
                $package_entry->UpdateSource = $package->header->UpdateSource;
                $package_entry->getDataPath();
                $this->Packages[$package->assembly->package] = $package_entry;
                $this->update();

                return;
            }

            $this->Packages[$package->assembly->package]->UpdateSource = $package->header->UpdateSource;
            $this->Packages[$package->assembly->package]->addVersion($package, $install_path, true);
            $this->Packages[$package->assembly->package]->getDataPath();
            $this->update();
        }

        /**
         * Removes a package version entry, removes the entire entry if there are no installed versions
         *
         * @param string $package
         * @param string $version
         * @return bool
         */
        public function removePackageVersion(string $package, string $version): bool
        {
            Console::outVerbose(sprintf('Removing package %s version %s from package lock file', $package, $version));

            if(isset($this->Packages[$package]))
            {
                $r = $this->Packages[$package]->removeVersion($version);

                // Remove the entire package entry if there's no installed versions
                if($r && $this->Packages[$package]->getLatestVersion() === null)
                {
                    unset($this->Packages[$package]);
                }

                $this->update();
                return $r;
            }

            return false;
        }

        /**
         * Removes an entire package entry
         *
         * @param string $package
         * @return bool
         * @noinspection PhpUnused
         */
        public function removePackage(string $package): bool
        {
            Console::outVerbose(sprintf('Removing package %s from package lock file', $package));
            if(isset($this->Packages[$package]))
            {
                unset($this->Packages[$package]);
                $this->update();
                return true;
            }

            return false;
        }

        /**
         * Gets an existing package entry, returns null if no such entry exists
         *
         * @param string $package
         * @return PackageEntry|null
         */
        public function getPackage(string $package): ?PackageEntry
        {
            Console::outDebug(sprintf('getting package %s from package lock file', $package));
            return $this->Packages[$package] ?? null;
        }

        /**
         * Determines if the requested package exists in the package lock
         *
         * @param string $package
         * @param string|null $version
         * @return bool
         */
        public function packageExists(string $package, ?string $version=null): bool
        {
            $package_entry = $this->getPackage($package);

            if($package_entry === null)
            {
                return false;
            }

            if($version !== null)
            {
                try
                {
                    $version_entry = $package_entry->getVersion($version);
                }
                catch (IOException $e)
                {
                    unset($e);
                    return false;
                }

                if($version_entry === null)
                {
                    return false;
                }
            }

            return true;
        }

        /**
         * Returns an array of all packages and their installed versions
         *
         * @return array
         */
        public function getPackages(): array
        {
            $results = [];

            foreach($this->Packages as $package => $entry)
            {
                $results[$package] = $entry->getVersions();
            }

            return $results;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $package_entries = [];
            foreach($this->Packages as $entry)
            {
                $package_entries[] = $entry->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('package_lock_version')  : 'package_lock_version') => $this->PackageLockVersion,
                ($bytecode ? Functions::cbc('last_updated_timestamp') : 'last_updated_timestamp') => $this->LastUpdatedTimestamp,
                ($bytecode ? Functions::cbc('packages') : 'packages') => $package_entries
             ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $packages = Functions::array_bc($data, 'packages');
            if($packages !== null)
            {
                foreach($packages as $_datum)
                {
                    $entry = PackageEntry::fromArray($_datum);
                    $object->Packages[$entry->Name] = $entry;
                }
            }

            $object->PackageLockVersion = Functions::array_bc($data, 'package_lock_version');
            $object->LastUpdatedTimestamp = Functions::array_bc($data, 'last_updated_timestamp');

            return $object;
        }
    }