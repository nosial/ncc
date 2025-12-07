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
    use ncc\CLI\Commands\Helper;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Enums\MacroVariable;
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\ExecutionUnitException;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;

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
        private array $components;
        /**
         * @var string[]
         */
        private array $resources;
        /**
         * @var string[]
         */
        private array $requiredExecutionUnits;
        /**
         * @var string[]
         */
        private array $temporaryExecutionUnits;
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
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            $projectFilePath = Helper::resolveProjectConfigurationPath($projectFilePath);
            if($projectFilePath === null)
            {
                throw new InvalidArgumentException("No project configuration file found");
            }

            $this->projectPath = dirname($projectFilePath);
            $this->projectConfiguration = Project::fromFile($projectFilePath, true);

            try
            {
                $this->projectConfiguration->validate();
            }
            catch(InvalidPropertyException $e)
            {
                throw new InvalidArgumentException("Project configuration is invalid: " . $e->getMessage(), $e->getCode(), $e);
            }

            if(!$this->projectConfiguration->buildConfigurationExists($buildConfiguration))
            {
                throw new InvalidArgumentException("Build configuration '$buildConfiguration' does not exist in project configuration");
            }

            $this->buildConfiguration = $this->projectConfiguration->getBuildConfiguration($buildConfiguration);
            $this->sourcePath = $this->projectPath . DIRECTORY_SEPARATOR . $this->projectConfiguration->getSourcePath();
            $this->outputPath = $this->projectPath . DIRECTORY_SEPARATOR . $this->buildConfiguration->getOutput();
            $this->includeComponents = array_merge(['*.php'], $this->buildConfiguration->getIncludedComponents());
            $this->excludeComponents = $this->buildConfiguration->getExcludedComponents();
            $this->includeResources = $this->buildConfiguration->getIncludedResources();
            $this->excludeResources = array_merge(['*.php'], $this->buildConfiguration->getExcludedResources());
            $this->requiredExecutionUnits = [];

            if($this->projectConfiguration->getEntryPoint() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getEntryPoint();
            }

            if($this->projectConfiguration->getPreInstall() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getPreInstall();
            }

            if($this->projectConfiguration->getPostInstall() !== null)
            {
                $this->requiredExecutionUnits[] = $this->getProjectConfiguration()->getPostInstall();
            }

            if($this->projectConfiguration->getPreCompile() !== null)
            {
                $this->temporaryExecutionUnits[] = $this->getProjectConfiguration()->getPreCompile();
            }

            if($this->projectConfiguration->getPostCompile() !== null)
            {
                $this->temporaryExecutionUnits[] = $this->getProjectConfiguration()->getPostCompile();
            }

            if(isset($this->buildConfiguration->getOptions()['static']) && is_bool($this->buildConfiguration->getOptions()['static']))
            {
                $this->staticallyLinked = $this->buildConfiguration->getOptions()['static'];
            }
            else
            {
                $this->staticallyLinked = false;
            }

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
        public function getComponents(): array
        {
            return $this->components;
        }

        /**
         * Gets the list of resource file paths.
         *
         * @return array An array of resource file paths.
         */
        public function getResources(): array
        {
            return $this->resources;
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
         * Refreshes the list of component and resource files based on the current include/exclude patterns and
         * verifies the required execution units are correctly configured.
         *
         * @return void
         */
        protected function refreshFiles(): void
        {
            // Find all the required components/resources in the source path based on the include/exclude patterns.
            $this->components = FileCollector::collectFiles($this->sourcePath, $this->includeComponents, $this->excludeComponents);
            $this->resources = FileCollector::collectFiles($this->sourcePath, $this->includeResources, $this->excludeResources);

            // Verify if all the execution units are correctly configured and that all the required files
            // are available to compile with, temporary units are not included since they are only used during compilation.
            foreach($this->requiredExecutionUnits as $executionUnitName)
            {
                $executionUnit = $this->projectConfiguration->getExecutionUnit($executionUnitName);
                if($executionUnit === null)
                {
                    throw new InvalidArgumentException(sprintf('The required execution unit %s was not found in the project configuration', $executionUnitName));
                }

                // Only handle PHP execution units for entry points, since system commands are not part of the project files.
                if($executionUnit->getType() === ExecutionUnitType::PHP)
                {
                    $entryPointPath = $this->projectPath . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint();
                    if(!file_exists($entryPointPath))
                    {
                        throw new InvalidArgumentException(sprintf('The entrypoint %s was not found in the project path %s for the execution unit %s', $executionUnit->getEntryPoint(), $this->projectPath, $executionUnitName));
                    }

                    $this->resources[] = realpath($entryPointPath);
                }

                // Include all required files for the execution unit.
                if($executionUnit->getRequiredFiles() !== null)
                {
                    foreach($executionUnit->getRequiredFiles() as $requiredFile)
                    {
                        $requiredFilePath = $this->projectPath . DIRECTORY_SEPARATOR . $requiredFile;
                        if(!file_exists($requiredFilePath))
                        {
                            throw new InvalidArgumentException(sprintf('The required file %s was not found in the project path %s for the execution unit %s', $requiredFile, $this->projectPath, $executionUnitName));
                        }

                        $this->resources[] = realpath($requiredFilePath);
                    }
                }
            }

            // Finally, we calculate the build number based on the collected files.
            $this->buildNumber = $this->calculateBuildNumber();
        }

        /**
         * Calculates a build number based on the hashes of components and resources.
         *
         * @return string The calculated build number.
         */
        private function calculateBuildNumber(): string
        {
            $hashes = [];
            foreach(array_merge($this->components, $this->resources) as $filePath)
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
                return;
            }

            foreach($this->getProjectConfiguration()->getPreCompile() as $unitName)
            {
                ExecutionUnitRunner::fromSource($this->projectPath, $unitName);
            }
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
                return;
            }

            foreach($this->getProjectConfiguration()->getPostInstall() as $unitName)
            {
                ExecutionUnitRunner::fromSource($this->projectPath, $unitName);
            }
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
            $this->preCompile();
            $buildPath = $this->compile(null, $overwrite);
            $this->postCompile();
            return $buildPath;
        }
    }