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

    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Build implements BytecodeObjectInterface
    {
        /**
         * The source directory that the compiler will target to generate a build
         *
         * @var string
         */
        public $source_path;

        /**
         * The default configuration to use when building
         *
         * @var string
         */
        public $default_configuration;

        /**
         * An array of files to exclude from processing/bundling into the build output
         *
         * @var string[]
         */
        public $exclude_files;

        /**
         * Build options to pass on to the compiler
         *
         * @var array
         */
        public $options;

        /**
         * The installation scope for the package (System/User/Shared)
         *
         * @var [type]
         */
        public $scope;

        /**
         * The execution policy to use as the main execution point
         *
         * @var string|null
         */
        public $main;

        /**
         * An array of constants to define by default
         *
         * @var string[]
         */
        public $define_constants;

        /**
         * An array of execution policies to execute pre build
         *
         * @var string[]
         */
        public $pre_build;

        /**
         * An array of execution policies to execute post build
         *
         * @var string[]
         */
        public $post_build;

        /**
         * An array of dependencies that are required by default
         *
         * @var Dependency[]
         */
        public $dependencies;

        /**
         * An array of build configurations
         *
         * @var BuildConfiguration[]
         */
        public $build_configurations;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->exclude_files = [];
            $this->options = [];
            $this->define_constants = [];
            $this->dependencies = [];
            $this->build_configurations = [];
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
         * Validates the build configuration object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
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
                if(in_array($configuration->name, $build_configurations, true))
                {
                    if($throw_exception)
                    {
                        throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $configuration->name));
                    }

                    return false;
                }
            }

            foreach($this->build_configurations as $configuration)
            {
                if (!$configuration->validate($throw_exception))
                {
                    return false;
                }
            }

            if($this->default_configuration === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The default build configuration is not set');
                }

                return false;
            }

            if(!Validate::nameFriendly($this->default_configuration))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The default build configuration name "%s" is not valid', $this->default_configuration));
                }

                return false;
            }

            $this->getBuildConfiguration($this->default_configuration);

            return true;
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
                $build_configurations[] = $configuration->name;
            }

            return $build_configurations;
        }

        /**
         * Returns the build configurations defined in the project configuration, throw an
         * exception if there is no such configuration defined in the project configuration
         *
         * @param string $name
         * @return BuildConfiguration
         * @throws ConfigurationException
         */
        public function getBuildConfiguration(string $name): BuildConfiguration
        {
            if($name === BuildConfigurationValues::DEFAULT)
            {
                $name = $this->default_configuration;
            }

            foreach($this->build_configurations as $configuration)
            {
                if($configuration->name === $name)
                {
                    return $configuration;
                }
            }

            throw new ConfigurationException(sprintf('The build configuration "%s" does not exist', $name));
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

           if($this->scope !== null)
           {
               $results[($bytecode ? Functions::cbc('scope') : 'scope')] = $this->scope;
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
         */
        public static function fromArray(array $data): Build
        {
            $object = new self();

            $object->source_path = Functions::array_bc($data, 'source_path');
            $object->default_configuration = Functions::array_bc($data, 'default_configuration');
            $object->exclude_files = (Functions::array_bc($data, 'exclude_files') ?? []);
            $object->options = (Functions::array_bc($data, 'options') ?? []);
            $object->scope = Functions::array_bc($data, 'scope');
            $object->main = Functions::array_bc($data, 'main');
            $object->define_constants = (Functions::array_bc($data, 'define_constants') ?? []);
            $object->pre_build = (Functions::array_bc($data, 'pre_build') ?? []);
            $object->post_build = (Functions::array_bc($data, 'post_build') ?? []);

            if(Functions::array_bc($data, 'dependencies') !== null)
            {
                foreach(Functions::array_bc($data, 'dependencies') as $dependency)
                {
                    $object->dependencies[] = Dependency::fromArray($dependency);
                }
            }

            if(Functions::array_bc($data, 'configurations') !== null)
            {
                foreach(Functions::array_bc($data, 'configurations') as $configuration)
                {
                    $object->build_configurations[] = BuildConfiguration::fromArray($configuration);
                }
            }

            return $object;
        }
    }