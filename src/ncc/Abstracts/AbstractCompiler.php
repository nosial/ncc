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

    namespace ncc\Abstracts;

    use InvalidArgumentException;
    use ncc\Classes\ExecutionUnitRunner;
    use ncc\Classes\FileCollector;
    use ncc\Classes\IO;
    use ncc\CLI\Commands\Helper;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Enums\MacroVariable;
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\ExecutionUnitException;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;
    use ncc\Objects\ResolvedDependency;

    abstract class AbstractCompiler
    {
        private string $projectPath;
        private Project $projectConfiguration;
        /**
         * @var string[]
         */
        private array $includeComponents;
        /**
         * @var string[]
         */
        private array $excludeComponents;
        /**
         * @var string[]
         */
        private array $includeResources;
        /**
         * @var string[]
         */
        private array $excludeResources;
        private string $sourcePath;
        private string $outputPath;
        private BuildConfiguration $buildConfiguration;
        /**
         * @var string[]
         */
        private array $sourceComponents;
        /**
         * @var string[]
         */
        private array $sourceResources;
        /**
         * @var string[]
         */
        private array $requiredExecutionUnits;
        /**
         * @var string[]
         */
        private array $temporaryExecutionUnits;
        private array $packageDependencies;
        private bool $staticallyLinked;
        private string $buildNumber;

        /**
         * AbstractCompiler constructor. This class is intended to be extended and usd to create different compilers,
         * the purpose of this class is to set up the compiler for pre-compile while collecting/preparing the information
         * about the environment so that the implementing compiler can simply grab what resources and information it
         * may require at compile-time.
         *
         * @param string $projectFilePath The path to the project configuration file or the project directory.
         * @param string $buildConfiguration The build configuration to use.
         * @throws OperationException Thrown if there was an error setting up the compiler.
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Initializing compiler with project file: %s, build configuration: %s', $projectFilePath, $buildConfiguration), 'AbstractCompiler');
            
            $projectFilePath = Helper::resolveProjectConfigurationPath($projectFilePath);
            if($projectFilePath === null)
            {
                \ncc\Classes\Logger::getLogger()->error('No project configuration file found', 'AbstractCompiler');
                throw new OperationException("No project configuration file found");
            }

            $this->projectPath = dirname($projectFilePath);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Project path resolved to: %s', $this->projectPath), 'AbstractCompiler');
            
            $this->projectConfiguration = Project::fromFile($projectFilePath, true);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Loaded project configuration from: %s', $projectFilePath), 'AbstractCompiler');

            try
            {
                $this->projectConfiguration->validate();
                \ncc\Classes\Logger::getLogger()->debug('Project configuration validated successfully', 'AbstractCompiler');
            }
            catch(InvalidPropertyException $e)
            {
                \ncc\Classes\Logger::getLogger()->error(sprintf('Project configuration validation failed: %s', $e->getMessage()), 'AbstractCompiler');
                throw new OperationException("Project configuration is invalid: " . $e->getMessage(), $e->getCode(), $e);
            }

            if(!$this->projectConfiguration->buildConfigurationExists($buildConfiguration))
            {
                \ncc\Classes\Logger::getLogger()->error(sprintf('Build configuration "%s" not found in project', $buildConfiguration), 'AbstractCompiler');
                throw new OperationException("Build configuration '$buildConfiguration' does not exist in project configuration");
            }

            $this->buildConfiguration = $this->projectConfiguration->getBuildConfiguration($buildConfiguration);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Using build configuration: %s', $buildConfiguration), 'AbstractCompiler');
            $this->sourcePath = $this->projectPath . DIRECTORY_SEPARATOR . $this->projectConfiguration->getSourcePath();
            $this->outputPath = $this->projectPath . DIRECTORY_SEPARATOR . $this->buildConfiguration->getOutput();
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Source path: %s', $this->sourcePath), 'AbstractCompiler');
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Output path: %s', $this->outputPath), 'AbstractCompiler');
            
            $this->includeComponents = array_merge(['*.php'], $this->buildConfiguration->getIncludedComponents());
            $this->excludeComponents = $this->buildConfiguration->getExcludedComponents();
            $this->includeResources = $this->buildConfiguration->getIncludedResources();
            $this->excludeResources = array_merge(['*.php'], $this->buildConfiguration->getExcludedResources());
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Component patterns - Include: %d, Exclude: %d', count($this->includeComponents), count($this->excludeComponents)), 'AbstractCompiler');
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Resource patterns - Include: %d, Exclude: %d', count($this->includeResources), count($this->excludeResources)), 'AbstractCompiler');
            $this->requiredExecutionUnits = [];
            $this->sourceComponents = [];
            $this->sourceResources = [];
            $this->packageDependencies = [];

            if($this->projectConfiguration->getEntryPoint() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getEntryPoint();
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Entry point unit: %s', $this->getProjectConfiguration()->getEntryPoint()), 'AbstractCompiler');
            }

            if($this->projectConfiguration->getPreInstall() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getPreInstall();
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Pre-install unit: %s', $this->getProjectConfiguration()->getPreInstall()), 'AbstractCompiler');
            }

            if($this->projectConfiguration->getPostInstall() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getPostInstall();
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Post-install unit: %s', $this->getProjectConfiguration()->getPostInstall()), 'AbstractCompiler');
            }

            if($this->projectConfiguration->getPreCompile() !== null)
            {
                $this->temporaryExecutionUnits[] = $this->getProjectConfiguration()->getPreCompile();
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Pre-compile unit: %s', $this->getProjectConfiguration()->getPreCompile()), 'AbstractCompiler');
            }

            if($this->projectConfiguration->getPostCompile() !== null)
            {
                $this->temporaryExecutionUnits[] = $this->getProjectConfiguration()->getPostCompile();
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Post-compile unit: %s', $this->getProjectConfiguration()->getPostCompile()), 'AbstractCompiler');
            }

            if(isset($this->buildConfiguration->getOptions()['static']))
            {
                $this->staticallyLinked = (bool) $this->buildConfiguration->getOptions()['static'];
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Static linking: %s', $this->staticallyLinked ? 'enabled' : 'disabled'), 'AbstractCompiler');
            }
            else
            {
                $this->staticallyLinked = false;
                \ncc\Classes\Logger::getLogger()->verbose('Static linking: disabled (default)', 'AbstractCompiler');
            }

            $this->packageDependencies = array_merge($this->packageDependencies, $this->buildConfiguration->getDependencies() ?? [], $this->projectConfiguration->getDependencies() ?? []);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Total package dependencies: %d', count($this->packageDependencies)), 'AbstractCompiler');
            
            $this->refreshFiles();
        }


        /**
         * Gets the project path.
         *
         * @return string The project path.
         */
        public function getProjectPath(): string
        {
            return $this->projectPath;
        }

        /**
         * Gets the project configuration.
         *
         * @return Project The project configuration.
         */
        public function getProjectConfiguration(): Project
        {
            return $this->projectConfiguration;
        }

        /**
         * Gets the source path of the project.
         *
         * @return string The source path.
         */
        public function getSourcePath(): string
        {
            return $this->sourcePath;
        }

        /**
         * Gets the output file path for the compiled project.
         *
         * @return string The output path.
         */
        public function getOutputPath(): string
        {
            return $this->outputPath;
        }

        /**
         * Gets the build configuration.
         *
         * @return Project\BuildConfiguration The build configuration.
         */
        public function getBuildConfiguration(): Project\BuildConfiguration
        {
            return $this->buildConfiguration;
        }

        /**
         * Gets the list of component file paths.
         *
         * @return array An array of component file paths.
         */
        public function getSourceComponents(): array
        {
            return $this->sourceComponents;
        }

        /**
         * Gets the list of resource file paths.
         *
         * @return array An array of resource file paths.
         */
        public function getSourceResources(): array
        {
            return $this->sourceResources;
        }

        /**
         * Gets the list of required execution unit names that will be included in the final build.
         *
         * @return array An array of required execution unit names.
         */
        public function getRequiredExecutionUnits(): array
        {
            return $this->requiredExecutionUnits;
        }

        /**
         * Gets the list of temporary execution unit names that will only be used during compilation.
         *
         * @return array An array of temporary execution unit names.
         */
        public function getTemporaryExecutionUnits(): array
        {
            return $this->temporaryExecutionUnits;
        }

        public function isStaticallyLinked(): bool
        {
            return $this->staticallyLinked;
        }

        /**
         * Gets the calculated build number of the build.
         *
         * @return string The build number.
         */
        public function getBuildNumber(): string
        {
            return $this->buildNumber;
        }

        private function applyMacrosFromInput(string $input, bool $strict=false): string
        {
            return MacroVariable::fromInput($input, $strict, function($input){
                return match($input)
                {
                    // Note: We don't resolve CWD at this time as it may be needed/preserved depending
                    //       on the context, everything else should be fine.
                    MacroVariable::PROCESS_ID->value => '100', //      TODO: Placeholder for now
                    MacroVariable::USER_ID->value => '200', //         TODO: Placeholder for now
                    MacroVariable::GLOBAL_ID->value => '300', //       TODO: Placeholder for now
                    MacroVariable::USER_HOME_PATH->value => '400', //  TODO: Placeholder for now
                    MacroVariable::COMPILE_TIMESTAMP->value => time(),
                    MacroVariable::NCC_BUILD_VERSION->value => '0.0.0', // TODO: Placeholder for now
                    MacroVariable::PROJECT_PATH->value => $this->projectPath,
                    MacroVariable::DEFAULT_BUILD_CONFIGURATION->value => $this->projectConfiguration->getDefaultBuild(),
                    MacroVariable::SOURCE_PATH->value => $this->sourcePath
                };
            });
        }

        /**
         * Returns the build configurations defined in the project
         *
         * @return array<string,PackageSource>|null An array of PackageSource objects keyed by package name, or null if no dependencies are defined
         */
        protected function getPackageDependencies(): ?array
        {
            return $this->packageDependencies;
        }

        /**
         * Returns a specific dependency by its name
         *
         * @param PackageSource|string $dependency The name of the dependency to retrieve
         * @return PackageSource|null The PackageSource object if found, or null if not found or no dependencies are defined
         */
        protected function getDependency(PackageSource|string $dependency): ?PackageSource
        {
            if(is_string($dependency) && isset($this->packageDependencies[$dependency]))
            {
                return $this->packageDependencies[$dependency];
            }

            if($dependency instanceof PackageSource)
            {
                $dependency = $dependency->getName();
            }

            foreach($this->packageDependencies as $packageSource)
            {
                if((string)$packageSource->getName() === $dependency)
                {
                    return $packageSource;
                }
            }

            return null;
        }

        /**
         * Checks if a dependency with the given name exists
         *
         * @param string $package
         * @return bool True if the dependency exists, false otherwise
         */
        protected function dependencyExists(string $package): bool
        {
            if(isset($this->packageDependencies[$package]))
            {
                return true;
            }

            return $this->getDependency($package) !== null;
        }

        /**
         * Adds a new dependency to the project
         *
         * @param PackageSource|string $dependency The PackageSource object representing the dependency to add
         * @throws InvalidArgumentException If a dependency with the same name already exists
         */
        protected function addDependency(string $package, PackageSource|string $dependency): void
        {
            if(is_string($dependency) && isset($this->packageDependencies[$package]))
            {
                return;
            }

            if(is_string($dependency))
            {
                $dependency = new PackageSource($dependency);
            }

            if($this->packageDependencies === null)
            {
                $this->packageDependencies = [];
            }

            if($this->dependencyExists($package))
            {
                throw new InvalidArgumentException('A dependency with the name \'' . (string)$package . '\' already exists');
            }

            $this->packageDependencies[$package] = $dependency;
        }

        /**
         * Removes a dependency from the project by its name
         *
         * @param PackageSource|string $dependency The name of the dependency to remove
         */
        protected function removeDependency(PackageSource|string $dependency): void
        {
            if($dependency instanceof PackageSource)
            {
                $dependency = (string)$dependency;
            }

            if($this->packageDependencies === null)
            {
                return;
            }

            if(isset($this->packageDependencies[$dependency]))
            {
                unset($this->packageDependencies[$dependency]);
                return;
            }

            foreach($this->packageDependencies as $packageName => $packageSource)
            {
                if((string)$packageSource->getName() === $dependency)
                {
                    unset($this->packageDependencies[$packageName]);
                    return;
                }
            }
        }

        /**
         * Refreshes the list of component and resource files based on the current include/exclude patterns and
         * verifies the required execution units are correctly configured.
         *
         * @return void
         */
        protected function refreshFiles(): void
        {
            \ncc\Classes\Logger::getLogger()->debug('Refreshing source files collection', 'AbstractCompiler');
            
            // Find all the required components/resources in the source path based on the include/exclude patterns.
            $this->sourceComponents = FileCollector::collectFiles($this->sourcePath, $this->includeComponents, $this->excludeComponents);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Collected %d source components', count($this->sourceComponents)), 'AbstractCompiler');
            
            $this->sourceResources = FileCollector::collectFiles($this->sourcePath, $this->includeResources, $this->excludeResources);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Collected %d source resources', count($this->sourceResources)), 'AbstractCompiler');

            // Verify if all the execution units are correctly configured and that all the required files
            // are available to compile with, temporary units are not included since they are only used during compilation.
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Verifying %d required execution units', count($this->requiredExecutionUnits)), 'AbstractCompiler');
            
            foreach($this->requiredExecutionUnits as $executionUnitName)
            {
                $executionUnit = $this->projectConfiguration->getExecutionUnit($executionUnitName);
                if($executionUnit === null)
                {
                    \ncc\Classes\Logger::getLogger()->error(sprintf('Required execution unit not found: %s', $executionUnitName), 'AbstractCompiler');
                    throw new OperationException(sprintf('The required execution unit %s was not found in the project configuration', $executionUnitName));
                }
                
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Verifying execution unit: %s (type: %s)', $executionUnitName, $executionUnit->getType()->value), 'AbstractCompiler');

                // Only handle PHP execution units for entry points, since system commands are not part of the project files.
                if($executionUnit->getType() === ExecutionUnitType::PHP)
                {
                    $entryPointPath = $this->projectPath . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint();
                    if(!IO::exists($entryPointPath))
                    {
                        \ncc\Classes\Logger::getLogger()->error(sprintf('Entry point not found: %s for unit %s', $entryPointPath, $executionUnitName), 'AbstractCompiler');
                        throw new OperationException(sprintf('The entrypoint %s was not found in the project path %s for the execution unit %s', $executionUnit->getEntryPoint(), $this->projectPath, $executionUnitName));
                    }

                    $this->sourceResources[] = realpath($entryPointPath);
                    \ncc\Classes\Logger::getLogger()->debug(sprintf('Added entry point to resources: %s', $executionUnit->getEntryPoint()), 'AbstractCompiler');
                }

                // Include all required files for the execution unit.
                if($executionUnit->getRequiredFiles() !== null)
                {
                    \ncc\Classes\Logger::getLogger()->debug(sprintf('Processing %d required files for unit: %s', count($executionUnit->getRequiredFiles()), $executionUnitName), 'AbstractCompiler');
                    
                    foreach($executionUnit->getRequiredFiles() as $requiredFile)
                    {
                        $requiredFilePath = $this->projectPath . DIRECTORY_SEPARATOR . $requiredFile;
                        if(!IO::exists($requiredFilePath))
                        {
                            \ncc\Classes\Logger::getLogger()->error(sprintf('Required file not found: %s for unit %s', $requiredFilePath, $executionUnitName), 'AbstractCompiler');
                            throw new OperationException(sprintf('The required file %s was not found in the project path %s for the execution unit %s', $requiredFile, $this->projectPath, $executionUnitName));
                        }

                        $this->sourceResources[] = realpath($requiredFilePath);
                    }
                }
            }

            // Finally, we calculate the build number based on the collected files.
            $this->buildNumber = $this->calculateBuildNumber();
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Build number calculated: %s', $this->buildNumber), 'AbstractCompiler');
        }

        protected function getDependencyReaders(): array
        {
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Resolving dependency readers for %d packages', count($this->packageDependencies)), 'AbstractCompiler');
            
            $results = [];

            foreach($this->packageDependencies as $packageName => $packageSource)
            {
                $results = array_merge($results, $this->resolveDependencyReaders($packageName, $packageSource));
            }

            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Resolved %d dependency readers total', count($results)), 'AbstractCompiler');
            return $results;
        }

        private function resolveDependencyReaders(string $package, PackageSource $source): array
        {
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Resolving dependency: %s', $package), 'AbstractCompiler');
            
            $resolvedDependency = new ResolvedDependency($package, $source);
            if($resolvedDependency->getPackageReader() === null)
            {
                \ncc\Classes\Logger::getLogger()->error(sprintf('Package not installed: %s', $package));
                throw new OperationException(sprintf('Cannot resolve %s becaues the package is not installed', $package));
            }

            $results = [$resolvedDependency];
            $transitiveDeps = count($resolvedDependency->getPackageReader()->getHeader()->getDependencyReferences());
            
            if($transitiveDeps > 0)
            {
                \ncc\Classes\Logger::getLogger()->debug(sprintf('Package %s has %d transitive dependencies', $package, $transitiveDeps), 'AbstractCompiler');
                
                foreach($resolvedDependency->getPackageReader()->getHeader()->getDependencyReferences() as $dependencyReference)
                {
                    $depSource = $dependencyReference->getSource();
                    if($depSource === null)
                    {
                        // Create a minimal PackageSource when none is specified
                        // The package name format is like: com.vendor.packagename or net.vendor.packagename
                        // We need to convert it to vendor/packagename format for PackageSource
                        $parts = explode('.', $dependencyReference->getPackage(), 3);
                        if(count($parts) === 3)
                        {
                            // Format: prefix.organization.name -> organization/name
                            $sourceString = $parts[1] . '/' . $parts[2];
                        }
                        else
                        {
                            // Fallback: use the package name as-is (might not parse but try)
                            $sourceString = $dependencyReference->getPackage();
                        }
                        
                        $depSource = new PackageSource($sourceString);
                        $depSource->setVersion($dependencyReference->getVersion());
                    }
                    
                    $results = array_merge($results, $this->resolveDependencyReaders($dependencyReference->getPackage(), $depSource));
                }
            }

            return $results;
        }

        /**
         * Calculates a build number based on the hashes of components and resources.
         *
         * @return string The calculated build number.
         */
        private function calculateBuildNumber(): string
        {
            $totalFiles = count(array_merge($this->sourceComponents, $this->sourceResources));
            \ncc\Classes\Logger::getLogger()->debug(sprintf('Calculating build number from %d files', $totalFiles), 'AbstractCompiler');
            
            $hashes = [];
            foreach(array_merge($this->sourceComponents, $this->sourceResources) as $filePath)
            {
                $hashes[] = hash_file('crc32b', $filePath);
            }

            return hash('crc32b', implode('', $hashes));
        }

        /**
         * Executes the execution units that is required to run in the pre-compile stage
         *
         * @return void
         * @throws ExecutionUnitException Thrown if one or more execution unit(s) failed to run
         */
        protected function preCompile(): void
        {
            if($this->getProjectConfiguration()->getPreCompile() === null)
            {
                \ncc\Classes\Logger::getLogger()->debug('No pre-compile execution units defined', 'AbstractCompiler');
                return;
            }

            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Running %d pre-compile execution units', count($this->getProjectConfiguration()->getPreCompile())), 'AbstractCompiler');
            
            foreach($this->getProjectConfiguration()->getPreCompile() as $unitName)
            {
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Executing pre-compile unit: %s', $unitName), 'AbstractCompiler');
                ExecutionUnitRunner::fromSource($this->projectPath, $unitName);
            }
            
            \ncc\Classes\Logger::getLogger()->verbose('Pre-compile execution units completed', 'AbstractCompiler');
        }

        /**
         * Executes the execution units that is required to run in the post-compile stage
         *
         * @throws ExecutionUnitException Thrown if one or more execution unit(s) failed to run
         */
        protected function postCompile(): void
        {
            if($this->getProjectConfiguration()->getPostInstall() === null)
            {
                \ncc\Classes\Logger::getLogger()->debug('No post-compile execution units defined', 'AbstractCompiler');
                return;
            }

            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Running %d post-compile execution units', count($this->getProjectConfiguration()->getPostInstall())), 'AbstractCompiler');
            
            foreach($this->getProjectConfiguration()->getPostInstall() as $unitName)
            {
                \ncc\Classes\Logger::getLogger()->verbose(sprintf('Executing post-compile unit: %s', $unitName), 'AbstractCompiler');
                ExecutionUnitRunner::fromSource($this->projectPath, $unitName);
            }
            
            \ncc\Classes\Logger::getLogger()->verbose('Post-compile execution units completed', 'AbstractCompiler');
        }

        /**
         * Compiles a project with the specified build configuration.
         *
         * @param callable|null $progressCallback A callback function to report progress.
         *                                        it accepts a method (string $stage, string $name, float $progress)
         *                                        stage: The current stage of the compilation process (e.g., "parsing", "compiling", etc.).
         *                                        name: The name of what is currently being processed (e.g., file name, module name, etc.).
         *                                        progress: A float value between 0.0 and 1.0 indicating
         * @param bool $overwrite Whether to overwrite existing output files. Default is true.
         * @return string The path to the compiled output file.
         * @throws CompileException Thrown if the compiler encounters an error.
         * @throws IOException thrown if there was an IO error
         */
        protected abstract function compile(?callable $progressCallback=null, bool $overwrite=true): string;

        /**
         * Builds the project in its entirety by executing the compiling operations in order
         *
         * @param callable|null $progressCallback A callback function to report progress.
         *                                        it accepts a method (string $stage, string $name, float $progress)
         *                                        stage: The current stage of the compilation process (e.g., "parsing", "compiling", etc.).
         *                                        name: The name of what is currently being processed (e.g., file name, module name, etc.).
         *                                        progress: A float value between 0.0 and 1.0 indicating
         * @param bool $overwrite Whether to overwrite existing output files. Default is true.
         * @return string The path to the built output file.
         * @throws CompileException Thrown if the compiler encounters an error.
         * @throws ExecutionUnitException Thrown if one or more execution unit failed to run
         * @throws IOException Thrown if there was an IO error
         */
        public function build(?callable $progressCallback=null, bool $overwrite=true): string
        {
            \ncc\Classes\Logger::getLogger()->verbose('Starting build process', 'AbstractCompiler');
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Build options - Overwrite: %s', $overwrite ? 'true' : 'false'), 'AbstractCompiler');
            
            $this->preCompile();
            
            \ncc\Classes\Logger::getLogger()->verbose('Starting compilation phase', 'AbstractCompiler');
            $buildPath = $this->compile(null, $overwrite);
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Compilation completed: %s', $buildPath), 'AbstractCompiler');
            
            $this->postCompile();
            
            \ncc\Classes\Logger::getLogger()->verbose(sprintf('Build process completed successfully: %s', $buildPath), 'AbstractCompiler');
            return $buildPath;
        }
    }