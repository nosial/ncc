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
    use ncc\ZiProto\ZiProto;
    use SplFileInfo;
    use Throwable;

    class PackageManager
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

            if(RuntimeCache::get(sprintf('installed.%s=%s', $package->assembly->package, $package->assembly->version)))
            {
                Console::outDebug(sprintf('skipping installation of %s=%s, already processed', $package->assembly->package, $package->assembly->version));
                return $package->assembly->package;
            }

            $extension = $package->header->CompilerExtension->extension;
            $installation_paths = new InstallationPaths($this->packages_path . DIRECTORY_SEPARATOR . $package->assembly->package . '=' . $package->assembly->version);

            $installer = match ($extension)
            {
                CompilerExtensions::PHP => new PhpInstaller($package),
                default => throw new NotSupportedException(sprintf('Compiler extension %s is not supported with ncc', $extension))
            };

            if($this->getPackageVersion($package->assembly->package, $package->assembly->version) !== null)
            {
                if(in_array(InstallPackageOptions::REINSTALL, $options, true))
                {
                    if($this->getPackageLockManager()?->getPackageLock()?->packageExists($package->assembly->package, $package->assembly->version))
                    {
                        $this->getPackageLockManager()?->getPackageLock()?->removePackageVersion(
                            $package->assembly->package, $package->assembly->version
                        );
                    }
                }
                else
                {
                    throw new PackageException('The package ' . $package->assembly->package . '=' . $package->assembly->version . ' is already installed');
                }
            }

            $execution_pointer_manager = new ExecutionPointerManager();
            PackageCompiler::compilePackageConstants($package, [
                ConstantReferences::INSTALL => $installation_paths
            ]);

            // Process all the required dependencies before installing the package
            if($package->dependencies !== null && count($package->dependencies) > 0 && !in_array(InstallPackageOptions::SKIP_DEPENDENCIES, $options, true))
            {
                foreach($package->dependencies as $dependency)
                {
                    // Uninstall the dependency if the option Reinstall is passed on
                    if(in_array(InstallPackageOptions::REINSTALL, $options, true) && $this->getPackageLockManager()?->getPackageLock()?->packageExists($dependency->name, $dependency->version))
                    {
                        if($dependency->version === null)
                        {
                            $this->uninstallPackage($dependency->name);
                        }
                        else
                        {
                            $this->uninstallPackageVersion($dependency->name, $dependency->version);
                        }
                    }

                    $this->processDependency($dependency, $package, $package_path, $entry, $options);
                }
            }

            Console::outVerbose(sprintf('Installing %s', $package_path));

            if(Resolver::checkLogLevel(LogLevel::DEBUG, Main::getLogLevel()))
            {
                Console::outDebug(sprintf('installer.install_path: %s', $installation_paths->getInstallationPath()));
                Console::outDebug(sprintf('installer.data_path:    %s', $installation_paths->getDataPath()));
                Console::outDebug(sprintf('installer.bin_path:     %s', $installation_paths->getBinPath()));
                Console::outDebug(sprintf('installer.src_path:     %s', $installation_paths->getSourcePath()));

                foreach($package->assembly->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                }

                foreach($package->header->CompilerExtension->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('header.compiler.%s: %s', $prop, ($value ?? 'n/a')));
                }
            }

            Console::out('Installing ' . $package->assembly->package);

            // Four For Directory Creation, preInstall, postInstall & initData methods
            $steps = (4 + count($package->components) + count ($package->resources) + count ($package->execution_units));

            // Include the Execution units
            if($package->installer?->PreInstall !== null)
            {
                $steps += count($package->installer->PreInstall);
            }

            if($package->installer?->PostInstall!== null)
            {
                $steps += count($package->installer->PostInstall);
            }

            $current_steps = 0;
            $filesystem = new Filesystem();

            try
            {
                $filesystem->mkdir($installation_paths->getInstallationPath(), 0755);
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

            if($package->installer?->PreInstall !== null && count($package->installer->PreInstall) > 0)
            {
                foreach($package->installer->PreInstall as $unit_name)
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
            foreach($package->components as $component)
            {
                Console::outDebug(sprintf('processing component %s (%s)', $component->name, $component->data_types));

                try
                {
                    $data = $installer->processComponent($component);
                    if($data !== null)
                    {
                        $component_path = $installation_paths->getSourcePath() . DIRECTORY_SEPARATOR . $component->name;
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
            foreach($package->resources as $resource)
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
            if($package->execution_units !== null && count($package->execution_units) > 0)
            {
                Console::outDebug('package contains execution units, processing');

                $execution_pointer_manager = new ExecutionPointerManager();
                $unit_paths = [];

                /** @var Package\ExecutionUnit $executionUnit */
                foreach($package->execution_units as $executionUnit)
                {
                    Console::outDebug(sprintf('processing execution unit %s', $executionUnit->execution_policy->name));
                    $execution_pointer_manager->addUnit($package->assembly->package, $package->assembly->version, $executionUnit);
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
            if(isset($package->header->Options['create_symlink']) && $package->header->Options['create_symlink'])
            {
                if($package->main_execution_policy === null)
                {
                    throw new OperationException('Cannot create symlink, no main execution policy is defined');
                }

                Console::outDebug(sprintf('creating symlink to %s', $package->assembly->package));

                $SymlinkManager = new SymlinkManager();
                $SymlinkManager->add($package->assembly->package, $package->main_execution_policy);
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

            if($package->installer?->PostInstall !== null && count($package->installer->PostInstall) > 0)
            {
                Console::outDebug('executing post-installation units');

                foreach($package->installer->PostInstall as $unit_name)
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

            if($package->header->UpdateSource !== null && $package->header->UpdateSource->repository !== null)
            {
                $sources_manager = new RemoteSourcesManager();
                if($sources_manager->getRemoteSource($package->header->UpdateSource->repository->name) === null)
                {
                    Console::outVerbose('Adding remote source ' . $package->header->UpdateSource->repository->name);

                    $defined_remote_source = new DefinedRemoteSource();
                    $defined_remote_source->name = $package->header->UpdateSource->repository->name;
                    $defined_remote_source->host = $package->header->UpdateSource->repository->host;
                    $defined_remote_source->type = $package->header->UpdateSource->repository->type;
                    $defined_remote_source->ssl = $package->header->UpdateSource->repository->ssl;

                    $sources_manager->addRemoteSource($defined_remote_source);
                }
            }

            $this->getPackageLockManager()?->getPackageLock()?->addPackage($package, $installation_paths->getInstallationPath());
            $this->getPackageLockManager()?->save();

            RuntimeCache::set(sprintf('installed.%s=%s', $package->assembly->package, $package->assembly->version), true);

            return $package->assembly->package;
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

            if($input->source === null)
            {
                throw new PackageException('No source specified');
            }

            if($input->package === null)
            {
                throw new PackageException('No package specified');
            }

            if($input->version === null)
            {
                $input->version = Versions::LATEST;
            }

            Console::outVerbose('Fetching package ' . $input->package . ' from ' . $input->source . ' (' . $input->version . ')');

            $remote_source_type = Resolver::detectRemoteSourceType($input->source);
            if($remote_source_type === RemoteSourceType::BUILTIN)
            {
                Console::outDebug('using builtin source ' . $input->source);

                if ($input->source === 'composer')
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

                throw new NotSupportedException(sprintf('Builtin source %s is not supported', $input->source));
            }

            if($remote_source_type === RemoteSourceType::DEFINED)
            {
                Console::outDebug('using defined source ' . $input->source);
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $source = (new RemoteSourcesManager())->getRemoteSource($input->source);
                if($source === null)
                {
                    throw new OperationException('Remote source ' . $input->source . ' is not defined');
                }

                $repositoryQueryResults = Functions::getRepositoryQueryResults($input, $source, $entry);
                $exceptions = [];

                if($repositoryQueryResults->Files->ZipballUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->package, $repositoryQueryResults->Files->ZipballUrl));
                        $archive = Functions::downloadGitServiceFile($repositoryQueryResults->Files->ZipballUrl, $entry);
                        return PackageCompiler::tryCompile(Functions::extractArchive($archive), $repositoryQueryResults->Version);
                    }
                    catch(Throwable $e)
                    {
                        Console::outDebug('cannot fetch package from zipball url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->Files->TarballUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->package, $repositoryQueryResults->Files->TarballUrl));
                        $archive = Functions::downloadGitServiceFile($repositoryQueryResults->Files->TarballUrl, $entry);
                        return PackageCompiler::tryCompile(Functions::extractArchive($archive), $repositoryQueryResults->Version);
                    }
                    catch(Exception $e)
                    {
                        Console::outDebug('cannot fetch package from tarball url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->Files->PackageUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->package, $repositoryQueryResults->Files->PackageUrl));
                        return Functions::downloadGitServiceFile($repositoryQueryResults->Files->PackageUrl, $entry);
                    }
                    catch(Exception $e)
                    {
                        Console::outDebug('cannot fetch package from package url, ' . $e->getMessage());
                        $exceptions[] = $e;
                    }
                }

                if($repositoryQueryResults->Files->GitHttpUrl !== null || $repositoryQueryResults->Files->GitSshUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->package, $repositoryQueryResults->Files->GitHttpUrl ?? $repositoryQueryResults->Files->GitSshUrl));
                        $git_repository = GitClient::cloneRepository($repositoryQueryResults->Files->GitHttpUrl ?? $repositoryQueryResults->Files->GitSshUrl);

                        foreach(GitClient::getTags($git_repository) as $tag)
                        {
                            if(VersionComparator::compareVersion($tag, $repositoryQueryResults->Version) === 0)
                            {
                                GitClient::checkout($git_repository, $tag);
                                return PackageCompiler::tryCompile($git_repository, $repositoryQueryResults->Version);
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
         */
        private function processDependency(Dependency $dependency, Package $package, string $package_path, ?Entry $entry=null, array $options=[]): void
        {
            if(RuntimeCache::get(sprintf('dependency_installed.%s=%s', $dependency->name, $dependency->version ?? 'null')))
            {
                Console::outDebug(sprintf('dependency %s=%s already processed, skipping', $dependency->name, $dependency->version ?? 'null'));
                return;
            }

            Console::outVerbose('processing dependency ' . $dependency->name . ' (' . $dependency->version . ')');
            $dependent_package = $this->getPackage($dependency->name);
            $dependency_met = false;

            if ($dependent_package !== null && $dependency->version !== null && Validate::version($dependency->version))
            {
                Console::outDebug('dependency has version constraint, checking if package is installed');
                $dependent_version = $this->getPackageVersion($dependency->name, $dependency->version);
                if ($dependent_version !== null)
                {
                    $dependency_met = true;
                }
            }
            elseif ($dependent_package !== null && $dependency->version === null)
            {
                Console::outDebug(sprintf('dependency %s has no version specified, assuming dependency is met', $dependency->name));
                $dependency_met = true;
            }

            Console::outDebug('dependency met: ' . ($dependency_met ? 'true' : 'false'));

            if ($dependency->source_type !== null && !$dependency_met)
            {
                Console::outVerbose(sprintf('Installing dependency %s=%s for %s=%s', $dependency->name, $dependency->version, $package->assembly->package, $package->assembly->version));
                switch ($dependency->source_type)
                {
                    case DependencySourceType::LOCAL:
                        Console::outDebug('installing from local source ' . $dependency->source);
                        $basedir = dirname($package_path);

                        if (!file_exists($basedir . DIRECTORY_SEPARATOR . $dependency->source))
                        {
                            throw new PathNotFoundException($basedir . DIRECTORY_SEPARATOR . $dependency->source);
                        }

                        $this->install($basedir . DIRECTORY_SEPARATOR . $dependency->source, null, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->name, $dependency->version), true);
                        break;

                    case DependencySourceType::STATIC:
                        throw new PackageException('Static linking not possible, package ' . $dependency->name . ' is not installed');

                    case DependencySourceType::REMOTE:
                        Console::outDebug('installing from remote source ' . $dependency->source);
                        $this->installFromSource($dependency->source, $entry, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->name, $dependency->version), true);
                        break;

                    default:
                        throw new NotSupportedException(sprintf('Dependency source type %s is not supported', $dependency->source_type));
                }
            }
            elseif(!$dependency_met)
            {
                throw new PackageException(sprintf('Required dependency %s=%s is not installed', $dependency->name, $dependency->version));
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
            return $this->getPackageLockManager()?->getPackageLock()?->getPackages() ?? [];
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

                    foreach ($version->Dependencies as $dependency)
                    {
                        if(!in_array($dependency->PackageName . '=' . $dependency->Version, $tree, true))
                        {
                            $packages[] = $dependency->PackageName . '=' . $dependency->Version;
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
                        if($version_entry->Dependencies !== null && count($version_entry->Dependencies) > 0)
                        {
                            $tree[$package_iter] = [];
                            foreach($version_entry->Dependencies as $dependency)
                            {
                                $dependency_name = sprintf('%s=%s', $dependency->PackageName, $dependency->Version);
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

            if($filesystem->exists($version_entry->Location))
            {
                Console::outVerbose(sprintf('Removing package files from %s', $version_entry->Location));

                /** @var SplFileInfo $item */
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                foreach($scanner($version_entry->Location, true) as $item)
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
                Console::outWarning(sprintf('warning: package location %s does not exist', $version_entry->Location));
            }

            $filesystem->remove($version_entry->Location);

            if($version_entry->ExecutionUnits !== null && count($version_entry->ExecutionUnits) > 0)
            {
                Console::outVerbose('Uninstalling execution units');

                $execution_pointer_manager = new ExecutionPointerManager();
                foreach($version_entry->ExecutionUnits as $executionUnit)
                {
                    if(!$execution_pointer_manager->removeUnit($package, $version, $executionUnit->execution_policy->name))
                    {
                        Console::outDebug(sprintf('warning: removing execution unit %s failed', $executionUnit->execution_policy->name));
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
                    $this->uninstallPackageVersion($package, $version_entry->Version);
                }
                catch(Exception $e)
                {
                    Console::outDebug(sprintf('warning: unable to uninstall package %s=%s, %s (%s)', $package, $version_entry->Version, $e->getMessage(), $e->getCode()));
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
            Console::outVerbose(sprintf('Initializing data for %s', $package->assembly->name));

            // Create data files
            $dependencies = [];
            foreach($package->dependencies as $dependency)
            {
                $dependencies[] = $dependency->toArray(true);
            }

            $data_files = [
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'assembly' =>
                    ZiProto::encode($package->assembly->toArray(true)),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'ext' =>
                    ZiProto::encode($package->header->CompilerExtension->toArray()),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'const' =>
                    ZiProto::encode($package->header->RuntimeConstants),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'dependencies' =>
                    ZiProto::encode($dependencies),
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