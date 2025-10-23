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
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\ExecutionUnitException;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;

    abstract class AbstractCompiler
    {
        private string $projectPath;
        private Project $projectConfiguration;
        private array $includeComponents;
        private array $excludeComponents;
        private array $includeResources;
        private array $excludeResources;
        private string $sourcePath;
        private string $outputPath;
        private BuildConfiguration $buildConfiguration;
        private array $components;
        private array $resources;
        private array $requiredExecutionUnits;
        private array $temporaryExecutionUnits;
        private bool $staticallyLinked;
        private string $buildNumber;

        /**
         * AbstractCompiler constructor.
         *
         * @param string $projectPath The path to the project configuration file or the project directory.
         * @param string $buildConfiguration The build configuration to use.
         */
        public function __construct(string $projectPath, string $buildConfiguration)
        {
            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                throw new InvalidArgumentException("No project configuration file found");
            }

            $this->projectPath = $projectPath;
            $this->projectConfiguration = Project::fromFile($projectPath);

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
            $this->includeResources = array_merge(['*.php'], $this->buildConfiguration->getIncludedComponents());
            $this->excludeComponents = $this->buildConfiguration->getExcludedComponents();
            $this->includeResources = $this->buildConfiguration->getIncludedResources();
            $this->includeResources = array_merge(['*.php'], $this->buildConfiguration->getExcludedResources());
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
                    if(!file_exists($this->projectPath . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint()))
                    {
                        throw new InvalidArgumentException(sprintf('The entrypoint %s was not found in the project path %s for the execution unit %s', $executionUnit->getEntryPoint(), $this->projectPath, $executionUnitName));
                    }

                    $this->resources[] = realpath($this->projectPath . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint());
                }

                // Include all required files for the execution unit.
                foreach($executionUnit->getRequiredFiles() as $requiredFile)
                {
                    if(!file_exists($this->projectPath . DIRECTORY_SEPARATOR . $requiredFile))
                    {
                        throw new InvalidArgumentException(sprintf('The required file %s was not found in the project path %s for the execution unit %s', $requiredFile(), $this->projectPath, $executionUnitName));
                    }

                    $this->resources[] = realpath($this->projectPath . DIRECTORY_SEPARATOR . $requiredFile);
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
         * @return void
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
         * @throws CompileException Thrown if the compiler encounters an error.
         */
        protected abstract function compile(?callable $progressCallback=null, bool $overwrite=true): void;

        /**
         * Builds the project in its entirety by executing the compiling operations in order
         *
         * @param callable|null $progressCallback A callback function to report progress.
         *                                        it accepts a method (string $stage, string $name, float $progress)
         *                                        stage: The current stage of the compilation process (e.g., "parsing", "compiling", etc.).
         *                                        name: The name of what is currently being processed (e.g., file name, module name, etc.).
         *                                        progress: A float value between 0.0 and 1.0 indicating
         * @param bool $overwrite Whether to overwrite existing output files. Default is true.
         * @return void
         * @throws CompileException Thrown if the compiler encounters an error.
         * @throws ExecutionUnitException Thrown if one or more execution unit failed to run
         */
        public function build(?callable $progressCallback=null, bool $overwrite=true): void
        {
            $this->preCompile();
            $this->compile();
            $this->postCompile();
        }
    }