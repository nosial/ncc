<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\CLI\Commands\Project;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\CLI\Commands\Helper;
    use ncc\CLI\Commands\InstallCommand;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Libraries\semver\Semver;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Runtime;

    class InstallDependencies extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                // Delegate to parent's help command
                return 0;
            }

            $projectPath = $argv['path'] ?? null;
            $autoConfirm = $argv['yes'] ?? $argv['y'] ?? false;
            $skipDependencies = $argv['skip-dependencies'] ?? $argv['skip-deps'] ?? $argv['sd'] ?? false;
            $skipRepositories = $argv['skip-repositories'] ?? $argv['skip-repos'] ?? $argv['sr'] ?? false;
            $skipExtensions = $argv['skip-extensions'] ?? $argv['skip-exts'] ?? $argv['se'] ?? false;
            $reinstall = $argv['reinstall'] ?? $argv['r'] ?? false;
            $buildConfiguration = $argv['build'] ?? $argv['b'] ?? null;

            // Parse dynamic repository authentication arguments (e.g., --github-auth=foo)
            $repositoryAuth = [];
            foreach($argv as $key => $value)
            {
                if(preg_match('/^(.+)-auth$/', $key, $matches))
                {
                    $repositoryName = $matches[1];
                    $repositoryAuth[$repositoryName] = $value;
                }
            }

            // Resolve project configuration path
            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            // Load the project configuration
            try
            {
                $projectConfiguration = Project::fromFile($projectPath, true);
            }
            catch(Exception $e)
            {
                Console::error("Failed to load project configuration: " . $e->getMessage());
                return 1;
            }

            // Validate the project configuration
            try
            {
                $projectConfiguration->validate();
            }
            catch(InvalidPropertyException $e)
            {
                Console::error("Project configuration is invalid: " . $e->getMessage());
                return 1;
            }

            // Collect all dependencies from project and build configurations
            $allDependencies = [];
            $dependencySources = [];

            // Get project-level dependencies
            if($projectConfiguration->getDependencies() !== null)
            {
                foreach($projectConfiguration->getDependencies() as $packageName => $packageSource)
                {
                    $allDependencies[(string)$packageSource] = $packageSource;
                    $dependencySources[(string)$packageSource] = 'project';
                }
            }

            // Get build configuration dependencies
            if($buildConfiguration !== null)
            {
                // Install dependencies for specific build configuration
                if(!$projectConfiguration->buildConfigurationExists($buildConfiguration))
                {
                    Console::error(sprintf('Build configuration "%s" not found in project', $buildConfiguration));
                    return 1;
                }

                $buildConfig = $projectConfiguration->getBuildConfiguration($buildConfiguration);
                if($buildConfig->getDependencies() !== null)
                {
                    foreach($buildConfig->getDependencies() as $packageName => $packageSource)
                    {
                        if(!isset($allDependencies[(string)$packageSource]))
                        {
                            $allDependencies[(string)$packageSource] = $packageSource;
                            $dependencySources[(string)$packageSource] = "build:{$buildConfiguration}";
                        }
                    }
                }
            }
            else
            {
                // Install dependencies for all build configurations
                foreach($projectConfiguration->getBuildConfigurations() as $buildConfig)
                {
                    if($buildConfig->getDependencies() !== null)
                    {
                        foreach($buildConfig->getDependencies() as $packageName => $packageSource)
                        {
                            if(!isset($allDependencies[(string)$packageSource]))
                            {
                                $allDependencies[(string)$packageSource] = $packageSource;
                                $dependencySources[(string)$packageSource] = "build:{$buildConfig->getName()}";
                            }
                        }
                    }
                }
            }

            // Check if there are any dependencies to install
            if(count($allDependencies) === 0)
            {
                Console::out("No dependencies found in the project configuration.");
                return 0;
            }

            // Display summary of dependencies to be installed
            Console::out(sprintf("Found %d dependencies to install:", count($allDependencies)));
            foreach($allDependencies as $depString => $packageSource)
            {
                $source = $dependencySources[$depString];
                Console::out(sprintf("  - %s (from %s)", $depString, $source));
            }
            Console::out('');

            // Confirm installation if not auto-confirmed
            if(!$autoConfirm)
            {
                $input = Console::prompt("Do you want to proceed with the installation? (y/n): ");
                if(!(strtolower($input) === 'y' || strtolower($input) === 'yes'))
                {
                    Console::out("Installation cancelled by user.");
                    return 0;
                }
            }

            // Install each dependency
            $installedCount = 0;
            $failedDependencies = [];
            $options = [
                'skip-dependencies' => $skipDependencies,
                'skip-repositories' => $skipRepositories,
                'skip-extensions' => $skipExtensions,
                'reinstall' => $reinstall
            ];

            foreach($allDependencies as $depString => $packageSource)
            {
                Console::out(sprintf("Installing %s...", $depString));
                
                try
                {
                    InstallCommand::installFromRemote($packageSource, $options, [], $repositoryAuth);
                    $installedCount++;
                }
                catch(Exception $e)
                {
                    Console::error(sprintf("Failed to install %s: %s", $depString, $e->getMessage()));
                    $failedDependencies[] = $depString;
                }
            }

            // Display final summary
            Console::out('');
            Console::out(sprintf("Installation complete: %d successful, %d failed", $installedCount, count($failedDependencies)));
            
            if(count($failedDependencies) > 0)
            {
                Console::out("Failed dependencies:");
                foreach($failedDependencies as $failedDep)
                {
                    Console::out("  - " . $failedDep);
                }
                return 1;
            }

            return 0;
        }

        /**
         * Checks if a package is already installed with a version that satisfies the requested version constraint
         *
         * @param string $packageName The name of the package (e.g., "organization/name")
         * @param string|null $requestedVersion The requested version or version constraint
         * @return bool True if a satisfying version is already installed, false otherwise
         */
        private static function isPackageSatisfied(string $packageName, ?string $requestedVersion): bool
        {
            // If no specific version is requested, check if any version is installed
            if($requestedVersion === null || $requestedVersion === 'latest')
            {
                return Runtime::packageInstalled($packageName, 'latest');
            }

            // First check for exact match
            if(Runtime::packageInstalled($packageName, $requestedVersion))
            {
                return true;
            }

            // Try to find a satisfying version using semver
            try
            {
                // Get all installed versions of this package
                $allVersions = [];
                
                // Get versions from system package manager
                $systemManager = Runtime::getSystemPackageManager();
                foreach($systemManager->getAllVersions($packageName) as $entry)
                {
                    $allVersions[] = $entry->getVersion();
                }
                
                // Get versions from user package manager
                $userManager = Runtime::getUserPackageManager();
                if($userManager !== null)
                {
                    foreach($userManager->getAllVersions($packageName) as $entry)
                    {
                        $allVersions[] = $entry->getVersion();
                    }
                }

                if(empty($allVersions))
                {
                    return false;
                }

                // Remove duplicates
                $allVersions = array_unique($allVersions);

                // Try to find a satisfying version
                $satisfying = Semver::satisfiedBy($allVersions, $requestedVersion);
                
                return !empty($satisfying);
            }
            catch(Exception $e)
            {
                // If semver matching fails, fall back to exact match only
                return false;
            }
        }


    }
