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

    namespace ncc\Objects;

    use InvalidArgumentException;
    use ncc\Abstracts\AbstractCompiler;
    use ncc\Classes\IO;
    use ncc\CLI\Logger;
    use ncc\Compilers\PackageCompiler;
    use ncc\Enums\BuildType;
    use ncc\Enums\MacroVariable;
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Exceptions\IOException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;
    use ncc\Libraries\Yaml\Yaml;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Project\BuildConfiguration;
    use ncc\Objects\Project\ExecutionUnit;
    use RuntimeException;

    class Project implements SerializableInterface, ValidatorInterface
    {
        private string $sourcePath;
        private string $defaultBuild;
        private ?string $entryPoint;
        private ?PackageSource $updateSource;
        /** @var string|string[]|null */
        private string|array|null $preCompile;
        /** @var string|string[]|null */
        private string|array|null $postCompile;
        /** @var string|string[]|null */
        private string|array|null $preInstall;
        /** @var string|string[]|null */
        private string|array|null $postInstall;

        private ?RepositoryConfiguration $repository;
        private Assembly $assembly;
        /** @var PackageSource[] */
        private ?array $dependencies;
        /** @var ExecutionUnit[]|null */
        private ?array $executionUnits;
        /** @var BuildConfiguration[] */
        private array $buildConfigurations;

        /**
         * Public Constructor for the Project configuration
         *
         * @param array $data Project Configuration data as an array representation
         */
        public function __construct(array $data)
        {
            $this->sourcePath = $data['source'] ?? 'src';
            $this->defaultBuild = $data['default_build'] ?? 'release';
            $this->entryPoint = $data['entry_point'] ?? null;
            $this->updateSource = isset($data['update_source']) ? new PackageSource($data['update_source']) : null;
            $this->preCompile = $data['pre_compile'] ?? null;
            $this->postCompile = $data['post_compile'] ?? null;
            $this->preInstall = $data['pre_install'] ?? null;
            $this->postInstall = $data['post_install'] ?? null;
            $this->repository = isset($data['repository']) ? RepositoryConfiguration::fromArray($data['repository']) : null;
            $this->assembly = isset($data['assembly']) ? Assembly::fromArray($data['assembly']) : new Assembly();
            $this->dependencies = isset($data['dependencies']) ? array_map(function($item) { return new PackageSource($item); }, $data['dependencies']) : null;
            $this->executionUnits = isset($data['execution_units']) ? array_map(function($item) { return ExecutionUnit::fromArray($item); }, $data['execution_units']) : null;
            $this->buildConfigurations = isset($data['build_configurations']) ? array_map(function($item) { return BuildConfiguration::fromArray($item); }, $data['build_configurations']) : [];
        }

        /**
         * Returns the source path of the project
         *
         * @return string The source path of the project
         */
        public function getSourcePath(): string
        {
            return $this->sourcePath;
        }

        /**
         * Sets the source path of the project
         *
         * @param string $path The new source path
         * @throws InvalidArgumentException If the provided path is an empty string
         */
        public function setSourcePath(string $path): void
        {
            if(trim($path) === '')
            {
                throw new InvalidArgumentException('The source path cannot be an empty string');
            }

            $this->sourcePath = $path;
        }

        /**
         * Returns the default build configuration name
         *
         * @return string The default build configuration name
         */
        public function getDefaultBuild(): string
        {
            return $this->defaultBuild;
        }

        /**
         * Sets the default build configuration name
         *
         * @param string $buildName The new default build configuration name
         * @throws InvalidArgumentException If the provided build name is an empty string or does not exist
         */
        public function setDefaultBuild(string $buildName): void
        {
            if(trim($buildName) === '')
            {
                throw new InvalidArgumentException('The default build name cannot be an empty string');
            }

            if(!$this->buildConfigurationExists($buildName))
            {
                throw new InvalidArgumentException('The build configuration \'' . $buildName . '\' does not exist');
            }

            $this->defaultBuild = $buildName;
        }

        /**
         * Returns the entry point execution unit name
         *
         * @return string|null The entry point execution unit name or null if not set
         */
        public function getEntryPoint(): ?string
        {
            return $this->entryPoint;
        }

        /**
         * Sets the entry point execution unit name
         *
         * @param string|null $unitName The new entry point execution unit name or null to unset
         * @throws InvalidArgumentException If the provided unit name is an empty string or does not exist
         */
        public function setEntryPoint(?string $unitName): void
        {
            if($unitName !== null && trim($unitName) === '')
            {
                throw new InvalidArgumentException('The entry point cannot be an empty string');
            }

            if($unitName !== null && !$this->executionUnitExists($unitName))
            {
                throw new InvalidArgumentException('The execution unit \'' . $unitName . '\' does not exist');
            }

            $this->entryPoint = $unitName;
        }

        /**
         * Returns the update source of the project
         *
         * @return PackageSource|null The update source or null if not set
         */
        public function getUpdateSource(): ?PackageSource
        {
            return $this->updateSource;
        }

        /**
         * Sets the update source of the project
         *
         * @param PackageSource|null $source The new update source or null to unset
         */
        public function setUpdateSource(?PackageSource $source): void
        {
            $this->updateSource = $source;
        }

        /**
         * Returns the pre-compile execution unit name(s)
         *
         * @return array|null The pre-compile execution unit name(s) or null if not set
         */
        public function getPreCompile(): array|null
        {
            if($this->preCompile === null)
            {
                return null;
            }

            if(is_string($this->preCompile))
            {
                return [$this->preCompile];
            }

            return $this->preCompile;
        }

        /**
         * Sets the pre-compile execution unit name(s)
         *
         * @param string|array|null $unitName The new pre-compile execution unit name(s) or null to unset
         * @throws InvalidArgumentException If the provided unit name(s) are invalid
         */
        public function setPreCompile(string|array|null $unitName): void
        {
            $this->validateExecutionUnits($unitName, 'pre-compile');
            $this->preCompile = $unitName;
        }

        /**
         * Returns the post-compile execution unit name(s)
         *
         * @return array|null The post-compile execution unit name(s) or null if not set
         */
        public function getPostCompile(): array|null
        {
            if($this->postCompile === null)
            {
                return null;
            }

            if(is_string($this->postCompile))
            {
                return [$this->preCompile];
            }

            return $this->postCompile;
        }

        /**
         * Sets the post-compile execution unit name(s)
         *
         * @param string|array|null $unitName The new post-compile execution unit name(s) or null to unset
         * @throws InvalidArgumentException If the provided unit name(s) are invalid
         */
        public function setPostCompile(string|array|null $unitName): void
        {
            $this->validateExecutionUnits($unitName, 'post-compile');
            $this->postCompile = $unitName;
        }

        /**
         * Returns the pre-install execution unit name(s)
         *
         * @return array|null The pre-install execution unit name(s) or null if not set
         */
        public function getPreInstall(): array|null
        {
            if($this->preInstall === null)
            {
                return null;
            }

            if(is_string($this->preInstall))
            {
                return [$this->preInstall];
            }


            return $this->preInstall;
        }

        /**
         * Sets the pre-install execution unit name(s)
         *
         * @param string|array|null $unitName The new pre-install execution unit name(s) or null to unset
         * @throws InvalidArgumentException If the provided unit name(s) are invalid
         */
        public function setPreInstall(string|array|null $unitName): void
        {
            $this->validateExecutionUnits($unitName, 'pre-install');
            $this->preInstall = $unitName;
        }

        /**
         * Returns the post-install execution unit name(s)
         *
         * @return array|null The post-install execution unit name(s) or null if not set
         */
        public function getPostInstall(): array|null
        {
            if($this->postInstall === null)
            {
                return null;
            }

            if(is_string($this->postInstall))
            {
                return [$this->postInstall];
            }

            return $this->postInstall;
        }

        /**
         * Sets the post-install execution unit name(s)
         *
         * @param string|array|null $unitName The new post-install execution unit name(s) or null to unset
         * @throws InvalidArgumentException If the provided unit name(s) are invalid
         */
        public function setPostInstall(string|array|null $unitName): void
        {
            $this->validateExecutionUnits($unitName, 'post-install');
            $this->postInstall = $unitName;
        }

        /**
         * Returns the repository configuration
         *
         * @return RepositoryConfiguration|null The repository configuration or null if not set
         */
        public function getRepository(): ?RepositoryConfiguration
        {
            return $this->repository;
        }

        /**
         * Sets the repository configuration
         *
         * @param RepositoryConfiguration|null $repository The new repository configuration or null to unset
         */
        public function setRepository(?RepositoryConfiguration $repository): void
        {
            $this->repository = $repository;
        }

        /**
         * Returns the assembly information of the project
         *
         * @return Assembly The assembly information of the project
         */
        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        /**
         * Validates that the given unit name(s) are valid execution units.
         *
         * @param string|array|null $unitNames
         * @param string $context Used in error messages (e.g., 'pre-compile')
         * @throws InvalidArgumentException
         */
        private function validateExecutionUnits(string|array|null $unitNames, string $context): void
        {
            if ($unitNames === null)
            {
                return;
            }

            if (is_string($unitNames))
            {
                $this->validateSingleExecutionUnit($unitNames, $context);
                return;
            }

            // It's an array
            if (!is_array($unitNames))
            {
                // This shouldn't happen due to type hint, but kept for safety
                throw new InvalidArgumentException('Execution unit must be a string, array of strings, or null.');
            }

            foreach ($unitNames as $index => $unit)
            {
                if (!is_string($unit))
                {
                    throw new InvalidArgumentException("All execution units must be strings. Invalid value at index {$index}.");
                }

                $this->validateSingleExecutionUnit($unit, $context);
            }
        }

        /**
         * Validates a single execution unit string.
         *
         * @param string $unitName
         * @param string $context
         * @throws InvalidArgumentException
         */
        private function validateSingleExecutionUnit(string $unitName, string $context): void
        {
            if (trim($unitName) === '')
            {
                throw new InvalidArgumentException("The {$context} command cannot be an empty string");
            }

            if (!$this->executionUnitExists($unitName))
            {
                throw new InvalidArgumentException("The execution unit '{$unitName}' does not exist");
            }
        }

        /**
         * Returns the build configurations defined in the project
         *
         * @return PackageSource[]|null An array of PackageSource objects or null if no dependencies are defined
         */
        public function getDependencies(): ?array
        {
            return $this->dependencies;
        }

        /**
         * Returns a specific dependency by its name
         *
         * @param string $dependencyName The name of the dependency to retrieve
         * @return PackageSource|null The PackageSource object if found, or null if not found or no dependencies are defined
         */
        public function getDependency(string $dependencyName): ?PackageSource
        {
            if($this->dependencies === null)
            {
                return null;
            }

            foreach($this->dependencies as $dependency)
            {
                if((string)$dependency->getName() === $dependencyName)
                {
                    return $dependency;
                }
            }

            return null;
        }

        /**
         * Checks if a dependency with the given name exists
         *
         * @param string $dependencyName The name of the dependency to check
         * @return bool True if the dependency exists, false otherwise
         */
        public function dependencyExists(string $dependencyName): bool
        {
            return $this->getDependency($dependencyName) !== null;
        }

        /**
         * Adds a new dependency to the project
         *
         * @param PackageSource $dependency The PackageSource object representing the dependency to add
         * @throws InvalidArgumentException If a dependency with the same name already exists
         */
        public function addDependency(PackageSource $dependency): void
        {
            if($this->dependencies === null)
            {
                $this->dependencies = [];
            }

            if($this->dependencyExists($dependency->getName()))
            {
                throw new InvalidArgumentException('A dependency with the name \'' . (string)$dependency->getName() . '\' already exists');
            }

            $this->dependencies[] = $dependency;
        }

        /**
         * Removes a dependency from the project by its name
         *
         * @param string $dependencyName The name of the dependency to remove
         */
        public function removeDependency(string $dependencyName): void
        {
            if($this->dependencies === null)
            {
                return;
            }

            foreach($this->dependencies as $index => $dependency)
            {
                if((string)$dependency->getName() === $dependencyName)
                {
                    array_splice($this->dependencies, $index, 1);
                    return;
                }
            }
        }

        /**
         * Returns the execution units defined in the project
         *
         * @return ExecutionUnit[]|null An array of ExecutionUnit objects or null if no execution units are defined
         */
        public function getExecutionUnits(): ?array
        {
            return $this->executionUnits;
        }

        /**
         * Returns a specific execution unit by its name
         *
         * @param string $unitName The name of the execution unit to retrieve
         * @return ExecutionUnit|null The ExecutionUnit object if found, or null if not found or no execution units are defined
         */
        public function getExecutionUnit(string $unitName): ?ExecutionUnit
        {
            if($this->executionUnits === null)
            {
                return null;
            }

            foreach($this->executionUnits as $unit)
            {
                if($unit->getName() === $unitName)
                {
                    return $unit;
                }
            }

            return null;
        }

        /**
         * Checks if an execution unit with the given name exists
         *
         * @param string $unitName The name of the execution unit to check
         * @return bool True if the execution unit exists, false otherwise
         */
        public function executionUnitExists(string $unitName): bool
        {
            return $this->getExecutionUnit($unitName) !== null;
        }

        /**
         * Adds a new execution unit to the project
         *
         * @param ExecutionUnit $unit The ExecutionUnit object representing the execution unit to add
         * @throws InvalidArgumentException If an execution unit with the same name already exists
         */
        public function addExecutionUnit(ExecutionUnit $unit): void
        {
            if($this->executionUnits === null)
            {
                $this->executionUnits = [];
            }

            if($this->executionUnitExists($unit->getName()))
            {
                throw new InvalidArgumentException('An execution unit with the name \'' . $unit->getName() . '\' already exists');
            }

            $this->executionUnits[] = $unit;
        }

        /**
         * Removes an execution unit from the project by its name
         *
         * @param string $unitName The name of the execution unit to remove
         */
        public function removeExecutionUnit(string $unitName): void
        {
            if($this->executionUnits === null)
            {
                return;
            }

            foreach($this->executionUnits as $index => $unit)
            {
                if($unit->getName() === $unitName)
                {
                    array_splice($this->executionUnits, $index, 1);
                    return;
                }
            }
        }

        /**
         * Returns the build configurations defined in the project
         *
         * @return BuildConfiguration[] An array of BuildConfiguration objects
         */
        public function getBuildConfigurations(): array
        {
            return $this->buildConfigurations;
        }

        /**
         * Returns a specific build configuration by its name
         *
         * @param string $configName The name of the build configuration to retrieve
         * @return BuildConfiguration|null The BuildConfiguration object if found, or null if not found
         */
        public function getBuildConfiguration(string $configName): ?BuildConfiguration
        {
            foreach($this->buildConfigurations as $config)
            {
                if($config->getName() === $configName)
                {
                    return $config;
                }
            }

            return null;
        }

        /**
         * Checks if a build configuration with the given name exists
         *
         * @param string $configName The name of the build configuration to check
         * @return bool True if the build configuration exists, false otherwise
         */
        public function buildConfigurationExists(string $configName): bool
        {
            return $this->getBuildConfiguration($configName) !== null;
        }

        /**
         * Adds a new build configuration to the project
         *
         * @param BuildConfiguration $config The BuildConfiguration object representing the build configuration to add
         * @throws InvalidArgumentException If a build configuration with the same name already exists
         */
        public function addBuildConfiguration(BuildConfiguration $config): void
        {
            if($this->buildConfigurationExists($config->getName()))
            {
                throw new InvalidArgumentException('A build configuration with the name \'' . $config->getName() . '\' already exists');
            }

            $this->buildConfigurations[] = $config;
        }

        /**
         * Removes a build configuration from the project by its name
         *
         * @param string $configName The name of the build configuration to remove
         */
        public function removeBuildConfiguration(string $configName): void
        {
            foreach($this->buildConfigurations as $index => $config)
            {
                if($config->getName() === $configName)
                {
                    array_splice($this->buildConfigurations, $index, 1);
                    return;
                }
            }
        }

        /**
         * Saves the Project configuration to a YAML file
         *
         * @param string $filePath The path to the YAML file
         * @throws IOException If the file cannot be created
         */
        public function save(string $filePath): void
        {
            IO::writeFile($filePath, Yaml::dump($this->toArray(), 4, 2));
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'source' => $this->sourcePath,
                'default_build' => $this->defaultBuild,
                'entry_point' => $this->entryPoint,
                'update_source' => $this->updateSource ? (string)$this->updateSource : null,
                'pre_compile' => $this->preCompile,
                'post_compile' => $this->postCompile,
                'pre_install' => $this->preInstall,
                'post_install' => $this->postInstall,
                'repository' => $this->repository?->toArray(),
                'assembly' => $this->assembly->toArray(),
                'dependencies' => $this->dependencies ? array_map(function($item) { return (string)$item; }, $this->dependencies) : null,
                'execution_units' => $this->executionUnits ? array_map(function($item) { return $item->toArray(); }, $this->executionUnits) : null,
                'build_configurations' => array_map(function($item) { return $item->toArray(); }, $this->buildConfigurations)
            ];
        }

        /**
         * Loads a Project configuration from a YAML file
         *
         * @param string $filePath The path to the YAML file
         * @param bool $macros Optional. Whether to process macros in the YAML file (default: false)
         * @return Project The loaded Project configuration
         */
        public static function fromFile(string $filePath, bool $macros=false): Project
        {
            if(!file_exists($filePath) || !is_readable($filePath))
            {
                throw new InvalidArgumentException('The file \'' . $filePath . '\' does not exist or is not readable');
            }


            $results = Yaml::parse(file_get_contents($filePath));

            // If macros are enabled, process them
            if($macros)
            {
                Logger::getLogger()->debug(sprintf('Applying macros to %s', $filePath));
                $results = MacroVariable::fromArray($results, handle: function($input) use ($results, $filePath){
                    return match($input)
                    {
                        MacroVariable::PROJECT_PATH->value => dirname($filePath),
                        MacroVariable::ASSEMBLY_NAME->value => $results['assembly']['name'] ?? null,
                        MacroVariable::ASSEMBLY_PACKAGE->value => $results['assembly']['package'] ?? null,
                        MacroVariable::ASSEMBLY_VERSION->value => $results['assembly']['version'] ?? null,
                        MacroVariable::ASSEMBLY_URL->value => $results['assembly']['url'] ?? null,
                        MacroVariable::ASSEMBLY_LICENSE->value => $results['assembly']['license'] ?? null,
                        MacroVariable::ASSEMBLY_DESCRIPTION->value => $results['assembly']['description'] ?? null,
                        MacroVariable::ASSEMBLY_AUTHOR->value => $results['assembly']['author'] ?? null,
                        MacroVariable::ASSEMBLY_ORGANIZATION->value => $results['assembly']['organization'] ?? null,
                        MacroVariable::ASSEMBLY_PRODUCT->value => $results['assembly']['product'] ?? null,
                        MacroVariable::ASSEMBLY_COPYRIGHT->value => $results['assembly']['copyright'] ?? null,
                        MacroVariable::ASSEMBLY_TRADEMARK->value => $results['assembly']['trademark'] ?? null,
                        default => $input
                    };
                });
            }

            return new self($results);
        }

        /**
         * Creates a compiler from the project's configuration file, the compiler type is based off the provided build
         * configuration name; if no name is provided then it will choose the default build configuration.
         *
         * @param string $filePath The file path to the project's configuration path.
         * @param string|null $buildConfigurationName Optional. The build configuration to use, if null the default one will be used.
         * @return AbstractCompiler Returns the Abstract compiler object allowing you to compile the project, the compiler
         *                          type is based off the build configuration however; still allowing you to run the
         *                          build() method the same way no matter the compiler
         * @throws CompileException Thrown if there was an issue creating the compiler for the project
         */
        public static function compilerFromFile(string $filePath, ?string $buildConfigurationName=null): AbstractCompiler
        {
            Logger::getLogger()->debug('Creating compiler from project configuration file: ' . $filePath);
            $projectConfiguration = self::fromFile($filePath, true);

            if($buildConfigurationName === null)
            {
                $buildConfigurationName = $projectConfiguration->getDefaultBuild();
                Logger::getLogger()->debug(sprintf('No build configuration name provided, using default build configuration: %s', $buildConfigurationName));
            }

            $buildConfiguration = $projectConfiguration->getBuildConfiguration($buildConfigurationName);
            if($buildConfiguration === null)
            {
                throw new CompileException('Could not find the build configuration in the project configuration');
            }

            return match ($buildConfiguration->getType())
            {
                BuildType::NCC_PACKAGE => new PackageCompiler(dirname($filePath), $buildConfigurationName),
                default => throw new CompileException('Compiler method not implemented yet'),
            };
        }

        /**
         * Creates a new Project configuration with default values
         *
         * @param string $name The name of the project
         * @param string $package The package name of the project
         * @return Project The newly created Project configuration
         */
        public static function createNew(string $name, string $package): Project
        {
            return new self([
                'assembly' => [
                    'name' => $name,
                    'version' => '0.1.0',
                    'package' => $package,
                ],
                'build_configurations' => [
                    BuildConfiguration::defaultDebug()->toArray(),
                    BuildConfiguration::defaultRelease()->toArray()
                ]
            ]);
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Project
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public static function validateArray(array $data): void
        {
            if(isset($data['source']) && (!is_string($data['source']) || trim($data['source']) === ''))
            {
                throw new InvalidPropertyException('source', 'The project source path must be a non-empty string if set');
            }

            if(isset($data['default_build']) && (!is_string($data['default_build']) || trim($data['default_build']) === ''))
            {
                throw new InvalidPropertyException('default_build', 'The default build configuration must be a non-empty string if set');
            }
            elseif(!self::validateBuildConfigurationExists($data, $data['default_build']))
            {
                throw new InvalidPropertyException('default_build', 'The default build configuration must point to a valid execution unit');
            }

            if(isset($data['entry_point']))
            {
                if((!is_string($data['entry_point']) || trim($data['entry_point']) === ''))
                {
                    throw new InvalidPropertyException('entry_point', 'The entry point must be a non-empty string if set');
                }

                if(!self::validateExecutionUnitExists($data, $data['entry_point']))
                {
                    throw new InvalidPropertyException('entry_point', 'The entry point must point to an existing execution unit');
                }
            }

            if(isset($data['update_source']) && !is_string($data['update_source']))
            {
                throw new InvalidPropertyException('update_source', 'The update source must be a string if set');
            }

            if(isset($data['pre_compile']) && !self::validateExecutionUnitExists($data, $data['pre_compile']))
            {
                throw new InvalidPropertyException('pre_compile', 'The pre-compile property must point to a valid execution point if it\'s set.');
            }

            if(isset($data['post_compile']) && !self::validateExecutionUnitExists($data, $data['post_compile']))
            {
                throw new InvalidPropertyException('post_compile', 'The post-compile property must point to a valid execution point if it\'s set');
            }

            if(isset($data['pre_install']) && !self::validateExecutionUnitExists($data, $data['pre_install']))
            {
                throw new InvalidPropertyException('pre_install', 'The pre-install property must point to a valid execution point if it\'s set');
            }

            if(isset($data['post_install']) && !self::validateExecutionUnitExists($data, $data['post_install']))
            {
                throw new InvalidPropertyException('post_install', 'The post-install property must point to a valid execution point if it\'s set');
            }

            if(isset($data['repository']) && !is_array($data['repository']))
            {
                throw new InvalidPropertyException('repository', 'The repository configuration must be an array if set');
            }

            if(isset($data['assembly']))
            {
                if(!is_array($data['assembly']))
                {
                    throw new InvalidPropertyException('assembly', 'The assembly configuration must be an array if set');
                }
                Assembly::validateArray($data['assembly']);
            }

            if(isset($data['dependencies']))
            {
                if(!is_array($data['dependencies']))
                {
                    throw new InvalidPropertyException('dependencies', 'The dependencies must be an array if set');
                }

                foreach($data['dependencies'] as $index => $dependency)
                {
                    if(!is_string($dependency))
                    {
                        throw new InvalidPropertyException("dependencies[{$index}]", 'Each dependency must be a project source string');
                    }
                }
            }

            if(isset($data['execution_units']))
            {
                if(!is_array($data['execution_units']))
                {
                    throw new InvalidPropertyException('execution_units', 'The execution units property must be an array of execution units if it\'s set');
                }

                foreach($data['execution_units'] as $index => $unit)
                {
                    if(!is_array($unit))
                    {
                        throw new InvalidPropertyException("execution_units[{$index}]", 'Each execution unit must be an array');
                    }

                    ExecutionUnit::validateArray($unit);
                }
            }

            if(isset($data['build_configurations']))
            {
                if(!is_array($data['build_configurations']))
                {
                    throw new InvalidPropertyException('build_configurations', 'The build configurations property must be an array of build configuration if it\'s set');
                }

                foreach($data['build_configurations'] as $index => $config)
                {
                    if(!is_array($config))
                    {
                        throw new InvalidPropertyException("build_configurations[{$index}]", 'Each build configuration must be an array');
                    }

                    BuildConfiguration::validateArray($config);
                }
            }
        }

        /**
         * Validates if the execution unit is defined in the arary, returns True if so; False otherwise.
         *
         * @param array $data The root of the project configuration array to check
         * @param string $name The name of the execution unit
         * @return bool True if the Execution Unit exists, False otherwise.
         */
        private static function validateExecutionUnitExists(array $data, string $name): bool
        {
            // TODO: Update this! Currently only works for single string, not arrays of strings.
            if(!isset($data['execution_units']) || !is_array($data['execution_units']))
            {
                return false;
            }

            foreach($data['execution_units'] as $unit)
            {
                if(isset($unit['name']) && $unit['name'] === $name)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Validates if the build configuration is defined in the array, returns True if so; False otherwise.
         *
         * @param array $data The root of the project configuration array to check
         * @param string $name The name of the build configuration to check for
         * @return bool True if the build configuration exists, False otherwise.
         */
        private static function validateBuildConfigurationExists(array $data, string $name): bool
        {
            if(!isset($data['build_configurations']) || !is_array($data['build_configurations']))
            {
                return false;
            }

            foreach($data['build_configurations'] as $config)
            {
                if(isset($config['name']) && $config['name'] === $name)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            self::validateArray($this->toArray());
        }
    }