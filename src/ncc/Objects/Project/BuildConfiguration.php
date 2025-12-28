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

    namespace ncc\Objects\Project;

    use InvalidArgumentException;
    use ncc\Enums\BuildType;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;
    use ncc\Objects\PackageSource;

    class BuildConfiguration implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private string $output;
        private BuildType $type;
        private array $definitions;
        private array $includeComponents;
        private array $excludeComponents;
        private array $includeResources;
        private array $excludeResources;
        private ?array $dependencies;
        private ?array $options;

        /**
         * BuildConfiguration constructor.
         *
         * @param array $data The data to initialize the build configuration.
         */
        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? 'release';
            $this->output = $data['output'] ?? 'out';
            $this->type = BuildType::tryFrom($data['type']) ?? BuildType::NCC_PACKAGE;
            $this->definitions = $data['definitions'] ?? [];
            $this->includeComponents = $data['include_components'] ?? [];
            $this->excludeComponents = $data['exclude_components'] ?? [];
            $this->includeResources = $data['include_resources'] ?? [];
            $this->excludeResources = $data['exclude_resources'] ?? [];
            $this->dependencies = isset($data['dependencies']) ? array_map(function($item) { return is_string($item) ? new PackageSource($item) : $item; }, $data['dependencies']) : null;
            $this->options = $data['options'] ?? null;
        }

        /**
         * Get the name of the build configuration.
         *
         * @return string The name of the build configuration.
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Set the name of the build configuration.
         *
         * @param string $name The name to set.
         * @throws InvalidArgumentException If the name is empty.
         */
        public function setName(string $name): void
        {
            if(empty($name))
            {
                throw new InvalidArgumentException('Build configuration name cannot be empty');
            }

            $this->name = $name;
        }

        /**
         * Get the output path of the build configuration.
         *
         * @return string The output path.
         */
        public function getOutput(): string
        {
            return $this->output;
        }

        /**
         * Set the output path of the build configuration.
         *
         * @param string $output The output path to set.
         * @throws InvalidArgumentException If the output path is empty.
         */
        public function setOutput(string $output): void
        {
            if(empty($output))
            {
                throw new InvalidArgumentException('Build configuration output cannot be empty');
            }

            $this->output = $output;
        }

        /**
         * Get the build type of the configuration.
         *
         * @return BuildType The build type.
         */
        public function getType(): BuildType
        {
            return $this->type;
        }

        /**
         * Set the build type of the configuration.
         *
         * @param BuildType $type The build type to set.
         */
        public function setType(BuildType $type): void
        {
            $this->type = $type;
        }

        /**
         * Get the definitions of the build configuration.
         *
         * @return array The definitions.
         */
        public function getDefinitions(): array
        {
            return $this->definitions;
        }

        /**
         * Set the definitions of the build configuration.
         *
         * @param array $definitions The definitions to set.
         */
        public function setDefinitions(array $definitions): void
        {
            $this->definitions = $definitions;
        }

        /**
         * Get the options of the build configuration.
         *
         * @return array|null The options, or null if none are set.
         */
        public function getOptions(): ?array
        {
            return $this->options;
        }

        /**
         * Adds an option to the build configuration.
         *
         * @param string $key The option key.
         * @param mixed $value The option value.
         */
        public function addOption(string $key, mixed $value): void
        {
            if($this->options === null)
            {
                $this->options = [];
            }

            $this->options[$key] = $value;
        }

        /**
         * Set the options of the build configuration.
         *
         * @param array|null $options The options to set, or null to unset.
         */
        public function setOptions(?array $options): void
        {
            $this->options = $options;
        }

        /**
         * Returns an array of file patterns to include certain component files
         *
         * @return string[] An array of file patterns to include components
         */
        public function getIncludedComponents(): array
        {
            return $this->includeComponents;
        }

        /**
         * Sets an array of file patterns to include certain component files
         *
         * @param string[] $components An array of file patterns to include components
         */
        public function setIncludedComponents(array $components): void
        {
            $this->includeComponents = $components;
        }

        /**
         * Adds a file pattern to include a certain component file
         *
         * @param string $component A file pattern to include a component
         */
        public function addIncludedComponent(string $component): void
        {
            if(!in_array($component, $this->includeComponents, true))
            {
                return;
            }

            $this->includeComponents[] = $component;
        }

        /**
         * Returns an array of file patterns to exclude certain component files
         *
         * @return string[] An array of file patterns to exclude components
         */
        public function getExcludedComponents(): array
        {
            return $this->excludeComponents;
        }

        /**
         * Sets an array of file patterns to exclude certain component files
         *
         * @param string[] $components An array of file patterns to exclude components
         */
        public function setExcludedComponents(array $components): void
        {
            $this->excludeComponents = $components;
        }

        /**
         * Adds a file pattern to exclude a certain component file
         *
         * @param string $component A file pattern to exclude a component
         */
        public function addExcludedComponent(string $component): void
        {
            if(!in_array($component, $this->excludeComponents, true))
            {
                return;
            }

            $this->excludeComponents[] = $component;
        }

        /**
         * Returns an array of file patterns used to include certain resource files
         *
         * @return string[] An array of file patterns to include resources
         */
        public function getIncludedResources(): array
        {
            return $this->includeResources;
        }

        /**
         * Sets an array of file patterns to include certain resource files
         *
         * @param string[] $resources An array of file patterns to include resources
         */
        public function setIncludedResources(array $resources): void
        {
            $this->includeResources = $resources;
        }

        /**
         * Adds a file pattern to include a certain resource file
         *
         * @param string $resource A file pattern to include a resource
         */
        public function addIncludedResource(string $resource): void
        {
            if(!in_array($resource, $this->includeResources, true))
            {
                return;
            }

            $this->includeResources[] = $resource;
        }

        /**
         * Returns an array of file patterns used to exclude certain resource files
         *
         * @return string[] An array of file patterns to exclude resources
         */
        public function getExcludedResources(): array
        {
            return $this->excludeResources;
        }

        /**
         * Sets an array of file patterns to exclude certain resource files
         *
         * @param string[] $resources An array of file patterns to exclude resources
         */
        public function setExcludedResources(array $resources): void
        {
            $this->excludeResources = $resources;
        }

        /**
         * Adds a file pattern to exclude a certain resource file
         *
         * @param string $resource A file pattern to exclude a resource
         */
        public function addExcludedResource(string $resource): void
        {
            if(!in_array($resource, $this->excludeResources, true))
            {
                return;
            }

            $this->excludeResources[] = $resource;
        }

        /**
         * Returns the build configurations defined in the project
         *
         * @return PackageSource[]|null An array of PackageSource objects keyed by package name, or null if no dependencies are defined
         */
        public function getDependencies(): ?array
        {
            return $this->dependencies;
        }

        /**
         * Returns a specific dependency by its name
         *
         * @param PackageSource|string $dependency The name of the dependency to retrieve
         * @return PackageSource|null The PackageSource object if found, or null if not found or no dependencies are defined
         */
        public function getDependency(PackageSource|string $dependency): ?PackageSource
        {
            if(is_string($dependency) && isset($this->dependencies[$dependency]))
            {
                return $this->dependencies[$dependency];
            }

            if($dependency instanceof PackageSource)
            {
                $dependency = $dependency->getName();
            }

            if($this->dependencies === null)
            {
                return null;
            }

            foreach($this->dependencies as $packageSource)
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
         * @param PackageSource|string $dependency The name of the dependency to check
         * @return bool True if the dependency exists, false otherwise
         */
        public function dependencyExists(PackageSource|string $dependency): bool
        {
            return $this->getDependency($dependency) !== null;
        }

        /**
         * Adds a new dependency to the project
         *
         * @param PackageSource|string $dependency The PackageSource object representing the dependency to add
         * @throws InvalidArgumentException If a dependency with the same name already exists
         */
        public function addDependency(string $package, PackageSource|string $dependency): void
        {
            if(is_string($dependency) && isset($this->dependencies[$package]))
            {
                return;
            }

            if(is_string($dependency))
            {
                $dependency = new PackageSource($dependency);
            }

            if($this->dependencies === null)
            {
                $this->dependencies = [];
            }

            if($this->dependencyExists($package))
            {
                throw new InvalidArgumentException('A dependency with the name \'' . (string)$dependency->getName() . '\' already exists');
            }

            $this->dependencies[$package] = $dependency;
        }

        /**
         * Removes a dependency from the project by its name
         *
         * @param PackageSource|string $dependency The name of the dependency to remove
         */
        public function removeDependency(PackageSource|string $dependency): void
        {
            if($dependency instanceof PackageSource)
            {
                $dependency = (string)$dependency;
            }

            if($this->dependencies === null)
            {
                return;
            }

            if(isset($this->dependencies[$dependency]))
            {
                unset($this->dependencies[$dependency]);
                return;
            }

            foreach($this->dependencies as $packageName => $packageSource)
            {
                if((string)$packageSource->getName() === $dependency)
                {
                    unset($this->dependencies[$packageName]);
                    return;
                }
            }
        }

        /**
         * Creates a default release build configuration.
         *
         * @return BuildConfiguration The default release build configuration.
         */
        public static function defaultRelease(): BuildConfiguration
        {
            return new self([
                'name' => 'release',
                'output' => 'target/release/${ASSEMBLY.PACKAGE}.ncc',
                'type' => BuildType::NCC_PACKAGE->value,
            ]);
        }

        /**
         * Creates a default debug build configuration.
         *
         * @return BuildConfiguration The default debug build configuration.
         */
        public static function defaultDebug(): BuildConfiguration
        {
            return new self([
                'name' => 'debug',
                'output' => 'target/debug/${ASSEMBLY.PACKAGE}.ncc',
                'definitions' => [
                    'NCC_DEBUG' => true
                ],
                'type' => BuildType::NCC_PACKAGE->value,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $dependenciesArray = null;

            if ($this->dependencies)
            {
                $dependenciesArray = [];
                foreach ($this->dependencies as $dependencyName => $dependencyObject)
                {
                    $dependenciesArray[$dependencyName] = (string)$dependencyObject;
                }
            }

            return [
                'name' => $this->name,
                'output' => $this->output,
                'type' => $this->type->value,
                'definitions' => $this->definitions,
                'include_components' => $this->includeComponents,
                'exclude_components' => $this->excludeComponents,
                'include_resources' => $this->includeResources,
                'exclude_resources' => $this->excludeResources,
                'dependencies' => $dependenciesArray,
                'options' => $this->options
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): BuildConfiguration
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public static function validateArray(array $data): void
        {
            if(!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '')
            {
                throw new InvalidPropertyException('build_configurations.?.name', 'Build configuration name must be a non-empty string');
            }

            if(!isset($data['output']) || !is_string($data['output']) || trim($data['output']) === '')
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.output', 'Build configuration output must be a non-empty string');
            }

            if(!isset($data['type']) || !is_string($data['type']) || !BuildType::tryFrom($data['type']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.type', 'Build configuration type must be a valid build type');
            }

            if(isset($data['definitions']) && !is_array($data['definitions']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.definitions', 'Build configuration definitions must be an array if set');
            }

            if(isset($data['include_components']) && !is_array($data['include_components']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.include_components', 'Build configuration include_components must be an array if set');
            }

            if(isset($data['exclude_components']) && !is_array($data['exclude_components']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.exclude_components', 'Build configuration exclude_components must be an array if set');
            }

            if(isset($data['include_resources']) && !is_array($data['include_resources']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.include_resources', 'Build configuration include_resources must be an array if set');
            }

            if(isset($data['exclude_resources']) && !is_array($data['exclude_resources']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.exclude_resources', 'Build configuration exclude_resources must be an array if set');
            }

            if(isset($data['dependencies']) && $data['dependencies'] !== null)
            {
                if(!is_array($data['dependencies']))
                {
                    throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.dependencies', 'Build configuration dependencies must be an array if set');
                }

                foreach($data['dependencies'] as $key => $dep)
                {
                    if(!is_string($dep))
                    {
                        throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.dependencies.' . $key, 'Each dependency must be a string');
                    }
                }
            }

            if(isset($data['options']) && !is_array($data['options']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.options', 'Build configuration options must be an array if set');
            }
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            self::validateArray($this->toArray());
        }
    }