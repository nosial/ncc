<?php

    namespace ncc\Classes\ComposerExtension;

    use Exception;
    use FilesystemIterator;
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\CompilerExtensionSupportedVersions;
    use ncc\Abstracts\ComponentFileExtensions;
    use ncc\Abstracts\DependencySourceType;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Scopes;
    use ncc\CLI\Main;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ComposerDisabledException;
    use ncc\Exceptions\ComposerException;
    use ncc\Exceptions\ComposerNotAvailableException;
    use ncc\Exceptions\DirectoryNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InternalComposerNotAvailableException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\PackageNotFoundException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Exceptions\ProjectConfigurationNotFoundException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Exceptions\UserAbortedOperationException;
    use ncc\Interfaces\RemoteSourceInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\ComposerLock;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\RemotePackageInput;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\RuntimeCache;
    use SplFileInfo;

    class ComposerSource implements RemoteSourceInterface
    {
        /**
         * Attempts to acquire the package from the composer repository and
         * convert all composer packages to standard NCC packages by generating
         * a package.json file and building all the required packages and dependencies.
         *
         * Returns the requested package, note that all the dependencies (if any) are also
         * in the same directory as the requested package and are referenced as local
         * packages that ncc can use to install the main package.
         *
         * @param RemotePackageInput $packageInput
         * @return string
         * @throws AccessDeniedException
         * @throws BuildConfigurationNotFoundException
         * @throws BuildException
         * @throws ComposerDisabledException
         * @throws ComposerException
         * @throws ComposerNotAvailableException
         * @throws DirectoryNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InternalComposerNotAvailableException
         * @throws InvalidScopeException
         * @throws MalformedJsonException
         * @throws PackageNotFoundException
         * @throws PackagePreparationFailedException
         * @throws ProjectConfigurationNotFoundException
         * @throws RuntimeException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedRunnerException
         * @throws UserAbortedOperationException
         */
        public static function fetch(RemotePackageInput $packageInput): string
        {
            $package_path = self::require($packageInput->Vendor, $packageInput->Package, $packageInput->Version);
            $packages = self::compilePackages($package_path . DIRECTORY_SEPARATOR . 'composer.lock');
            RuntimeCache::setFileAsTemporary($package_path);
            $real_package_name = explode('=', $packageInput->toStandard(false))[0];
            foreach($packages as $package => $path)
            {
                if(explode('=', $package)[0] == $real_package_name)
                    return $path;
            }

            throw new RuntimeException(sprintf('Could not find package %s in the compiled packages', $packageInput->toStandard()));
        }

        /**
         * @param string $composer_lock_path
         * @return array
         * @throws AccessDeniedException
         * @throws BuildConfigurationNotFoundException
         * @throws BuildException
         * @throws DirectoryNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PackageNotFoundException
         * @throws PackagePreparationFailedException
         * @throws ProjectConfigurationNotFoundException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedRunnerException
         */
        private static function compilePackages(string $composer_lock_path): array
        {
            if (!file_exists($composer_lock_path))
            {
                throw new FileNotFoundException($composer_lock_path);
            }

            $base_dir = dirname($composer_lock_path);
            $composer_lock = ComposerLock::fromArray(json_decode(IO::fread($composer_lock_path), true));
            $filesystem = new Filesystem();
            $built_packages = [];

            if ($filesystem->exists($base_dir . DIRECTORY_SEPARATOR . 'build'))
            {
                $filesystem->remove($base_dir . DIRECTORY_SEPARATOR . 'build');
            }

            $filesystem->mkdir($base_dir . DIRECTORY_SEPARATOR . 'build');

            foreach ($composer_lock->Packages as $package)
            {
                $package_path = $base_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $package->Name;
                // Generate the package configuration
                $project_configuration = ComposerSource::generateProjectConfiguration($package->Name, $composer_lock);

                // Process the source files
                if ($package->Autoload !== null)
                {
                    $source_directory = $package_path . DIRECTORY_SEPARATOR . '.src';
                    if ($filesystem->exists($source_directory))
                    {
                        $filesystem->remove($source_directory);
                    }
                    $filesystem->mkdir($source_directory);
                    $source_directories = [];
                    $static_files = [];

                    // TODO: Implement static files handling

                    // Extract all the source directories
                    if ($package->Autoload->Psr4 !== null && count($package->Autoload->Psr4) > 0)
                    {
                        Console::outVerbose('Extracting PSR-4 source directories');
                        foreach ($package->Autoload->Psr4 as $namespace_pointer)
                        {
                            if ($namespace_pointer->Path !== null && !in_array($namespace_pointer->Path, $source_directories))
                            {
                                $source_directories[] = $package_path . DIRECTORY_SEPARATOR . $namespace_pointer->Path;
                            }
                        }
                    }

                    if ($package->Autoload->Psr0 !== null && count($package->Autoload->Psr0) > 0)
                    {
                        Console::outVerbose('Extracting PSR-0 source directories');
                        foreach ($package->Autoload->Psr0 as $namespace_pointer)
                        {
                            if ($namespace_pointer->Path !== null && !in_array($namespace_pointer->Path, $source_directories))
                            {
                                $source_directories[] = $package_path . DIRECTORY_SEPARATOR . $namespace_pointer->Path;
                            }
                        }
                    }

                    if ($package->Autoload->Files !== null && count($package->Autoload->Files) > 0)
                    {
                        Console::outVerbose('Extracting static files');
                        foreach ($package->Autoload->Files as $file)
                        {
                            $static_files[] = $package_path . DIRECTORY_SEPARATOR . $file;
                        }
                    }

                    Console::outDebug(sprintf('source directories: %s', implode(', ', $source_directories)));

                    // First scan the project files and create a file struct.
                    $DirectoryScanner = new DirectoryScanner();

                    // TODO: Implement exclude-class handling
                    try
                    {
                        $DirectoryScanner->unsetFlag(FilesystemIterator::FOLLOW_SYMLINKS);
                    }
                    catch (Exception $e)
                    {
                        throw new PackagePreparationFailedException('Cannot unset flag \'FOLLOW_SYMLINKS\' in DirectoryScanner, ' . $e->getMessage(), $e);
                    }

                    // Include file components that can be compiled
                    $DirectoryScanner->setIncludes(ComponentFileExtensions::Php);

                    foreach ($source_directories as $directory)
                    {
                        /** @var SplFileInfo $item */
                        /** @noinspection PhpRedundantOptionalArgumentInspection */
                        foreach ($DirectoryScanner($directory, True) as $item)
                        {
                            if (is_dir($item->getPathName()))
                                continue;

                            $parsed_path = str_ireplace($package_path . DIRECTORY_SEPARATOR, '', $item->getPathName());

                            Console::outDebug(sprintf('copying file %s for package %s', $parsed_path, $package->Name));
                            $filesystem->copy($item->getPathName(), $source_directory . DIRECTORY_SEPARATOR . $parsed_path);
                        }
                    }

                    if (count($static_files) > 0)
                    {
                        $project_configuration->Project->Options['static_files'] = $static_files;
                        $parsed_path = str_ireplace($package_path . DIRECTORY_SEPARATOR, '', $item->getPathName());
                        if (!$filesystem->exists($source_directory . DIRECTORY_SEPARATOR . $parsed_path))
                        {
                            Console::outDebug(sprintf('copying file %s for package %s', $parsed_path, $package->Name));
                            $filesystem->copy($item->getPathName(), $source_directory . DIRECTORY_SEPARATOR . $parsed_path);
                        }
                    }

                    $project_configuration->toFile($package_path . DIRECTORY_SEPARATOR . 'project.json');
                }

                // Load the project
                $project_manager = new ProjectManager($package_path);
                $project_manager->load();
                $built_package = $project_manager->build();

                // Copy the project to the build directory
                $out_path = $base_dir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . sprintf('%s=%s.ncc', $project_configuration->Assembly->Package, $project_configuration->Assembly->Version);
                $filesystem->copy($built_package, $out_path);
                $built_packages[$project_configuration->Assembly->Package] = $out_path;

            }

            return $built_packages;
        }

        /**
         * Converts a composer package name to a valid package name
         *
         * @param string $input
         * @return string|null
         */
        private static function toPackageName(string $input): ?string
        {
            if (stripos($input, ':'))
            {
                $input = explode(':', $input)[0];
            }

            $parsed_input = explode("/", $input);
            if (count($parsed_input) == 2)
            {
                return str_ireplace(
                    "-", "_", 'com.' . $parsed_input[0] . "." . $parsed_input[1]
                );
            }

            return null;
        }

        /**
         * Generates a project configuration from a package selection
         * from the composer.lock file
         *
         * @param string $package_name
         * @param ComposerLock $composer_lock
         * @return ProjectConfiguration
         * @throws PackageNotFoundException
         */
        private static function generateProjectConfiguration(string $package_name, ComposerLock $composer_lock): ProjectConfiguration
        {
            // Load the composer lock file
            $composer_package = $composer_lock->getPackage($package_name);
            if ($composer_package == null)
                throw new PackageNotFoundException(sprintf('Package "%s" not found in composer lock file', $package_name));

            // Generate a new project configuration object
            $project_configuration = new ProjectConfiguration();

            if (isset($composer_package->Name))
                $project_configuration->Assembly->Name = $composer_package->Name;
            if (isset($composer_package->Description))
                $project_configuration->Assembly->Description = $composer_package->Description;
            if (isset($composer_package->Version))
                $project_configuration->Assembly->Version = Functions::parseVersion($composer_package->Version);

            $project_configuration->Assembly->UUID = Uuid::v1()->toRfc4122();
            $project_configuration->Assembly->Package = self::toPackageName($package_name);

            // Process the dependencies
            foreach ($composer_package->Require as $item)
            {
                $package_name = self::toPackageName($item->PackageName);
                $package_version = $composer_lock->getPackage($item->PackageName)?->Version;
                if ($package_version == null)
                {
                    $package_version = '1.0.0';
                }
                else
                {
                    $package_version = Functions::parseVersion($package_version);
                }
                if ($package_name == null)
                    continue;
                $dependency = new ProjectConfiguration\Dependency();
                $dependency->Name = $package_name;
                $dependency->SourceType = DependencySourceType::Local;
                $dependency->Version = $package_version;
                $dependency->Source = $package_name . '=' . $dependency->Version . '.ncc';
                $project_configuration->Build->Dependencies[] = $dependency;
            }

            // Create a build configuration
            $build_configuration = new ProjectConfiguration\BuildConfiguration();
            $build_configuration->Name = 'default';
            $build_configuration->OutputPath = 'build';

            // Apply the final properties
            $project_configuration->Build->Configurations[] = $build_configuration;
            $project_configuration->Build->DefaultConfiguration = 'default';
            $project_configuration->Build->SourcePath = '.src';

            // Apply compiler extension
            $project_configuration->Project->Compiler->Extension = CompilerExtensions::PHP;
            $project_configuration->Project->Compiler->MinimumVersion = CompilerExtensionSupportedVersions::PHP[0];
            $project_configuration->Project->Compiler->MaximumVersion = CompilerExtensionSupportedVersions::PHP[(count(CompilerExtensionSupportedVersions::PHP) - 1)];

            return $project_configuration;
        }

        /**
         * Extracts a version if available from the input
         *
         * @param string $input
         * @return string|null
         */
        private static function extractVersion(string $input): ?string
        {
            if (stripos($input, ':'))
            {
                return explode(':', $input)[1];
            }

            return null;
        }

        /**
         * Gets the applicable options configured for composer
         *
         * @return array
         */
        private static function getOptions(): array
        {
            $options = Functions::getConfigurationProperty('composer.options');
            if ($options == null || !is_array($options))
                return [];

            $results = [];
            if (isset($options['quiet']) && $options['quiet'])
                $results[] = '--quiet';
            if (isset($options['no_asni']) && $options['no_asni'])
                $results[] = '--no-asni';
            if (isset($options['no_interaction']) && $options['no_interaction'])
                $results[] = '--no-interaction';
            if (isset($options['profile']) && $options['profile'])
                $results[] = '--profile';
            if (isset($options['no_scripts']) && $options['no_scripts']) {
                $results[] = '--no-scripts';
                $results[] = '--no-plugins'; // Also include this for safe measures
            }
            if (isset($options['no_cache']) && $options['no_cache'])
                $results[] = '--no-cache';

            // Determine the logging option
            if (isset($options['logging']))
            {
                if ((int)$options['logging'] == 3)
                {
                    $results[] = '-vvv';
                }
                elseif ((int)$options['logging'] == 2)
                {
                    $results[] = '-vv';
                }
                elseif ((int)$options['logging'] == 1)
                {
                    $results[] = '-v';
                }
                else
                {
                    switch (Main::getLogLevel())
                    {
                        default:
                        case LogLevel::Fatal:
                        case LogLevel::Warning:
                        case LogLevel::Error:
                        case LogLevel::Info:
                            $results[] = '-v';
                            break;

                        case LogLevel::Verbose:
                            $results[] = '-vv';
                            break;

                        case LogLevel::Debug:
                            $results[] = '-vvv';
                            break;

                        case LogLevel::Silent:
                            if (!in_array('--quiet', $results))
                                $results[] = '--quiet';
                            break;
                    }
                }
            }

            return $results;
        }

        /**
         * Uses composer's require command to temporarily create a
         * composer.json file and install the specified package
         *
         * @param string $vendor
         * @param string $package
         * @param string|null $version
         * @param array $options
         * @return string
         * @throws AccessDeniedException
         * @throws ComposerDisabledException
         * @throws ComposerException
         * @throws ComposerNotAvailableException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InternalComposerNotAvailableException
         * @throws InvalidScopeException
         * @throws UserAbortedOperationException
         */
        private static function require(string $vendor, string $package, ?string $version = null, array $options = []): string
        {
            if (Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Insufficient permissions to require');

            if ($version == null)
                $version = '*';

            $tpl_file = __DIR__ . DIRECTORY_SEPARATOR . 'composer.jtpl';
            if (!file_exists($tpl_file))
                throw new FileNotFoundException($tpl_file);

            $composer_exec = self::getComposerPath();

            $template = IO::fread($tpl_file);
            $template = str_ireplace('%VENDOR%', $vendor, $template);
            $template = str_ireplace('%PACKAGE%', $package, $template);
            $template = str_ireplace('%VERSION%', $version, $template);

            $filesystem = new Filesystem();
            $tmp_dir = PathFinder::getCachePath(Scopes::System) . DIRECTORY_SEPARATOR . hash('haval128,3', $template);
            $composer_json_path = $tmp_dir . DIRECTORY_SEPARATOR . 'composer.json';
            if ($filesystem->exists($tmp_dir)) {
                Console::outVerbose(sprintf('Deleting already existing %s', $tmp_dir));
                $filesystem->remove($tmp_dir);
            }

            $filesystem->mkdir($tmp_dir);
            IO::fwrite($composer_json_path, $template, 0777);

            // Execute composer with options
            $options = self::getOptions();
            $process = new Process(array_merge([$composer_exec, 'require'], $options));
            $process->setWorkingDirectory($tmp_dir);

            // Check if scripts are enabled while running as root
            if (!in_array('--no-scripts', $options) && Resolver::resolveScope() == Scopes::System)
            {
                Console::outWarning('composer scripts are enabled while running as root, this can allow malicious scripts to run as root');
                if (!isset($options['--no-interaction']))
                {
                    if (!Console::getBooleanInput('Do you want to continue?'))
                        throw new UserAbortedOperationException('The operation was aborted by the user');

                    // The user understands the risks and wants to continue
                    $process->setEnv(['COMPOSER_ALLOW_SUPERUSER' => 1]);
                }
            }
            else
            {
                // Composer is running "safely". We can disable the superuser check
                $process->setEnv(['COMPOSER_ALLOW_SUPERUSER' => 1]);
            }

            Console::outDebug(sprintf('executing %s', $process->getCommandLine()));
            $process->run(function ($type, $buffer) {
                Console::out($buffer, false);
            });

            if (!$process->isSuccessful())
                throw new ComposerException($process->getErrorOutput());

            return $tmp_dir;
        }

        /**
         * Attempts to find the composer path to use that is currently configured
         *
         * @return string
         * @throws ComposerDisabledException
         * @throws ComposerNotAvailableException
         * @throws InternalComposerNotAvailableException
         */
        private static function getComposerPath(): string
        {
            Console::outVerbose(sprintf('Getting composer path for %s', Functions::getConfigurationProperty('composer.path')));

            $composer_enabled = Functions::getConfigurationProperty('composer.enabled');
            if ($composer_enabled !== null && $composer_enabled === false)
                throw new ComposerDisabledException('Composer is disabled by the configuration `composer.enabled`');

            $config_property = Functions::getConfigurationProperty('composer.executable_path');

            Console::outDebug(sprintf('composer.enabled = %s', ($composer_enabled ?? 'n/a')));
            Console::outDebug(sprintf('composer.executable_path = %s', ($config_property ?? 'n/a')));

            if ($config_property !== null && strlen($config_property) > 0)
            {
                if (!file_exists($config_property))
                {
                    Console::outWarning('Cannot find composer executable path from configuration `composer.executable_path`');
                }
                else
                {
                    Console::outDebug(sprintf('using composer path from configuration: %s', $config_property));
                    return $config_property;
                }
            }

            if (defined('NCC_EXEC_LOCATION'))
            {
                if (!file_exists(NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar'))
                    throw new InternalComposerNotAvailableException(NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar');
                Console::outDebug(sprintf('using composer path from NCC_EXEC_LOCATION: %s', NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar'));
                return NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar';
            }

            throw new ComposerNotAvailableException('No composer executable path is configured');
        }
    }