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

    namespace ncc\ProjectConverters;

    use Exception;
    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\Classes\IO;
    use ncc\CLI\Logger;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\IOException;
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
        public function convert(string $filePath): Project
        {
            $content = IO::readFile($filePath);
            $composerData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new IOException('Failed to parse JSON: ' . json_last_error_msg());
            }

            $project = new Project();

            // Apply assembly
            $project->setAssembly($this->generateAssembly($composerData));

            // Apply release configuration
            $releaseConfiguration = Project\BuildConfiguration::defaultRelease();
            if(isset($composerData['require']))
            {
                foreach($this->generateDependencies($composerData) as $dependencyName => $dependencySource)
                {
                    $project->addDependency($dependencyName, $dependencySource);
                }
            }

            // Apply debug configuration
            $debugConfiguration = Project\BuildConfiguration::defaultDebug();
            if(isset($composerData['require-dev']))
            {
                foreach($this->generateDebugDependencies($composerData) as $dependencyName => $dependencySource)
                {
                    $debugConfiguration->addDependency($dependencyName, $dependencySource);
                }
            }

            // Apply packagist repository configurations
            $project->setRepository(new RepositoryConfiguration(
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
                $project->setSourcePath($this->validateSourcePath($baseDir, $composerData['target-dir']));
            }
            // PSR-4 autoloading
            elseif(isset($composerData['autoload']['psr-4']))
            {
                // Get first item, we consider this the main source path
                $psr4Paths = array_values($composerData['autoload']['psr-4']);
                $firstPath = $psr4Paths[0] ?? 'src';
                // Empty string in PSR-4 means the root directory, use '.' instead
                $normalizedPath = $firstPath === '' ? '.' : rtrim($firstPath, '/\\');
                $project->setSourcePath($this->validateSourcePath($baseDir, $normalizedPath));

                // If there are more than one, add them as included components
                if(count($psr4Paths) > 1)
                {
                    foreach(array_slice($psr4Paths, 1) as $item)
                    {
                        // Empty string means root directory
                        $includePath = $item === '' ? '.' : rtrim($item, '/\\');
                        $validatedPath = $this->validateSourcePath($baseDir, $includePath);
                        if($validatedPath !== null)
                        {
                            $releaseConfiguration->addIncludedComponent($validatedPath);
                            $debugConfiguration->addIncludedComponent($validatedPath);
                        }
                    }
                }
            }
            // PSR-0 autoloading or classmap
            elseif(isset($composerData['autoload']['classmap']))
            {
                // Get first item, we consider this the main source path
                $firstPath = $composerData['autoload']['classmap'][0] ?? 'src';
                // Empty string in classmap means the root directory, use '.' instead
                $normalizedPath = $firstPath === '' ? '.' : rtrim($firstPath, '/\\');
                $project->setSourcePath($this->validateSourcePath($baseDir, $normalizedPath));

                // If there are more than one, add them as included components
                if(count($composerData['autoload']['classmap']) > 1)
                {
                    foreach(array_slice($composerData['autoload']['classmap'], 1) as $item)
                    {
                        // Empty string means root directory
                        $includePath = $item === '' ? '.' : rtrim($item, '/\\');
                        $validatedPath = $this->validateSourcePath($baseDir, $includePath);
                        if($validatedPath !== null)
                        {
                            $releaseConfiguration->addIncludedComponent($validatedPath);
                            $debugConfiguration->addIncludedComponent($validatedPath);
                        }
                    }
                }
            }
            else
            {
                // No autoload information, try to find the source path automatically
                $project->setSourcePath($this->detectSourcePath($baseDir));
            }

            // Add any files autoloaded via files to included components
            if(isset($composerData['autoload']['files']))
            {
                foreach($composerData['autoload']['files'] as $item)
                {
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
                    
                foreach($excludedComponents as $component)
                {
                    $releaseConfiguration->addExcludedComponent($component);
                    $debugConfiguration->addExcludedComponent($component);
                }
            }

            $project->addBuildConfiguration($releaseConfiguration);
            $project->addBuildConfiguration($debugConfiguration);

            if(isset($composerData['bin']) && is_array($composerData['bin']) && count($composerData['bin']) > 0)
            {
                $project->addExecutionUnit($this->generateExecutionUnit($composerData));
            }

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
         * @return array An associative array of dependency names to PackageSource objects.
         */
        private function generateDependencies(array $composerData): array
        {
            $dependencies = [];
            foreach ($composerData['require'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    continue;
                }

                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                $resolvedVersion = $this->resolvePackageVersion($dependency, $version);
                $dependencies[$this->generatePackageName($dependency)]->setVersion($resolvedVersion);
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate debug dependencies from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return array An associative array of dependency names to PackageSource objects for debug dependencies.
         */
        private function generateDebugDependencies(array $composerData): array
        {
            $dependencies = [];
            foreach ($composerData['require-dev'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    continue;
                }

                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                $resolvedVersion = $this->resolvePackageVersion($dependency, $version);
                $dependencies[$this->generatePackageName($dependency)]->setVersion($resolvedVersion);
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate a Project\Assembly object from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return Project\Assembly The generated assembly object.
         */
        private function generateAssembly(array $composerData): Project\Assembly
        {
            $assembly = new Project\Assembly();

            // Description
            if(isset($composerData['description']))
            {
                $assembly->setDescription($composerData['description']);
            }

            // Homepage
            if(isset($composerData['homepage']))
            {
                $assembly->setUrl($composerData['homepage']);
            }

            // Authors
            if(isset($composerData['authors']) && count($composerData['authors']) > 0)
            {
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
                $assembly->setLicense($composerData['license']);
            }

            // Set the package identifier (e.g., com.symfony.process)
            $assembly->setPackage($this->generatePackageName($composerData['name']));
            
            // Set the friendly name from the composer package name
            $assembly->setName($composerData['name']);

            // Extract and normalize version from composer.json
            // First check for explicit version field
            if(isset($composerData['version']))
            {
                try
                {
                    $normalizedVersion = (new VersionParser())->normalize($composerData['version']);
                    $assembly->setVersion($normalizedVersion);
                }
                catch(Exception $e)
                {
                    // If version normalization fails, keep default 0.0.0
                }
            }
            // If no version field, check for branch-alias in extra section
            elseif(isset($composerData['extra']['branch-alias']))
            {
                try
                {
                    $versionParser = new VersionParser();
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
                        }
                    }
                }
                catch(Exception $e)
                {
                    // If branch-alias parsing fails, keep default 0.0.0
                }
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
            // If it's already a specific version, normalize and return it
            $versionParser = new VersionParser();
            if($versionParser->isValid($versionConstraint))
            {
                try
                {
                    return $versionParser->normalize($versionConstraint);
                }
                catch(Exception $e)
                {
                    // Fall through to constraint resolution
                }
            }

            // For wildcard or constraint, we need to query packagist
            try
            {
                if(!Runtime::repositoryExists('packagist'))
                {
                    return 'latest';
                }

                $repository = Runtime::getRepository('packagist')->createClient();
                list($vendor, $name) = explode('/', $package, 2);
                
                // Get all available versions
                $versions = $repository->getReleases($vendor, $name);
                
                // Parse the constraint
                $constraint = $versionParser->parseConstraints($versionConstraint);
                
                // Filter out dev versions and sort in descending order
                $stableVersions = array_filter($versions, function($version) {
                    return stripos($version, '-dev') === false;
                });
                
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
                        Logger::getLogger()->warning(sprintf('Failed to compare versions %s and %s: %s', $a, $b, $e->getMessage()), $e);
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
                            return $normalizedVersion;
                        }
                    }
                    catch(Exception $e)
                    {
                        continue;
                    }
                }
            }
            catch(Exception $e)
            {
                Logger::getLogger()->warning(sprintf('Failed to resolve version for package %s: %s, defaulting to latest.', $package, $e->getMessage()));
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
                return '.';
            }
            
            // Check if the path exists
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $normalizedPath;
            if(is_dir($fullPath) || is_file($fullPath))
            {
                return $normalizedPath;
            }
            
            // Path doesn't exist, return null
            return null;
        }

        /**
         * Automatically detects the source path by looking for common directory structures.
         * 
         * @param string $baseDir The base directory to search in.
         * @return string The detected source path (defaults to '.' if nothing specific is found).
         */
        private function detectSourcePath(string $baseDir): string
        {
            // Common source directory names to check, in order of preference
            $commonSourceDirs = ['src', 'lib', 'Source', 'includes', 'app'];
            
            foreach($commonSourceDirs as $dir)
            {
                if(is_dir($baseDir . DIRECTORY_SEPARATOR . $dir))
                {
                    return $dir;
                }
            }
            
            // If no common source directory found, check if there are any .php files in the root
            // If yes, use the root directory as source
            $files = glob($baseDir . DIRECTORY_SEPARATOR . '*.php');
            if(!empty($files))
            {
                return '.';
            }
            
            // Default to root directory if nothing else is found
            return '.';
        }
    }