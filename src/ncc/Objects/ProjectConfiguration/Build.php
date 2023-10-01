<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use InvalidArgumentException;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Interfaces\ValidatableObjectInterface;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Build implements BytecodeObjectInterface, ValidatableObjectInterface
    {
        /**
         * The source directory that the compiler will target to generate a build
         *
         * @var string
         */
        private $source_path;

        /**
         * The default configuration to use when building
         *
         * @var string
         */
        private $default_configuration;

        /**
         * An array of files to exclude from processing/bundling into the build output
         *
         * @var string[]
         */
        private $exclude_files;

        /**
         * Build options to pass on to the compiler
         *
         * @var array
         */
        private $options;

        /**
         * The execution policy to use as the main execution point
         *
         * @var string|null
         */
        private $main;

        /**
         * An array of constants to define by default
         *
         * @var string[]
         */
        private $define_constants;

        /**
         * An array of execution policies to execute pre build
         *
         * @var string[]
         */
        private $pre_build;

        /**
         * An array of execution policies to execute post build
         *
         * @var string[]
         */
        private $post_build;

        /**
         * An array of dependencies that are required by default
         *
         * @var Dependency[]
         */
        private $dependencies;

        /**
         * An array of build configurations
         *
         * @var BuildConfiguration[]
         */
        private $build_configurations;

        /**
         * Public Constructor
         */
        public function __construct(string $source_path, ?string $default_configuration=null)
        {
            $this->source_path = $source_path;
            $this->default_configuration = $default_configuration ?? BuildConfigurationValues::DEFAULT;
            $this->exclude_files = [];
            $this->options = [];
            $this->define_constants = [];
            $this->dependencies = [];
            $this->build_configurations = [];
        }

        /**
         * @return string
         */
        public function getSourcePath(): string
        {
            return $this->source_path;
        }

        /**
         * @param string $source_path
         */
        public function setSourcePath(string $source_path): void
        {
            $this->source_path = $source_path;
        }

        /**
         * @return string
         */
        public function getDefaultConfiguration(): string
        {
            return $this->default_configuration;
        }

        /**
         * @param string $default_configuration
         */
        public function setDefaultConfiguration(string $default_configuration): void
        {
            $this->default_configuration = $default_configuration;
        }

        /**
         * @return array|string[]
         */
        public function getExcludeFiles(): array
        {
            return $this->exclude_files;
        }

        /**
         * @param array|string[] $exclude_files
         */
        public function setExcludeFiles(array $exclude_files): void
        {
            $this->exclude_files = $exclude_files;
        }

        /**
         * @param string $file
         * @return void
         */
        public function excludeFile(string $file): void
        {
            $this->exclude_files[] = $file;
        }

        /**
         * @param string $file
         * @return void
         */
        public function removeExcludedFile(string $file): void
        {
            foreach($this->exclude_files as $key => $exclude_file)
            {
                if($exclude_file === $file)
                {
                    unset($this->exclude_files[$key]);
                    return;
                }
            }
        }

        /**
         * Returns the options for the build, optionally including the options for the given build configuration
         *
         * @param string|null $build_configuration
         * @return array
         */
        public function getOptions(?string $build_configuration=null): array
        {
            if($build_configuration === null)
            {
                return $this->options;
            }

            return array_merge($this->options, $this->getBuildConfiguration($build_configuration)->getOptions());
        }

        /**
         * @param array $options
         */
        public function setOptions(array $options): void
        {
            $this->options = $options;
        }

        /**
         * @param string $name
         * @param string $value
         * @return void
         */
        public function setOption(string $name, mixed $value): void
        {
            $this->options[$name] = $value;
        }

        /**
         * @param string $name
         * @return void
         */
        public function removeOption(string $name): void
        {
            foreach($this->options as $key => $option)
            {
                if($option === $name)
                {
                    unset($this->options[$key]);
                    return;
                }
            }
        }

        /**
         * @return string|null
         */
        public function getMain(): ?string
        {
            return $this->main;
        }

        /**
         * @param string|null $main
         * @return void
         */
        public function setMain(?string $main): void
        {
            $this->main = $main;
        }

        /**
         * Adds a new dependency to the build if it doesn't already exist
         *
         * @param Dependency $dependency
         * @return void
         */
        public function addDependency(Dependency $dependency): void
        {
            foreach($this->dependencies as $dep)
            {
                if($dep->getName() === $dependency->getName())
                {
                    $this->removeDependency($dep->getName());
                    break;
                }
            }

            $this->dependencies[] = $dependency;
        }

        /**
         * Removes a dependency from the build
         *
         * @param string $name
         * @return void
         */
        private function removeDependency(string $name): void
        {
            foreach($this->dependencies as $key => $dep)
            {
                if($dep->getName() === $name)
                {
                    unset($this->dependencies[$key]);
                    return;
                }
            }
        }

        /**
         * @return Dependency[]
         */
        public function getDependencies(): array
        {
            return $this->dependencies;
        }

        /**
         * @param Dependency[] $dependencies
         * @return void
         */
        public function setDependencies(array $dependencies): void
        {
            $this->dependencies = $dependencies;
        }

        /**
         * @return array|string[]
         */
        public function getDefineConstants(): array
        {
            return $this->define_constants;
        }

        /**
         * @param array|string[] $define_constants
         */
        public function setDefineConstants(array $define_constants): void
        {
            $this->define_constants = $define_constants;
        }

        /**
         * @param string $name
         * @param string $value
         * @return void
         */
        public function addDefineConstant(string $name, string $value): void
        {
            $this->define_constants[$name] = $value;
        }

        /**
         * @param string $name
         * @return void
         */
        public function removeDefineConstant(string $name): void
        {
            foreach($this->define_constants as $key => $define_constant)
            {
                if($define_constant === $name)
                {
                    unset($this->define_constants[$key]);
                    return;
                }
            }
        }

        /**
         * @return string[]
         */
        public function getPreBuild(): array
        {
            return $this->pre_build;
        }

        /**
         * @param string[] $pre_build
         */
        public function setPreBuild(array $pre_build): void
        {
            $this->pre_build = $pre_build;
        }

        /**
         * Adds a new pre-build policy to the build
         *
         * @param string $policy
         * @return void
         */
        public function addPreBuildPolicy(string $policy): void
        {
            $this->pre_build[] = $policy;
        }

        /**
         * @param string $policy
         * @return void
         */
        public function removePreBuildPolicy(string $policy): void
        {
            foreach($this->pre_build as $key => $pre_build)
            {
                if($pre_build === $policy)
                {
                    unset($this->pre_build[$key]);
                    return;
                }
            }
        }

        /**
         * @return string[]
         */
        public function getPostBuild(): array
        {
            return $this->post_build;
        }

        /**
         * @param string[] $post_build
         */
        public function setPostBuild(array $post_build): void
        {
            $this->post_build = $post_build;
        }

        /**
         * @param string $policy
         * @return void
         */
        public function addPostBuildPolicy(string $policy): void
        {
            $this->post_build[] = $policy;
        }

        /**
         * @param string $policy
         * @return void
         */
        public function removePostBuildPolicy(string $policy): void
        {
            foreach($this->post_build as $key => $post_build)
            {
                if($post_build === $policy)
                {
                    unset($this->post_build[$key]);
                    return;
                }
            }
        }


        /**
         * Returns an array of all the build configurations defined in the project configuration
         *
         * @return array
         * @noinspection PhpUnused
         */
        public function getBuildConfigurations(): array
        {
            $build_configurations = [];

            foreach($this->build_configurations as $configuration)
            {
                $build_configurations[] = $configuration->getName();
            }

            return $build_configurations;
        }

        /**
         * Returns the build configurations defined in the project configuration, throw an
         * exception if there is no such configuration defined in the project configuration
         *
         * @param string $name
         * @return BuildConfiguration
         */
        public function getBuildConfiguration(string $name): BuildConfiguration
        {
            if($name === BuildConfigurationValues::DEFAULT)
            {
                $name = $this->default_configuration;
            }

            foreach($this->build_configurations as $configuration)
            {
                if($configuration->getName() === $name)
                {
                    return $configuration;
                }
            }

            throw new InvalidArgumentException(sprintf('The build configuration "%s" does not exist', $name));
        }

        /**
         * @param array $build_configurations
         * @return void
         */
        public function setBuildConfigurations(array $build_configurations): void
        {
            $this->build_configurations = $build_configurations;
        }

        /**
         * @param BuildConfiguration $configuration
         * @return void
         */
        public function addBuildConfiguration(BuildConfiguration $configuration): void
        {
            $this->build_configurations[] = $configuration;
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            // Check the defined constants
            foreach($this->define_constants as $name => $value)
            {
                if(!Validate::constantName($name))
                {
                    throw new ConfigurationException(sprintf('The name "%s" is not valid for a constant declaration', $name));
                }
            }

            // Check for duplicate configuration names
            $build_configurations = [];
            foreach($this->build_configurations as $configuration)
            {
                if(in_array($configuration->getName(), $build_configurations, true))
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $configuration->getName()));
                }
            }

            foreach($this->build_configurations as $configuration)
            {
                $configuration->validate();
            }

            if($this->default_configuration === null)
            {
                throw new ConfigurationException('The default build configuration is not set');
            }

            if(!Validate::nameFriendly($this->default_configuration))
            {
                throw new ConfigurationException(sprintf('The default build configuration name "%s" is not valid', $this->default_configuration));
            }
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->source_path !== null)
            {
                $results[($bytecode ? Functions::cbc('source_path') : 'source_path')] = $this->source_path;
            }
            if($this->default_configuration !== null)
            {
                $results[($bytecode ? Functions::cbc('default_configuration') : 'default_configuration')] = $this->default_configuration;
            }
            if($this->exclude_files !== null && count($this->exclude_files) > 0)
            {
                $results[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->exclude_files;
            }
           if($this->options !== null && count($this->options) > 0)
           {
               $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;
           }

           if($this->main !== null)
           {
               $results[($bytecode ? Functions::cbc('main') : 'main')] = $this->main;
           }

           if($this->define_constants !== null && count($this->define_constants) > 0)
           {
               $results[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->define_constants;
           }

           if($this->pre_build !== null && count($this->pre_build) > 0)
           {
               $results[($bytecode ? Functions::cbc('pre_build') : 'pre_build')] = $this->pre_build;
           }

           if($this->post_build !== null && count($this->post_build) > 0)
           {
               $results[($bytecode ? Functions::cbc('post_build') : 'post_build')] = $this->post_build;
           }

           if($this->dependencies !== null && count($this->dependencies) > 0)
           {
                $dependencies = [];
                foreach($this->dependencies as $dependency)
                {
                     $dependencies[] = $dependency->toArray($bytecode);
                }
                $results[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = $dependencies;
           }

           if($this->build_configurations !== null && count($this->build_configurations) > 0)
           {
                $configurations = [];

                foreach($this->build_configurations as $configuration)
                {
                     $configurations[] = $configuration->toArray($bytecode);
                }

                $results[($bytecode ? Functions::cbc('configurations') : 'configurations')] = $configurations;
           }

            return $results;
        }

        /**
         * Returns an array
         *
         * @param array $data
         * @return Build
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Build
        {
            $source_path = Functions::array_bc($data, 'source_path');
            if($source_path === null)
            {
                throw new ConfigurationException('The property \'project.build.source_path\' must not be null.');
            }

            $object = new self($source_path, Functions::array_bc($data, 'default_configuration'));

            $object->exclude_files = (Functions::array_bc($data, 'exclude_files') ?? []);
            $object->options = (Functions::array_bc($data, 'options') ?? []);
            $object->define_constants = (Functions::array_bc($data, 'define_constants') ?? []);
            $object->pre_build = (Functions::array_bc($data, 'pre_build') ?? []);
            $object->post_build = (Functions::array_bc($data, 'post_build') ?? []);
            $object->main = Functions::array_bc($data, 'main');

            $dependencies = Functions::array_bc($data, 'dependencies');
            if($dependencies !== null)
            {
                foreach($dependencies as $dependency)
                {
                    $object->dependencies[] = Dependency::fromArray($dependency);
                }
            }

            $configurations = Functions::array_bc($data, 'configurations');
            if($configurations !== null)
            {
                foreach($configurations as $configuration)
                {
                    $object->build_configurations[] = BuildConfiguration::fromArray($configuration);
                }
            }

            return $object;
        }
    }