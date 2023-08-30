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

    use ncc\Enums\Scopes;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration\UpdateSource;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Functions;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;

    class PackageEntry implements BytecodeObjectInterface
    {
        /**
         * The name of the package that's installed
         *
         * @var string
         */
        private $name;

        /**
         * The latest version of the package entry, this is updated automatically
         *
         * @var string|null
         */
        private $latest_version;

        /**
         * An array of installed versions for this package
         *
         * @var VersionEntry[]
         */
        private $versions;

        /**
         * The update source of the package entry
         *
         * @var UpdateSource|null
         */
        private $update_source;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->versions = [];
        }

        /**
         * Searches and returns a version of the package
         *
         * @param string $version
         * @param bool $throw_exception
         * @return VersionEntry|null
         * @throws IOException
         */
        public function getVersion(string $version, bool $throw_exception=false): ?VersionEntry
        {
            if($version === Versions::LATEST && $this->latest_version !== null)
            {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $version = $this->latest_version;
            }

            foreach($this->versions as $versionEntry)
            {
                if($versionEntry->getVersion() === $version)
                {
                    return $versionEntry;
                }
            }

            if($throw_exception)
            {
                throw new IOException(sprintf('Version %s of %s is not installed', $version, $this->name));
            }

            return null;
        }

        /**
         * Removes version entry from the package
         *
         * @param string $version
         * @return bool
         * @noinspection PhpUnused
         */
        public function removeVersion(string $version): bool
        {
            $count = 0;
            $found_node = false;

            foreach($this->versions as $versionEntry)
            {
                if($versionEntry->getVersion() === $version)
                {
                    $found_node = true;
                    break;
                }

                ++$count;
            }

            if($found_node)
            {
                unset($this->versions[$count]);
                $this->updateLatestVersion();
                return true;
            }

            return false;
        }

        /**
         * Adds a new version entry to the package, if overwrite is true then
         * the entry will be overwritten if it exists, otherwise it will return
         * false.
         *
         * @param Package $package
         * @param string $install_path
         * @param bool $overwrite
         * @return bool
         */
        public function addVersion(Package $package, string $install_path, bool $overwrite=false): bool
        {
            try
            {
                if ($this->getVersion($package->getAssembly()->getVersion()) !== null)
                {
                    if(!$overwrite)
                    {
                        return false;
                    }

                    $this->removeVersion($package->getAssembly()->getVersion());
                }
            }
            catch (IOException $e)
            {
                unset($e);
            }

            $version = new VersionEntry();
            $version->setVersion($package->getAssembly()->getVersion());
            $version->setCompiler($package->getHeader()->getCompilerExtension());
            $version->setExecutionUnits($package->getExecutionUnits());
            $version->main_execution_policy = $package->getMainExecutionPolicy();
            $version->location = $install_path;

            foreach($version->getExecutionUnits() as $unit)
            {
                $unit->setData(null);
            }

            foreach($package->getDependencies() as $dependency)
            {
                $version->addDependency(new DependencyEntry($dependency));
            }

            $this->versions[] = $version;
            $this->updateLatestVersion();
            return true;
        }

        /**
         * Updates and returns the latest version of this package entry
         *
         * @return void
         */
        private function updateLatestVersion(): void
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

            $this->latest_version = $latest_version;
        }

        /**
         * @return string|null
         */
        public function getLatestVersion(): ?string
        {
            return $this->latest_version;
        }

        /**
         * Returns an array of all versions installed
         *
         * @return array
         */
        public function getVersions(): array
        {
            $r = [];

            foreach($this->versions as $version)
            {
                $r[] = $version->getVersion();
            }

            return $r;

        }

        /**
         * @return string
         * @throws ConfigurationException
         */
        public function getDataPath(): string
        {
            $path = PathFinder::getPackageDataPath($this->name);

            if(!file_exists($path) && Resolver::resolveScope() === Scopes::SYSTEM)
            {
                $filesystem = new Filesystem();
                $filesystem->mkdir($path);
            }

            return $path;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return UpdateSource|null
         */
        public function getUpdateSource(): ?UpdateSource
        {
            return $this->update_source;
        }

        /**
         * @param UpdateSource|null $update_source
         */
        public function setUpdateSource(?UpdateSource $update_source): void
        {
            $this->update_source = $update_source;
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
                ($bytecode ? Functions::cbc('latest_version')  : 'latest_version')  => $this->latest_version,
                ($bytecode ? Functions::cbc('versions')  : 'versions')  => $versions,
                ($bytecode ? Functions::cbc('update_source')  : 'update_source')  => ($this->update_source?->toArray($bytecode)),
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return PackageEntry
         */
        public static function fromArray(array $data): PackageEntry
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->latest_version = Functions::array_bc($data, 'latest_version');
            $object->update_source = Functions::array_bc($data, 'update_source');
            $versions = Functions::array_bc($data, 'versions');

            if($object->update_source !== null)
            {
                $object->update_source = UpdateSource::fromArray($object->update_source);
            }

            if($versions !== null)
            {
                foreach($versions as $_datum)
                {
                    $object->versions[] = VersionEntry::fromArray($_datum);
                }
            }

            return $object;
        }

    }