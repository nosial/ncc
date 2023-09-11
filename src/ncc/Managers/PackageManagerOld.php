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

    namespace ncc\Managers;

    use Exception;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\ConstantReferences;
    use ncc\Enums\DependencySourceType;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\InstallPackageOptions;
    use ncc\Enums\RemoteSourceType;
    use ncc\Enums\Scopes;
    use ncc\Enums\Versions;
    use ncc\Classes\ComposerExtension\ComposerSourceBuiltin;
    use ncc\Classes\GitClient;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Classes\PhpExtension\PhpInstaller;
    use ncc\CLI\Main;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PackageException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\DefinedRemoteSource;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package;
    use ncc\Objects\PackageLock\PackageEntry;
    use ncc\Objects\PackageLock\VersionEntry;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\RemotePackageInput;
    use ncc\Objects\Vault\Entry;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\RuntimeCache;
    use ncc\Utilities\Validate;
    use ncc\Extensions\ZiProto\ZiProto;
    use SplFileInfo;
    use Throwable;

    class PackageManagerOld
    {
        /**
         * @var string
         */
        private $packages_path;

        /**
         * @var PackageLockManager|null
         */
        private $package_lock_manager;

        /**
         * @throws IOException
         */
        public function __construct()
        {
            $this->packages_path = PathFinder::getPackagesPath(Scopes::SYSTEM);
            $this->package_lock_manager = new PackageLockManager();
            $this->package_lock_manager->load();
        }

        /**
         * Installs a local package onto the system
         *
         * @param string $package_path
         * @param Entry|null $entry
         * @param array $options
         * @return string
         * @throws AuthenticationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PackageException
         * @throws PathNotFoundException
         * @throws ConfigurationException
         */
        public function install(string $package_path, ?Entry $entry=null, array $options=[]): string
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient permission to install packages');
            }

            if(!file_exists($package_path) || !is_file($package_path) || !is_readable($package_path))
            {
                throw new PathNotFoundException($package_path);
            }

            $package = Package::load($package_path);

            if(RuntimeCache::get(sprintf('installed.%s=%s', $package->getAssembly()->getPackage(), $package->getAssembly()->getVersion())))
            {
                Console::outDebug(sprintf('skipping installation of %s=%s, already processed', $package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()));
                return $package->getAssembly()->getPackage();
            }

            $extension = $package->getMetadata()->getCompilerExtension()->getExtension();
            $installation_paths = new InstallationPaths($this->packages_path . DIRECTORY_SEPARATOR . $package->getAssembly()->getPackage() . '=' . $package->getAssembly()->getVersion());

            $installer = match ($extension)
            {
                CompilerExtensions::PHP => new PhpInstaller($package),
                default => throw new NotSupportedException(sprintf('Compiler extension %s is not supported with ncc', $extension))
            };

            if($this->getPackageVersion($package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()) !== null)
            {
                if(in_array(InstallPackageOptions::REINSTALL, $options, true))
                {
                    if($this->getPackageLockManager()?->getPackageLock()?->packageExists($package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()))
                    {
                        $this->getPackageLockManager()?->getPackageLock()?->removePackageVersion(
                            $package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()
                        );
                    }
                }
                else
                {
                    throw new PackageException('The package ' . $package->getAssembly()->getPackage() . '=' . $package->getAssembly()->getVersion() . ' is already installed');
                }
            }

            $execution_pointer_manager = new ExecutionPointerManager();
            PackageCompiler::compilePackageConstants($package, [
                ConstantReferences::INSTALL => $installation_paths
            ]);

            // Process all the required dependencies before installing the package
            if(count($package->getDependencies()) > 0 && !in_array(InstallPackageOptions::SKIP_DEPENDENCIES, $options, true))
            {
                foreach($package->getDependencies() as $dependency)
                {
                    // Uninstall the dependency if the option Reinstall is passed on
                    if(in_array(InstallPackageOptions::REINSTALL, $options, true) && $this->getPackageLockManager()?->getPackageLock()?->packageExists($dependency->getName(), $dependency->getVersion()))
                    {
                        if($dependency->getVersion() === 'latest')
                        {
                            $this->uninstallPackage($dependency->getName());
                        }
                        else
                        {
                            $this->uninstallPackageVersion($dependency->getName(), $dependency->getVersion());
                        }
                    }

                    $this->processDependency($dependency, $package, $package_path, $entry, $options);
                }
            }

            Console::outVerbose(sprintf('Installing %s', $package_path));

            if(Resolver::checkLogLevel(LogLevel::DEBUG, Main::getLogLevel()))
            {
                Console::outDebug(sprintf('installer.install_path: %s', $installation_paths->getInstallationpath()));
                Console::outDebug(sprintf('installer.data_path:    %s', $installation_paths->getDataPath()));
                Console::outDebug(sprintf('installer.bin_path:     %s', $installation_paths->getBinPath()));
                Console::outDebug(sprintf('installer.src_path:     %s', $installation_paths->getSourcePath()));

                foreach($package->getAssembly()->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                }

                foreach($package->getMetadata()->getCompilerExtension()->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('header.compiler.%s: %s', $prop, ($value ?? 'n/a')));
                }
            }

            Console::out('Installing ' . $package->getAssembly()->getPackage());

            // Four For Directory Creation, preInstall, postInstall & initData methods
            $steps = (4 + count($package->getComponents()) + count ($package->getResources()) + count ($package->getExecutionUnits()));

            // Include the Execution units
            if($package->getInstaller()?->getPreInstall() !== null)
            {
                $steps += count($package->getInstaller()?->getPreInstall());
            }

            if($package->getInstaller()?->getPostInstall()!== null)
            {
                $steps += count($package->getInstaller()->getPostInstall());
            }

            $current_steps = 0;
            $filesystem = new Filesystem();

            try
            {
                $filesystem->mkdir($installation_paths->getInstallationpath(), 0755);
                $filesystem->mkdir($installation_paths->getBinPath(), 0755);
                $filesystem->mkdir($installation_paths->getDataPath(), 0755);
                $filesystem->mkdir($installation_paths->getSourcePath(), 0755);

                ++$current_steps;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch(Exception $e)
            {
                throw new IOException('Error while creating directory, ' . $e->getMessage(), $e);
            }

            try
            {
                Console::outDebug(sprintf('saving shadow package to %s', $installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'pkg'));

                self::initData($package, $installation_paths);
                $package->save($installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'pkg');
                ++$current_steps;

                Console::inlineProgressBar($current_steps, $steps);
            }
            catch(Exception $e)
            {
                throw new OperationException('Cannot initialize package install, ' . $e->getMessage(), $e);
            }

            // Execute the pre-installation stage before the installation stage
            try
            {
                $installer->preInstall($installation_paths);
                ++$current_steps;
                Console::inlineProgressBar($current_steps, $steps);
            }
            catch (Exception $e)
            {
                throw new OperationException('Pre installation stage failed, ' . $e->getMessage(), $e);
            }

            if($package->getInstaller()?->getPreInstall() !== null && count($package->getInstaller()->getPreInstall()) > 0)
            {
                foreach($package->getInstaller()->getPreInstall() as $unit_name)
                {
                    try
                    {
                        $execution_pointer_manager->temporaryExecute($package, $unit_name);
                    }
                    catch(Exception $e)
                    {
                        Console::outWarning('Cannot execute unit ' . $unit_name . ', ' . $e->getMessage());
                    }

                    ++$current_steps;
                    Console::inlineProgressBar($current_steps, $steps);
                }
            }

            // Process & Install the components
            foreach($package->getComponents() as $component)
            {
                Console::outDebug(sprintf('processing component %s (%s)', $component->getName(), $component->getDataType()));

                try
                {
                    $data = $installer->processComponent($component);
                    if($data !== null)
                    {
                        $component_path = $installation_paths->getSourcePath() . DIRECTORY_SEPARATOR . $component->getName();
                        $component_dir = dirname($component_path);

                        if(!$filesystem->exists($component_dir))
                        {
                            $filesystem->mkdir($component_dir);
                        }

                        IO::fwrite($component_path, $data);
                    }
                }
                catch(Exception $e)
                {
                    throw new OperationException('Cannot process one or more components, ' . $e->getMessage(), $e);
                }

                ++$current_steps;
                Console::inlineProgressBar($current_steps, $steps);
            }

            // Process & Install the resources
            foreach($package->getResources() as $resource)
            {
                Console::outDebug(sprintf('processing resource %s', $resource->getName()));

                try
                {
                    $data = $installer->processResource($resource);
                    if($data !== null)
                    {
                        $resource_path = $installation_paths->getSourcePath() . DIRECTORY_SEPARATOR . $resource->getName();
                        $resource_dir = dirname($resource_path);

                        if(!$filesystem->exists($resource_dir))
                        {
                            $filesystem->mkdir($resource_dir);
                        }

                        IO::fwrite($resource_path, $data);
                    }
                }
                catch(Exception $e)
                {
                    throw new OperationException('Cannot process one or more resources, ' . $e->getMessage(), $e);
                }

                ++$current_steps;
                Console::inlineProgressBar($current_steps, $steps);
            }

            // Install execution units
            if($package->getExecutionUnits() !== null && count($package->getExecutionUnits()) > 0)
            {
                Console::outDebug('package contains execution units, processing');

                $execution_pointer_manager = new ExecutionPointerManager();
                $unit_paths = [];

                /** @var Package\ExecutionUnit $executionUnit */
                foreach($package->getExecutionUnits() as $executionUnit)
                {
                    Console::outDebug(sprintf('processing execution unit %s', $executionUnit->getExecutionPolicy()->getName()));
                    $execution_pointer_manager->addUnit($package->getAssembly()->getPackage(), $package->getAssembly()->getVersion(), $executionUnit);
                    ++$current_steps;
                    Console::inlineProgressBar($current_steps, $steps);
                }

                IO::fwrite($installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'exec', ZiProto::encode($unit_paths));
            }
            else
            {
                Console::outDebug('package does not contain execution units, skipping');
            }

            // After execution units are installed, create a symlink if needed
            if(!is_null($package->getMetadata()->getOption('create_symlink')) && $package->getMetadata()->getOption('create_symlink'))
            {
                if($package->getMainExecutionPolicy() === null)
                {
                    throw new OperationException('Cannot create symlink, no main execution policy is defined');
                }

                Console::outDebug(sprintf('creating symlink to %s', $package->getAssembly()->getPackage()));

                $SymlinkManager = new SymlinkManager();
                $SymlinkManager->add($package->getAssembly()->getPackage(), $package->getMainExecutionPolicy());
            }

            // Execute the post-installation stage after the installation is complete
            try
            {
                Console::outDebug('executing post-installation stage');

                $installer->postInstall($installation_paths);
                ++$current_steps;

                Console::inlineProgressBar($current_steps, $steps);
            }
            catch (Exception $e)
            {
                throw new OperationException('Post installation stage failed, ' . $e->getMessage(), $e);
            }

            if($package->getInstaller()?->getPostInstall() !== null && count($package->getInstaller()->getPostInstall()) > 0)
            {
                Console::outDebug('executing post-installation units');

                foreach($package->getInstaller()->getPostInstall() as $unit_name)
                {
                    try
                    {
                        $execution_pointer_manager->temporaryExecute($package, $unit_name);
                    }
                    catch(Exception $e)
                    {
                        Console::outWarning('Cannot execute unit ' . $unit_name . ', ' . $e->getMessage());
                    }
                    finally
                    {
                        ++$current_steps;
                        Console::inlineProgressBar($current_steps, $steps);
                    }
                }
            }
            else
            {
                Console::outDebug('no post-installation units to execute');
            }

            if($package->getMetadata()->getUpdateSource()?->getRepository() !== null)
            {
                $sources_manager = new RemoteSourcesManager();
                if($sources_manager->getRemoteSource($package->getMetadata()->getUpdateSource()->getRepository()->getName()) === null)
                {
                    Console::outVerbose('Adding remote source ' . $package->getMetadata()->getUpdateSource()->getRepository()->getName());

                    $defined_remote_source = new DefinedRemoteSource();
                    $defined_remote_source->setName($package->getMetadata()->getUpdateSource()?->getRepository()?->getName());
                    $defined_remote_source->setHost($package->getMetadata()->getUpdateSource()?->getRepository()?->getHost());
                    $defined_remote_source->setType($package->getMetadata()->getUpdateSource()?->getRepository()?->getType());
                    $defined_remote_source->setSsl($package->getMetadata()->getUpdateSource()?->getRepository()?->isSsl());

                    $sources_manager->addRemoteSource($defined_remote_source);
                }
            }

            $this->getPackageLockManager()?->getPackageLock()?->addPackage($package, $installation_paths->getInstallationpath());
            $this->getPackageLockManager()?->save();

            RuntimeCache::set(sprintf('installed.%s=%s', $package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()), true);

            return $package->getAssembly()->getPackage();
        }

        /**
         * @param string $source
         * @param Entry|null $entry
         * @return string
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PackageException
         */
        public function fetchFromSource(string $source, ?Entry $entry=null): string
        {
            $input = new RemotePackageInput($source);

            if($input->getSource() === null)
            {
                throw new PackageException('No source specified');
            }

            if($input->getVersion() === null)
            {
                $input->setVersion(Versions::LATEST);
            }

            Console::outVerbose('Fetching package ' . $input->getPackage() . ' from ' . $input->getSource() . ' (' . $input->getVersion() . ')');

            $remote_source_type = Resolver::detectRemoteSourceType($input->getSource());
            if($remote_source_type === RemoteSourceType::BUILTIN)
            {
                Console::outDebug('using builtin source ' . $input->getSource());

                if ($input->getSource() === 'composer')
                {
                    try
                    {
                        return ComposerSourceBuiltin::fetch($input);
                    }
                    catch (Exception $e)
                    {
                        throw new PackageException('Cannot fetch package from composer source, ' . $e->getMessage(), $e);
                    }
                }

                throw new NotSupportedException(sprintf('Builtin source %s is not supported', $input->getSource()));
            }

            if($remote_source_type === RemoteSourceType::DEFINED)
            {
                Console::outDebug('using defined source ' . $input->getSource());
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $source = (new RemoteSourcesManager())->getRemoteSource($input->getSource());
                if($source === null)
                {
                    throw new OperationException('Remote source ' . $input->getSource() . ' is not defined');
                }

                $repositoryQueryResults = Functions::getRepositoryQueryResults($input, $source, $entry);
                $exceptions = [];

                if($repositoryQueryResults->getFiles()->ZipballUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->getPackage(), $repositoryQueryResults->getFiles()->ZipballUrl));
                        $archive = Functions::downloadGitServiceFile($repositoryQueryResults->getFiles()->ZipballUrl, $entry);
                        return PackageCompiler::tryCompile(Functions::extractArchive($archive), $repositoryQueryResults->getVersion());
                    }
                    catch(Throwable $e)
                    {
                        Console::outDebug('cannot fetch package from zipball url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->getFiles()->TarballUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->getPackage(), $repositoryQueryResults->getFiles()->TarballUrl));
                        $archive = Functions::downloadGitServiceFile($repositoryQueryResults->getFiles()->TarballUrl, $entry);
                        return PackageCompiler::tryCompile(Functions::extractArchive($archive), $repositoryQueryResults->getVersion());
                    }
                    catch(Exception $e)
                    {
                        Console::outDebug('cannot fetch package from tarball url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->getFiles()->PackageUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->getPackage(), $repositoryQueryResults->getFiles()->PackageUrl));
                        return Functions::downloadGitServiceFile($repositoryQueryResults->getFiles()->PackageUrl, $entry);
                    }
                    catch(Exception $e)
                    {
                        Console::outDebug('cannot fetch package from package url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->getFiles()->GitHttpUrl !== null || $repositoryQueryResults->getFiles()->GitSshUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->getPackage(), $repositoryQueryResults->getFiles()->GitHttpUrl ?? $repositoryQueryResults->getFiles()->GitSshUrl));
                        $git_repository = GitClient::cloneRepository($repositoryQueryResults->getFiles()->GitHttpUrl ?? $repositoryQueryResults->getFiles()->GitSshUrl);

                        foreach(GitClient::getTags($git_repository) as $tag)
                        {
                            if(VersionComparator::compareVersion($tag, $repositoryQueryResults->getVersion()) === 0)
                            {
                                GitClient::checkout($git_repository, $tag);
                                return PackageCompiler::tryCompile($git_repository, $repositoryQueryResults->getVersion());
                            }
                        }

                        Console::outDebug('cannot fetch package from git repository, no matching tag found');
                    }
                    catch(Exception $e)
                    {
                        Console::outDebug('cannot fetch package from git repository, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                // Recursively create an exception with the previous exceptions as the previous exception
                $exception = null;

                if(count($exceptions) > 0)
                {
                    foreach($exceptions as $e)
                    {
                        if($exception === null)
                        {
                            $exception = new PackageException($e->getMessage(), $e);
                        }
                        else
                        {
                            if($e->getMessage() === $exception->getMessage())
                            {
                                continue;
                            }

                            $exception = new PackageException($e->getMessage(), $exception);
                        }
                    }
                }
                else
                {
                    $exception = new PackageException('Cannot fetch package from remote source, no assets found');
                }

                throw $exception;
            }

            throw new PackageException(sprintf('Unknown remote source type %s', $remote_source_type));
        }

        /**
         * Installs a package from a source syntax (vendor/package=version@source)
         *
         * @param string $source
         * @param Entry|null $entry
         * @param array $options
         * @return string
         * @throws OperationException
         */
        public function installFromSource(string $source, ?Entry $entry, array $options=[]): string
        {
            try
            {
                Console::outVerbose(sprintf('Installing package from source %s', $source));

                $package = $this->fetchFromSource($source, $entry);
                return $this->install($package, $entry, $options);
            }
            catch(Exception $e)
            {
                throw new OperationException('Cannot install package from source, ' . $e->getMessage(), $e);
            }
        }

        /**
         * @param Dependency $dependency
         * @param Package $package
         * @param string $package_path
         * @param Entry|null $entry
         * @param array $options
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PackageException
         * @throws PathNotFoundException
         * @throws ConfigurationException
         */
        private function processDependency(Dependency $dependency, Package $package, string $package_path, ?Entry $entry=null, array $options=[]): void
        {
            if(RuntimeCache::get(sprintf('dependency_installed.%s=%s', $dependency->getName(), $dependency->getVersion())))
            {
                Console::outDebug(sprintf('dependency %s=%s already processed, skipping', $dependency->getName(), $dependency->getVersion()));
                return;
            }

            Console::outVerbose('processing dependency ' . $dependency->getVersion() . ' (' . $dependency->getVersion() . ')');
            $dependent_package = $this->getPackage($dependency->getName());
            $dependency_met = false;

            if ($dependent_package !== null && $dependency->getVersion() !== null && Validate::version($dependency->getVersion()))
            {
                Console::outDebug('dependency has version constraint, checking if package is installed');
                $dependent_version = $this->getPackageVersion($dependency->getName(), $dependency->getVersion());
                if ($dependent_version !== null)
                {
                    $dependency_met = true;
                }
            }
            elseif ($dependent_package !== null && $dependency->getVersion() === null)
            {
                Console::outDebug(sprintf('dependency %s has no version specified, assuming dependency is met', $dependency->getName()));
                $dependency_met = true;
            }

            Console::outDebug('dependency met: ' . ($dependency_met ? 'true' : 'false'));

            if ($dependency->getSourceType() !== null && !$dependency_met)
            {
                Console::outVerbose(sprintf('Installing dependency %s=%s for %s=%s', $dependency->getName(), $dependency->getVersion(), $package->getAssembly()->getPackage(), $package->getAssembly()->getVersion()));
                switch ($dependency->getSourceType())
                {
                    case DependencySourceType::LOCAL:
                        Console::outDebug('installing from local source ' . $dependency->getSource());
                        $basedir = dirname($package_path);

                        if (!file_exists($basedir . DIRECTORY_SEPARATOR . $dependency->getSourceType()))
                        {
                            throw new PathNotFoundException($basedir . DIRECTORY_SEPARATOR . $dependency->getSource());
                        }

                        $this->install($basedir . DIRECTORY_SEPARATOR . $dependency->getSource(), null, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->getName(), $dependency->getVersion()), true);
                        break;

                    case DependencySourceType::STATIC:
                        throw new PackageException('Static linking not possible, package ' . $dependency->getName() . ' is not installed');

                    case DependencySourceType::REMOTE:
                        Console::outDebug('installing from remote source ' . $dependency->getSource());
                        $this->installFromSource($dependency->getSource(), $entry, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->getName(), $dependency->getVersion()), true);
                        break;

                    default:
                        throw new NotSupportedException(sprintf('Dependency source type %s is not supported', $dependency->getSourceType()));
                }
            }
            elseif(!$dependency_met)
            {
                throw new PackageException(sprintf('Required dependency %s=%s is not installed', $dependency->getName(), $dependency->getVersion()));
            }
        }

        /**
         * Returns an existing package entry, returns null if no such entry exists
         *
         * @param string $package
         * @return PackageEntry|null
         * @throws IOException
         */
        public function getPackage(string $package): ?PackageEntry
        {
            Console::outDebug('getting package ' . $package);
            return $this->getPackageLockManager()?->getPackageLock()?->getPackage($package);
        }

        /**
         * Returns an existing version entry, returns null if no such entry exists
         *
         * @param string $package
         * @param string $version
         * @return VersionEntry|null
         * @throws IOException
         */
        public function getPackageVersion(string $package, string $version): ?VersionEntry
        {
            Console::outDebug('getting package version ' . $package . '=' . $version);
            return $this->getPackage($package)?->getVersion($version);
        }

        /**
         * Returns the latest version of the package, or null if there is no entry
         *
         * @param string $package
         * @return VersionEntry|null
         * @throws IOException
         */
        public function getLatestVersion(string $package): ?VersionEntry
        {
            Console::outDebug('getting latest version of package ' . $package);
            return $this->getPackage($package)?->getVersion($this->getPackage($package)?->getLatestVersion());
        }

        /**
         * Returns an array of all packages and their installed versions
         *
         * @return array
         * @throws IOException
         */
        public function getInstalledPackages(): array
        {
            return $this->getPackageLockManager()?->getPackageLock()?->getEntries() ?? [];
        }

        /**
         * Returns a package tree representation
         *
         * @param array $tree
         * @param string|null $package
         * @return array
         */
        public function getPackageTree(array $tree=[], ?string $package=null): array
        {
            // First build the packages to scan first
            $packages = [];
            if($package !== null)
            {
                // If it's coming from a selected package, query the package and process its dependencies
                $exploded = explode('=', $package);
                try
                {
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $package = $this->getPackage($exploded[0]);
                    if($package === null)
                    {
                        throw new PackageException('Package ' . $exploded[0] . ' not found');
                    }

                    $version = $package->getVersion($exploded[1]);
                    if($version === null)
                    {
                        throw new OperationException('Version ' . $exploded[1] . ' not found for package ' . $exploded[0]);
                    }

                    foreach ($version->getDependencies() as $dependency)
                    {
                        if(!in_array($dependency->getPackageName() . '=' . $dependency->getVersion(), $tree, true))
                        {
                            $packages[] = $dependency->getPackageName() . '=' . $dependency->getVersion();
                        }
                    }
                }
                catch(Exception $e)
                {
                    unset($e);
                }

            }
            else
            {
                // If it's coming from nothing, start with the installed packages on the system
                try
                {
                    foreach ($this->getInstalledPackages() as $installed_package => $versions)
                    {
                        foreach ($versions as $version)
                        {
                            if (!in_array($installed_package . '=' . $version, $packages, true))
                            {
                                $packages[] = $installed_package . '=' . $version;
                            }
                        }
                    }
                }
                catch (IOException $e)
                {
                    unset($e);
                }
            }

            // Go through each package
            foreach($packages as $package_iter)
            {
                $package_e = explode('=', $package_iter);
                try
                {
                    $version_entry = $this->getPackageVersion($package_e[0], $package_e[1]);
                    if($version_entry === null)
                    {
                        Console::outWarning('Version ' . $package_e[1] . ' of package ' . $package_e[0] . ' not found');
                    }
                    else
                    {
                        $tree[$package_iter] = null;
                        if(count($version_entry->getDependencies()) > 0)
                        {
                            $tree[$package_iter] = [];
                            foreach($version_entry->getDependencies() as $dependency)
                            {
                                $dependency_name = sprintf('%s=%s', $dependency->getPackageName(), $dependency->getVersion());
                                $tree[$package_iter] = $this->getPackageTree($tree[$package_iter], $dependency_name);
                            }
                        }
                    }
                }
                catch(Exception $e)
                {
                    unset($e);
                }
            }

            return $tree;
        }

        /**
         * Uninstalls a package version
         *
         * @param string $package
         * @param string $version
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         * @throws PackageException
         * @throws PathNotFoundException
         */
        public function uninstallPackageVersion(string $package, string $version): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient permission to uninstall packages');
            }

            $version_entry = $this->getPackageVersion($package, $version);
            if($version_entry === null)
            {
                throw new PackageException(sprintf('The package %s=%s was not found', $package, $version));
            }

            Console::out(sprintf('Uninstalling %s=%s', $package, $version));
            Console::outVerbose(sprintf('Removing package %s=%s from PackageLock', $package, $version));

            if(!$this->getPackageLockManager()?->getPackageLock()?->removePackageVersion($package, $version))
            {
                Console::outDebug('warning: removing package from package lock failed');
            }

            $this->getPackageLockManager()?->save();

            Console::outVerbose('Removing package files');
            $scanner = new DirectoryScanner();
            $filesystem = new Filesystem();

            if($filesystem->exists($version_entry->location))
            {
                Console::outVerbose(sprintf('Removing package files from %s', $version_entry->location));

                /** @var SplFileInfo $item */
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                foreach($scanner($version_entry->location, true) as $item)
                {
                    if(is_file($item->getPath()))
                    {
                        Console::outDebug('removing file ' . $item->getPath());
                        Console::outDebug(sprintf('deleting %s', $item->getPath()));
                        $filesystem->remove($item->getPath());
                    }
                }
            }
            else
            {
                Console::outWarning(sprintf('warning: package location %s does not exist', $version_entry->location));
            }

            $filesystem->remove($version_entry->location);

            if(count($version_entry->getExecutionUnits()) > 0)
            {
                Console::outVerbose('Uninstalling execution units');

                $execution_pointer_manager = new ExecutionPointerManager();
                foreach($version_entry->getExecutionUnits() as $executionUnit)
                {
                    if(!$execution_pointer_manager->removeUnit($package, $version, $executionUnit->getExecutionPolicy()->getName()))
                    {
                        Console::outDebug(sprintf('warning: removing execution unit %s failed', $executionUnit->getExecutionPolicy()->getName()));
                    }
                }
            }

            $symlink_manager = new SymlinkManager();
            $symlink_manager->sync();
        }

        /**
         * Uninstalls all versions of a package
         *
         * @param string $package
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         * @throws PackageException
         */
        public function uninstallPackage(string $package): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient permission to uninstall packages');
            }

            $package_entry = $this->getPackage($package);
            if($package_entry === null)
            {
                throw new PackageException(sprintf('The package %s was not found', $package));
            }

            foreach($package_entry->getVersions() as $version)
            {
                $version_entry = $package_entry->getVersion($version);

                if($version_entry === null)
                {
                    Console::outDebug(sprintf('warning: version %s of package %s not found', $version, $package));
                    continue;
                }

                try
                {
                    $this->uninstallPackageVersion($package, $version_entry->getVersion());
                }
                catch(Exception $e)
                {
                    Console::outDebug(sprintf('warning: unable to uninstall package %s=%s, %s (%s)', $package, $version_entry->getVersion(), $e->getMessage(), $e->getCode()));
                }
            }
        }

        /**
         * @param Package $package
         * @param InstallationPaths $paths
         * @throws OperationException
         */
        private static function initData(Package $package, InstallationPaths $paths): void
        {
            Console::outVerbose(sprintf('Initializing data for %s', $package->getAssembly()->getName()));

            // Create data files
            $dependencies = [];
            foreach($package->getDependencies() as $dependency)
            {
                $dependencies[] = $dependency->toArray(true);
            }

            $data_files = [
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'assembly' => ZiProto::encode($package->getAssembly()->toArray(true)),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'ext' => ZiProto::encode($package->getMetadata()->getCompilerExtension()->toArray()),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'const' => ZiProto::encode($package->getMetadata()->getRuntimeConstants()),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'dependencies' => ZiProto::encode($dependencies),
            ];

            foreach($data_files as $file => $data)
            {
                try
                {
                    Console::outDebug(sprintf('generating data file %s', $file));
                    IO::fwrite($file, $data);
                }
                catch (IOException $e)
                {
                    throw new OperationException('Cannot write to file \'' . $file . '\', ' . $e->getMessage(), $e);
                }
            }
        }

        /**
         * @return PackageLockManager|null
         */
        private function getPackageLockManager(): ?PackageLockManager
        {
            if($this->package_lock_manager === null)
            {
                $this->package_lock_manager = new PackageLockManager();
            }

            return $this->package_lock_manager;
        }

    }