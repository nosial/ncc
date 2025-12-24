<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\PackageReader;
    use ncc\CLI\Logger;
    use ncc\Enums\ProjectType;
    use ncc\Enums\RemotePackageType;
    use ncc\Exceptions\OperationException;
    use ncc\Objects\Package\DependencyReference;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Runtime;

    class InstallCommand extends AbstractCommandHandler
    {
        /**
         * Prints out the help menu for the install command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc install <package> [options]' . PHP_EOL);
            Console::out('Installs an ncc package from a file or remote repository.' . PHP_EOL);
            Console::out('The install command can handle local .ncc files or fetch packages');
            Console::out('from configured repositories. Dependencies are resolved and installed');
            Console::out('automatically unless explicitly skipped.' . PHP_EOL);
            Console::out('Arguments:');
            Console::out('  <package>         Package name or path to .ncc file' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --yes, -y         Automatically confirm all prompts');
            Console::out('  --skip-dependencies, --skip-deps, --sd');
            Console::out('                    Skip installing package dependencies');
            Console::out('  --skip-repositories, --skip-repos, --sr');
            Console::out('                    Skip adding package repositories');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc install mypackage.ncc');
            Console::out('  ncc install com.example.package --yes');
            Console::out('  ncc install package.ncc --skip-deps');
        }

        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $package = $argv['package'] ?? null;
            if ($package === null)
            {
                Console::error("No package specified for installation.");
                return 1;
            }

            $autoConfirm = $argv['yes'] ?? $argv['y'] ?? false;
            $skipDependencies = $argv['skip-dependencies'] ?? $argv['skip-deps'] ?? $argv['sd'] ?? false;
            $skipRepositories = $argv['skip-repositories'] ?? $argv['skip-repos'] ?? $argv['sr'] ?? false;

            if($skipDependencies)
            {
                Console::warning('The "skip-dependencies" option was used, the package may not meet the requirements');
            }

            if(is_file($package))
            {
                try
                {
                    $packageReader = new PackageReader($package);
                }
                catch(Exception $e)
                {
                    Console::error($e->getMessage());
                    return 1;
                }

                Console::out("Package found: " . $packageReader->getAssembly()->getPackage() . "=" . $packageReader->getAssembly()->getVersion());
                if(!$packageReader->getHeader()->getUpdateSource())
                {
                    Console::warning("This package does not specify an update source. This package will not receive updates automatically.");
                }
                else
                {
                    Console::out("Update source: " . $packageReader->getHeader()->getUpdateSource());
                }

                if($packageReader->getHeader()->getRepositories() !== null)
                {
                    if($packageReader->getHeader()->getMainRepository()->getName() !== $packageReader->getHeader()->getUpdateSource()?->getRepository())
                    {
                        Console::warning("The repository name specified in the package header does not match the repository in the update source, updates will not be found correctly.");
                    }
                    else
                    {
                        Console::out("Repository: " . $packageReader->getHeader()->getMainRepository()->getHost());
                    }

                    if($skipRepositories)
                    {
                        /** @var RepositoryConfiguration $repositoryConfiguration */
                        foreach($packageReader->getHeader()->getRepositories() as $repositoryConfiguration)
                        {
                            if(!Runtime::repositoryExists($repositoryConfiguration->getName()))
                            {
                                Console::warning(sprintf('The package requires repository "%s" (%s) which is not available, updates will not be found correctly.',
                                    $repositoryConfiguration->getName(),
                                    $repositoryConfiguration->getHost()
                                ));
                            }
                        }
                    }
                }

                if(!$autoConfirm)
                {
                    $input = Console::prompt("Do you want to proceed with the installation? (y/n): ");
                    if(!(strtolower($input) === 'y' || strtolower($input) === 'yes'))
                    {
                        Console::out("Installation cancelled by user.");
                        return 0;
                    }
                }

                $installedPackages = self::installFromFile($packageReader);
            }
            else
            {
                try
                {
                    $packageSource = new PackageSource($package);
                }
                catch(Exception $e)
                {
                    Console::error('Failed to parse package source: ' . $e->getMessage());
                    return 1;
                }

                if($packageSource->getRepository() === null)
                {
                    Console::error(sprintf('Cannot install "%s", a remote repository is required', $packageSource));
                    return 1;
                }

                if(!Runtime::repositoryExists($packageSource->getRepository()))
                {
                    Console::error(sprintf('Cannot install "%s", unknown remote repository "%s"', $packageSource, $packageSource->getRepository()));
                    return 1;
                }

                if(!$autoConfirm)
                {
                    Console::out(sprintf("You are about to download and install %s from a remote repository", $packageSource));
                    $input = Console::prompt("Do you want to proceed with the installation? (y/n): ");
                    if(!(strtolower($input) === 'y' || strtolower($input) === 'yes'))
                    {
                        Console::out("Installation cancelled by user.");
                        return 0;
                    }
                }

                $installedPackages = self::installFromRemote($packageSource);
            }

            if(count($installedPackages) === 0)
            {
                Console::out("No installation actions were necessary.");
                return 0;
            }

            Console::out(sprintf("Completed installation of %d package(s).", count($installedPackages)));
            foreach($installedPackages as $installedPackage)
            {
                Console::out(" - " . $installedPackage);
            }

            return 0;
        }

        private static function installFromFile(PackageReader $reader, array $options=[]): array
        {
            $installed = [];
            $options = self::parseOptions($options);
            $packageReader = $reader;

            // Check if package is already installed
            if(Runtime::packageInstalled($packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()) && !$options['reinstall'])
            {
                Console::out(sprintf("Package %s=%s is already installed, skipping installation.", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()));
                return $installed;
            }

            Console::out(sprintf("Installing package %s=%s", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()));

            // Add the repositories if required
            if(!$options['skip-repositories'] && count($packageReader->getHeader()->getRepositories() ?? []) > 0)
            {
                /** @var RepositoryConfiguration $repositoryConfiguration */
                foreach($packageReader->getHeader()->getRepositories() as $repositoryConfiguration)
                {
                    if(!Runtime::repositoryExists($repositoryConfiguration->getName()))
                    {
                        Console::out(sprintf('Adding repository "%s" (%s)', $repositoryConfiguration->getName(), $repositoryConfiguration->getHost()));
                        Runtime::getRepositoryManager()->addConfiguration($repositoryConfiguration);
                    }
                }
            }

            // Install all dependencies first if it's not statically linked
            if(!$options['skip-dependencies'] && !$packageReader->getHeader()->isStaticallyLinked())
            {
                /** @var DependencyReference $dependency */
                foreach($packageReader->getHeader()->getDependencyReferences() as $dependency)
                {
                    if(Runtime::packageInstalled($dependency->getPackage(), $dependency->getVersion()))
                    {
                        continue;
                    }

                    Console::out(sprintf("Installing dependency %s for package %s", $dependency, $packageReader->getPackageName()));
                    $dependencySource = new PackageSource($dependency);
                    $installed = array_merge($installed, self::installFromRemote($dependencySource, $options, $installed));
                }
            }

            Runtime::getPackageManager()->install($packageReader, $options);
            $installed[] = sprintf("%s=%s", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion());

            return $installed;
        }

        private static function installFromRemote(PackageSource $source, array $options=[], array $installed=[]): array
        {
            $options = self::parseOptions($options);

            if($source->getRepository() === null)
            {
                throw new OperationException(sprintf('Cannot install "%s", a remote repository is required', $source));
            }

            if(!Runtime::repositoryExists($source->getRepository()))
            {
                throw new OperationException(sprintf('Cannot install "%s", unknown remote repository "%s"', $source, $source->getRepository()));
            }

            // TODO: Implement authentication handling here
            $repository = Runtime::getRepository($source->getRepository())->createClient();
            $resolvedPackages = $repository->getAll($source->getOrganization(), $source->getName(), $source->getVersion());
            $selectedPackage = null;

            foreach($resolvedPackages as $package)
            {
                if($package->getType() === RemotePackageType::NCC && !$options['build-source'])
                {
                    $selectedPackage = $package;
                    break;
                }

                if($package->getType() === RemotePackageType::SOURCE_ZIP)
                {
                    $selectedPackage = $package;
                    break;
                }

                $selectedPackage = $package;
                break;
            }

            if($selectedPackage === null)
            {
                throw new OperationException(sprintf('No suitable package found for %s in repository %s', $source, $source->getRepository()));
            }

            Console::out(sprintf("Downloading %s from repository %s", $source, $source->getRepository()));
            $downloadedPackage = $repository->download($selectedPackage);

            // If it's a file, we assume it's an NCC package
            if(is_file($downloadedPackage))
            {
                return array_merge($installed, self::installFromFile(new PackageReader($downloadedPackage), $options));
            }

            // Otherwise, we assume its source code that needs to be built, so we try to figure out it's project type
            try
            {
                // Convert the project source to a ncc project configuration
                $projectConfiguration = ProjectType::detectProjectType($downloadedPackage)->getConverter()->convert(
                    ProjectType::detectProjectPath($downloadedPackage)
                );
                
                // If the project configuration has default version (0.0.0), inject the actual resolved version
                if($projectConfiguration->getAssembly()->getVersion() === '0.0.0')
                {
                    // Resolve the actual version from the repository
                    // Determine which version to use based on repository type and requested version
                    if($source->getRepository() === 'packagist')
                    {
                        // For packagist, get the latest stable release
                        // This is already filtered for stable versions and sorted by the repository
                        $actualVersion = $repository->getLatestRelease($source->getOrganization(), $source->getName());
                        Logger::getLogger()->debug(sprintf('Received version from packagist: "%s" (type: %s, length: %d)', $actualVersion, gettype($actualVersion), strlen($actualVersion)));
                    }
                    elseif($source->getVersion() !== null && $source->getVersion() !== 'latest')
                    {
                        // For other repositories, use the specified version
                        $actualVersion = $source->getVersion();
                    }
                    else
                    {
                        throw new OperationException('Could not determine package version');
                    }
                    
                    // Normalize the version using semver to ensure consistency
                    // The normalize method handles 'v' prefixes and other version formats
                    $versionParser = new \ncc\Libraries\semver\VersionParser();
                    
                    try
                    {
                        Logger::getLogger()->debug(sprintf('Testing if version is valid: "%s"', $actualVersion));
                        if($versionParser->isValid($actualVersion))
                        {
                            Logger::getLogger()->debug('Version is valid, normalizing...');
                            $normalizedVersion = $versionParser->normalize($actualVersion);
                            Logger::getLogger()->debug(sprintf('Normalized version: "%s"', $normalizedVersion));
                            
                            // The VersionParser may add a 4th component (e.g., "8.0.0.0")
                            // but ncc expects 3-component semantic versions (X.Y.Z)
                            // Strip the 4th component if present
                            if(preg_match('/^(\d+\.\d+\.\d+)\.0$/', $normalizedVersion, $matches))
                            {
                                $normalizedVersion = $matches[1];
                                Logger::getLogger()->debug(sprintf('Stripped 4th component, using: "%s"', $normalizedVersion));
                            }
                        }
                        else
                        {
                            Logger::getLogger()->debug('Version is not valid, trying to clean it...');
                            // If normalization fails, strip common prefixes and try again
                            $cleanVersion = ltrim($actualVersion, 'vV');
                            Logger::getLogger()->debug(sprintf('Cleaned version: "%s"', $cleanVersion));
                            
                            if($versionParser->isValid($cleanVersion))
                            {
                                Logger::getLogger()->debug('Cleaned version is valid, normalizing...');
                                $normalizedVersion = $versionParser->normalize($cleanVersion);
                                Logger::getLogger()->debug(sprintf('Normalized version: "%s"', $normalizedVersion));
                                
                                // Strip the 4th component if present
                                if(preg_match('/^(\d+\.\d+\.\d+)\.0$/', $normalizedVersion, $matches))
                                {
                                    $normalizedVersion = $matches[1];
                                    Logger::getLogger()->debug(sprintf('Stripped 4th component, using: "%s"', $normalizedVersion));
                                }
                            }
                            else
                            {
                                Logger::getLogger()->debug('Cleaned version is still invalid, checking regex pattern...');
                                // Last resort: use the version as-is if it looks like a semantic version
                                if(preg_match('/^\d+\.\d+\.\d+/', $cleanVersion))
                                {
                                    Logger::getLogger()->debug(sprintf('Using version as-is: "%s"', $cleanVersion));
                                    $normalizedVersion = $cleanVersion;
                                }
                                else
                                {
                                    throw new OperationException(sprintf('Invalid version format received from repository: "%s"', $actualVersion));
                                }
                            }
                        }
                        
                        $projectConfiguration->getAssembly()->setVersion($normalizedVersion);
                        Console::out(sprintf("Resolved package version: %s", $normalizedVersion));
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()->error(sprintf('Exception during version resolution: %s', $e->getMessage()));
                        throw new OperationException(sprintf('Failed to resolve version for %s: %s', $source, $e->getMessage()));
                    }
                }
                
                $outputPath = dirname(ProjectType::detectProjectPath($downloadedPackage)) . DIRECTORY_SEPARATOR . 'project.yml';
                $projectConfiguration->save($outputPath);
                $compiler = Project::compilerFromFile($outputPath);
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('Failed to convert project source for %s: %s', $source, $e->getMessage()));
            }

            // Build & install the project
            try
            {
                Console::out(sprintf("Building and installing package %s", $source));
                return array_merge($installed, self::installFromFile(new PackageReader($compiler->build()), $options));
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('Failed to build project source for %s: %s', $source, $e->getMessage()));
            }
        }

        private static function parseOptions(array $options): array
        {
            $results = [
                'static' => false,
                'skip-repositories' => false,
                'skip-dependencies' => false,
                'build-source' => false,
                'reinstall' => false
            ];

            foreach($options as $option)
            {
                switch($option)
                {
                    case 'static':
                    case 's':
                        $results['static'] = true;
                        break;

                    case 'skip-repositories':
                    case 'skip-repos':
                    case 'sr':
                        $results['skip-repositories'] = true;
                        break;

                    case 'skip-dependencies':
                    case 'skip-deps':
                    case 'sd':
                        $results['skip-dependencies'] = true;
                        break;

                    case 'build-source':
                    case 'bs':
                        $results['build-source'] = true;
                        break;

                    case 'reinstall':
                    case 'r':
                        $results['reinstall'] = true;
                        break;
                }
            }

            return $results;
        }
    }