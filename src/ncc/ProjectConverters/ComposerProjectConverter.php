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

    namespace ncc\ProjectConverters;

    use Exception;
    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\Libraries\fslib\IO;
    use ncc\Classes\Logger;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\semver\Constraint\Constraint;
    use ncc\Libraries\semver\VersionParser;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Runtime;

    class ComposerProjectConverter extends AbstractProjectConverter
    {
        /**
         * @inheritDoc
         */
        public function convert(string $filePath, ?string $version = null, ?callable $progressCallback = null): Project
        {
            Logger::getLogger()?->verbose(sprintf('Converting Composer project from %s', $filePath));
            if($version !== null)
            {
                Logger::getLogger()?->debug(sprintf('Using provided version: %s', $version));
            }
            $content = IO::readFile($filePath);
            $composerData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                Logger::getLogger()?->error(sprintf('Failed to parse composer.json: %s', json_last_error_msg()));
                throw new OperationException('Failed to parse JSON: ' . json_last_error_msg());
            }

            Logger::getLogger()?->debug('Successfully parsed composer.json');
            $project = new Project();

            // Apply assembly
            Logger::getLogger()?->debug('Generating assembly configuration from composer.json');
            $project->setAssembly($this->generateAssembly($composerData, $version));

            // Apply release configuration
            $releaseConfiguration = Project\BuildConfiguration::defaultRelease();
            // Exclude vendor directory from compilation to avoid permission/scanning issues
            $releaseConfiguration->addExcludedComponent('vendor' . DIRECTORY_SEPARATOR . '*');
            $releaseConfiguration->addExcludedComponent('vendor');
            
            if(isset($composerData['require']))
            {
                Logger::getLogger()?->debug(sprintf('Processing %d production dependencies', count($composerData['require'])));
                foreach($this->generateDependencies($composerData, $progressCallback) as $dependencyName => $dependencySource)
                {
                    $project->addDependency($dependencyName, $dependencySource);
                }
                Logger::getLogger()?->verbose(sprintf('Added %d production dependencies', count($this->generateDependencies($composerData))));

                // Extract PHP extensions from require
                $extensions = $this->extractExtensions($composerData['require']);
                if(!empty($extensions))
                {
                    Logger::getLogger()?->verbose(sprintf('Found %d required PHP extensions', count($extensions)));
                    foreach($extensions as $extension)
                    {
                        $project->addExtension($extension);
                        $releaseConfiguration->addExtension($extension);
                        Logger::getLogger()?->debug(sprintf('Added required extension: %s', $extension));
                    }
                }
            }

            // Apply debug configuration
            $debugConfiguration = Project\BuildConfiguration::defaultDebug();
            // Exclude vendor directory from compilation to avoid permission/scanning issues
            $debugConfiguration->addExcludedComponent('vendor' . DIRECTORY_SEPARATOR . '*');
            $debugConfiguration->addExcludedComponent('vendor');
            
            if(isset($composerData['require-dev']))
            {
                Logger::getLogger()?->debug(sprintf('Processing %d development dependencies', count($composerData['require-dev'])));
                foreach($this->generateDebugDependencies($composerData, $progressCallback) as $dependencyName => $dependencySource)
                {
                    $debugConfiguration->addDependency($dependencyName, $dependencySource);
                }
                Logger::getLogger()?->verbose(sprintf('Added %d development dependencies', count($this->generateDebugDependencies($composerData))));

                // Extract PHP extensions from require-dev
                $devExtensions = $this->extractExtensions($composerData['require-dev']);
                if(!empty($devExtensions))
                {
                    Logger::getLogger()?->verbose(sprintf('Found %d development PHP extensions', count($devExtensions)));
                    foreach($devExtensions as $extension)
                    {
                        $debugConfiguration->addExtension($extension);
                        Logger::getLogger()?->debug(sprintf('Added development extension: %s', $extension));
                    }
                }
            }

            // Copy production extensions to debug configuration
            if(isset($composerData['require']))
            {
                $extensions = $this->extractExtensions($composerData['require']);
                foreach($extensions as $extension)
                {
                    $debugConfiguration->addExtension($extension);
                }
            }

            // Apply packagist repository configurations
            Logger::getLogger()?->debug('Configuring Packagist repository');
            $project->setRepositories(new RepositoryConfiguration(
                name: 'packagist',
                type: RepositoryType::PACKAGIST,
                host: 'packagist.org',
                ssl: true
            ));

            // Get the base directory of the composer.json file
            $baseDir = dirname($filePath);
            
            // Even if target-dir is deprecated, we will still support it for setting the source path if it exists
            if(isset($composerData['target-dir']))
            {
                Logger::getLogger()?->verbose(sprintf('Using deprecated target-dir for source path: %s', $composerData['target-dir']));
                $validatedPath = $this->validateSourcePath($baseDir, $composerData['target-dir']);
                $project->setSourcePath($validatedPath ?? $this->detectSourcePath($baseDir));
            }
            // PSR-4 autoloading
            elseif(isset($composerData['autoload']['psr-4']))
            {
                Logger::getLogger()?->debug(sprintf('Processing PSR-4 autoload configuration with %d entries', count($composerData['autoload']['psr-4'])));
                // Get first item, we consider this the main source path
                $psr4Paths = array_values($composerData['autoload']['psr-4']);
                $firstPath = $psr4Paths[0] ?? 'src';
                
                // PSR-4 paths can be either strings or arrays of strings
                if(is_array($firstPath))
                {
                    $firstPath = $firstPath[0] ?? 'src';
                }
                
                // Empty string in PSR-4 means the root directory, use '.' instead
                $normalizedPath = $firstPath === '' ? '.' : rtrim($firstPath, '/\\');
                $validatedPath = $this->validateSourcePath($baseDir, $normalizedPath);
                $project->setSourcePath($validatedPath ?? $this->detectSourcePath($baseDir));
                Logger::getLogger()?->verbose(sprintf('Set source path from PSR-4 autoload: %s', $validatedPath ?? $this->detectSourcePath($baseDir)));

                // If there are more than one, add them as included components
                if(count($psr4Paths) > 1)
                {
                    Logger::getLogger()?->verbose(sprintf('Adding %d additional PSR-4 paths as included components', count($psr4Paths) - 1));
                    foreach(array_slice($psr4Paths, 1) as $item)
                    {
                        // PSR-4 paths can be either strings or arrays of strings
                        $paths = is_array($item) ? $item : [$item];
                        
                        foreach($paths as $path)
                        {
                            // Empty string means root directory
                            $includePath = $path === '' ? '.' : rtrim($path, '/\\');
                            $validatedPath = $this->validateSourcePath($baseDir, $includePath);
                            if($validatedPath !== null)
                            {
                                Logger::getLogger()?->debug(sprintf('Adding included component: %s', $includePath));
                                $releaseConfiguration->addIncludedComponent($validatedPath);
                                $debugConfiguration->addIncludedComponent($validatedPath);
                            }
                            else
                            {
                                Logger::getLogger()?->warning(sprintf('PSR-4 path does not exist, skipping: %s', $includePath));
                            }
                        }
                    }
                }
            }
            // PSR-0 autoloading or classmap
            elseif(isset($composerData['autoload']['classmap']))
            {
                Logger::getLogger()?->debug(sprintf('Processing classmap autoload configuration with %d entries', count($composerData['autoload']['classmap'])));
                // Get first item, we consider this the main source path
                $firstPath = $composerData['autoload']['classmap'][0] ?? 'src';
                // Empty string in classmap means the root directory, use '.' instead
                $normalizedPath = $firstPath === '' ? '.' : rtrim($firstPath, '/\\');
                $validatedPath = $this->validateSourcePath($baseDir, $normalizedPath);
                $project->setSourcePath($validatedPath ?? $this->detectSourcePath($baseDir));
                Logger::getLogger()?->verbose(sprintf('Set source path from classmap autoload: %s', $validatedPath ?? $this->detectSourcePath($baseDir)));

                // If there are more than one, add them as included components
                if(count($composerData['autoload']['classmap']) > 1)
                {
                    Logger::getLogger()?->verbose(sprintf('Adding %d additional classmap paths as included components', count($composerData['autoload']['classmap']) - 1));
                    foreach(array_slice($composerData['autoload']['classmap'], 1) as $item)
                    {
                        // Empty string means root directory
                        $includePath = $item === '' ? '.' : rtrim($item, '/\\');
                        $validatedPath = $this->validateSourcePath($baseDir, $includePath);
                        if($validatedPath !== null)
                        {
                            Logger::getLogger()?->debug(sprintf('Adding included component: %s', $includePath));
                            $releaseConfiguration->addIncludedComponent($validatedPath);
                            $debugConfiguration->addIncludedComponent($validatedPath);
                        }
                        else
                        {
                            Logger::getLogger()?->warning(sprintf('Classmap path does not exist, skipping: %s', $includePath));
                        }
                    }
                }
            }
            else
            {
                // No autoload information, try to find the source path automatically
                Logger::getLogger()?->debug('No autoload configuration found, attempting to detect source path automatically');
                $project->setSourcePath($this->detectSourcePath($baseDir));
                Logger::getLogger()?->verbose(sprintf('Auto-detected source path: %s', $project->getSourcePath()));
            }

            // Add any files autoloaded via files to included components
            if(isset($composerData['autoload']['files']))
            {
                Logger::getLogger()?->debug(sprintf('Adding %d autoloaded files as included components', count($composerData['autoload']['files'])));
                foreach($composerData['autoload']['files'] as $item)
                {
                    Logger::getLogger()?->verbose(sprintf('Including autoload file: %s', $item));
                    $releaseConfiguration->addIncludedComponent($item);
                    $debugConfiguration->addIncludedComponent($item);
                }
            }

            // If there are any excluded files from classmap, add them to excluded components
            if(isset($composerData['autoload']['exclude-from-classmap']))
            {
                $excludedComponents = is_array($composerData['autoload']['exclude-from-classmap']) 
                    ? $composerData['autoload']['exclude-from-classmap'] 
                    : [$composerData['autoload']['exclude-from-classmap']];
                    
                Logger::getLogger()?->debug(sprintf('Adding %d excluded components', count($excludedComponents)));
                foreach($excludedComponents as $component)
                {
                    Logger::getLogger()?->verbose(sprintf('Excluding component: %s', $component));
                    $releaseConfiguration->addExcludedComponent($component);
                    $debugConfiguration->addExcludedComponent($component);
                }
            }

            $project->addBuildConfiguration($releaseConfiguration);
            $project->addBuildConfiguration($debugConfiguration);
            Logger::getLogger()?->debug('Added release and debug build configurations');

            if(isset($composerData['bin']) && is_array($composerData['bin']) && count($composerData['bin']) > 0)
            {
                Logger::getLogger()?->verbose(sprintf('Generating execution unit from %d bin entries', count($composerData['bin'])));
                $project->addExecutionUnit($this->generateExecutionUnit($composerData));
                $project->setEntryPoint('main');
                Logger::getLogger()?->verbose('Set entry point to "main" from composer bin field');
                
                // Add Composer compatibility stub files to the build
                $this->addComposerCompatibilityStubs($baseDir, $releaseConfiguration, $debugConfiguration);
            }

            Logger::getLogger()?->verbose('Successfully converted Composer project');
            return $project;
        }

        /**
         * Generate an execution unit from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return Project\ExecutionUnit The generated execution unit.
         */
        private function generateExecutionUnit(array $composerData): Project\ExecutionUnit
        {
            Logger::getLogger()?->verbose(sprintf('Generating execution unit with entry point: %s', $composerData['bin'][0]));
            return new Project\ExecutionUnit([
                'name' => 'main',
                'type' => 'php',
                'mode' => 'auto',
                'entry' => $composerData['bin'][0], // TODO: Ensure the entry is considered to be a required file
                'working_directory' => MacroVariable::CURRENT_WORKING_DIRECTORY->value,
            ]);
        }

        /**
         * Generate dependencies from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @param callable|null $progressCallback Optional callback for progress updates.
         * @return array An associative array of dependency names to PackageSource objects.
         */
        private function generateDependencies(array $composerData, ?callable $progressCallback = null): array
        {
            $dependencies = [];
            foreach ($composerData['require'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    Logger::getLogger()?->debug(sprintf('Skipping PHP/extension requirement: %s', $dependency));
                    continue;
                }

                // Skip Composer virtual packages (composer-runtime-api, composer-plugin-api, etc.)
                if(!str_contains($dependency, '/'))
                {
                    Logger::getLogger()?->debug(sprintf('Skipping Composer virtual package: %s', $dependency));
                    continue;
                }

                if($progressCallback !== null)
                {
                    $progressCallback(sprintf('Resolving dependency %s', $dependency));
                }

                Logger::getLogger()?->verbose(sprintf('Processing dependency: %s@%s', $dependency, $version));
                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                $resolvedVersion = $this->resolvePackageVersion($dependency, $version);
                Logger::getLogger()?->debug(sprintf('Resolved %s version %s to %s', $dependency, $version, $resolvedVersion));
                $dependencies[$this->generatePackageName($dependency)]->setVersion($resolvedVersion);
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate debug dependencies from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @param callable|null $progressCallback Optional callback for progress updates.
         * @return array An associative array of dependency names to PackageSource objects for debug dependencies.
         */
        private function generateDebugDependencies(array $composerData, ?callable $progressCallback = null): array
        {
            $dependencies = [];
            foreach ($composerData['require-dev'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    Logger::getLogger()?->debug(sprintf('Skipping PHP/extension development requirement: %s', $dependency));
                    continue;
                }

                // Skip Composer virtual packages (composer-runtime-api, composer-plugin-api, etc.)
                if(!str_contains($dependency, '/'))
                {
                    Logger::getLogger()?->debug(sprintf('Skipping Composer virtual package: %s', $dependency));
                    continue;
                }

                if($progressCallback !== null)
                {
                    $progressCallback(sprintf('Resolving dev dependency %s', $dependency));
                }

                Logger::getLogger()?->verbose(sprintf('Processing development dependency: %s@%s', $dependency, $version));
                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                $resolvedVersion = $this->resolvePackageVersion($dependency, $version);
                Logger::getLogger()?->debug(sprintf('Resolved %s version %s to %s', $dependency, $version, $resolvedVersion));
                $dependencies[$this->generatePackageName($dependency)]->setVersion($resolvedVersion);
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate a Project\Assembly object from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @param string|null $providedVersion Optional version provided externally (from repository)
         * @return Project\Assembly The generated assembly object.
         */
        private function generateAssembly(array $composerData, ?string $providedVersion = null): Project\Assembly
        {
            Logger::getLogger()?->debug('Generating assembly configuration');
            $assembly = new Project\Assembly();

            // Description
            if(isset($composerData['description']))
            {
                Logger::getLogger()?->verbose(sprintf('Setting description: %s', substr($composerData['description'], 0, 50) . (strlen($composerData['description']) > 50 ? '...' : '')));
                $assembly->setDescription($composerData['description']);
            }

            // Homepage
            if(isset($composerData['homepage']))
            {
                Logger::getLogger()?->verbose(sprintf('Setting homepage: %s', $composerData['homepage']));
                $assembly->setUrl($composerData['homepage']);
            }

            // Authors
            if(isset($composerData['authors']) && count($composerData['authors']) > 0)
            {
                Logger::getLogger()?->debug(sprintf('Processing %d authors', count($composerData['authors'])));
                if(isset($composerData['authors']['name']))
                {
                    $assembly->setAuthor(sprintf("%s %s%s",
                        $composerData['authors']['name'],
                        ($composerData['authors']['email'] ?' <' . $composerData['authors']['email'] . '>' : ''),
                        ($composerData['authors']['homepage'] ? ' (' . $composerData['authors']['homepage'] . ')' : '')
                    ));
                }
                else
                {
                    $authorString = (string)null;
                    foreach($composerData['authors'] as $author)
                    {
                        if(isset($authorString[0]))
                        {
                            $authorString .= ', ';
                        }

                        $authorString .= sprintf("%s %s%s",
                            $author['name'],
                            (isset($author['email']) ? ' <' . $author['email'] . '>' : ''),
                            (isset($author['homepage']) ? ' (' . $author['homepage'] . ')' : '')
                        );
                    }

                    $assembly->setAuthor($authorString);
                }
            }

            // License
            if(isset($composerData['license']))
            {
                $license = is_array($composerData['license']) 
                    ? implode(' OR ', $composerData['license']) 
                    : $composerData['license'];
                Logger::getLogger()?->verbose(sprintf('Setting license: %s', $license));
                $assembly->setLicense($license);
            }

            // Set the package identifier (e.g., com.symfony.process)
            $packageName = $this->generatePackageName($composerData['name']);
            Logger::getLogger()?->debug(sprintf('Generated package name: %s from %s', $packageName, $composerData['name']));
            $assembly->setPackage($packageName);
            
            // Set the assembly name from the composer package name (without vendor prefix)
            // Assembly names must not contain slashes to avoid path conflicts
            list($name) = explode('/', $composerData['name'], 2);
            $assembly->setName($name);

            // Extract and normalize version
            // First prioritize the version provided externally (from repository)
            if($providedVersion !== null)
            {
                Logger::getLogger()?->verbose(sprintf('Using externally provided version: %s', $providedVersion));
                try
                {
                    $normalizedVersion = self::normalizeVersion($providedVersion);
                    $assembly->setVersion($normalizedVersion);
                    Logger::getLogger()?->verbose(sprintf('Set assembly version to %s', $normalizedVersion));
                }
                catch(Exception $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to normalize provided version %s: %s, falling back to composer.json', $providedVersion, $e->getMessage()));
                    // Fall through to check composer.json version
                }
            }
            // If no provided version or normalization failed, check for explicit version field in composer.json
            elseif(isset($composerData['version']))
            {
                Logger::getLogger()?->verbose(sprintf('Found explicit version in composer.json: %s', $composerData['version']));
                try
                {
                    $normalizedVersion = self::normalizeVersion($composerData['version']);
                    $assembly->setVersion($normalizedVersion);
                    Logger::getLogger()?->verbose(sprintf('Set assembly version to %s', $normalizedVersion));
                }
                catch(Exception $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to normalize version %s: %s', $composerData['version'], $e->getMessage()));
                    // If version normalization fails, keep default 0.0.0
                }
            }
            // If no version field, check for branch-alias in extra section
            elseif(isset($composerData['extra']['branch-alias']))
            {
                Logger::getLogger()?->verbose('Attempting to extract version from branch-alias');
                try
                {
                    // Get the first branch alias (typically dev-main or dev-master)
                    $branchAliases = $composerData['extra']['branch-alias'];
                    if(is_array($branchAliases) && count($branchAliases) > 0)
                    {
                        // Get the first alias value (e.g., "2.9-dev" or "7.1.x-dev")
                        $aliasVersion = reset($branchAliases);
                        
                        // Try to extract the numeric version from the alias
                        // "2.9-dev" -> "2.9.0", "7.1.x-dev" -> "7.1.9999999"
                        if(preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?(?:\.x)?-dev$/', $aliasVersion, $matches))
                        {
                            $major = $matches[1];
                            $minor = $matches[2];
                            $patch = isset($matches[3]) ? $matches[3] : '0';
                            $normalizedVersion = sprintf('%d.%d.%d', $major, $minor, $patch);
                            $assembly->setVersion($normalizedVersion);
                            Logger::getLogger()?->verbose(sprintf('Set assembly version to %s from branch-alias', $normalizedVersion));
                        }
                    }
                }
                catch(Exception $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to parse branch-alias: %s', $e->getMessage()));
                    // If branch-alias parsing fails, keep default 0.0.0
                }
            }
            else
            {
                Logger::getLogger()?->verbose('No version information found, using default version 0.0.0');
            }

            return $assembly;
        }

        /**
         * Resolve a package version constraint to an actual version
         *
         * @param string $package The package name (e.g., "symfony/process")
         * @param string $versionConstraint The version constraint (e.g., "^5.0", "~3.4", "*")
         * @return string The resolved version or 'latest' if resolution fails
         */
        private function resolvePackageVersion(string $package, string $versionConstraint): string
        {
            Logger::getLogger()?->debug(sprintf('Resolving version for %s with constraint %s', $package, $versionConstraint));
            // If it's already a specific version, normalize and return it
            $versionParser = new VersionParser();
            if($versionParser->isValid($versionConstraint))
            {
                try
                {
                    $normalized = $versionParser->normalize($versionConstraint);
                    Logger::getLogger()?->debug(sprintf('Version constraint %s is a specific version, normalized to %s', $versionConstraint, $normalized));
                    return $normalized;
                }
                catch(Exception $e)
                {
                    Logger::getLogger()?->debug(sprintf('Failed to normalize version %s: %s', $versionConstraint, $e->getMessage()));
                    // Fall through to constraint resolution
                }
            }

            // For wildcard or constraint, we need to query packagist
            Logger::getLogger()?->verbose(sprintf('Querying Packagist for available versions of %s', $package));
            try
            {
                if(!Runtime::repositoryExists('packagist'))
                {
                    Logger::getLogger()?->warning('Packagist repository not configured, defaulting to latest');
                    return 'latest';
                }

                $repository = Runtime::getRepository('packagist')->createClient();
                list($vendor, $name) = explode('/', $package, 2);
                
                // Get all available versions
                $versions = $repository->getReleases($vendor, $name);
                Logger::getLogger()?->verbose(sprintf('Found %d versions for %s', count($versions), $package));
                
                // Parse the constraint
                $constraint = $versionParser->parseConstraints($versionConstraint);
                
                // Filter out dev versions and sort in descending order
                $stableVersions = array_filter($versions, function($version) {
                    return stripos($version, '-dev') === false;
                });
                Logger::getLogger()?->debug(sprintf('Filtered to %d stable versions', count($stableVersions)));
                
                // Sort versions in descending order using Semver
                usort($stableVersions, function($a, $b) use ($versionParser)
                {
                    try
                    {
                        $normalizedA = $versionParser->normalize($a);
                        $normalizedB = $versionParser->normalize($b);
                        return version_compare($normalizedB, $normalizedA);
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()?->warning(sprintf('Failed to compare versions %s and %s: %s', $a, $b, $e->getMessage()), $e);
                        return 0;
                    }
                });
                
                // Find the latest version that satisfies the constraint
                foreach($stableVersions as $version)
                {
                    try
                    {
                        $normalizedVersion = $versionParser->normalize($version);
                        if($constraint->matches(new Constraint('==', $normalizedVersion)))
                        {
                            Logger::getLogger()?->verbose(sprintf('Found matching version %s for constraint %s on package %s', $normalizedVersion, $versionConstraint, $package));
                            return $normalizedVersion;
                        }
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()?->debug(sprintf('Failed to check version %s: %s', $version, $e->getMessage()));
                        continue;
                    }
                }
                Logger::getLogger()?->warning(sprintf('No matching version found for constraint %s on package %s, defaulting to latest', $versionConstraint, $package));
            }
            catch(Exception $e)
            {
                Logger::getLogger()?->warning(sprintf('Failed to resolve version for package %s: %s, defaulting to latest.', $package, $e->getMessage()));
            }

            return 'latest';
        }

        /**
         * Generate a package name from a Composer package name.
         *
         * @param string $composerPackageName The Composer package name (e.g., "vendor/package-name").
         * @return string The generated package name (e.g., "com.vendor.packagename").
         */
        private function generatePackageName(string $composerPackageName): string
        {
            return sprintf("%s.%s.%s",
                'com',
                str_replace('-', '_', explode('/', $composerPackageName)[0]),
                str_replace('-', '_', explode('/', $composerPackageName)[1])
            );
        }

        /**
         * Validates and normalizes a source path, checking if it exists in the base directory.
         * 
         * @param string $baseDir The base directory (where composer.json is located).
         * @param string $sourcePath The source path to validate (relative to baseDir).
         * @return string|null The validated source path, or null if the path doesn't exist.
         */
        private function validateSourcePath(string $baseDir, string $sourcePath): ?string
        {
            // Normalize the path
            $normalizedPath = rtrim($sourcePath, '/\\');
            
            // '.' means root directory, which always exists
            if($normalizedPath === '.')
            {
                Logger::getLogger()?->debug('Source path is root directory');
                return '.';
            }
            
            // Check if the path exists
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $normalizedPath;
            if(IO::isDirectory($fullPath) || IO::isFile($fullPath))
            {
                Logger::getLogger()?->debug(sprintf('Validated source path: %s', $normalizedPath));
                return $normalizedPath;
            }
            
            // Path doesn't exist, return null
            Logger::getLogger()?->warning(sprintf('Source path does not exist: %s', $fullPath));
            return null;
        }

        /**
         * Adds Composer compatibility stub files to the build configuration.
         * Creates vendor/autoload.php stubs that will be included in the package,
         * allowing Composer bin scripts to find them at runtime without littering the filesystem.
         *
         * @param string $baseDir The base directory where composer.json is located.
         * @param Project\BuildConfiguration $releaseConfig The release build configuration.
         * @param Project\BuildConfiguration $debugConfig The debug build configuration.
         * @return void
         */
        private function addComposerCompatibilityStubs(string $baseDir, Project\BuildConfiguration $releaseConfig, Project\BuildConfiguration $debugConfig): void
        {
            Logger::getLogger()?->verbose('Adding Composer compatibility stub files to build');
            
            // Create a minimal autoload stub that does nothing (ncc already handles autoloading)
            $autoloadStub = <<<'PHP'
<?php
/**
 * NCC Composer Compatibility Stub
 * 
 * This file provides compatibility for Composer packages that check for
 * or require vendor/autoload.php. Since ncc handles autoloading through
 * its Runtime system, this file exists only to satisfy existence checks.
 * 
 * Generated by ncc's ComposerProjectConverter
 */

// NCC already handles autoloading - nothing to do here
return true;

PHP;
            
            // Create stub files in the base directory
            // These will be picked up during the build process and included in the package
            $stubLocations = [
                'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
                'autoload.php',
            ];
            
            foreach ($stubLocations as $location)
            {
                $fullPath = $baseDir . DIRECTORY_SEPARATOR . $location;
                $directory = dirname($fullPath);
                
                // Create directory if needed, or fix permissions if it exists
                if (!IO::isDirectory($directory))
                {
                    try
                    {
                        IO::createDirectory($directory, 0755, true);
                        Logger::getLogger()?->verbose(sprintf('Created directory: %s', $directory));
                    }
                    catch (Exception $e)
                    {
                        Logger::getLogger()?->warning(sprintf('Failed to create directory %s: %s', $directory, $e->getMessage()));
                        continue;
                    }
                }
                
                // Ensure directory is writable - fix permissions if needed
                if (IO::exists($directory) && !IO::isWritable($directory))
                {
                    try
                    {
                        @chmod($directory, 0755);
                        Logger::getLogger()?->debug(sprintf('Fixed permissions for directory: %s', $directory));
                    }
                    catch (Exception $e)
                    {
                        Logger::getLogger()?->warning(sprintf('Could not fix permissions for %s: %s', $directory, $e->getMessage()));
                        continue;
                    }
                }
                
                // Skip if directory still not writable
                if (!IO::isWritable($directory))
                {
                    Logger::getLogger()?->warning(sprintf('Directory not writable, skipping stub creation: %s', $directory));
                    continue;
                }
                
                // Write the stub file
                try
                {
                    IO::writeFile($fullPath, $autoloadStub);
                    Logger::getLogger()?->verbose(sprintf('Created Composer compatibility stub: %s', $location));
                    
                    // Add the stub file as an included component so it gets packaged
                    if (IO::exists($fullPath))
                    {
                        $releaseConfig->addIncludedComponent($location);
                        $debugConfig->addIncludedComponent($location);
                        Logger::getLogger()?->debug(sprintf('Added stub to build configurations: %s', $location));
                    }
                }
                catch (Exception $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to create compatibility stub %s: %s', $location, $e->getMessage()));
                }
            }
        }

        /**
         * Automatically detects the source path by looking for common directory structures.
         * 
         * @param string $baseDir The base directory to search in.
         * @return string The detected source path (defaults to '.' if nothing specific is found).
         */
        private function detectSourcePath(string $baseDir): string
        {
            Logger::getLogger()?->debug(sprintf('Auto-detecting source path in %s', $baseDir));
            // Common source directory names to check, in order of preference
            $commonSourceDirs = ['src', 'lib', 'Source', 'includes', 'app'];
            
            foreach($commonSourceDirs as $dir)
            {
                if(IO::isDirectory($baseDir . DIRECTORY_SEPARATOR . $dir))
                {
                    Logger::getLogger()?->verbose(sprintf('Detected source directory: %s', $dir));
                    return $dir;
                }
            }
            
            // If no common source directory found, check if there are any .php files in the root
            // If yes, use the root directory as source
            $files = glob($baseDir . DIRECTORY_SEPARATOR . '*.php');
            if(!empty($files))
            {
                Logger::getLogger()?->verbose('Found PHP files in root directory, using root as source path');
                return '.';
            }
            
            // Default to root directory if nothing else is found
            Logger::getLogger()?->warning('No source directory detected, defaulting to root directory');
            return '.';
        }

        /**
         * Normalize and resolve a version string to a valid semantic version format.
         * This method handles various version formats including those with 'v' prefixes,
         * and ensures the version conforms to ncc's 3-component format (X.Y.Z).
         *
         * @param string $version The version string to normalize.
         * @return string The normalized version.
         * @throws OperationException If the version cannot be normalized to a valid format.
         */
        public static function normalizeVersion(string $version): string
        {
            Logger::getLogger()?->debug(sprintf('Normalizing version: "%s"', $version));
            $versionParser = new VersionParser();
            
            try
            {
                Logger::getLogger()?->debug(sprintf('Testing if version is valid: "%s"', $version));
                if($versionParser->isValid($version))
                {
                    Logger::getLogger()?->debug('Version is valid, normalizing...');
                    $normalizedVersion = $versionParser->normalize($version);
                    Logger::getLogger()?->debug(sprintf('Normalized version: "%s"', $normalizedVersion));
                    
                    // The VersionParser may add a 4th component (e.g., "8.0.0.0")
                    // but ncc expects 3-component semantic versions (X.Y.Z)
                    // Strip the 4th component if present
                    if(preg_match('/^(\d+\.\d+\.\d+)\.0$/', $normalizedVersion, $matches))
                    {
                        $normalizedVersion = $matches[1];
                        Logger::getLogger()?->debug(sprintf('Stripped 4th component, using: "%s"', $normalizedVersion));
                    }
                    
                    return $normalizedVersion;
                }
                else
                {
                    Logger::getLogger()?->debug('Version is not valid, trying to clean it...');
                    // If normalization fails, strip common prefixes and try again
                    $cleanVersion = ltrim($version, 'vV');
                    Logger::getLogger()?->debug(sprintf('Cleaned version: "%s"', $cleanVersion));
                    
                    if($versionParser->isValid($cleanVersion))
                    {
                        Logger::getLogger()?->debug('Cleaned version is valid, normalizing...');
                        $normalizedVersion = $versionParser->normalize($cleanVersion);
                        Logger::getLogger()?->debug(sprintf('Normalized version: "%s"', $normalizedVersion));
                        
                        // Strip the 4th component if present
                        if(preg_match('/^(\d+\.\d+\.\d+)\.0$/', $normalizedVersion, $matches))
                        {
                            $normalizedVersion = $matches[1];
                            Logger::getLogger()?->debug(sprintf('Stripped 4th component, using: "%s"', $normalizedVersion));
                        }
                        
                        return $normalizedVersion;
                    }
                    else
                    {
                        Logger::getLogger()?->debug('Cleaned version is still invalid, checking regex pattern...');
                        // Last resort: use the version as-is if it looks like a semantic version
                        if(preg_match('/^\d+\.\d+\.\d+/', $cleanVersion))
                        {
                            Logger::getLogger()?->debug(sprintf('Using version as-is: "%s"', $cleanVersion));
                            return $cleanVersion;
                        }
                        else
                        {
                            throw new OperationException(sprintf('Invalid version format received: "%s"', $version));
                        }
                    }
                }
            }
            catch(OperationException $e)
            {
                // Re-throw OperationException as-is
                throw $e;
            }
            catch(Exception $e)
            {
                Logger::getLogger()?->error(sprintf('Exception during version normalization: %s', $e->getMessage()));
                throw new OperationException(sprintf('Failed to normalize version "%s": %s', $version, $e->getMessage()));
            }
        }

        /**
         * Extract PHP extensions from composer require section.
         *
         * @param array $requirements The require or require-dev section from composer.json
         * @return string[] An array of PHP extension names (without 'ext-' prefix)
         */
        private function extractExtensions(array $requirements): array
        {
            $extensions = [];
            
            foreach($requirements as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-'))
                {
                    $extensionName = substr($dependency, 4);
                    $extensions[] = $extensionName;
                }
            }
            
            return $extensions;
        }
    }