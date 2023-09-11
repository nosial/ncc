<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Managers;

    use Exception;
    use ncc\Classes\PackageReader;
    use ncc\Enums\FileDescriptor;
    use ncc\Enums\Options\ComponentDecodeOptions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Objects\PackageLock;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;

    class PackageManager
    {
        /**
         * @var PackageLock
         */
        private $package_lock;

        /**
         * PackageManager constructor.
         *
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function __construct()
        {
            if(file_exists(PathFinder::getPackageLock()))
            {
                $this->package_lock = PackageLock::fromArray(ZiProto::decode(IO::fread(PathFinder::getPackageLock())));
            }
            else
            {
                $this->package_lock = new PackageLock();
            }
        }

        /**
         * Installs a package from an ncc package file
         *
         * @param string $file_path
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function installPackage(string $file_path): void
        {
            $package_reader = new PackageReader($file_path);

            if($this->package_lock->entryExists($package_reader->getAssembly()->getPackage()))
            {
                $package_entry = $this->package_lock->getEntry($package_reader->getAssembly()->getPackage());

                if($package_entry->versionExists($package_reader->getAssembly()->getVersion()))
                {
                    throw new ConfigurationException(sprintf(
                        'Package "%s" version "%s" is already installed',
                        $package_reader->getAssembly()->getPackage(),
                        $package_reader->getAssembly()->getVersion()
                    ));
                }
            }

            $filesystem = new Filesystem();
            $package_path = PathFinder::getPackagesPath() . DIRECTORY_SEPARATOR . sprintf(
                '%s=%s', $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()
            );

            try
            {
                if($filesystem->exists($package_path))
                {
                    $filesystem->remove($package_path);
                }

                $this->extractPackageContents($package_reader, $package_path);
            }
            catch(Exception $e)
            {
                $filesystem->remove($package_path);
                unset($package_reader);
                throw new IOException(sprintf('Failed to extract package contents due to an exception: %s', $e->getMessage()), $e);
            }

            try
            {
                $this->package_lock->addPackage($package_reader);
            }
            catch(Exception $e)
            {
                $filesystem->remove($package_path);
                $this->loadLock();
                unset($package_reader);
                throw new IOException(sprintf('Failed to add package to package lock file due to an exception: %s', $e->getMessage()), $e);
            }

            $this->saveLock();
        }

        /**
         * Returns the package lock object
         *
         * @return PackageLock
         */
        public function getPackageLock(): PackageLock
        {
            return $this->package_lock;
        }

        /**
         * Extracts the contents of a package to the specified path
         *
         * @param PackageReader $package_reader
         * @param string $package_path
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         */
        private function extractPackageContents(PackageReader $package_reader, string $package_path): void
        {
            $bin_path = $package_path . DIRECTORY_SEPARATOR . 'bin';

            foreach($package_reader->getComponents() as $component_name)
            {
                IO::fwrite(
                    $bin_path . DIRECTORY_SEPARATOR . $component_name,
                    $package_reader->getComponent($component_name)->getData([ComponentDecodeOptions::AS_FILE]), 0755
                );
            }

            foreach($package_reader->getResources() as $resource_name)
            {
                IO::fwrite(
                    $bin_path . DIRECTORY_SEPARATOR . $resource_name,
                    $package_reader->getResource($resource_name)->getData(), 0755
                );
            }

            foreach($package_reader->getExecutionUnits() as $unit)
            {
                $execution_unit = $package_reader->getExecutionUnit($unit);
                $unit_path = $package_path . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $execution_unit->getExecutionPolicy()->getName() . '.unit';
                IO::fwrite($unit_path, ZiProto::encode($execution_unit->toArray(true)), 0755);
            }

            $class_map = [];
            foreach($package_reader->getClassMap() as $class)
            {
                $class_map[$class] = $package_reader->getComponentByClass($class)->getName();
            }

            if($package_reader->getInstaller() !== null)
            {
                IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::INSTALLER, ZiProto::encode($package_reader->getInstaller()?->toArray(true)));
            }

            if(count($class_map) > 0)
            {
                IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::CLASS_MAP, ZiProto::encode($class_map));
            }

            IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::ASSEMBLY, ZiProto::encode($package_reader->getAssembly()->toArray(true)));
            IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::METADATA, ZiProto::encode($package_reader->getMetadata()->toArray(true)));
        }

        /**
         * Reloads the package lock file from disk
         *
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        private function loadLock(): void
        {
            if(file_exists(PathFinder::getPackageLock()))
            {
                $this->package_lock = PackageLock::fromArray(ZiProto::decode(IO::fread(PathFinder::getPackageLock())));
            }
            else
            {
                $this->package_lock = new PackageLock();
            }
        }

        /**
         * Saves the package lock file to disk
         *
         * @return void
         * @throws IOException
         */
        private function saveLock(): void
        {
            IO::fwrite(PathFinder::getPackageLock(), ZiProto::encode($this->package_lock->toArray(true)));
        }
    }