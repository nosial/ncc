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
    use InvalidArgumentException;
    use ncc\Classes\ArchiveExtractor;
    use ncc\Classes\PackageReader;
    use ncc\Classes\ShutdownHandler;
    use ncc\CLI\Main;
    use ncc\Enums\FileDescriptor;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\Options\ComponentDecodeOptions;
    use ncc\Enums\Options\InitializeProjectOptions;
    use ncc\Enums\Options\InstallPackageOptions;
    use ncc\Enums\Options\ProjectOptions;
    use ncc\Enums\RegexPatterns;
    use ncc\Enums\Scopes;
    use ncc\Enums\Types\ProjectType;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Interfaces\AuthenticationInterface;
    use ncc\Objects\PackageLock;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\RemotePackageInput;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Console;
    use ncc\Utilities\ConsoleProgressBar;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;

    class PackageManager
    {
        /**
         * @var PackageLock
         */
        private $package_lock;

        /**
         * @var RepositoryManager
         */
        private $repository_manager;

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

            $this->repository_manager = new RepositoryManager();
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
         * Returns an array of dependencies that are required by the given package
         * based the system's current package lock file
         *
         * @param PackageReader $package_reader
         * @return Dependency[]
         * @throws ConfigurationException
         */
        public function checkRequiredDependencies(PackageReader $package_reader): array
        {
            $missing_dependencies = [];

            foreach($package_reader->getDependencies() as $dependency)
            {
                $dependency = $package_reader->getDependency($dependency);

                if(!$this->package_lock->entryExists($dependency->getName()))
                {
                    $missing_dependencies[] = $dependency;
                    continue;
                }

                $package_entry = $this->package_lock->getEntry($dependency->getName());
                if(!$package_entry->versionExists($dependency->getVersion()))
                {
                    $missing_dependencies[] = $dependency;
                }
            }

            return $missing_dependencies;
        }

        /**
         * Installs a package from the given input
         *
         * @param string|PackageReader $input
         * @param AuthenticationInterface|null $authentication
         * @param array $options
         * @return array
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public function install(string|PackageReader $input, ?AuthenticationInterface $authentication=null, array $options=[]): array
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to install packages');
            }

            // If the input is a PackageReader, we can install it directly
            if($input instanceof PackageReader)
            {
                return $this->installPackage($input, $authentication, $options);
            }

            // If the input is a file, we can assume it's a local package file
            if(is_file($input))
            {
                return $this->installPackage(new PackageReader($input), $authentication, $options);
            }

            // If the input is a remote package, we can assume it's a remote package input
            if(preg_match(RegexPatterns::REMOTE_PACKAGE->value, $input) === 1)
            {
                return $this->installRemotePackage(RemotePackageInput::fromString($input), $authentication, $options);
            }

            throw new InvalidArgumentException(sprintf('Invalid package input, expected a PackageReader stream, a file path, or a remote package input, got %s', gettype($input)));
        }

        /**
         * Uninstalls a package from the system, returns an array of removed packages
         *
         * @param string $package_name
         * @param string|null $version
         * @return array
         * @throws IOException
         * @throws OperationException
         */
        public function uninstall(string $package_name, ?string $version=null): array
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to uninstall packages');
            }

            if(!$this->package_lock->entryExists($package_name, $version))
            {
                throw new OperationException(sprintf('Cannot uninstall package %s, it is not installed', $package_name));
            }

            $removed_packages = [];

            if($version === null)
            {
                if($this->package_lock->getEntry($package_name)->isSymlinkRegistered())
                {
                    Console::outVerbose(sprintf(
                        'Removing symlink for %s=%s at %s',
                        $package_name, $this->package_lock->getEntry($package_name)->getVersion(Versions::LATEST->value)->getVersion(),
                        PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_name)
                    ));

                    try
                    {
                        $symlink_path = PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($this->package_lock->getEntry($package_name)->getAssembly()->getName());
                    }
                    catch(Exception $e)
                    {
                        throw new IOException(sprintf('Failed to resolve symlink for %s=%s: %s', $package_name, $this->package_lock->getEntry($package_name)->getVersion(Versions::LATEST->value)->getVersion(), $e->getMessage()), $e);
                    }

                    if(is_file($symlink_path))
                    {
                        (new Filesystem())->remove($symlink_path);
                        $this->package_lock->getEntry($package_name)->setSymlinkRegistered(false);
                    }
                }

                foreach($this->package_lock->getEntry($package_name)->getVersions() as $iter_version)
                {
                    Console::out(sprintf('Uninstalling package %s=%s', $package_name, $iter_version));
                    $package_path = $this->package_lock->getPath($package_name, $iter_version);
                    $this->package_lock->getEntry($package_name)->removeVersion($iter_version);
                    (new Filesystem())->remove($package_path);

                    $removed_packages[] = sprintf('%s=%s', $package_name, $iter_version);
                }

                $this->package_lock->removeEntry($package_name);
            }
            else
            {
                Console::out(sprintf('Uninstalling package %s=%s', $package_name, $version));
                $package_path = $this->package_lock->getPath($package_name, $version);

                if($this->package_lock->getEntry($package_name)->isSymlinkRegistered())
                {
                    Console::outVerbose(sprintf(
                        'Removing symlink for %s=%s at %s',
                        $package_name, $version, PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_name)
                    ));

                    try
                    {
                        $symlink_path = PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($this->package_lock->getEntry($package_name)->getAssembly($version)->getName());
                    }
                    catch(Exception $e)
                    {
                        Console::outWarning(sprintf('Failed to resolve symlink for %s=%s: %s', $package_name, $version, $e->getMessage()));
                    }

                    if(isset($symlink_path) && is_file($symlink_path))
                    {
                        (new Filesystem())->remove($symlink_path);
                        $this->package_lock->getEntry($package_name)->setSymlinkRegistered(false);
                    }
                }

                $this->package_lock->getEntry($package_name)->removeVersion($version);
                (new Filesystem())->remove($package_path);

                $removed_packages[] = sprintf('%s=%s', $package_name, $version);
            }

            $this->saveLock();
            return $removed_packages;
        }

        /**
         * Uninstalls all packages from the system, returns an array of removed packages
         *
         * @return array
         * @throws IOException
         * @throws OperationException
         */
        public function uninstallAll(): array
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to uninstall packages');
            }

            $results = [];
            foreach($this->package_lock->getEntries() as $entry)
            {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $results = array_merge($results, $this->uninstall($entry));
            }

            return $results;
        }

        /**
         * Returns an array of installed packages on the system
         *
         * @return array
         */
        public function getInstalledPackages(): array
        {
            $results = [];

            foreach($this->package_lock->getEntries() as $entry)
            {
                foreach($this->package_lock->getEntry($entry)->getVersions() as $version)
                {
                    $results[] = sprintf('%s=%s', $entry, $version);
                }
            }

            return $results;
        }

        /**
         * Returns an array of missing package dependencies detected on the system
         *
         * @return array
         */
        public function getMissingPackages(): array
        {
            $results = [];

            foreach($this->package_lock->getEntries() as $entry)
            {
                foreach($this->package_lock->getEntry($entry)->getVersions() as $version)
                {
                    foreach($this->package_lock->getEntry($entry)->getVersion($version)->getDependencies() as $dependency)
                    {
                        if($this->package_lock->entryExists($dependency->getName(), $dependency->getVersion()))
                        {
                            continue;
                        }

                        $dependency_entry = sprintf('%s=%s', $dependency->getName(), $dependency->getVersion());

                        if(isset($results[$dependency_entry]))
                        {
                            continue;
                        }

                        if($dependency->getSource() === null)
                        {
                            $results[$dependency_entry] = null;
                            continue;
                        }

                        $results[$dependency_entry] = $dependency->getSource();
                    }
                }
            }

            return $results;
        }

        /**
         * Returns an array of broken packages detected on the system
         *
         * @return array
         */
        public function getBrokenPackages(): array
        {
            $results = [];

            foreach($this->package_lock->getEntries() as $entry)
            {
                foreach($this->package_lock->getEntry($entry)->getBrokenVersions() as $version)
                {
                    if(in_array(sprintf('%s=%s', $entry, $version), $results, true))
                    {
                        continue;
                    }

                    $results[] = sprintf('%s=%s', $entry, $version);
                }
            }

            return $results;
        }

        /**
         * Installs a package onto the system from a local ncc package file
         *
         * @param PackageReader $package_reader
         * @param AuthenticationInterface|null $authentication
         * @param array $options
         * @return array
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        private function installPackage(PackageReader $package_reader, ?AuthenticationInterface $authentication=null, array $options=[]): array
        {
            $installed_packages = [];

            Console::out(sprintf('Installing package %s=%s', $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()));
            if($this->package_lock->entryExists($package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()))
            {
                if(!isset($options[InstallPackageOptions::REINSTALL]))
                {
                    Console::outVerbose(sprintf(
                        'Package %s=%s is already installed, skipping',
                        $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()
                    ));

                    return $installed_packages;
                }

                Console::outVerbose(sprintf('Package %s=%s is already installed, reinstalling',
                    $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()
                ));

                /** @noinspection UnusedFunctionResultInspection */
                $this->uninstall($package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion());
            }

            $filesystem = new Filesystem();
            $package_path = PathFinder::getPackagesPath() . DIRECTORY_SEPARATOR . sprintf(
                '%s=%s', $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()
            );

            try
            {
                $this->extractPackageContents($package_reader, $package_path);
            }
            catch(Exception $e)
            {
                throw new IOException(sprintf('Failed to extract package contents due to an exception: %s', $e->getMessage()), $e);
            }
            finally
            {
                ShutdownHandler::declareTemporaryPath($package_path);
            }

            try
            {
                $this->package_lock->addPackage($package_reader);
                $installed_packages[] = $package_reader->getAssembly()->getPackage();
            }
            catch(Exception $e)
            {
                $filesystem->remove($package_path);
                $this->loadLock();
                throw new IOException(sprintf('Failed to add package to package lock file due to an exception: %s', $e->getMessage()), $e);
            }

            if($package_reader->getMetadata()->getOption(ProjectOptions::CREATE_SYMLINK->value) === null)
            {
                // Remove the symlink if it exists
                if($this->package_lock->getEntry($package_reader->getAssembly()->getPackage())->isSymlinkRegistered())
                {
                    if(is_file(PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_reader->getAssembly()->getName())))
                    {
                        Console::outVerbose(sprintf(
                            'Removing symlink for %s=%s at %s',
                            $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion(),
                            PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_reader->getAssembly()->getName())
                        ));
                        $filesystem->remove(PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_reader->getAssembly()->getName()));
                    }
                }

                $this->package_lock->getEntry($package_reader->getAssembly()->getPackage())->setSymlinkRegistered(false);
            }
            else
            {
                // Register/Update the symlink
                $symlink = PathFinder::findBinPath() . DIRECTORY_SEPARATOR  . strtolower($package_reader->getAssembly()->getName());

                if(is_file($symlink) && !$this->package_lock->getEntry($package_reader->getAssembly()->getPackage())->isSymlinkRegistered())
                {
                    // Avoid overwriting already existing symlinks that are not handled by ncc
                    Console::outWarning(sprintf(
                        'A symlink already exists at %s, skipping symlink creation for %s=%s',
                        $symlink, $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()
                    ));
                }
                else
                {
                    Console::outVerbose(sprintf(
                        'Creating symlink for %s=%s at %s',
                        $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion(), $symlink
                    ));

                    if(is_file($symlink))
                    {
                        $filesystem->remove($symlink);
                    }

                    IO::fwrite($symlink, Functions::createExecutionPointer($package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()));
                    chmod($symlink, 0755); // Make it executable

                    $this->package_lock->getEntry($package_reader->getAssembly()->getPackage())->setSymlinkRegistered(true);
                }
            }

            $this->saveLock();

            if(!isset($options[InstallPackageOptions::SKIP_DEPENDENCIES]))
            {
                foreach($this->checkRequiredDependencies($package_reader) as $dependency)
                {
                    if($this->package_lock->entryExists($dependency->getName(), $dependency->getVersion()))
                    {
                        continue;
                    }

                    Console::outVerbose(sprintf(
                        'Package %s=%s requires %s=%s, installing dependency',
                        $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion(),
                        $dependency->getName(), $dependency->getVersion()
                    ));

                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $installed_packages = array_merge($installed_packages, $this->install($dependency->getSource(), $authentication));
                }
            }

            return $installed_packages;
        }

        /**
         * Installs a package from a remote repository
         *
         * @param RemotePackageInput $input
         * @param AuthenticationInterface|null $authentication
         * @param array $options
         * @return array
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        private function installRemotePackage(RemotePackageInput $input, ?AuthenticationInterface $authentication=null, array $options=[]): array
        {
            // Check if the repository exists, so we can fetch the package
            if(!$this->repository_manager->repositoryExists($input->getRepository()))
            {
                throw new OperationException(sprintf('Cannot install remote package %s, the repository %s does not exist on this system', $input, $input->getRepository()));
            }

            Console::out(sprintf('Fetching package %s/%s=%s from %s', $input->getVendor(), $input->getPackage(), $input->getVersion(), $input->getRepository()));

            try
            {
                Console::outVerbose(sprintf(
                    'Attempting to fetch a pre-built ncc package for %s=%s from %s',
                    $input->getPackage(), $input->getVersion(), $input->getRepository()
                ));

                // First try to fetch a pre-built package from the repository
                $results = $this->repository_manager->getRepository($input->getRepository())->fetchPackage(
                    $input->getVendor(), $input->getPackage(), $input->getVersion(), $authentication, $options
                );

                $package_path = $this->downloadFile($results->getUrl(), PathFinder::getCachePath());
            }
            catch(Exception $e)
            {
                Console::outVerbose(sprintf(
                    'Failed to fetch a pre-built ncc package for %s=%s from %s: %s',
                    $input->getPackage(), $input->getVersion(), $input->getRepository(), $e->getMessage()
                ));

                // Clean up the package file if it exists
                if(isset($package_path) && is_file($package_path))
                {
                    ShutdownHandler::declareTemporaryPath($package_path);
                }

                // This is a warning because we can still attempt to build from source
                unset($results, $package_path);
            }

            if(!isset($package_path))
            {
                // If the $package_path variable is not set, we failed to fetch a pre-built package,
                // So we'll try to obtain a source archive and build from source
                try
                {
                    Console::outVerbose(sprintf(
                        'Attempting to fetch a source archive for %s=%s from %s',
                        $input->getPackage(), $input->getVersion(), $input->getRepository()
                    ));

                    $results = $this->repository_manager->getRepository($input->getRepository())->fetchSourceArchive(
                        $input->getVendor(), $input->getPackage(), $input->getVersion(), $authentication, $options
                    );

                    $archive_path = $this->downloadFile($results->getUrl(), PathFinder::getCachePath());
                    $package_path = $this->buildFromSource($archive_path, [
                        InitializeProjectOptions::COMPOSER_PACKAGE_VERSION->value => $results->getVersion(),
                        InitializeProjectOptions::COMPOSER_REMOTE_SOURCE->value => $input->toString()
                    ]);
                }
                catch(Exception $e)
                {
                    if(isset($package_path) && is_file($package_path))
                    {
                        ShutdownHandler::declareTemporaryPath($package_path);
                        unset($package_path);
                    }

                    throw new OperationException(sprintf('Failed to fetch a pre-built ncc package for %s, and failed to build from source: %s', $input, $e->getMessage()), $e);
                }
                finally
                {
                    if(isset($archive_path) && is_file($archive_path))
                    {
                        ShutdownHandler::declareTemporaryPath($archive_path);
                        unset($archive_path);
                    }
                }
            }

            if(isset($package_path))
            {
                Console::outVerbose(sprintf(
                    'Successfully fetched a package for %s=%s from %s',
                    $input->getPackage(), $input->getVersion(), $input->getRepository()
                ));

                ShutdownHandler::declareTemporaryPath($package_path);
                return $this->installPackage(new PackageReader($package_path), $authentication, $options);
            }

            throw new OperationException(sprintf('Failed to install remote package %s, no package was found', $input));
        }

        /**
         * Extracts the contents of a package to the specified path
         *
         * @param PackageReader $package_reader
         * @param string $package_path
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         */
        private function extractPackageContents(PackageReader $package_reader, string $package_path): void
        {
            $bin_path = $package_path . DIRECTORY_SEPARATOR . 'bin';

            $total_steps =
                count($package_reader->getComponents()) +
                count($package_reader->getResources()) +
                count($package_reader->getExecutionUnits()) +
                6;
            //$current_step = 0;
            $progress_bar = new ConsoleProgressBar(sprintf('Extracting package %s=%s', $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()), $total_steps);

            //Console::inlineProgressBar(++$current_step, $total_steps);
            $progress_bar->increaseValue(1, true);
            foreach($package_reader->getComponents() as $component_name)
            {
                $progress_bar->setMiscText($component_name);

                if(Resolver::checkLogLevel(LogLevel::VERBOSE->value, Main::getLogLevel()))
                {
                    Console::outVerbose(sprintf('Extracting component %s to %s', $component_name, $bin_path . DIRECTORY_SEPARATOR . $component_name));
                }

                IO::fwrite(
                    $bin_path . DIRECTORY_SEPARATOR . $component_name,
                    $package_reader->getComponent($component_name)->getData([ComponentDecodeOptions::AS_FILE->value]), 0755
                );

                //Console::inlineProgressBar(++$current_step, $total_steps);
                $progress_bar->increaseValue(1, true);
            }

            foreach($package_reader->getResources() as $resource_name)
            {
                $progress_bar->setMiscText($resource_name);

                if(Resolver::checkLogLevel(LogLevel::VERBOSE->value, Main::getLogLevel()))
                {
                    Console::outVerbose(sprintf('Extracting resource %s to %s', $resource_name, $bin_path . DIRECTORY_SEPARATOR . $resource_name));
                }

                IO::fwrite(
                    $bin_path . DIRECTORY_SEPARATOR . $resource_name,
                    $package_reader->getResource($resource_name)->getData(), 0755
                );

                //Console::inlineProgressBar(++$current_step, $total_steps);
                $progress_bar->increaseValue(1, true);
            }

            foreach($package_reader->getExecutionUnits() as $unit)
            {
                $progress_bar->setMiscText($unit);

                if(Resolver::checkLogLevel(LogLevel::VERBOSE->value, Main::getLogLevel()))
                {
                    Console::outVerbose(sprintf('Extracting execution unit %s to %s', $unit, $package_path . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $package_reader->getExecutionUnit($unit)->getExecutionPolicy()->getName() . '.unit'));
                }

                $execution_unit = $package_reader->getExecutionUnit($unit);
                $unit_path = $package_path . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $execution_unit->getExecutionPolicy()->getName() . '.unit';
                IO::fwrite($unit_path, ZiProto::encode($execution_unit->toArray(true)), 0755);

                //Console::inlineProgressBar(++$current_step, $total_steps);
                $progress_bar->increaseValue(1, true);
            }

            $class_map = [];
            foreach($package_reader->getClassMap() as $class)
            {
                $progress_bar->setMiscText($class);
                $class_map[$class] = $package_reader->getComponentByClass($class)->getName();
            }
            //Console::inlineProgressBar(++$current_step, $total_steps);
            $progress_bar->increaseValue(1, true);

            if($package_reader->getInstaller() !== null)
            {
                $progress_bar->setMiscText('installer');
                IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::INSTALLER->value, ZiProto::encode($package_reader->getInstaller()?->toArray(true)));
            }
            //Console::inlineProgressBar(++$current_step, $total_steps);
            $progress_bar->increaseValue(1, true);

            if(count($class_map) > 0)
            {
                $progress_bar->setMiscText('class map');
                IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::CLASS_MAP->value, ZiProto::encode($class_map));
            }
            //Console::inlineProgressBar(++$current_step, $total_steps);
            $progress_bar->increaseValue(1, true);

            IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::ASSEMBLY->value, ZiProto::encode($package_reader->getAssembly()->toArray(true)));
            IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::METADATA->value, ZiProto::encode($package_reader->getMetadata()->toArray(true)));

            if($package_reader->getMetadata()->getUpdateSource() !== null)
            {
                IO::fwrite($package_path . DIRECTORY_SEPARATOR . FileDescriptor::UPDATE->value, ZiProto::encode($package_reader->getMetadata()->getUpdateSource()?->toArray(true)));
            }
            //Console::inlineProgressBar(++$current_step, $total_steps);
            $progress_bar->increaseValue(1, true);

            $progress_bar->setMiscText('creating shadowcopy', true);
            $package_reader->saveCopy($package_path . DIRECTORY_SEPARATOR . FileDescriptor::SHADOW_PACKAGE->value);
            //Console::inlineProgressBar(++$current_step, $total_steps);

            $progress_bar->setMiscText('done', true);
            $progress_bar->increaseValue(1, true);
            unset($progress_bar);
        }

        /**
         * @param string $archive
         * @param array $options
         * @return string
         * @throws NotSupportedException
         * @throws OperationException
         */
        private function buildFromSource(string $archive, array $options=[]): string
        {
            $source_directory = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . uniqid('source_', true);
            Console::outVerbose(sprintf('Extracting source archive %s to %s', $archive, $source_directory));

            try
            {
                ArchiveExtractor::extract($archive, $source_directory);
            }
            catch(Exception $e)
            {
                if(is_dir($source_directory))
                {
                    (new Filesystem())->remove($source_directory);
                }

                throw new OperationException(sprintf('Failed to extract source archive %s: %s', $archive, $e->getMessage()), $e);
            }

            try
            {
                $project_detection = Resolver::detectProject($source_directory);
            }
            catch(Exception $e)
            {
                if(is_dir($source_directory))
                {
                    ShutdownHandler::declareTemporaryPath($source_directory);
                }

                throw new OperationException(sprintf('Failed to detect project type from source %s: %s', $archive, $e->getMessage()), $e);
            }

            // TODO: getProjectType() could return the enum case
            switch($project_detection->getProjectType())
            {
                case ProjectType::NCC->value:
                    try
                    {
                        $package_path = (new ProjectManager($project_detection->getProjectFilePath()))->build(
                            BuildConfigurationValues::DEFAULT,
                            [BuildConfigurationOptions::OUTPUT_FILE => PathFinder::getCachePath() . DIRECTORY_SEPARATOR . hash('sha1', $archive) . '.ncc']
                        );

                        ShutdownHandler::declareTemporaryPath($source_directory);
                        ShutdownHandler::declareTemporaryPath($package_path);

                        return $package_path;
                    }
                    catch(Exception $e)
                    {
                        if(is_dir($source_directory))
                        {
                            ShutdownHandler::declareTemporaryPath($source_directory);
                        }

                        throw new OperationException(sprintf('Failed to build from source %s: %s', $archive, $e->getMessage()), $e);
                    }

                case ProjectType::COMPOSER->value:
                    try
                    {
                        $project_manager = ProjectManager::initializeFromComposer(dirname($project_detection->getProjectFilePath()), $options);
                        $package_path = $project_manager->build(
                            BuildConfigurationValues::DEFAULT,
                            [BuildConfigurationOptions::OUTPUT_FILE => PathFinder::getCachePath() . DIRECTORY_SEPARATOR . hash('sha1', $archive) . '.ncc']
                        );

                        ShutdownHandler::declareTemporaryPath($package_path);
                        ShutdownHandler::declareTemporaryPath($source_directory);

                        return $package_path;
                    }
                    catch(Exception $e)
                    {
                        if(is_dir($source_directory))
                        {
                            ShutdownHandler::declareTemporaryPath($source_directory);
                        }

                        throw new OperationException(sprintf('Failed to build composer package %s: %s', $archive, $e->getMessage()), $e);
                    }

                default:
                    throw new NotSupportedException(sprintf('Cannot build from source %s, the project type %s is not supported', $archive, $project_detection->getProjectType()));
            }
        }

        /**
         * Downloads the given URL to the path, returns the full path to the downloaded file on success.
         *
         * @param string $url
         * @param string $path
         * @return string
         * @throws NetworkException
         * @noinspection UnusedFunctionResultInspection
         */
        private function downloadFile(string $url, string $path): string
        {
            $file_path = basename(parse_url($url, PHP_URL_PATH));
            $curl = curl_init($url);

            if(empty($file_path))
            {
                $file_path = uniqid('download_', true) . '.bin';
            }

            if(str_ends_with($path, '/'))
            {
                $path = substr($path, 0, -1);
            }

            $file_path = $path . DIRECTORY_SEPARATOR . $file_path;

            if(is_file($file_path))
            {
                ShutdownHandler::declareTemporaryPath($file_path);
            }

            $file_handle = fopen($file_path, 'wb');
            $end = false;
            $progress_bar = new ConsoleProgressBar(sprintf('Downloading %s', $url), 100);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_NOPROGRESS, false);
            curl_setopt($curl, CURLOPT_FILE, $file_handle);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'User-Agent: ncc'
            ]);
            curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, static function ($resource, $download_size, $downloaded)  use ($url, &$end, $progress_bar)
            {
                if($download_size === $downloaded && $end)
                {
                    return;
                }

                if($download_size === 0)
                {
                    return;
                }

                if(Resolver::checkLogLevel(LogLevel::VERBOSE->value, Main::getLogLevel()))
                {
                    $percentage = round(($downloaded / $download_size) * 100, 2);
                    Console::out(sprintf('Download progress %s (%s/%s) for %s', $percentage, $downloaded, $download_size, $url));
                }
                else
                {
                    $progress_bar->setMaxValue($download_size);
                    $progress_bar->setValue($downloaded);
                    $progress_bar->setMiscText(sprintf('%s/%s', $downloaded, $download_size));

                    $progress_bar->update();
                }

                if($download_size === $downloaded)
                {
                    $end = true;
                }
            });

            unset($progress_bar);
            curl_exec($curl);
            fclose($file_handle);

            if(curl_errno($curl))
            {
                ShutdownHandler::declareTemporaryPath($file_path);
                throw new NetworkException(sprintf('Failed to download file from %s: %s', $url, curl_error($curl)));
            }

            curl_close($curl);
            return $file_path;
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