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

    use InvalidArgumentException;
    use ncc\Classes\PackageReader;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\PackageLock\PackageEntry;
    use ncc\Objects\PackageLock\VersionEntry;
    use ncc\Utilities\Functions;

    class PackageLock implements BytecodeObjectInterface
    {
        private const string PACKAGE_LOCK_VERSION = '2.0.0';

        /**
         * The version of package lock file structure
         *
         * @var Versions
         */
        private $package_lock_version;

        /**
         * The Unix Timestamp for when this package lock file was last updated
         *
         * @var int
         */
        private $last_updated_timestamp;

        /**
         * An array of installed packages in the PackageLock file
         *
         * @var PackageEntry[]
         */
        private $entries;

        /**
         * Public Constructor
         */
        public function __construct(array $entries=[], ?int $last_updated_timestamp=null)
        {
            $this->entries = $entries;
            $this->package_lock_version = self::PACKAGE_LOCK_VERSION;
            $this->last_updated_timestamp = $last_updated_timestamp ? null : time();
        }

        /**
         * Returns the package lock structure version
         *
         * @return string
         */
        public function getPackageLockVersion(): string
        {
            return $this->package_lock_version;
        }

        /**
         * Returns the Unix Timestamp for when this package lock file was last updated
         *
         * @return int
         */
        public function getLastUpdatedTimestamp(): int
        {
            return $this->last_updated_timestamp;
        }

        /**
         * Updates the version and timestamp
         *
         * @return void
         */
        private function update(): void
        {
            $this->package_lock_version = self::PACKAGE_LOCK_VERSION;
            $this->last_updated_timestamp = time();
        }

        /**
         * Adds a new PackageEntry to the PackageLock file from a PackageReader
         *
         * @param PackageReader $package_reader
         * @return void
         * @throws ConfigurationException
         */
        public function addPackage(PackageReader $package_reader): void
        {
            if(!$this->entryExists($package_reader->getAssembly()->getPackage()))
            {
                $package_entry = new PackageEntry($package_reader->getAssembly()->getPackage());
                $package_entry->addVersion($package_reader);
                $this->addEntry($package_entry);

                return;
            }

            $package_entry = $this->getEntry($package_reader->getAssembly()->getPackage());
            $package_entry->addVersion($package_reader);
            $this->addEntry($package_entry);
        }

        /**
         * Returns True if the package entry exists
         *
         * @param string $package_name
         * @param string|null $version
         * @return bool
         */
        public function entryExists(string $package_name, ?string $version=null): bool
        {
            if($version === null)
            {
                foreach($this->entries as $entry)
                {
                    if($entry->getName() === $package_name)
                    {
                        return true;
                    }
                }

                return false;
            }

            foreach($this->entries as $entry)
            {
                if($entry->getName() === $package_name && $entry->versionExists($version))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Returns an existing package entry
         *
         * @param string $package_name
         * @return PackageEntry
         */
        public function getEntry(string $package_name): PackageEntry
        {
            foreach($this->entries as $entry)
            {
                if($entry->getName() === $package_name)
                {
                    return $entry;
                }
            }

            throw new InvalidArgumentException(sprintf('Package entry %s does not exist', $package_name));
        }

        /**
         * Returns a version entry from a package entry
         *
         * @param string $package_name
         * @param string $version
         * @return VersionEntry
         */
        public function getVersionEntry(string $package_name, string $version): VersionEntry
        {
            return $this->getEntry($package_name)->getVersion($version);
        }

        /**
         * Adds a new package entry to the package lock file
         *
         * @param PackageEntry $entry
         * @param bool $overwrite
         * @return void
         */
        public function addEntry(PackageEntry $entry, bool $overwrite=true): void
        {
            if($this->entryExists($entry->getName()))
            {
                if(!$overwrite)
                {
                    return;
                }

                $this->removeEntry($entry->getName());
            }

            $this->update();
            $this->entries[] = $entry;
        }

        /**
         * Removes a package entry from the package lock file
         *
         * @param string $package_name
         * @return void
         */
        public function removeEntry(string $package_name): void
        {
            foreach($this->entries as $index => $entry)
            {
                if($entry->getName() === $package_name)
                {
                    unset($this->entries[$index]);
                    $this->update();
                    return;
                }
            }
        }

        /**
         * Returns an array of package entries
         *
         * @return string[]
         */
        public function getEntries(): array
        {
            return array_map(static function(PackageEntry $entry) {
                return $entry->getName();
            }, $this->entries);
        }

        /**
         * Returns the path where the package is installed
         *
         * @param string $package_name
         * @param string $version
         * @return string
         */
        public function getPath(string $package_name, string $version): string
        {
            return $this->getEntry($package_name)->getPath($version);
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $package_entries = [];
            foreach($this->entries as $entry)
            {
                $package_entries[] = $entry->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('package_lock_version')  : 'package_lock_version') => $this->package_lock_version,
                ($bytecode ? Functions::cbc('last_updated_timestamp') : 'last_updated_timestamp') => $this->last_updated_timestamp,
                ($bytecode ? Functions::cbc('entries') : 'entries') => $package_entries
             ];
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): self
        {
            $entries_array = Functions::array_bc($data, 'entries') ?? [];
            $entries = array_map(static function($entry) {
                return PackageEntry::fromArray($entry);
            }, $entries_array);


            $package_lock_version = Functions::array_bc($data, 'package_lock_version') ?? self::PACKAGE_LOCK_VERSION;
            $last_updated_timestamp = Functions::array_bc($data, 'last_updated_timestamp') ?? time();

            if($package_lock_version === null)
            {
                throw new ConfigurationException('Package lock version is missing');
            }

            return new self($entries, $last_updated_timestamp);
        }
    }