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
    use ncc\Exceptions\VersionNotFoundException;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration\UpdateSource;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Functions;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;

    class PackageEntry
    {
        /**
         * The name of the package that's installed
         *
         * @var string
         */
        public $Name;

        /**
         * The latest version of the package entry, this is updated automatically
         *
         * @var string|null
         */
        private $LatestVersion;

        /**
         * An array of installed versions for this package
         *
         * @var VersionEntry[]
         */
        public $Versions;

        /**
         * The update source of the package entry
         *
         * @var UpdateSource|null
         */
        public $UpdateSource;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Versions = [];
        }

        /**
         * Searches and returns a version of the package
         *
         * @param string $version
         * @param bool $throw_exception
         * @return VersionEntry|null
         * @throws VersionNotFoundException
         */
        public function getVersion(string $version, bool $throw_exception=false): ?VersionEntry
        {
            if($version === Versions::LATEST && $this->LatestVersion !== null)
            {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $version = $this->LatestVersion;
            }

            foreach($this->Versions as $versionEntry)
            {
                if($versionEntry->Version === $version)
                {
                    return $versionEntry;
                }
            }

            if($throw_exception)
            {
                throw new VersionNotFoundException('The version entry is not found');
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

            foreach($this->Versions as $versionEntry)
            {
                if($versionEntry->Version === $version)
                {
                    $found_node = true;
                    break;
                }

                ++$count;
            }

            if($found_node)
            {
                unset($this->Versions[$count]);
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
                if ($this->getVersion($package->assembly->version) !== null)
                {
                    if(!$overwrite)
                    {
                        return false;
                    }

                    $this->removeVersion($package->assembly->version);
                }
            }
            catch (VersionNotFoundException $e)
            {
                unset($e);
            }

            $version = new VersionEntry();
            $version->Version = $package->assembly->version;
            $version->Compiler = $package->header->CompilerExtension;
            $version->ExecutionUnits = $package->execution_units;
            $version->MainExecutionPolicy = $package->main_execution_policy;
            $version->Location = $install_path;

            foreach($version->ExecutionUnits as $unit)
            {
                $unit->Data = null;
            }

            foreach($package->dependencies as $dependency)
            {
                $version->Dependencies[] = new DependencyEntry($dependency);
            }

            $this->Versions[] = $version;
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

            foreach($this->Versions as $version)
            {
                $version = $version->Version;

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

            $this->LatestVersion = $latest_version;
        }

        /**
         * @return string|null
         */
        public function getLatestVersion(): ?string
        {
            return $this->LatestVersion;
        }

        /**
         * Returns an array of all versions installed
         *
         * @return array
         */
        public function getVersions(): array
        {
            $r = [];

            foreach($this->Versions as $version)
            {
                $r[] = $version->Version;
            }

            return $r;
        }

        /**
         * @return string
         * @throws ConfigurationException
         */
        public function getDataPath(): string
        {
            $path = PathFinder::getPackageDataPath($this->Name);

            if(!file_exists($path) && Resolver::resolveScope() === Scopes::SYSTEM)
            {
                $filesystem = new Filesystem();
                $filesystem->mkdir($path);
            }

            return $path;
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

            foreach($this->Versions as $version)
            {
                $versions[] = $version->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('name')  : 'name')  => $this->Name,
                ($bytecode ? Functions::cbc('latest_version')  : 'latest_version')  => $this->LatestVersion,
                ($bytecode ? Functions::cbc('versions')  : 'versions')  => $versions,
                ($bytecode ? Functions::cbc('update_source')  : 'update_source')  => ($this->UpdateSource?->toArray($bytecode)),
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return PackageEntry
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Name = Functions::array_bc($data, 'name');
            $object->LatestVersion = Functions::array_bc($data, 'latest_version');
            $object->UpdateSource = Functions::array_bc($data, 'update_source');
            $versions = Functions::array_bc($data, 'versions');

            if($object->UpdateSource !== null)
            {
                $object->UpdateSource = UpdateSource::fromArray($object->UpdateSource);
            }

            if($versions !== null)
            {
                foreach($versions as $_datum)
                {
                    $object->Versions[] = VersionEntry::fromArray($_datum);
                }
            }

            return $object;
        }

    }