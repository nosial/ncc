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
    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Project\BuildConfiguration;

    class Project implements SerializableInterface
    {
        private string $sourcePath;
        private string $defaultBuild;
        private ?string $entryPoint;
        private ?PackageSource $updateSource;
        private ?RepositoryConfiguration $repository;
        private Assembly $assembly;
        /**
         * @var PackageSource[]
         */
        private array $dependencies;
        /**
         * @var BuildConfiguration[]
         */
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
            $this->repository = isset($data['repository']) ? RepositoryConfiguration::fromArray($data['repository']) : null;
            $this->assembly = new Assembly($data['assembly'] ?? []);
            $this->dependencies = array_map(function($item)
            {
                return new PackageSource($item);
            }, $data['dependencies'] ?? []);
            $this->buildConfigurations = array_map(function($item)
            {
                return new BuildConfiguration($item);
            }, $data['build_configurations'] ?? []);

        }

        /**
         * Returns the current source path of the project
         *
         * @return string The source path of the project
         */
        public function getSourcePath(): string
        {
            return $this->sourcePath;
        }

        /**
         * Sets the source path of the configuration, this is where the PHP code of the project is retained
         *
         * @param string $sourcePath The source path of the project
         */
        public function setSourcePath(string $sourcePath): void
        {
            if(empty($sourcePath))
            {
                throw new InvalidArgumentException('The \'path\' parameter cannot be empty');
            }

            $this->sourcePath = $sourcePath;
        }

        /**
         * Returns the default build configuration of the project
         *
         * @return string The default build configuration of the project
         */
        public function getDefaultBuild(): string
        {
            return $this->defaultBuild;
        }

        /**
         * Sets the default build configuration of the project
         *
         * @param string $defaultBuild The default build configuration of the project
         */
        public function setDefaultBuild(string $defaultBuild): void
        {
            if(empty($defaultBuild))
            {
                throw new InvalidArgumentException('The \'defaultBuild\' parameter cannot be empty');
            }

            if(!$this->buildConfigurationExists($defaultBuild))
            {
                throw new InvalidArgumentException('The build configuration \'' . $defaultBuild . '\' does not exist');
            }

            $this->defaultBuild = $defaultBuild;
        }

        /**
         * Returns the entry point of the project, if set
         *
         * @return string|null The entry point of the project, or null if not set
         */
        public function getEntryPoint(): ?string
        {
            return $this->entryPoint;
        }

        /**
         * Sets the entry point of the project
         *
         * @param string|null $entryPoint The entry point of the project, or null to unset it
         */
        public function setEntryPoint(?string $entryPoint): void
        {
            $this->entryPoint = $entryPoint;
        }

        /**
         * Returns the update source of the project, if set
         *
         * @return PackageSource|null The update source of the project, or null if not set
         */
        public function getUpdateSource(): ?PackageSource
        {
            return $this->updateSource;
        }

        /**
         * Sets the update source of the project
         *
         * @param PackageSource|null $updateSource The update source of the project, or null to unset it
         */
        public function setUpdateSource(?PackageSource $updateSource): void
        {
            $this->updateSource = $updateSource;
        }

        /**
         * Returns the repository configuration of the project, if set
         *
         * @return RepositoryConfiguration|null The repository configuration of the project, or null if not set
         */
        public function getRepository(): ?RepositoryConfiguration
        {
            return $this->repository;
        }

        /**
         * Sets the repository configuration of the project
         *
         * @param RepositoryConfiguration|null $repository The repository configuration of the project, or null to unset it
         */
        public function setRepository(?RepositoryConfiguration $repository): void
        {
            $this->repository = $repository;
        }

        /**
         * Returns the configurable Assembly property of the project configuration
         *
         * @return Assembly The Assembly property of the project configuration
         */
        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        /**
         * Returns all the dependencies of the project
         *
         * @return PackageSource[] An array of PackageSource objects
         */
        public function getDependencies(): array
        {
            return $this->dependencies;
        }


        public function dependencyExists(string $name): bool
        {
            foreach($this->dependencies as $dependency)
            {
                if((string)$dependency === $name)
                {
                    return true;
                }
            }

            return false;
        }

        public function addDependency(PackageSource|string $dependency): void
        {
            if(is_string($dependency))
            {
                $dependency = new PackageSource($dependency);
            }

            if($this->dependencyExists((string)$dependency))
            {
                throw new InvalidArgumentException('The dependency \'' . (string)$dependency . '\' already exists');
            }

            $this->dependencies[] = $dependency;
        }

        public function removeDependency(string $name): void
        {
            foreach($this->dependencies as $index => $dependency)
            {
                if((string)$dependency === $name)
                {
                    unset($this->dependencies[$index]);
                    $this->dependencies = array_values($this->dependencies);
                    return;
                }
            }

            throw new InvalidArgumentException('The dependency \'' . $name . '\' does not exist');
        }

        /**
         *
         * @param PackageSource[] $dependencies
         */
        public function setDependencies(array $dependencies): void
        {
            $this->dependencies = $dependencies;
        }


        /**
         * Returns all the build configurations of the project
         *
         * @return BuildConfiguration[] An array of BuildConfiguration objects
         */
        public function getBuildConfigurations(): array
        {
            return $this->buildConfigurations;
        }

        public function getBuildConfiguration(string $name): ?BuildConfiguration
        {
            foreach($this->buildConfigurations as $config)
            {
                if($config->getName() === $name)
                {
                    return $config;
                }
            }

            return null;
        }

        public function buildConfigurationExists(string $name): bool
        {
            foreach($this->buildConfigurations as $config)
            {
                if($config->getName() === $name)
                {
                    return true;
                }
            }

            return false;
        }

        public function setBuildConfigurations(array $buildConfigurations): void
        {
            $this->buildConfigurations = $buildConfigurations;
        }

        public function addBuildConfiguration(BuildConfiguration $buildConfiguration): void
        {
            if($this->buildConfigurationExists($buildConfiguration->getName()))
            {
                throw new InvalidArgumentException('A build configuration with the name \'' . $buildConfiguration->getName() . '\' already exists');
            }

            $this->buildConfigurations[] = $buildConfiguration;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $this->dependencies = array_map(function($item)
            {
                return new PackageSource($item);
            }, $data['dependencies'] ?? []);
            return [
                'source' => $this->sourcePath,
                'default_build' => $this->defaultBuild,
                'entry_point' => $this->entryPoint,
                'update_source' => $this->updateSource ? (string)$this->updateSource : null,
                'repository' => $this->repository?->toArray(),
                'assembly' => $this->assembly->toArray(),
                'dependencies' => array_map(function($item) { return (string)$item; }, $this->dependencies),
                'build_configurations' => array_map(function($item) { return $item->toArray(); }, $this->buildConfigurations)
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Project
        {
            return new self($data);
        }
    }