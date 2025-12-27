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
    use ncc\Classes\IO;
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

            if(IO::isFile($package))
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
                    if($packageReader->getHeader()->getMainRepository() !== null && $packageReader->getHeader()->getMainRepository()->getName() !== $packageReader->getHeader()->getUpdateSource()?->getRepository())
                    {
                        Console::warning("The repository name specified in the package header does not match the repository in the update source, updates will not be found correctly.");
                    }
                    elseif($packageReader->getHeader()->getMainRepository() !== null)
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

                $installedPackages = self::installFromFile($packageReader, [], []);
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

                $installedPackages = self::installFromRemote($packageSource, [], []);
            }

            if(count($installedPackages) === 0)
            {
                Console::out("No installation actions were necessary.");
                return 0;
            }

            // Remove duplicates from the installed packages list
            $installedPackages = array_unique($installedPackages);

            Console::out(sprintf("Completed installation of %d package(s).", count($installedPackages)));
            foreach($installedPackages as $installedPackage)
            {
                Console::out(" - " . $installedPackage);
            }

            return 0;
        }

        private static function installFromFile(PackageReader $reader, array $options=[], array $installed=[]): array
        {
            $options = self::parseOptions($options);
            $packageReader = $reader;
            $packageIdentifier = sprintf("%s=%s", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion());

            // Check if package is already in the installed list (to avoid duplicates in current session)
            if(in_array($packageIdentifier, $installed, true))
            {
                return $installed;
            }

            // Check if package is already installed on the system
            if(Runtime::packageInstalled($packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()) && !$options['reinstall'])
            {
                Console::out(sprintf("Package %s is already installed, skipping installation.", $packageIdentifier));
                return $installed;
            }

            Console::out(sprintf("Installing package %s=%s", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()));

            // Mark package as being installed to prevent circular dependency loops
            $installed[] = $packageIdentifier;

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
                    $depIdentifier = sprintf("%s=%s", $dependency->getPackage(), $dependency->getVersion());
                    
                    // Check if already in the installed list (current session)
                    if(in_array($depIdentifier, $installed, true))
                    {
                        continue;
                    }
                    
                    // Check if already installed on the system
                    if(Runtime::packageInstalled($dependency->getPackage(), $dependency->getVersion()))
                    {
                        continue;
                    }

                    Console::out(sprintf("Installing dependency %s for package %s", $dependency, $packageReader->getPackageName()));
                    $dependencySource = new PackageSource($dependency);
                    $installed = self::installFromRemote($dependencySource, $options, $installed);
                }
            }

            Runtime::getPackageManager()->install($packageReader, $options);

            return $installed;
        }

        public static function installFromRemote(PackageSource $source, array $options=[], array $installed=[]): array
        {
            Console::out(sprintf("Resolving package %s from repository %s", $source, $source->getRepository()));
            $options = self::parseOptions($options);
            if($source->getRepository() === null)
            {
                throw new OperationException(sprintf('Cannot install "%s", a remote repository is required', $source));
            }

            if(!Runtime::repositoryExists($source->getRepository()))
            {
                throw new OperationException(sprintf('Cannot install "%s", unknown remote repository "%s"', $source, $source->getRepository()));
            }

            // Define stages with weights for dynamic progress calculation
            $stages = [
                'download' => ['weight' => 60, 'start' => 0],
                'detect' => ['weight' => 5, 'start' => 0],
                'convert' => ['weight' => 5, 'start' => 0],
                'resolve_deps' => ['weight' => 10, 'start' => 0],
                'generate_config' => ['weight' => 5, 'start' => 0],
                'compile' => ['weight' => 15, 'start' => 0],
            ];
            
            // Calculate stage start positions
            $currentPos = 0;
            foreach($stages as $key => &$stage)
            {
                $stage['start'] = $currentPos;
                $currentPos += $stage['weight'];
            }
            unset($stage);

            // Helper function to calculate progress within a stage
            $calculateProgress = function(string $stageName, float $stageProgress = 1.0) use ($stages): int {
                $stage = $stages[$stageName];
                return (int)($stage['start'] + ($stage['weight'] * $stageProgress));
            };

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

            // Stage: Download
            $downloadedPackage = $repository->download($selectedPackage, function(int $downloaded, int $total, string $message) use ($calculateProgress) {
                $downloadProgress = $total > 0 ? ($downloaded / $total) : 0;
                $progress = $calculateProgress('download', $downloadProgress);
                Console::inlineProgress($progress, 100, $message);
            });

            // If it's a file, we assume it's an NCC package
            if(IO::isFile($downloadedPackage))
            {
                Console::completeProgress();
                return self::installFromFile(new PackageReader($downloadedPackage), $options, $installed);
            }

            // Stage: Detect project type
            Console::inlineProgress($calculateProgress('detect'), 100, sprintf("Detecting project type %s", $source));

            // Otherwise, we assume its source code that needs to be built, so we try to figure out it's project type
            try
            {
                // Get the version from the RemotePackage (already resolved by the repository)
                $resolvedVersion = $selectedPackage->getVersion();
                if($resolvedVersion !== null)
                {
                    Logger::getLogger()->debug(sprintf('Using version from repository: " %s. The downloaded source may not contain a recognized project configuration file (composer.json, package.json, etc.)', $source));
                }

                $projectPath = ProjectType::detectProjectPath($downloadedPackage);
                if($projectPath === null)
                {
                    Console::clearInlineProgress();
                    Logger::getLogger()->error(sprintf('Unable to detect project path for %s', $source));
                    throw new OperationException(sprintf('Unable to detect project configuration file path for %s', $source));
                }

                Console::inlineProgress($calculateProgress('detect', 0.5), 100, sprintf("Analyzing project structure %s", $source));

                $projectType = ProjectType::detectProjectType($downloadedPackage);
                if($projectType === null)
                {
                    Console::clearInlineProgress();
                    Logger::getLogger()->error(sprintf('Unable to detect project type for %s', $source));
                    throw new OperationException(sprintf('Unable to detect project type for %s', $source));
                }

                Logger::getLogger()->debug(sprintf('Detected project type: %s at path: %s', $projectType->value, $projectPath));

                // Stage: Converting project
                Console::inlineProgress($calculateProgress('convert'), 100, sprintf("Converting %s project %s", $projectType->value, $source));

                // Get the converter for the project type
                $converter = $projectType->getConverter();
                if($converter === null)
                {
                    Console::clearInlineProgress();
                    Logger::getLogger()->error(sprintf('No converter available for project type %s', $projectType->value));
                    throw new OperationException(sprintf('Cannot convert project type %s to ncc format. No converter is available for this project type.', $projectType->value));
                }

                // Stage: Resolving dependencies
                Console::inlineProgress($calculateProgress('resolve_deps'), 100, sprintf("Resolving dependencies for %s", $source));

                // Convert the project source to a ncc project configuration
                // Pass the resolved version to the converter
                // Track progress dynamically during dependency resolution
                $depResolutionProgress = 0.0;
                $depResolutionStep = 0.15; // Increment by 15% of the stage per callback
                
                $projectConfiguration = $converter->convert($projectPath, $resolvedVersion, function(string $message) use ($calculateProgress, &$depResolutionProgress, $depResolutionStep) {
                    $depResolutionProgress = min(0.95, $depResolutionProgress + $depResolutionStep);
                    Console::inlineProgress($calculateProgress('resolve_deps', $depResolutionProgress), 100, $message);
                });
                
                // Stage: Generating project configuration
                Console::inlineProgress($calculateProgress('generate_config'), 100, sprintf("Generating project configuration for %s", $source));
                
                $outputPath = dirname($projectPath) . DIRECTORY_SEPARATOR . 'project.yml';
                $projectConfiguration->save($outputPath);
                
                Console::inlineProgress($calculateProgress('generate_config', 0.7), 100, sprintf("Loading compiler for %s", $source));
                $compiler = Project::compilerFromFile($outputPath);
            }
            catch(Exception $e)
            {
                Console::clearInlineProgress();
                throw new OperationException(sprintf('Failed to convert project source for %s: %s', $source, $e->getMessage()));
            }

            // Stage: Compile
            // Build & install the project
            try
            {
                $packageReader = new PackageReader($compiler->compile(function(int $current, int $total, string $message) use ($calculateProgress) {
                    $compileProgress = $total > 0 ? ($current / $total) : 0;
                    $progress = $calculateProgress('compile', $compileProgress);
                    Console::inlineProgress($progress, 100, $message);
                }));
                Console::completeProgress();
                return self::installFromFile($packageReader, $options, $installed);
            }
            catch(Exception $e)
            {
                Console::clearInlineProgress();
                throw new OperationException(sprintf('Failed to build project source for %s: %s', $source, $e->getMessage()));
            }
        }

        private static function parseOptions(array $options): array
        {
            $results = [
                'skip-repositories' => false,
                'skip-dependencies' => false,
                'build-source' => false,
                'reinstall' => false
            ];

            foreach($options as $option)
            {
                switch($option)
                {
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
    }