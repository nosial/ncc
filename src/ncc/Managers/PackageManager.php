<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use Exception;
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\ConstantReferences;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Scopes;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Classes\PhpExtension\Installer;
    use ncc\CLI\Main;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InstallationException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageAlreadyInstalledException;
    use ncc\Exceptions\PackageLockException;
    use ncc\Exceptions\PackageNotFoundException;
    use ncc\Exceptions\PackageParsingException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Exceptions\VersionNotFoundException;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package;
    use ncc\Objects\PackageLock\PackageEntry;
    use ncc\Objects\PackageLock\VersionEntry;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\ZiProto\ZiProto;
    use SplFileInfo;

    class PackageManager
    {
        /**
         * @var string
         */
        private $PackagesPath;

        /**
         * @var PackageLockManager|null
         */
        private $PackageLockManager;

        /**
         * @throws InvalidScopeException
         * @throws PackageLockException
         */
        public function __construct()
        {
            $this->PackagesPath = PathFinder::getPackagesPath(Scopes::System);
            $this->PackageLockManager = new PackageLockManager();
            $this->PackageLockManager->load();
        }

        /**
         * Installs a local package onto the system
         *
         * @param string $input
         * @return string
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InstallationException
         * @throws PackageAlreadyInstalledException
         * @throws PackageLockException
         * @throws PackageParsingException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedRunnerException
         * @throws VersionNotFoundException
         */
        public function install(string $input): string
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permission to install packages');

            Console::outVerbose(sprintf('Installing %s', $input));
            if(!file_exists($input) || !is_file($input) || !is_readable($input))
                throw new FileNotFoundException('The specified file \'' . $input .' \' does not exist or is not readable.');

            $package = Package::load($input);

            if($this->getPackageVersion($package->Assembly->Package, $package->Assembly->Version) !== null)
                throw new PackageAlreadyInstalledException('The package ' . $package->Assembly->Package . '==' . $package->Assembly->Version . ' is already installed');

            $extension = $package->Header->CompilerExtension->Extension;
            $installation_paths = new InstallationPaths($this->PackagesPath . DIRECTORY_SEPARATOR . $package->Assembly->Package . '==' . $package->Assembly->Version);
            $installer = match ($extension) {
                CompilerExtensions::PHP => new Installer($package),
                default => throw new UnsupportedCompilerExtensionException('The compiler extension \'' . $extension . '\' is not supported'),
            };
            $execution_pointer_manager = new ExecutionPointerManager();
            PackageCompiler::compilePackageConstants($package, [
                ConstantReferences::Install => $installation_paths
            ]);

            Console::outVerbose(sprintf('Successfully parsed %s', $package->Assembly->Package));

            if(Resolver::checkLogLevel(LogLevel::Debug, Main::getLogLevel()))
            {
                Console::outDebug(sprintf('installer.install_path: %s', $installation_paths->getInstallationPath()));
                Console::outDebug(sprintf('installer.data_path:    %s', $installation_paths->getDataPath()));
                Console::outDebug(sprintf('installer.bin_path:     %s', $installation_paths->getBinPath()));
                Console::outDebug(sprintf('installer.src_path:     %s', $installation_paths->getSourcePath()));

                foreach($package->Assembly->toArray() as $prop => $value)
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                foreach($package->Header->CompilerExtension->toArray() as $prop => $value)
                    Console::outDebug(sprintf('header.compiler.%s: %s', $prop, ($value ?? 'n/a')));
            }

            Console::out('Installing ' . $package->Assembly->Package);

            // 4 For Directory Creation, preInstall, postInstall & initData methods
            $steps = (4 + count($package->Components) + count ($package->Resources) + count ($package->ExecutionUnits));

            // Include the Execution units
            if($package->Installer?->PreInstall !== null)
                $steps += count($package->Installer->PreInstall);
            if($package->Installer?->PostInstall!== null)
                $steps += count($package->Installer->PostInstall);

            $current_steps = 0;
            $filesystem = new Filesystem();

            try
            {
                $filesystem->mkdir($installation_paths->getInstallationPath(), 0755);
                $filesystem->mkdir($installation_paths->getBinPath(), 0755);
                $filesystem->mkdir($installation_paths->getDataPath(), 0755);
                $filesystem->mkdir($installation_paths->getSourcePath(), 0755);
                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch(Exception $e)
            {
                throw new InstallationException('Error while creating directory, ' . $e->getMessage(), $e);
            }

            try
            {
                self::initData($package, $installation_paths);
                Console::outDebug(sprintf('saving shadow package to %s', $installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'pkg'));
                $package->save($installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'pkg');
                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch(Exception $e)
            {
                throw new InstallationException('Cannot initialize package install, ' . $e->getMessage(), $e);
            }

            // Execute the pre-installation stage before the installation stage
            try
            {
                $installer->preInstall($installation_paths);
                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch (Exception $e)
            {
                throw new InstallationException('Pre installation stage failed, ' . $e->getMessage(), $e);
            }

            if($package->Installer?->PreInstall !== null && count($package->Installer->PreInstall) > 0)
            {
                foreach($package->Installer->PreInstall as $unit_name)
                {
                    try
                    {
                        $execution_pointer_manager->temporaryExecute($package, $unit_name);
                    }
                    catch(Exception $e)
                    {
                        Console::outWarning('Cannot execute unit ' . $unit_name . ', ' . $e->getMessage());
                    }

                    $current_steps += 1;
                    Console::inlineProgressBar($current_steps, $steps);
                }
            }

            // Process & Install the components
            foreach($package->Components as $component)
            {
                Console::outDebug(sprintf('processing component %s (%s)', $component->Name, $component->DataType));

                try
                {
                    $data = $installer->processComponent($component);
                    if($data !== null)
                    {
                        $component_path = $installation_paths->getSourcePath() . DIRECTORY_SEPARATOR . $component->Name;
                        $component_dir = dirname($component_path);
                        if(!$filesystem->exists($component_dir))
                            $filesystem->mkdir($component_dir);
                        IO::fwrite($component_path, $data);
                    }
                }
                catch(Exception $e)
                {
                    throw new InstallationException('Cannot process one or more components, ' . $e->getMessage(), $e);
                }

                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }

            // Process & Install the resources
            foreach($package->Resources as $resource)
            {
                Console::outDebug(sprintf('processing resource %s', $resource->Name));

                try
                {
                    $data = $installer->processResource($resource);
                    if($data !== null)
                    {
                        $resource_path = $installation_paths->getSourcePath() . DIRECTORY_SEPARATOR . $resource->Name;
                        $resource_dir = dirname($resource_path);
                        if(!$filesystem->exists($resource_dir))
                            $filesystem->mkdir($resource_dir);
                        IO::fwrite($resource_path, $data);
                    }
                }
                catch(Exception $e)
                {
                    throw new InstallationException('Cannot process one or more resources, ' . $e->getMessage(), $e);
                }

                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }

            // Install execution units
            // TODO: Implement symlink support
            if(count($package->ExecutionUnits) > 0)
            {
                $execution_pointer_manager = new ExecutionPointerManager();
                $unit_paths = [];

                foreach($package->ExecutionUnits as $executionUnit)
                {
                    $execution_pointer_manager->addUnit($package->Assembly->Package, $package->Assembly->Version, $executionUnit);
                    $current_steps += 1;
                    Console::inlineProgressBar($current_steps, $steps);
                }

                IO::fwrite($installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'exec', ZiProto::encode($unit_paths));
            }

            // Execute the post-installation stage after the installation is complete
            try
            {
                $installer->postInstall($installation_paths);
                $current_steps += 1;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch (Exception $e)
            {
                throw new InstallationException('Post installation stage failed, ' . $e->getMessage(), $e);
            }

            if($package->Installer?->PostInstall !== null && count($package->Installer->PostInstall) > 0)
            {
                foreach($package->Installer->PostInstall as $unit_name)
                {
                    try
                    {
                        $execution_pointer_manager->temporaryExecute($package, $unit_name);
                    }
                    catch(Exception $e)
                    {
                        Console::outWarning('Cannot execute unit ' . $unit_name . ', ' . $e->getMessage());
                    }

                    $current_steps += 1;
                    Console::inlineProgressBar($current_steps, $steps);
                }
            }

            $this->getPackageLockManager()->getPackageLock()->addPackage($package, $installation_paths->getInstallationPath());
            $this->getPackageLockManager()->save();

            return $package->Assembly->Package;
        }

        /**
         * Returns an existing package entry, returns null if no such entry exists
         *
         * @param string $package
         * @return PackageEntry|null
         * @throws PackageLockException
         * @throws PackageLockException
         */
        public function getPackage(string $package): ?PackageEntry
        {
            return $this->getPackageLockManager()->getPackageLock()->getPackage($package);
        }

        /**
         * Returns an existing version entry, returns null if no such entry exists
         *
         * @param string $package
         * @param string $version
         * @return VersionEntry|null
         * @throws VersionNotFoundException
         * @throws PackageLockException
         */
        public function getPackageVersion(string $package, string $version): ?VersionEntry
        {
            return $this->getPackage($package)?->getVersion($version);
        }

        /**
         * Returns the latest version of the package, or null if there is no entry
         *
         * @param string $package
         * @return VersionEntry|null
         * @throws VersionNotFoundException
         * @throws PackageLockException
         */
        public function getLatestVersion(string $package): ?VersionEntry
        {
            return $this->getPackage($package)?->getVersion($this->getPackage($package)?->getLatestVersion());
        }

        /**
         * Returns an array of all packages and their installed versions
         *
         * @return array
         * @throws PackageLockException
         * @throws PackageLockException
         */
        public function getInstalledPackages(): array
        {
            return $this->getPackageLockManager()->getPackageLock()->getPackages();
        }

        /**
         * Uninstalls a package version
         *
         * @param string $package
         * @param string $version
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws PackageLockException
         * @throws PackageNotFoundException
         * @throws VersionNotFoundException
         */
        public function uninstallPackageVersion(string $package, string $version): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permission to uninstall packages');

            $version_entry = $this->getPackageVersion($package, $version);
            if($version_entry == null)
                throw new PackageNotFoundException(sprintf('The package %s==%s was not found', $package, $version));

            Console::out(sprintf('Uninstalling %s==%s', $package, $version));
            Console::outVerbose(sprintf('Removing package %s==%s from PackageLock', $package, $version));
            if(!$this->getPackageLockManager()->getPackageLock()->removePackageVersion($package, $version))
                Console::outDebug('warning: removing package from package lock failed');

            $this->getPackageLockManager()->save();

            Console::outVerbose('Removing package files');
            $scanner = new DirectoryScanner();
            $filesystem = new Filesystem();

            /** @var SplFileInfo $item */
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            foreach($scanner($version_entry->Location, true) as $item)
            {
                if(is_file($item->getPath()))
                {
                    Console::outDebug(sprintf('deleting %s', $item->getPath()));
                    $filesystem->remove($item->getPath());
                }
            }

            $filesystem->remove($version_entry->Location);

            if($version_entry->ExecutionUnits !== null && count($version_entry->ExecutionUnits) > 0)
            {
                Console::outVerbose('Uninstalling execution units');

                $execution_pointer_manager = new ExecutionPointerManager();
                foreach($version_entry->ExecutionUnits as $executionUnit)
                {
                    if(!$execution_pointer_manager->removeUnit($package, $version, $executionUnit->ExecutionPolicy->Name))
                        Console::outDebug(sprintf('warning: removing execution unit %s failed', $executionUnit->ExecutionPolicy->Name));
                }
            }
        }

        /**
         * Uninstalls all versions of a package
         *
         * @param string $package
         * @return void
         * @throws AccessDeniedException
         * @throws PackageLockException
         * @throws PackageNotFoundException
         * @throws VersionNotFoundException
         */
        public function uninstallPackage(string $package): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permission to uninstall packages');

            $package_entry = $this->getPackage($package);
            if($package_entry == null)
                throw new PackageNotFoundException(sprintf('The package %s was not found', $package));

            foreach($package_entry->getVersions() as $version)
            {
                $version_entry = $package_entry->getVersion($version);
                try
                {
                    $this->uninstallPackageVersion($package, $version_entry->Version);
                }
                catch(Exception $e)
                {
                    Console::outDebug(sprintf('warning: unable to uninstall package %s==%s, %s (%s)', $package, $version_entry->Version, $e->getMessage(), $e->getCode()));
                }
            }
        }

        /**
         * @param Package $package
         * @param InstallationPaths $paths
         * @throws InstallationException
         */
        private static function initData(Package $package, InstallationPaths $paths): void
        {
            // Create data files
            $dependencies = [];
            foreach($package->Dependencies as $dependency)
            {
                $dependencies[] = $dependency->toArray(true);
            }

            $data_files = [
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'assembly' =>
                    ZiProto::encode($package->Assembly->toArray(true)),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'ext' =>
                    ZiProto::encode($package->Header->CompilerExtension->toArray(true)),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'const' =>
                    ZiProto::encode($package->Header->RuntimeConstants),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'dependencies' =>
                    ZiProto::encode($dependencies),
            ];

            foreach($data_files as $file => $data)
            {
                try
                {
                    IO::fwrite($file, $data);
                }
                catch (IOException $e)
                {
                    throw new InstallationException('Cannot write to file \'' . $file . '\', ' . $e->getMessage(), $e);
                }
            }
        }

        /**
         * @return PackageLockManager|null
         */
        private function getPackageLockManager(): ?PackageLockManager
        {
            if($this->PackageLockManager == null)
            {
                $this->PackageLockManager = new PackageLockManager();
            }

            return $this->PackageLockManager;
        }

    }