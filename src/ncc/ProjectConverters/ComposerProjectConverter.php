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
    use InvalidArgumentException;
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
        public const string COMPOSER_NAME_OPTION = 'composer_name';

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
            
            if(isset($composerData['require']))
            {
                Logger::getLogger()?->debug(sprintf('Processing %d production dependencies', count($composerData['require'])));
                $dependencies = $this->generateDependencies($composerData['require'], false, $progressCallback);
                foreach($dependencies as $dependencyName => $dependencySource)
                {
                    $project->addDependency($dependencyName, $dependencySource);
                }
                Logger::getLogger()?->verbose(sprintf('Added %d production dependencies', count($dependencies)));

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
            
            if(isset($composerData['require-dev']))
            {
                Logger::getLogger()?->debug(sprintf('Processing %d development dependencies', count($composerData['require-dev'])));
                $devDependencies = $this->generateDependencies($composerData['require-dev'], true, $progressCallback);
                foreach($devDependencies as $dependencyName => $dependencySource)
                {
                    $debugConfiguration->addDependency($dependencyName, $dependencySource);
                }
                Logger::getLogger()?->verbose(sprintf('Added %d development dependencies', count($devDependencies)));

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

            // Composer packages are updated from Packagist
            if(isset($composerData['name']) && is_string($composerData['name']))
            {
                try
                {
                    $project->setUpdateSource(new PackageSource($composerData['name'] . '@packagist'));
                }
                catch(InvalidArgumentException)
                {
                    Logger::getLogger()?->debug(sprintf('Cannot use "%s" as the update source, it is not a valid package name', $composerData['name']));
                }
            }

            // Get the base directory of the composer.json file
            $baseDir = dirname($filePath);
            
            $project->setSourcePath('.');
            foreach(self::getExcludedPaths($baseDir, $composerData, [$releaseConfiguration, $debugConfiguration]) as $excludedPath)
            {
                foreach([$excludedPath, $excludedPath . '/*'] as $pattern)
                {
                    foreach([$releaseConfiguration, $debugConfiguration] as $buildConfiguration)
                    {
                        $buildConfiguration->addExcludedComponent($pattern);
                        $buildConfiguration->addExcludedResource($pattern);
                    }
                }
            }

            if(isset($composerData['autoload']['files']))
            {
                Logger::getLogger()?->debug(sprintf('Adding %d autoloaded files as included components', count($composerData['autoload']['files'])));
                foreach($composerData['autoload']['files'] as $item)
                {
                    Logger::getLogger()?->verbose(sprintf('Including autoload file: %s', $item));
                    $releaseConfiguration->addIncludedComponent($item);
                    $debugConfiguration->addIncludedComponent($item);

                    // Executed whenever the package is imported, like Composer does
                    $releaseConfiguration->addAutoloadFile($item);
                    $debugConfiguration->addAutoloadFile($item);
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
                    $component = trim(str_replace('\\', '/', (string)$component), '/');
                    if($component === '')
                    {
                        continue;
                    }

                    Logger::getLogger()?->verbose(sprintf('Excluding component: %s', $component));
                    foreach([$component, $component . '/*'] as $pattern)
                    {
                        $releaseConfiguration->addExcludedComponent($pattern);
                        $debugConfiguration->addExcludedComponent($pattern);
                    }
                }
            }

            if(isset($composerData['name']) && is_string($composerData['name']))
            {
                $releaseConfiguration->addOption(self::COMPOSER_NAME_OPTION, $composerData['name']);
                $debugConfiguration->addOption(self::COMPOSER_NAME_OPTION, $composerData['name']);
            }

            $project->addBuildConfiguration($releaseConfiguration);
            $project->addBuildConfiguration($debugConfiguration);
            Logger::getLogger()?->debug('Added release and debug build configurations');

            $bins = self::getBins($composerData);
            if(count($bins) > 0)
            {
                Logger::getLogger()?->verbose(sprintf('Generating execution units from %d bin entries', count($bins)));
                foreach($this->generateExecutionUnits($bins) as $executionUnit)
                {
                    $project->addExecutionUnit($executionUnit);
                }

                $project->setEntryPoint('main');
                Logger::getLogger()?->verbose('Set entry point to "main" from composer bin field');
                
                $this->addComposerCompatibilityStubs($baseDir, $releaseConfiguration, $debugConfiguration);
            }

            Logger::getLogger()?->verbose('Successfully converted Composer project');
            return $project;
        }

        /**
         * Returns the bin entries of a composer.json, skipping invalid ones
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return string[] The bin paths in the order they are declared
         */
        public static function getBins(array $composerData): array
        {
            $bins = $composerData['bin'] ?? [];
            if(is_string($bins))
            {
                $bins = [$bins];
            }

            if(!is_array($bins))
            {
                return [];
            }

            return array_values(array_filter($bins, static fn($bin) => is_string($bin) && trim($bin) !== ''));
        }

        /**
         * Generates the execution units for the Composer bin entries: one unit per bin named after the bin's basename
         * (the name Composer gives it in vendor/bin), plus "main" running the first bin as the package entry point.
         *
         * @param string[] $bins The bin paths from composer.json
         * @return Project\ExecutionUnit[] The generated execution units
         */
        private function generateExecutionUnits(array $bins): array
        {
            $units = [self::createBinExecutionUnit('main', $bins[0])];
            $usedNames = ['main' => true];

            foreach($bins as $bin)
            {
                $name = self::getBinName($bin);
                $uniqueName = $name;
                $suffix = 2;
                while(isset($usedNames[$uniqueName]))
                {
                    // "main" is taken by the entry point, a bin that is literally called main is that same script
                    if($uniqueName === 'main' && $bin === $bins[0])
                    {
                        continue 2;
                    }

                    $uniqueName = $name . '-' . $suffix++;
                }

                $usedNames[$uniqueName] = true;
                Logger::getLogger()?->verbose(sprintf('Generating execution unit %s for bin %s', $uniqueName, $bin));
                $units[] = self::createBinExecutionUnit($uniqueName, $bin);
            }

            return $units;
        }

        /**
         * Returns the command name of a Composer bin entry (its basename, like Composer's vendor/bin proxies)
         *
         * @param string $bin The bin path from composer.json
         * @return string The command name
         */
        public static function getBinName(string $bin): string
        {
            return basename(str_replace('\\', '/', $bin));
        }

        /**
         * Creates the execution unit for a Composer bin script
         *
         * @param string $name The execution unit name
         * @param string $bin The bin path relative to the project path
         * @return Project\ExecutionUnit The execution unit
         */
        private static function createBinExecutionUnit(string $name, string $bin): Project\ExecutionUnit
        {
            return new Project\ExecutionUnit([
                'name' => $name,
                'type' => 'php',
                'mode' => 'auto',
                'entry' => $bin,
                'working_directory' => MacroVariable::CURRENT_WORKING_DIRECTORY->value,
                'composer_bin' => true,
            ]);
        }

        /**
         * Generates the ncc dependencies for a Composer require or require-dev section, skipping PHP, extensions and
         * Composer virtual packages (composer-runtime-api, composer-plugin-api, ...)
         *
         * @param array $requirements The require or require-dev section from composer.json.
         * @param bool $development Whether these are development dependencies (only used for messages).
         * @param callable|null $progressCallback Optional callback for progress updates.
         * @return array An associative array of dependency names to PackageSource objects.
         */
        private function generateDependencies(array $requirements, bool $development, ?callable $progressCallback = null): array
        {
            $dependencies = [];
            foreach ($requirements as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    Logger::getLogger()?->debug(sprintf('Skipping PHP/extension requirement: %s', $dependency));
                    continue;
                }

                if(!str_contains($dependency, '/'))
                {
                    Logger::getLogger()?->debug(sprintf('Skipping Composer virtual package: %s', $dependency));
                    continue;
                }

                if($progressCallback !== null)
                {
                    $progressCallback(sprintf($development ? 'Resolving dev dependency %s' : 'Resolving dependency %s', $dependency));
                }

                Logger::getLogger()?->verbose(sprintf('Processing %sdependency: %s@%s', $development ? 'development ' : '', $dependency, $version));
                $packageName = self::generatePackageName($dependency);
                $resolvedVersion = $this->resolvePackageVersion($dependency, $version, $development);
                Logger::getLogger()?->debug(sprintf('Resolved %s version %s to %s', $dependency, $version, $resolvedVersion));

                $dependencies[$packageName] = new PackageSource($dependency);
                $dependencies[$packageName]->setVersion($resolvedVersion);
                $dependencies[$packageName]->setRepository('packagist');
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
            $packageName = self::generatePackageName($composerData['name']);
            Logger::getLogger()?->debug(sprintf('Generated package name: %s from %s', $packageName, $composerData['name']));
            $assembly->setPackage($packageName);
            
            // Set the assembly name from the composer package name (without vendor prefix, eg; symfony/process -> process)
            // Assembly names must not contain slashes to avoid path conflicts
            $assembly->setName(self::getAssemblyName($composerData['name']));

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
         * @param bool $development Whether the package is a development dependency, dev dependencies of third-party
         *                          packages are often not available on Packagist so failing to resolve them is only
         *                          logged at debug level
         * @return string The resolved version or 'latest' if resolution fails
         */
        private function resolvePackageVersion(string $package, string $versionConstraint, bool $development=false): string
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
                $message = sprintf('No matching version found for constraint %s on package %s, defaulting to latest', $versionConstraint, $package);
                $development ? Logger::getLogger()?->debug($message) : Logger::getLogger()?->warning($message);
            }
            catch(Exception $e)
            {
                $message = sprintf('Failed to resolve version for package %s: %s, defaulting to latest.', $package, $e->getMessage());
                $development ? Logger::getLogger()?->debug($message) : Logger::getLogger()?->warning($message);
            }

            return 'latest';
        }

        /**
         * Generate a package name from a Composer package name.
         *
         * @param string $composerPackageName The Composer package name (e.g., "vendor/package-name").
         * @return string The generated package name (e.g., "com.vendor.packagename").
         */
        public static function generatePackageName(string $composerPackageName): string
        {
            return sprintf("%s.%s.%s",
                'com',
                str_replace('-', '_', explode('/', $composerPackageName)[0]),
                str_replace('-', '_', explode('/', $composerPackageName)[1])
            );
        }

        /**
         * Returns the assembly name for a Composer package name: the package part without the vendor
         *
         * @param string $composerPackageName The Composer package name (e.g., "symfony/process").
         * @return string The assembly name (e.g., "process").
         */
        public static function getAssemblyName(string $composerPackageName): string
        {
            $parts = explode('/', $composerPackageName, 2);
            return $parts[1] ?? $parts[0];
        }

        /**
         * Returns the paths (relative to the package root, without leading slash) that Composer would not install or
         * that are not part of the package: vendor/, the build outputs, and what the package excludes from its
         * archives through `archive.exclude` in composer.json or `export-ignore` in .gitattributes. Packages
         * downloaded from Packagist already lack the latter, these matter when converting a working copy.
         *
         * @param string $baseDir The package root
         * @param array $composerData The parsed Composer JSON data
         * @param Project\BuildConfiguration[] $buildConfigurations The build configurations whose output is excluded
         * @return string[] The excluded paths, matched as glob patterns from the package root
         */
        public static function getExcludedPaths(string $baseDir, array $composerData, array $buildConfigurations = []): array
        {
            $patterns = ['vendor'];

            foreach($buildConfigurations as $buildConfiguration)
            {
                // target/release/${ASSEMBLY.PACKAGE}.ncc -> target/release
                $patterns[] = dirname($buildConfiguration->getOutput());
            }

            foreach((array)($composerData['archive']['exclude'] ?? []) as $pattern)
            {
                if(is_string($pattern) && !str_starts_with($pattern, '!'))
                {
                    $patterns[] = $pattern;
                }
            }

            $gitAttributesPath = $baseDir . DIRECTORY_SEPARATOR . '.gitattributes';
            if(is_file($gitAttributesPath))
            {
                foreach(file($gitAttributesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line)
                {
                    $parts = preg_split('/\s+/', trim($line));
                    if(count($parts) > 1 && !str_starts_with($parts[0], '#') && in_array('export-ignore', array_slice($parts, 1), true))
                    {
                        $patterns[] = $parts[0];
                    }
                }
            }

            $normalized = [];
            foreach($patterns as $pattern)
            {
                $pattern = trim(str_replace('\\', '/', $pattern), '/');
                if($pattern === '' || $pattern === '.' || str_contains($pattern, '..'))
                {
                    continue;
                }

                $normalized[] = $pattern;
            }

            return array_values(array_unique($normalized));
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