<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use ncc\Abstracts\Versions;
    use ncc\Objects\PackageLock\PackageEntry;
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
            $this->PackageLockVersion = Versions::PackageLockVersion;
            $this->Packages = [];
        }

        /**
         * Updates the version and timestamp
         *
         * @return void
         */
        private function update(): void
        {
            $this->PackageLockVersion = Versions::PackageLockVersion;
            $this->LastUpdatedTimestamp = time();
        }

        /**
         * @param Package $package
         * @param string $install_path
         * @return void
         */
        public function addPackage(Package $package, string $install_path): void
        {
            if(!isset($this->Packages[$package->Assembly->Package]))
            {
                $package_entry = new PackageEntry();
                $package_entry->addVersion($package, $install_path, true);
                $package_entry->Name = $package->Assembly->Package;
                $this->Packages[$package->Assembly->Package] = $package_entry;
                $this->update();

                return;
            }

            $this->Packages[$package->Assembly->Package]->addVersion($package, true);
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
            if(isset($this->Packages[$package]))
            {
                $r = $this->Packages[$package]->removeVersion($version);

                // Remove the entire package entry if there's no installed versions
                if($this->Packages[$package]->getLatestVersion() == null && $r)
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
         */
        public function removePackage(string $package): bool
        {
            if(isset($this->Packages[$package]))
            {
                unset($this->Packages[$package]);
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
            if(isset($this->Packages[$package]))
            {
                return $this->Packages[$package];
            }

            return null;
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
                $results[$package] = $entry->getVersions();
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