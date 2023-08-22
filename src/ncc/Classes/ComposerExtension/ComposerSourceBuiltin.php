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

    namespace ncc\Classes\ComposerExtension;

    use Exception;
    use FilesystemIterator;
    use JsonException;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\CompilerExtensionSupportedVersions;
    use ncc\Enums\ComponentFileExtensions;
    use ncc\Enums\DependencySourceType;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Scopes;
    use ncc\CLI\Main;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ComposerException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PackageException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UserAbortedOperationException;
    use ncc\Interfaces\ServiceSourceInterface;
    use ncc\Managers\ProjectManager;
    use ncc\ncc;
    use ncc\Objects\ComposerJson;
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

    class ComposerSourceBuiltin implements ServiceSourceInterface
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
         * @throws BuildException
         * @throws ComposerException
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws NotSupportedException
         * @throws PackageException
         * @throws PathNotFoundException
         * @throws RuntimeException
         * @throws UserAbortedOperationException
         */
        public static function fetch(RemotePackageInput $packageInput): string
        {
            $package_path = self::require($packageInput->vendor, $packageInput->package, $packageInput->version);
            $packages = self::compilePackages($package_path . DIRECTORY_SEPARATOR . 'composer.lock');
            $real_package_name = explode('=', $packageInput->toStandard(false))[0];

            RuntimeCache::setFileAsTemporary($package_path);
            foreach($packages as $package => $path)
            {
                if(explode('=', $package)[0] === $real_package_name)
                {
                    return $path;
                }
            }

            throw new RuntimeException(sprintf('Could not find package %s in the compiled packages', $packageInput->toStandard()));
        }

        /**
         * Works with a local composer.json file and attempts to compile the required packages
         * and their dependencies, returns the path to the compiled package.
         *
         * @param string $path
         * @return string
         * @throws BuildException
         * @throws ComposerException
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws NotSupportedException
         * @throws PackageException
         * @throws PathNotFoundException
         * @throws UserAbortedOperationException
         */
        public static function fromLocal(string $path): string
        {
            // Check if the file composer.json exists
            if (!file_exists($path . DIRECTORY_SEPARATOR . 'composer.json'))
            {
                throw new PathNotFoundException(sprintf('File "%s" not found', $path . DIRECTORY_SEPARATOR . 'composer.json'));
            }

            // Execute composer with options
            $options = self::getOptions();
            $composer_exec = self::getComposerPath();
            $process = new Process([$composer_exec, 'install']);
            self::prepareProcess($process, $path, $options);

            Console::outDebug(sprintf('executing %s', $process->getCommandLine()));
            $process->run(function ($type, $buffer)
            {
                if($type === Process::ERR)
                {
                    Console::outWarning($buffer, false);
                }
                else
                {
                    Console::out($buffer, false);
                }
            });

            if(!$process->isSuccessful())
            {
                throw new ComposerException($process->getErrorOutput());
            }

            $filesystem = new Filesystem();

            if($filesystem->exists($path . DIRECTORY_SEPARATOR . 'build'))
            {
                $filesystem->remove($path . DIRECTORY_SEPARATOR . 'build');
            }

            $filesystem->mkdir($path . DIRECTORY_SEPARATOR . 'build');

            // Compile dependencies
            self::compilePackages($path . DIRECTORY_SEPARATOR . 'composer.lock');

            $composer_lock = Functions::loadJson(IO::fread($path . DIRECTORY_SEPARATOR . 'composer.lock'), Functions::FORCE_ARRAY);
            $version_map = self::getVersionMap(ComposerLock::fromArray($composer_lock));

            // Finally, convert the main package's composer.json to package.json and compile it
            self::convertProject($path, $version_map);
            $project_manager = new ProjectManager($path);
            $project_manager->load();
            $built_package = $project_manager->build();

            RuntimeCache::setFileAsTemporary($built_package);
            return $built_package;
        }

        /**
         * @param string $composer_lock_path
         * @return array
         * @throws BuildException
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws NotSupportedException
         * @throws PackageException
         * @throws PathNotFoundException
         */
        private static function compilePackages(string $composer_lock_path): array
        {
            if (!file_exists($composer_lock_path))
            {
                throw new PathNotFoundException($composer_lock_path);
            }

            $base_dir = dirname($composer_lock_path);

            try
            {
                $composer_lock = ComposerLock::fromArray(json_decode(IO::fread($composer_lock_path), true, 512, JSON_THROW_ON_ERROR));
            }
            catch(JsonException $e)
            {
                throw new MalformedJsonException($composer_lock_path, $e);
            }

            $filesystem = new Filesystem();
            $built_packages = [];

            if ($filesystem->exists($base_dir . DIRECTORY_SEPARATOR . 'build'))
            {
                $filesystem->remove($base_dir . DIRECTORY_SEPARATOR . 'build');
            }

            $filesystem->mkdir($base_dir . DIRECTORY_SEPARATOR . 'build');
            $version_map = self::getVersionMap($composer_lock);

            foreach ($composer_lock->Packages as $package)
            {
                $package_path = $base_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $package->Name;

                // Load the composer lock file
                $composer_package = $composer_lock->getPackage($package->Name);
                if ($composer_package === null)
                {
                    throw new PackageException(sprintf('Package "%s" not found in composer lock file', $package->Name));
                }

                // Convert it to an NCC project configuration
                $project_configuration = self::convertProject($package_path, $version_map, $composer_package);

                // Load the project
                $project_manager = new ProjectManager($package_path);
                $project_manager->load();
                $built_package = $project_manager->build();

                // Copy the project to the build directory
                $out_path = $base_dir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . sprintf('%s.ncc', $project_configuration->assembly->package);
                $filesystem->copy($built_package, $out_path);
                $filesystem->remove($built_package);
                $built_packages[$project_configuration->assembly->package] = $out_path;
            }

            return $built_packages;
        }

        /**
         * Returns array of versions from the ComposerLock file
         *
         * @param ComposerLock $composerLock
         * @return array
         */
        private static function getVersionMap(ComposerLock $composerLock): array
        {
            $version_map = [];
            foreach($composerLock->Packages as $package)
            {
                $version_map[$package->Name] = $package->Version;
            }
            return $version_map;
        }

        /**
         * Converts a composer package name to a valid package name
         *
         * @param string $input
         * @return string|null
         */
        private static function toPackageName(string $input): ?string
        {
            if (strpos($input, ':'))
            {
                $input = explode(':', $input)[0];
            }

            $parsed_input = explode("/", $input);
            if (count($parsed_input) === 2)
            {
                return str_ireplace(
                    "-", "_", 'com.' . $parsed_input[0] . "." . $parsed_input[1]
                );
            }

            return null;
        }

        /**
         * Returns a valid version from a version map
         *
         * @param string $package_name
         * @param array $version_map
         * @return string
         */
        private static function versionMap(string $package_name, array $version_map): string
        {
            if (array_key_exists($package_name, $version_map))
            {
                return Functions::convertToSemVer($version_map[$package_name]);
            }

            return '1.0.0';
        }

        /**
         * Generates a project configuration from a package selection
         * from the composer.lock file
         *
         * @param ComposerJson $composer_package
         * @param array $version_map
         * @return ProjectConfiguration
         */
        private static function generateProjectConfiguration(ComposerJson $composer_package, array $version_map): ProjectConfiguration
        {
            // Generate a new project configuration object
            $project_configuration = new ProjectConfiguration();

            if (isset($composer_package->Name))
            {
                $project_configuration->assembly->name = $composer_package->Name;
            }

            if (isset($composer_package->Description))
            {
                $project_configuration->assembly->description = $composer_package->Description;
            }

            if(isset($version_map[$composer_package->Name]))
            {
                $project_configuration->assembly->version = self::versionMap($composer_package->Name, $version_map);
            }

            if($project_configuration->assembly->version === null || $project_configuration->assembly->version === '')
            {
                $project_configuration->assembly->version = '1.0.0';
            }


            $project_configuration->assembly->uuid = Uuid::v1()->toRfc4122();
            $project_configuration->assembly->package = self::toPackageName($composer_package->Name);

            // Add the update source
            $project_configuration->project->update_source = new ProjectConfiguration\UpdateSource();
            $project_configuration->project->update_source->source = sprintf('%s@composer', str_ireplace('\\', '/', $composer_package->Name));
            $project_configuration->project->update_source->repository = null;

            // Process the dependencies
            if($composer_package->Require !== null && count($composer_package->Require) > 0)
            {
                foreach ($composer_package->Require as $item)
                {
                    // Check if the dependency is already in the project configuration
                    $package_name = self::toPackageName($item->PackageName);

                    if($package_name === null)
                    {
                        continue;
                    }

                    $dependency = new ProjectConfiguration\Dependency();
                    $dependency->name = $package_name;
                    $dependency->source_type = DependencySourceType::LOCAL;
                    $dependency->version = self::versionMap($item->PackageName, $version_map);
                    $dependency->source = $package_name . '.ncc';
                    $project_configuration->build->addDependency($dependency);
                }
            }

            // Create a build configuration
            $build_configuration = new ProjectConfiguration\Build\BuildConfiguration();
            $build_configuration->name = 'default';
            $build_configuration->output_path = 'build';

            // Apply the final properties
            $project_configuration->build->build_configurations[] = $build_configuration;
            $project_configuration->build->default_configuration = 'default';
            $project_configuration->build->source_path = '.src';

            // Apply a compiler extension
            $project_configuration->project->compiler->extension = CompilerExtensions::PHP;
            $project_configuration->project->compiler->minimum_version = CompilerExtensionSupportedVersions::PHP[0];
            $project_configuration->project->compiler->maximum_version = CompilerExtensionSupportedVersions::PHP[(count(CompilerExtensionSupportedVersions::PHP) - 1)];

            return $project_configuration;
        }

        /**
         * Gets the applicable options configured for composer
         *
         * @return array
         */
        private static function getOptions(): array
        {
            $results = [];
            $arguments = Main::getArgs();

            // Anything beginning with --composer- is a composer option
            foreach ($arguments as $argument => $value)
            {
                if (str_starts_with($argument, 'composer-') && !in_array($argument, $results, true))
                {
                    if (is_bool($value) && $value)
                    {
                        $results[] = '--' . str_ireplace('composer-', '', $argument);

                    }
                    else
                    {
                        $results[] = '--' . str_ireplace('composer-', '', $argument) . '=' . $value;
                    }
                }
            }


            $options = Functions::getConfigurationProperty('composer.options');

            if (!is_array($options))
            {
                return $results;
            }

            if (isset($options['quiet']) && $options['quiet'])
            {
                $results[] = '--quiet';
            }

            if (isset($options['no_asni']) && $options['no_asni'])
            {
                $results[] = '--no-asni';
            }

            if (isset($options['no_interaction']) && $options['no_interaction'])
            {
                $results[] = '--no-interaction';
            }

            if(isset($options['profile']) && $options['profile'])
            {
                $results[] = '--profile';
            }

            if (isset($options['no_scripts']) && $options['no_scripts'])
            {
                $results[] = '--no-scripts';
                $results[] = '--no-plugins'; // Also include this for safe measures
            }

            if (isset($options['no_cache']) && $options['no_cache'])
            {
                $results[] = '--no-cache';
            }

            // Determine the logging option
            if (isset($options['logging']))
            {
                if ((int)$options['logging'] === 3)
                {
                    $results[] = '-vvv';
                }
                elseif ((int)$options['logging'] === 2)
                {
                    $results[] = '-vv';
                }
                elseif ((int)$options['logging'] === 1)
                {
                    $results[] = '-v';
                }
                else
                {
                    switch (Main::getLogLevel())
                    {
                        default:
                        case LogLevel::FATAL:
                        case LogLevel::WARNING:
                        case LogLevel::ERROR:
                        case LogLevel::INFO:
                            $results[] = '-v';
                            break;

                        case LogLevel::VERBOSE:
                            $results[] = '-vv';
                            break;

                        case LogLevel::DEBUG:
                            $results[] = '-vvv';
                            break;

                        case LogLevel::SILENT:
                            if (!in_array('--quiet', $results, true))
                            {
                                $results[] = '--quiet';
                            }
                            break;
                    }
                }
            }

            return $results;
        }

        /**
         * Uses composers require command to temporarily create a
         * composer.json file and install the specified package
         *
         * @param string $vendor
         * @param string $package
         * @param string|null $version
         * @return string
         * @throws ComposerException
         * @throws IOException
         * @throws PathNotFoundException
         * @throws UserAbortedOperationException
         */
        private static function require(string $vendor, string $package, ?string $version = null): string
        {
            if (Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new \RuntimeException('Cannot require a package with insufficient permissions');
            }

            if ($version === null)
            {
                $version = '*';
            }

            if($version === 'latest')
            {
                $version = '*';
            }

            $tpl_file = __DIR__ . DIRECTORY_SEPARATOR . 'composer.jtpl';

            if (!file_exists($tpl_file))
            {
                throw new PathNotFoundException($tpl_file);
            }

            $composer_exec = self::getComposerPath();

            $template = IO::fread($tpl_file);
            $template = str_ireplace('%VENDOR%', $vendor, $template);
            $template = str_ireplace('%PACKAGE%', $package, $template);
            $template = str_ireplace('%VERSION%', $version, $template);

            $filesystem = new Filesystem();
            $tmp_dir = PathFinder::getCachePath(Scopes::SYSTEM) . DIRECTORY_SEPARATOR . hash('haval128,3', $template);
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
            self::prepareProcess($process, $tmp_dir, $options);

            Console::outDebug(sprintf('executing %s', $process->getCommandLine()));
            $process->run(function ($type, $buffer)
            {
                Console::out($buffer, false);
            });

            if (!$process->isSuccessful())
            {
                throw new ComposerException($process->getErrorOutput());
            }

            return $tmp_dir;
        }

        /**
         * Attempts to find the composer path to use that is currently configured
         *
         * @return string
         * @throws ComposerException
         */
        private static function getComposerPath(): string
        {
            Console::outVerbose(sprintf('Getting composer path for %s', Functions::getConfigurationProperty('composer.path')));

            $composer_enabled = Functions::getConfigurationProperty('composer.enabled');
            $internal_composer_enabled = Functions::getConfigurationProperty('composer.enable_internal_composer');
            if ($composer_enabled !== null && $composer_enabled === false)
            {
                throw new ComposerException('Composer is disabled by the configuration `composer.enabled`');
            }

            $config_property = Functions::getConfigurationProperty('composer.executable_path');

            Console::outDebug(sprintf('composer.enabled = %s', ($composer_enabled ?? 'n/a')));
            Console::outDebug(sprintf('composer.enable_internal_composer = %s', ($internal_composer_enabled ?? 'n/a')));
            Console::outDebug(sprintf('composer.executable_path = %s', ($config_property ?? 'n/a')));

            if ($internal_composer_enabled && defined('NCC_EXEC_LOCATION'))
            {
                if (!file_exists(NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar'))
                {
                    throw new ComposerException(NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar');
                }

                Console::outDebug(sprintf('using composer path from NCC_EXEC_LOCATION: %s', NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar'));
                return NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'composer.phar';
            }

            if ($config_property !== null && $config_property !== '')
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

            throw new ComposerException('No composer executable path is configured');
        }

        /**
         * @param Process $process
         * @param string $path
         * @param array $options
         * @return void
         * @throws UserAbortedOperationException
         */
        private static function prepareProcess(Process $process, string $path, array $options): void
        {
            $process->setWorkingDirectory($path);

            // Check if scripts are enabled while running as root
            if (!in_array('--no-scripts', $options, true) && Resolver::resolveScope() === Scopes::SYSTEM)
            {
                Console::outWarning('composer scripts are enabled while running as root, this can allow malicious scripts to run as root');

                if (!isset($options['--no-interaction']))
                {
                    if(!Console::getBooleanInput('Do you want to continue?'))
                    {
                        throw new UserAbortedOperationException('The operation was aborted by the user');
                    }

                    // The user understands the risks and wants to continue
                    $process->setEnv(['COMPOSER_ALLOW_SUPERUSER' => 1]);
                }
            }
            else
            {
                // Composer is running "safely". We can disable the superuser check
                $process->setEnv(['COMPOSER_ALLOW_SUPERUSER' => 1]);
            }
        }

        /**
         * Converts a composer project to a NCC project
         *
         * @param string $package_path
         * @param array $version_map
         * @param mixed $composer_package
         * @return ProjectConfiguration
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PackageException
         * @throws PathNotFoundException
         */
        private static function convertProject(string $package_path, array $version_map, ?ComposerJson $composer_package=null): ProjectConfiguration
        {
            if($composer_package === null)
            {
                $composer_package = Functions::loadComposerJson($package_path . DIRECTORY_SEPARATOR . 'composer.json');
            }

            $project_configuration = self::generateProjectConfiguration($composer_package, $version_map);
            $filesystem = new Filesystem();

            // Process the source files
            if ($composer_package->Autoload !== null)
            {
                $source_directory = $package_path . DIRECTORY_SEPARATOR . '.src';

                if($filesystem->exists($source_directory))
                {
                    $filesystem->remove($source_directory);
                }

                $filesystem->mkdir($source_directory);
                $source_directories = [];
                $static_files = [];

                // Extract all the source directories
                if ($composer_package->Autoload->Psr4 !== null && count($composer_package->Autoload->Psr4) > 0)
                {
                    Console::outVerbose('Extracting PSR-4 source directories');
                    foreach ($composer_package->Autoload->Psr4 as $namespace_pointer)
                    {
                        if ($namespace_pointer->Path !== null && !in_array($namespace_pointer->Path, $source_directories, true))
                        {
                            $source_directories[] = $package_path . DIRECTORY_SEPARATOR . $namespace_pointer->Path;
                        }
                    }
                }

                if ($composer_package->Autoload->Psr0 !== null && count($composer_package->Autoload->Psr0) > 0)
                {
                    Console::outVerbose('Extracting PSR-0 source directories');
                    foreach ($composer_package->Autoload->Psr0 as $namespace_pointer)
                    {
                        if ($namespace_pointer->Path !== null && !in_array($namespace_pointer->Path, $source_directories, true))
                        {
                            $source_directories[] = $package_path . DIRECTORY_SEPARATOR . $namespace_pointer->Path;
                        }
                    }
                }

                if ($composer_package->Autoload->Files !== null && count($composer_package->Autoload->Files) > 0)
                {
                    Console::outVerbose('Extracting static files');
                    foreach ($composer_package->Autoload->Files as $file)
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
                    throw new PackageException('Cannot unset flag \'FOLLOW_SYMLINKS\' in DirectoryScanner, ' . $e->getMessage(), $e);
                }

                // Include file components that can be compiled
                $DirectoryScanner->setIncludes(ComponentFileExtensions::PHP);

                foreach ($source_directories as $directory)
                {
                    /** @var SplFileInfo $item */
                    foreach ($DirectoryScanner($directory) as $item)
                    {
                        if(is_dir($item->getPathName()))
                        {
                            continue;
                        }

                        $parsed_path = str_ireplace($package_path . DIRECTORY_SEPARATOR, '', $item->getPathName());

                        Console::outDebug(sprintf('copying file %s for package %s', $parsed_path, $composer_package->Name));
                        $filesystem->copy($item->getPathName(), $source_directory . DIRECTORY_SEPARATOR . $parsed_path);
                    }
                }

                if (count($static_files) > 0)
                {
                    $project_configuration->project->options['static_files'] = $static_files;

                    foreach ($static_files as $file)
                    {
                        $parsed_path = str_ireplace($package_path . DIRECTORY_SEPARATOR, '', $file);
                        Console::outDebug(sprintf('copying file %s for package %s', $parsed_path, $composer_package->Name));
                        $filesystem->copy($file, $source_directory . DIRECTORY_SEPARATOR . $parsed_path);
                        unset($file);
                    }
                }
            }

            $project_configuration->toFile($package_path . DIRECTORY_SEPARATOR . 'project.json');

            // This part simply displays the package information to the command-line interface
            if(ncc::cliMode())
            {
                $license_files = [
                    'LICENSE',
                    'license',
                    'LICENSE.txt',
                    'license.txt',
                    'LICENSE.md',
                    'license.md',
                ];

                foreach($license_files as $license_file)
                {
                    // Check configuration if composer.extension.display_licenses is set
                    if($filesystem->exists($package_path . DIRECTORY_SEPARATOR . $license_file) && Functions::cbool(Functions::getConfigurationProperty('composer.extension.display_licenses')))
                    {
                        Console::out(sprintf('License for package %s:', $composer_package->Name));
                        Console::out(IO::fread($package_path . DIRECTORY_SEPARATOR . $license_file));
                        break;
                    }
                }

                if(Functions::cbool(!is_null($composer_package->Authors) && count($composer_package->Authors) > 0 && Functions::getConfigurationProperty('composer.extension.display_authors')))
                {
                    Console::out(sprintf('Authors for package %s:', $composer_package->Name));
                    foreach($composer_package->Authors as $author)
                    {
                        Console::out(sprintf(' - %s', $author->Name));

                        if($author->Email !== null)
                        {
                            Console::out(sprintf('   %s', $author->Email));
                        }

                        if($author->Homepage !== null)
                        {
                            Console::out(sprintf('   %s', $author->Homepage));
                        }

                        if($author->Role !== null)
                        {
                            Console::out(sprintf('   %s', $author->Role));
                        }

                    }
                }
            }

            return $project_configuration;
        }
    }