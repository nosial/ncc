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
    use ncc\Libraries\fslib\IO;
    use ncc\Classes\PackageReader;
    use ncc\CLI\Commands\Helper;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Objects\Project;
    use ncc\Runtime;

    class GenerateStubs extends AbstractCommandHandler
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
            $outputPath = $argv['output'] ?? $argv['o'] ?? null;
            $buildConfiguration = $argv['build'] ?? $argv['b'] ?? null;

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
            // Key = actual installed package name (e.g., net.nosial.optslib)
            // Value = array with 'source' (PackageSource) and 'from' (source description)
            $allDependencies = [];

            // Get project-level dependencies
            if($projectConfiguration->getDependencies() !== null)
            {
                foreach($projectConfiguration->getDependencies() as $packageName => $packageSource)
                {
                    if(!isset($allDependencies[$packageName]))
                    {
                        $allDependencies[$packageName] = [
                            'source' => $packageSource,
                            'from' => 'project'
                        ];
                    }
                }
            }

            // Get build configuration dependencies
            if($buildConfiguration !== null)
            {
                // Extract stubs for specific build configuration
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
                        if(!isset($allDependencies[$packageName]))
                        {
                            $allDependencies[$packageName] = [
                                'source' => $packageSource,
                                'from' => "build:{$buildConfiguration}"
                            ];
                        }
                    }
                }
            }
            else
            {
                // Extract stubs for all build configurations
                foreach($projectConfiguration->getBuildConfigurations() as $buildConfig)
                {
                    if($buildConfig->getDependencies() !== null)
                    {
                        foreach($buildConfig->getDependencies() as $packageName => $packageSource)
                        {
                            if(!isset($allDependencies[$packageName]))
                            {
                                $allDependencies[$packageName] = [
                                    'source' => $packageSource,
                                    'from' => "build:{$buildConfig->getName()}"
                                ];
                            }
                        }
                    }
                }
            }

            // Check if there are any dependencies to extract
            if(count($allDependencies) === 0)
            {
                Console::out("No dependencies found in the project configuration.");
                return 0;
            }

            // Display summary of dependencies
            Console::out(sprintf("Found %d dependencies:", count($allDependencies)));
            foreach($allDependencies as $packageName => $depInfo)
            {
                Console::out(sprintf("  - %s [%s] (from %s)", $packageName, (string)$depInfo['source'], $depInfo['from']));
            }
            Console::out('');

            // Check if packages are installed and collect missing ones
            $missingPackages = [];
            $installedPackageReaders = [];

            foreach($allDependencies as $packageName => $depInfo)
            {
                $packageSource = $depInfo['source'];
                $packageVersion = $packageSource->getVersion() ?? 'latest';

                if(!Runtime::packageInstalled($packageName, $packageVersion))
                {
                    // Try to find any installed version if no exact match
                    $userManager = Runtime::getUserPackageManager();
                    $systemManager = Runtime::getSystemPackageManager();
                    
                    $foundVersion = false;
                    
                    // Check system manager
                    $systemVersions = $systemManager->getAllVersions($packageName);
                    if(!empty($systemVersions))
                    {
                        $foundVersion = true;
                        $packageVersion = 'latest'; // Will use latest available
                    }
                    
                    // Check user manager if system didn't have it
                    if(!$foundVersion && $userManager !== null)
                    {
                        $userVersions = $userManager->getAllVersions($packageName);
                        if(!empty($userVersions))
                        {
                            $foundVersion = true;
                            $packageVersion = 'latest'; // Will use latest available
                        }
                    }
                    
                    if(!$foundVersion)
                    {
                        $missingPackages[] = $packageName . ' [' . (string)$packageSource . ']';
                        continue;
                    }
                }

                // Get the package path
                $packagePath = Runtime::getPackagePath($packageName, $packageVersion);
                if($packagePath === null)
                {
                    $missingPackages[] = $packageName . ' [' . (string)$packageSource . ']';
                    continue;
                }

                try
                {
                    $packageReader = new PackageReader($packagePath);
                    $installedPackageReaders[$packageName] = $packageReader;
                }
                catch(Exception $e)
                {
                    Console::error(sprintf("Failed to read package %s: %s", $packageName, $e->getMessage()));
                    $missingPackages[] = $packageName . ' [' . (string)$packageSource . ']';
                }
            }

            // Report missing packages
            if(count($missingPackages) > 0)
            {
                Console::error(sprintf("Cannot generate stubs: %d package(s) are not installed", count($missingPackages)));
                Console::out('');
                Console::out("Missing packages:");
                foreach($missingPackages as $missingPackage)
                {
                    Console::out("  - " . $missingPackage);
                }
                Console::out('');
                Console::out("To install missing dependencies, run:");
                Console::out("  ncc project install");
                return 1;
            }

            // Resolve transitive dependencies for all installed packages
            // This includes dependencies of dependencies recursively
            $visited = [];
            foreach($installedPackageReaders as $packageName => $packageReader)
            {
                $visited[$packageName] = true;
            }

            $initialCount = count($installedPackageReaders);
            foreach($installedPackageReaders as $packageReader)
            {
                self::resolveTransitiveDependencies($packageReader, $visited, $installedPackageReaders);
            }

            $transitiveCount = count($installedPackageReaders) - $initialCount;
            if($transitiveCount > 0)
            {
                Console::out(sprintf("Resolved %d transitive dependencie(s)", $transitiveCount));
                Console::out('');
            }

            // Determine output path
            if($outputPath === null)
            {
                // Default to vendor directory in current working directory
                $outputPath = getcwd() . DIRECTORY_SEPARATOR . 'vendor';
            }
            else
            {
                $outputPath = IO::getRealPath($outputPath);
                if($outputPath === null)
                {
                    Console::error("The specified output path does not exist");
                    return 1;
                }
            }

            // Create vendor directory if it doesn't exist
            if(!IO::exists($outputPath))
            {
                try
                {
                    IO::createDirectory($outputPath);
                }
                catch(Exception $e)
                {
                    Console::error("Failed to create output directory: " . $e->getMessage());
                    return 1;
                }
            }

            Console::out(sprintf("Generating stubs to: %s", $outputPath));
            Console::out('');

            // Extract each package
            $extractedCount = 0;
            $failedPackages = [];

            foreach($installedPackageReaders as $packageName => $packageReader)
            {
                $packageIdentifier = sprintf("%s=%s", $packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());
                Console::out(sprintf("Extracting %s...", $packageIdentifier));

                try
                {
                    // Extract to vendor/<package-name>
                    $packageOutputPath = $outputPath . DIRECTORY_SEPARATOR . $packageReader->getAssembly()->getPackage();
                    
                    // Remove existing directory if it exists
                    if(IO::exists($packageOutputPath))
                    {
                        IO::delete($packageOutputPath, true);
                    }

                    $packageReader->extract($packageOutputPath);
                    $extractedCount++;
                    Console::out(sprintf("Extracted to %s", $packageOutputPath));
                }
                catch(Exception $e)
                {
                    Console::error(sprintf("Failed to extract %s: %s", $packageIdentifier, $e->getMessage()));
                    $failedPackages[] = $packageIdentifier;
                }
            }

            // Generate the main autoload.php that loads all package autoloaders
            Console::out('');
            Console::out("Generating vendor/autoload.php...");
            
            try
            {
                $autoloadContent = self::generateVendorAutoload($installedPackageReaders, $outputPath);
                IO::writeFile($outputPath . DIRECTORY_SEPARATOR . 'autoload.php', $autoloadContent);
                Console::out("Generated vendor/autoload.php");
            }
            catch(Exception $e)
            {
                Console::error(sprintf("Failed to generate autoload.php: %s", $e->getMessage()));
                return 1;
            }

            // Display final summary
            Console::out('');
            Console::out(sprintf("Stub generation complete: %d successful, %d failed", $extractedCount, count($failedPackages)));
            
            if(count($failedPackages) > 0)
            {
                Console::out("Failed packages:");
                foreach($failedPackages as $failedPkg)
                {
                    Console::out("  - " . $failedPkg);
                }
                return 1;
            }

            return 0;
        }

        /**
         * Recursively resolves transitive dependencies for a given package reader.
         * Missing transitive dependencies emit a warning and are skipped.
         *
         * @param PackageReader $packageReader The package reader to resolve dependencies for
         * @param array $visited Reference to array of visited package names (prevents circular dependencies)
         * @param array $installedPackageReaders Reference to the array of installed package readers
         */
        private static function resolveTransitiveDependencies(PackageReader $packageReader, array &$visited, array &$installedPackageReaders): void
        {
            $dependencyReferences = $packageReader->getHeader()->getDependencyReferences();

            foreach($dependencyReferences as $dependencyReference)
            {
                $depPackageName = $dependencyReference->getPackage();

                // Skip if already visited (circular dependency prevention) or already resolved
                if(isset($visited[$depPackageName]) || isset($installedPackageReaders[$depPackageName]))
                {
                    $visited[$depPackageName] = true;
                    continue;
                }

                $visited[$depPackageName] = true;
                $depVersion = $dependencyReference->getVersion() ?? 'latest';

                try
                {
                    // Check if the package is installed
                    if(!Runtime::packageInstalled($depPackageName, $depVersion))
                    {
                        // Try to find any installed version
                        $userManager = Runtime::getUserPackageManager();
                        $systemManager = Runtime::getSystemPackageManager();
                        $found = false;

                        $systemVersions = $systemManager->getAllVersions($depPackageName);
                        if(!empty($systemVersions))
                        {
                            $found = true;
                            $depVersion = 'latest';
                        }

                        if(!$found && $userManager !== null)
                        {
                            $userVersions = $userManager->getAllVersions($depPackageName);
                            if(!empty($userVersions))
                            {
                                $found = true;
                                $depVersion = 'latest';
                            }
                        }

                        if(!$found)
                        {
                            Console::out(sprintf("  [warning] Transitive dependency \"%s\" is not installed, skipping", $depPackageName));
                            continue;
                        }
                    }

                    $depPath = Runtime::getPackagePath($depPackageName, $depVersion);
                    if($depPath === null)
                    {
                        Console::out(sprintf("  [warning] Transitive dependency \"%s\" is not installed, skipping", $depPackageName));
                        continue;
                    }

                    $depReader = new PackageReader($depPath);
                    $installedPackageReaders[$depPackageName] = $depReader;

                    // Recursively resolve further transitive dependencies
                    self::resolveTransitiveDependencies($depReader, $visited, $installedPackageReaders);
                }
                catch(Exception $e)
                {
                    Console::out(sprintf("  [warning] Failed to resolve transitive dependency \"%s\": %s", $depPackageName, $e->getMessage()));
                }
            }
        }

        /**
         * Generates the main vendor/autoload.php file that loads all package autoloaders
         *
         * @param array $packageReaders Array of PackageReader instances
         * @param string $vendorPath Path to the vendor directory
         * @return string The generated autoload.php content
         */
        private static function generateVendorAutoload(array $packageReaders, string $vendorPath): string
        {
            $content = "<?php\n";
            $content .= "/**\n";
            $content .= " * autoloader for ncc packages\n";
            $content .= " * Generated by: ncc project stubs\n";
            $content .= " * Generated on: " . date('Y-m-d H:i:s') . "\n";
            $content .= " */\n\n";

            foreach($packageReaders as $packageName => $packageReader)
            {
                $packagePath = $packageReader->getAssembly()->getPackage();
                $autoloadPath = $vendorPath . DIRECTORY_SEPARATOR . $packagePath . DIRECTORY_SEPARATOR . 'autoload.php';
                
                if(IO::exists($autoloadPath))
                {
                    $content .= "// Load {$packageName}\n";
                    $content .= "if(file_exists(__DIR__ . '/{$packagePath}/autoload.php')) {\n";
                    $content .= "    require_once __DIR__ . '/{$packagePath}/autoload.php';\n";
                    $content .= "}\n\n";
                }
            }
            
            return $content;
        }


    }
