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
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\ConstantReferences;
    use ncc\Abstracts\DependencySourceType;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Options\InstallPackageOptions;
    use ncc\Abstracts\RemoteSourceType;
    use ncc\Abstracts\Scopes;
    use ncc\Abstracts\Versions;
    use ncc\Classes\ComposerExtension\ComposerSourceBuiltin;
    use ncc\Classes\GitClient;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Classes\PhpExtension\PhpInstaller;
    use ncc\CLI\Main;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InstallationException;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MissingDependencyException;
    use ncc\Exceptions\NotImplementedException;
    use ncc\Exceptions\PackageAlreadyInstalledException;
    use ncc\Exceptions\PackageFetchException;
    use ncc\Exceptions\PackageLockException;
    use ncc\Exceptions\PackageNotFoundException;
    use ncc\Exceptions\PackageParsingException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\SymlinkException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\VersionNotFoundException;
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
         * @param string $package_path
         * @param Entry|null $entry
         * @param array $options
         * @return string
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InstallationException
         * @throws InvalidPackageNameException
         * @throws InvalidScopeException
         * @throws MissingDependencyException
         * @throws NotImplementedException
         * @throws PackageAlreadyInstalledException
         * @throws PackageLockException
         * @throws PackageNotFoundException
         * @throws PackageParsingException
         * @throws RunnerExecutionException
         * @throws SymlinkException
         * @throws UnsupportedCompilerExtensionException
         * @throws VersionNotFoundException
         */
        public function install(string $package_path, ?Entry $entry=null, array $options=[]): string
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permission to install packages');

            if(!file_exists($package_path) || !is_file($package_path) || !is_readable($package_path))
                throw new FileNotFoundException('The specified file \'' . $package_path .' \' does not exist or is not readable.');

            $package = Package::load($package_path);

            if(RuntimeCache::get(sprintf('installed.%s=%s', $package->Assembly->Package, $package->Assembly->Version)))
            {
                Console::outDebug(sprintf('skipping installation of %s=%s, already processed', $package->Assembly->Package, $package->Assembly->Version));
                return $package->Assembly->Package;
            }

            $extension = $package->Header->CompilerExtension->Extension;
            $installation_paths = new InstallationPaths($this->PackagesPath . DIRECTORY_SEPARATOR . $package->Assembly->Package . '=' . $package->Assembly->Version);

            $installer = match ($extension)
            {
                CompilerExtensions::PHP => new PhpInstaller($package),
                default => throw new UnsupportedCompilerExtensionException('The compiler extension \'' . $extension . '\' is not supported'),
            };

            if($this->getPackageVersion($package->Assembly->Package, $package->Assembly->Version) !== null)
            {
                if(in_array(InstallPackageOptions::Reinstall, $options))
                {
                    if($this->getPackageLockManager()->getPackageLock()->packageExists(
                        $package->Assembly->Package, $package->Assembly->Version
                    ))
                    {
                        $this->getPackageLockManager()->getPackageLock()->removePackageVersion(
                            $package->Assembly->Package, $package->Assembly->Version
                        );
                    }
                }
                else
                {
                    throw new PackageAlreadyInstalledException('The package ' . $package->Assembly->Package . '=' . $package->Assembly->Version . ' is already installed');
                }
            }

            $execution_pointer_manager = new ExecutionPointerManager();
            PackageCompiler::compilePackageConstants($package, [
                ConstantReferences::Install => $installation_paths
            ]);

            // Process all the required dependencies before installing the package
            if($package->Dependencies !== null && count($package->Dependencies) > 0 && !in_array(InstallPackageOptions::SkipDependencies, $options))
            {
                foreach($package->Dependencies as $dependency)
                {
                    if(in_array(InstallPackageOptions::Reinstall, $options))
                    {
                        // Uninstall the dependency if the option Reinstall is passed on
                        if($this->getPackageLockManager()->getPackageLock()->packageExists($dependency->Name, $dependency->Version))
                        {
                            if($dependency->Version == null)
                            {
                                $this->uninstallPackage($dependency->Name);
                            }
                            else
                            {
                                $this->uninstallPackageVersion($dependency->Name, $dependency->Version);
                            }
                        }
                    }

                    $this->processDependency($dependency, $package, $package_path, $entry, $options);
                }
            }

            Console::outVerbose(sprintf('Installing %s', $package_path));

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
            if($package->ExecutionUnits !== null && count($package->ExecutionUnits) > 0)
            {
                $execution_pointer_manager = new ExecutionPointerManager();
                $unit_paths = [];

                /** @var Package\ExecutionUnit $executionUnit */
                foreach($package->ExecutionUnits as $executionUnit)
                {
                    Console::outDebug(sprintf('processing execution unit %s', $executionUnit->ExecutionPolicy->Name));
                    $execution_pointer_manager->addUnit($package->Assembly->Package, $package->Assembly->Version, $executionUnit);
                    $current_steps += 1;
                    Console::inlineProgressBar($current_steps, $steps);
                }

                IO::fwrite($installation_paths->getDataPath() . DIRECTORY_SEPARATOR . 'exec', ZiProto::encode($unit_paths));
            }

            // After execution units are installed, create a symlink if needed
            if(isset($package->Header->Options['create_symlink']) && $package->Header->Options['create_symlink'])
            {
                if($package->MainExecutionPolicy === null)
                    throw new InstallationException('Cannot create symlink, no main execution policy is defined');

                $SymlinkManager = new SymlinkManager();
                $SymlinkManager->add($package->Assembly->Package, $package->MainExecutionPolicy);
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

            if($package->Header->UpdateSource !== null && $package->Header->UpdateSource->Repository !== null)
            {
                $sources_manager = new RemoteSourcesManager();
                if($sources_manager->getRemoteSource($package->Header->UpdateSource->Repository->Name) === null)
                {
                    Console::outVerbose('Adding remote source ' . $package->Header->UpdateSource->Repository->Name);
                    $defined_remote_source = new DefinedRemoteSource();
                    $defined_remote_source->Name = $package->Header->UpdateSource->Repository->Name;
                    $defined_remote_source->Host = $package->Header->UpdateSource->Repository->Host;
                    $defined_remote_source->Type = $package->Header->UpdateSource->Repository->Type;
                    $defined_remote_source->SSL = $package->Header->UpdateSource->Repository->SSL;

                    $sources_manager->addRemoteSource($defined_remote_source);
                }
            }

            $this->getPackageLockManager()->getPackageLock()->addPackage($package, $installation_paths->getInstallationPath());
            $this->getPackageLockManager()->save();

            RuntimeCache::set(sprintf('installed.%s=%s', $package->Assembly->Package, $package->Assembly->Version), true);

            return $package->Assembly->Package;
        }

        /**
         * @param string $source
         * @param Entry|null $entry
         * @return string
         * @throws InstallationException
         * @throws NotImplementedException
         * @throws PackageFetchException
         */
        public function fetchFromSource(string $source, ?Entry $entry=null): string
        {
            $input = new RemotePackageInput($source);

            if($input->Source == null)
                throw new PackageFetchException('No source specified');
            if($input->Package == null)
                throw new PackageFetchException('No package specified');
            if($input->Version == null)
                $input->Version = Versions::Latest;

            Console::outVerbose('Fetching package ' . $input->Package . ' from ' . $input->Source . ' (' . $input->Version . ')');

            $remote_source_type = Resolver::detectRemoteSourceType($input->Source);
            if($remote_source_type == RemoteSourceType::Builtin)
            {
                Console::outDebug('using builtin source ' . $input->Source);
                switch($input->Source)
                {
                    case 'composer':
                        try
                        {
                            return ComposerSourceBuiltin::fetch($input);
                        }
                        catch(Exception $e)
                        {
                            throw new PackageFetchException('Cannot fetch package from composer source, ' . $e->getMessage(), $e);
                        }

                    default:
                        throw new NotImplementedException('Builtin source type ' . $input->Source . ' is not implemented');
                }
            }

            if($remote_source_type == RemoteSourceType::Defined)
            {
                Console::outDebug('using defined source ' . $input->Source);
                $remote_source_manager = new RemoteSourcesManager();
                $source = $remote_source_manager->getRemoteSource($input->Source);
                if($source == null)
                    throw new InstallationException('Remote source ' . $input->Source . ' is not defined');

                $repositoryQueryResults = Functions::getRepositoryQueryResults($input, $source, $entry);
                $exceptions = [];

                if($repositoryQueryResults->Files->ZipballUrl !== null)
                {
                    try
                    {
                        Console::outDebug(sprintf('fetching package %s from %s', $input->Package, $repositoryQueryResults->Files->ZipballUrl));
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
                        Console::outDebug(sprintf('fetching package %s from %s', $input->Package, $repositoryQueryResults->Files->TarballUrl));
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
                        Console::outDebug(sprintf('fetching package %s from %s', $input->Package, $repositoryQueryResults->Files->PackageUrl));
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
                        Console::outDebug(sprintf('fetching package %s from %s', $input->Package, $repositoryQueryResults->Files->GitHttpUrl ?? $repositoryQueryResults->Files->GitSshUrl));
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
                        if($exception == null)
                        {
                            $exception = new PackageFetchException($e->getMessage(), $e);
                        }
                        else
                        {
                            if($e->getMessage() == $exception->getMessage())
                                continue;

                            $exception = new PackageFetchException($e->getMessage(), $exception);
                        }
                    }
                }
                else
                {
                    $exception = new PackageFetchException('Cannot fetch package from remote source, no assets found');
                }

                throw $exception;
            }

            throw new PackageFetchException(sprintf('Unknown remote source type %s', $remote_source_type));
        }

        /**
         * Installs a package from a source syntax (vendor/package=version@source)
         *
         * @param string $source
         * @param Entry|null $entry
         * @param array $options
         * @return string
         * @throws InstallationException
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
                throw new InstallationException('Cannot install package from source, ' . $e->getMessage(), $e);
            }
        }

        /**
         * @param Dependency $dependency
         * @param Package $package
         * @param string $package_path
         * @param Entry|null $entry
         * @param array $options
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InstallationException
         * @throws InvalidPackageNameException
         * @throws InvalidScopeException
         * @throws MissingDependencyException
         * @throws NotImplementedException
         * @throws PackageAlreadyInstalledException
         * @throws PackageLockException
         * @throws PackageNotFoundException
         * @throws PackageParsingException
         * @throws RunnerExecutionException
         * @throws SymlinkException
         * @throws UnsupportedCompilerExtensionException
         * @throws VersionNotFoundException
         */
        private function processDependency(Dependency $dependency, Package $package, string $package_path, ?Entry $entry=null, array $options=[]): void
        {
            if(RuntimeCache::get(sprintf('depndency_installed.%s=%s', $dependency->Name, $dependency->Version ?? 'null')))
            {
                Console::outDebug(sprintf('dependency %s=%s already processed, skipping', $dependency->Name, $dependency->Version ?? 'null'));
                return;
            }

            Console::outVerbose('processing dependency ' . $dependency->Name . ' (' . $dependency->Version . ')');
            $dependent_package = $this->getPackage($dependency->Name);
            $dependency_met = false;

            if ($dependent_package !== null && $dependency->Version !== null && Validate::version($dependency->Version))
            {
                Console::outDebug('dependency has version constraint, checking if package is installed');
                $dependent_version = $this->getPackageVersion($dependency->Name, $dependency->Version);
                if ($dependent_version !== null)
                    $dependency_met = true;
            }
            elseif ($dependent_package !== null && $dependency->Version == null)
            {
                Console::outDebug(sprintf('dependency %s has no version specified, assuming dependency is met', $dependency->Name));
                $dependency_met = true;
            }

            Console::outDebug('dependency met: ' . ($dependency_met ? 'true' : 'false'));

            if ($dependency->SourceType !== null && !$dependency_met)
            {
                Console::outVerbose(sprintf('Installing dependency %s=%s for %s=%s', $dependency->Name, $dependency->Version, $package->Assembly->Package, $package->Assembly->Version));
                switch ($dependency->SourceType)
                {
                    case DependencySourceType::Local:
                        Console::outDebug('installing from local source ' . $dependency->Source);
                        $basedir = dirname($package_path);
                        if (!file_exists($basedir . DIRECTORY_SEPARATOR . $dependency->Source))
                            throw new FileNotFoundException($basedir . DIRECTORY_SEPARATOR . $dependency->Source);
                        $this->install($basedir . DIRECTORY_SEPARATOR . $dependency->Source, null, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->Name, $dependency->Version), true);
                        break;

                    case DependencySourceType::StaticLinking:
                        throw new PackageNotFoundException('Static linking not possible, package ' . $dependency->Name . ' is not installed');

                    case DependencySourceType::RemoteSource:
                        Console::outDebug('installing from remote source ' . $dependency->Source);
                        $this->installFromSource($dependency->Source, $entry, $options);
                        RuntimeCache::set(sprintf('dependency_installed.%s=%s', $dependency->Name, $dependency->Version), true);
                        break;

                    default:
                        throw new NotImplementedException('Dependency source type ' . $dependency->SourceType . ' is not implemented');
                }
            }
            elseif(!$dependency_met)
            {
                throw new MissingDependencyException(sprintf('The dependency %s=%s for %s=%s is not met', $dependency->Name, $dependency->Version, $package->Assembly->Package, $package->Assembly->Version));
            }
        }

        /**
         * Returns an existing package entry, returns null if no such entry exists
         *
         * @param string $package
         * @return PackageEntry|null
         * @throws PackageLockException
         */
        public function getPackage(string $package): ?PackageEntry
        {
            Console::outDebug('getting package ' . $package);
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
            Console::outDebug('getting package version ' . $package . '=' . $version);
            return $this->getPackage($package)?->getVersion($version);
        }

        /**
         * Returns the latest version of the package, or null if there is no entry
         *
         * @param string $package
         * @return VersionEntry|null
         * @throws VersionNotFoundException
         * @throws PackageLockException
         * @noinspection PhpUnused
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
         * @throws PackageLockException
         * @throws PackageLockException
         */
        public function getInstalledPackages(): array
        {
            return $this->getPackageLockManager()->getPackageLock()->getPackages();
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
                    $package = $this->getPackage($exploded[0]);
                    if($package == null)
                        throw new PackageNotFoundException('Package ' . $exploded[0] . ' not found');

                    $version = $package->getVersion($exploded[1]);
                    if($version == null)
                        throw new VersionNotFoundException('Version ' . $exploded[1] . ' not found for package ' . $exploded[0]);

                    foreach ($version->Dependencies as $dependency)
                    {
                        if(!in_array($dependency->PackageName . '=' . $dependency->Version, $tree))
                            $packages[] = $dependency->PackageName . '=' . $dependency->Version;
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
                            if (!in_array($installed_package . '=' . $version, $packages))
                                $packages[] = $installed_package . '=' . $version;
                        }
                    }
                }
                catch (PackageLockException $e)
                {
                    unset($e);
                }
            }

            // Go through each package
            foreach($packages as $package)
            {
                $package_e = explode('=', $package);
                try
                {
                    $version_entry = $this->getPackageVersion($package_e[0], $package_e[1]);
                    if($version_entry == null)
                    {
                        Console::outWarning('Version ' . $package_e[1] . ' of package ' . $package_e[0] . ' not found');
                    }
                    else
                    {
                        $tree[$package] = null;
                        if($version_entry->Dependencies !== null && count($version_entry->Dependencies) > 0)
                        {
                            $tree[$package] = [];
                            foreach($version_entry->Dependencies as $dependency)
                            {
                                $dependency_name = sprintf('%s=%s', $dependency->PackageName, $dependency->Version);
                                $tree[$package] = $this->getPackageTree($tree[$package], $dependency_name);
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws PackageLockException
         * @throws PackageNotFoundException
         * @throws SymlinkException
         * @throws VersionNotFoundException
         */
        public function uninstallPackageVersion(string $package, string $version): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permission to uninstall packages');

            $version_entry = $this->getPackageVersion($package, $version);
            if($version_entry == null)
                throw new PackageNotFoundException(sprintf('The package %s=%s was not found', $package, $version));

            Console::out(sprintf('Uninstalling %s=%s', $package, $version));
            Console::outVerbose(sprintf('Removing package %s=%s from PackageLock', $package, $version));
            if(!$this->getPackageLockManager()->getPackageLock()->removePackageVersion($package, $version))
                Console::outDebug('warning: removing package from package lock failed');

            $this->getPackageLockManager()->save();

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
                    if(!$execution_pointer_manager->removeUnit($package, $version, $executionUnit->ExecutionPolicy->Name))
                        Console::outDebug(sprintf('warning: removing execution unit %s failed', $executionUnit->ExecutionPolicy->Name));
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
                    Console::outDebug(sprintf('warning: unable to uninstall package %s=%s, %s (%s)', $package, $version_entry->Version, $e->getMessage(), $e->getCode()));
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
            Console::outVerbose(sprintf('Initializing data for %s', $package->Assembly->Name));

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
                    ZiProto::encode($package->Header->CompilerExtension->toArray()),
                $paths->getDataPath() . DIRECTORY_SEPARATOR . 'const' =>
                    ZiProto::encode($package->Header->RuntimeConstants),
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