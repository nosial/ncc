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

    namespace ncc\Objects\PackageLock;

    use InvalidArgumentException;
    use ncc\Classes\PackageReader;
    use ncc\Enums\FileDescriptor;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Objects\ProjectConfiguration\UpdateSource;
    use ncc\ThirdParty\composer\Semver\Semver;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;

    class PackageEntry implements BytecodeObjectInterface
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var VersionEntry[]
         */
        private $versions;

        /**
         * @var UpdateSource|null
         */
        private $update_source;

        /**
         * @var bool
         */
        private $symlink_registered;

        /**
         * Public Constructor
         */
        public function __construct(string $name, array $versions=[])
        {
            $this->name = $name;
            $this->versions = $versions;
            $this->symlink_registered = false;
        }

        /**
         * Returns the name of the package entry
         *
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Optional. Returns the update source of the package entry
         *
         * @return UpdateSource|null
         */
        public function getUpdateSource(): ?UpdateSource
        {
            return $this->update_source;
        }

        /**
         * @return bool
         */
        public function isSymlinkRegistered(): bool
        {
            return $this->symlink_registered;
        }

        /**
         * @param bool $symlink_registered
         */
        public function setSymlinkRegistered(bool $symlink_registered): void
        {
            $this->symlink_registered = $symlink_registered;
        }

        /**
         * Returns the path to where the package is installed
         *
         * @param string $version
         * @return string
         */
        public function getPath(string $version): string
        {
            return $this->getVersion($version)->getPath($this->name);
        }

        /**
         * Returns the path to where the shadow package is located
         *
         * @param string $version
         * @return string
         */
        public function getShadowPackagePath(string $version): string
        {
            return $this->getVersion($version)->getShadowPackagePath($this->name);
        }

        /**
         * Adds a new version entry to the package, if overwrite is true then
         * the entry will be overwritten if it exists, otherwise it will return
         * false.
         *
         * @param PackageReader $package_reader
         * @param bool $overwrite
         * @return bool
         * @throws ConfigurationException
         */
        public function addVersion(PackageReader $package_reader, bool $overwrite=false): bool
        {
            if($this->versionExists($package_reader->getAssembly()->getVersion()))
            {
                if(!$overwrite)
                {
                    return false;
                }

                $this->removeVersion($package_reader->getAssembly()->getVersion());
            }

            $version_entry = new VersionEntry($package_reader->getAssembly()->getVersion());
            $version_entry->setMainExecutionPolicy($package_reader->getMetadata()->getMainExecutionPolicy());

            foreach($package_reader->getExecutionUnits() as $unit)
            {
                $version_entry->addExecutionPolicy($package_reader->getExecutionUnit($unit)->getExecutionPolicy());
            }

            foreach($package_reader->getDependencies() as $dependency)
            {
                $version_entry->addDependency($package_reader->getDependency($dependency));
            }

            $this->versions[] = $version_entry;
            $this->update_source = $package_reader->getMetadata()->getUpdateSource();

            return true;
        }

        /**
         * Returns True if the given version exists in the entry
         *
         * @param string $version
         * @return bool
         */
        public function versionExists(string $version): bool
        {
            if($version === Versions::LATEST)
            {
                try
                {
                    $version = $this->getLatestVersion();
                }
                catch(InvalidArgumentException $e)
                {
                    return false;
                }
            }

            foreach($this->versions as $version_entry)
            {
                if(false === stripos($version, "-dev") && false !== stripos($version_entry->getVersion(), "-dev"))
                {
                    continue;
                }

                if(Semver::satisfies($version_entry->getVersion(), $version))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Returns the version that satisfies the given version constraint
         *
         * @param string $version
         * @return string
         */
        public function getSatisfyingVersion(string $version): string
        {
            if($version === Versions::LATEST)
            {
                $version = $this->getLatestVersion();
            }

            foreach($this->versions as $version_entry)
            {
                if(false === stripos($version, "-dev") && false !== stripos($version_entry->getVersion(), "-dev"))
                {
                    continue;
                }

                if(Semver::satisfies($version_entry->getVersion(), $version))
                {
                    return $version_entry->getVersion();
                }
            }

            throw new InvalidArgumentException(sprintf('Version %s does not exist in package %s', $version, $this->name));
        }

        /**
         * Returns a version entry by version
         *
         * @param string $version
         * @return VersionEntry
         */
        public function getVersion(string $version): VersionEntry
        {
            if($version === Versions::LATEST)
            {
                $version = $this->getLatestVersion();
            }

            foreach($this->versions as $version_entry)
            {
                if($version_entry->getVersion() === $version)
                {
                    return $version_entry;
                }
            }

            throw new InvalidArgumentException(sprintf('Version %s does not exist in package %s', $version, $this->name));
        }

        /**
         * Updates and returns the latest version of this package entry
         *
         * @return string
         */
        private function getLatestVersion(): string
        {
            $latest_version = null;

            foreach($this->versions as $version)
            {
                $version = $version->getVersion();

                if($latest_version === null)
                {
                    $latest_version = $version;
                    continue;
                }

                if(VersionComparator::compareVersion($version, $latest_version))
                {
                    $latest_version = $version;
                }
            }

            if($latest_version === null)
            {
                throw new InvalidArgumentException(sprintf('Package %s does not have any versions', $this->name));
            }

            return $latest_version;
        }

        /**
         * Returns an array of all versions installed
         *
         * @return string[]
         */
        public function getVersions(): array
        {
            return array_map(static function(VersionEntry $version_entry) {
                return $version_entry->getVersion();
            }, $this->versions);
        }

        /**
         * Removes version entry from the package entry
         *
         * @param string $version
         * @return bool
         */
        public function removeVersion(string $version): bool
        {
            $count = 0;
            $found_node = false;

            foreach($this->versions as $version_entry)
            {
                if($version_entry->getVersion() === $version)
                {
                    $found_node = true;
                    break;
                }

                ++$count;
            }

            if($found_node)
            {
                unset($this->versions[$count]);
                return true;
            }

            return false;
        }

        /**
         * Returns the assembly of the package entry
         *
         * @param string $version
         * @return Assembly
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getAssembly(string $version=Versions::LATEST): Assembly
        {
            $assembly_path = $this->getPath($version) . DIRECTORY_SEPARATOR . FileDescriptor::ASSEMBLY;
            if(!is_file($assembly_path))
            {
                throw new IOException(sprintf('Assembly file for package %s version %s does not exist (Expected %s)', $this->name, $version, $assembly_path));
            }

            return Assembly::fromArray(ZiProto::decode(IO::fread($assembly_path)));
        }

        /**
         * Returns the metadata of the package entry
         *
         * @param string $version
         * @return Metadata
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         * @throws NotSupportedException
         */
        public function getMetadata(string $version=Versions::LATEST): Metadata
        {
            $metadata_path = $this->getPath($version) . DIRECTORY_SEPARATOR . FileDescriptor::METADATA;
            if(!is_file($metadata_path))
            {
                throw new IOException(sprintf('Metadata file for package %s version %s does not exist (Expected %s)', $this->name, $version, $metadata_path));
            }

            return Metadata::fromArray(ZiProto::decode(IO::fread($metadata_path)));
        }

        /**
         * Optional. Returns the installer details of the package entry
         *
         * @param string $version
         * @return Installer|null
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getInstaller(string $version=Versions::LATEST): ?Installer
        {
            $installer_path = $this->getPath($version) . DIRECTORY_SEPARATOR . FileDescriptor::INSTALLER;
            if(!is_file($installer_path))
            {
                return null;
            }

            return Installer::fromArray(ZiProto::decode(IO::fread($installer_path)));
        }

        /**
         * Returns the class map of the package entry
         *
         * @param string $version
         * @return array
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getClassMap(string $version=Versions::LATEST): array
        {
            $class_map_path = $this->getPath($version) . DIRECTORY_SEPARATOR . FileDescriptor::CLASS_MAP;
            if(!is_file($class_map_path))
            {
                return [];
            }

            return ZiProto::decode(IO::fread($class_map_path));
        }

        /**
         * Returns the execution policy of the package entry
         *
         * @param string $policy_name
         * @param string $version
         * @return ExecutionPolicy
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getExecutionPolicy(string $policy_name, string $version=Versions::LATEST): ExecutionPolicy
        {
            $execution_policy_path = $this->getPath($version) . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $policy_name . '.policy';
            if(!is_file($execution_policy_path))
            {
                throw new IOException(sprintf('Execution policy %s for package %s version %s does not exist (Expected %s)', $policy_name, $this->name, $version, $execution_policy_path));
            }

            return ExecutionPolicy::fromArray(ZiProto::decode(IO::fread($execution_policy_path)));
        }

        /**
         * Returns the path to the execution binary of the package entry of a given policy name
         *
         * @param string $policy_name
         * @param string $version
         * @return string
         * @throws IOException
         */
        public function getExecutionBinaryPath(string $policy_name, string $version=Versions::LATEST): string
        {
            $execution_binary_path = $this->getPath($version) . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $policy_name;
            if(!is_file($execution_binary_path))
            {
                throw new IOException(sprintf('Execution binary %s for package %s version %s does not exist (Expected %s)', $policy_name, $this->name, $version, $execution_binary_path));
            }

            return $execution_binary_path;
        }

        /**
         * Returns an array of all broken versions
         *
         * @return array
         */
        public function getBrokenVersions(): array
        {
            $broken_versions = [];

            foreach($this->versions as $version)
            {
                if($version->isBroken($this->name))
                {
                    $broken_versions[] = $version->getVersion();
                }
            }

            return $broken_versions;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $versions = [];

            foreach($this->versions as $version)
            {
                $versions[] = $version->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('name')  : 'name')  => $this->name,
                ($bytecode ? Functions::cbc('versions')  : 'versions')  => $versions,
                ($bytecode ? Functions::cbc('update_source')  : 'update_source')  => ($this->update_source?->toArray($bytecode)),
                ($bytecode ? Functions::cbc('symlink_registered')  : 'symlink_registered')  => $this->symlink_registered,
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return PackageEntry
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): PackageEntry
        {
            $name = Functions::array_bc($data, 'name');
            $raw_versions = Functions::array_bc($data, 'versions') ?? [];
            $versions = [];

            if($name === null)
            {
                throw new ConfigurationException('PackageEntry is missing name');
            }

            foreach($raw_versions as $raw_version)
            {
                $versions[] = VersionEntry::fromArray($raw_version);
            }

            $object = new self($name, $versions);
            $update_source = Functions::array_bc($data, 'update_source');

            if($update_source !== null)
            {
                $object->update_source = UpdateSource::fromArray($update_source);
            }

            $object->symlink_registered = Functions::array_bc($data, 'symlink_registered') ?? false;
            return $object;
        }

    }