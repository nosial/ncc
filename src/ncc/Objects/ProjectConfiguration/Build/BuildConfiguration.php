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

    namespace ncc\Objects\ProjectConfiguration\Build;

    use ncc\Enums\Types\BuildOutputType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Interfaces\ValidatableObjectInterface;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class BuildConfiguration implements BytecodeObjectInterface, ValidatableObjectInterface
    {
        /**
         * The unique name of the build configuration
         *
         * @var string
         */
        private $name;

        /**
         * @var string
         */
        private $build_type;

        /**
         * Options to pass onto the extension compiler
         *
         * @var array
         */
        private $options;

        /**
         * The build output for the build configuration, eg; build/%ASSEMBLY.PACKAGE%.ncc
         *
         * @var string
         */
        private $output;

        /**
         * An array of constants to define for the build when importing or executing.
         *
         * @var string[]
         */
        private $define_constants;

        /**
         * An array of files to exclude in this build configuration
         *
         * @var string[]
         */
        private $exclude_files;

        /**
         * An array of policies to execute pre-building the package
         *
         * @var string[]
         */
        private $pre_build;

        /**
         * An array of policies to execute post-building the package
         *
         * @var string[]
         */
        private $post_build;

        /**
         * Dependencies required for the build configuration, cannot conflict with the
         * default dependencies
         *
         * @var Dependency[]
         */
        private $dependencies;

        /**
         * Public Constructor
         */
        public function __construct(string $name, string $output)
        {
            $this->name = $name;
            $this->build_type = BuildOutputType::NCC_PACKAGE->value;
            $this->output = $output;
            $this->options = [];
            $this->define_constants = [];
            $this->exclude_files = [];
            $this->pre_build = [];
            $this->post_build = [];
            $this->dependencies = [];
        }

        /**
         * Validates the BuildConfiguration object
         *
         * @return void
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function validate(): void
        {
            if(!Validate::nameFriendly($this->name))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if(!Validate::pathName($this->output))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if($this->define_constants !== null && !is_array($this->define_constants))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if($this->exclude_files !== null && !is_array($this->exclude_files))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if($this->pre_build !== null && !is_array($this->pre_build))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if($this->post_build !== null && !is_array($this->post_build))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            if($this->dependencies !== null && !is_array($this->dependencies))
            {
                throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
            }

            /** @var Dependency $dependency */
            foreach($this->dependencies as $dependency)
            {
                $dependency->validate();
            }
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function getBuildType(): string
        {
            return $this->build_type;
        }

        /**
         * @param string $build_type
         */
        public function setBuildType(string $build_type): void
        {
            $this->build_type = $build_type;
        }

        /**
         * @return array
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * @param array $options
         */
        public function setOptions(array $options): void
        {
            $this->options = $options;
        }

        /**
         * Sets an option for the build configuration
         *
         * @param string $name
         * @param mixed $value
         * @return void
         */
        public function setOption(string $name, mixed $value): void
        {
            $this->options[$name] = $value;
        }

        /**
         * @return string
         */
        public function getOutput(): string
        {
            return $this->output;
        }

        /**
         * @param string $output
         */
        public function setOutput(string $output): void
        {
            $this->output = $output;
        }

        /**
         * @return array|string[]
         */
        public function getDefineConstants(): array
        {
            return $this->define_constants;
        }

        /**
         * Sets a defined constant for the build configuration
         *
         * @param string $name
         * @param string $value
         */
        public function setDefinedConstant(string $name, string $value): void
        {
            $this->define_constants[$name] = $value;
        }

        /**
         * Removes a defined constant from the build configuration
         *
         * @param string $name
         * @return void
         */
        public function removeDefinedConstant(string $name): void
        {
            unset($this->define_constants[$name]);
        }

        /**
         * Returns excluded files
         *
         * @return array|string[]
         */
        public function getExcludeFiles(): array
        {
            return $this->exclude_files;
        }

        /**
         * Adds a file to the excluded files list
         *
         * @param string $file
         */
        public function addExcludedFile(string $file): void
        {
            $this->exclude_files[] = $file;
        }

        /**
         * Removes a file from the excluded files list
         *
         * @param string $file
         * @return void
         */
        public function removeExcludedFile(string $file): void
        {
            $this->exclude_files = array_filter($this->exclude_files, static function($item) use ($file)
            {
                return $item !== $file;
            });
        }

        /**
         * @return array|string[]
         */
        public function getPreBuild(): array
        {
            return $this->pre_build;
        }

        /**
         * @param string $pre_build
         */
        public function addPreBuild(string $pre_build): void
        {
            $this->pre_build[] = $pre_build;
        }

        /**
         * Removes a pre-build policy
         *
         * @param string $pre_build
         * @return void
         */
        public function removePreBuild(string $pre_build): void
        {
            $this->pre_build = array_filter($this->pre_build, static function($item) use ($pre_build)
            {
                return $item !== $pre_build;
            });
        }

        /**
         * @return array
         */
        public function getPostBuild(): array
        {
            return $this->post_build;
        }

        /**
         * Adds a post-build policy
         *
         * @param string $post_build
         */
        public function addPostBuild(string $post_build): void
        {
            $this->post_build[] = $post_build;
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
         */
        public function setDependencies(array $dependencies): void
        {
            $this->dependencies = $dependencies;
        }

        /**
         * Adds a dependency to the build configuration
         *
         * @param Dependency $dependency
         * @return void
         */
        public function addDependency(Dependency $dependency): void
        {
            $this->dependencies[] = $dependency;
        }

        /**
         * Removes a dependency from the build configuration
         *
         * @param Dependency $dependency
         * @return void
         */
        public function removeDependency(Dependency $dependency): void
        {
            $this->dependencies = array_filter($this->dependencies, static function($item) use ($dependency)
            {
                return $item !== $dependency;
            });
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;
            $results[($bytecode ? Functions::cbc('build_type') : 'build_type')] = $this->build_type;
            $results[($bytecode ? Functions::cbc('output') : 'output')] = $this->output;

            if(count($this->options) > 0)
            {
                $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;
            }

            if(count($this->define_constants) > 0)
            {
                $results[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->define_constants;
            }

            if(count($this->exclude_files) > 0)
            {
                $results[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->exclude_files;
            }

            if(count($this->pre_build) > 0)
            {
                $results[($bytecode ? Functions::cbc('pre_build') : 'pre_build')] = $this->pre_build;
            }

            if(count($this->dependencies) > 0)
            {
                $dependencies = array_map(static function(Dependency $Dependency) use ($bytecode)
                {
                    return $Dependency->toArray($bytecode);
                }, $this->dependencies);

                $results[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = $dependencies;
            }

            return $results;
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): BuildConfiguration
        {
            $name = Functions::array_bc($data, 'name');
            $output = Functions::array_bc($data, 'output');

            if($name === null)
            {
                throw new ConfigurationException('Build configuration "name" property is required');
            }

            if($output === null)
            {
                throw new ConfigurationException('Build configuration "output" property is required');
            }

            $object = new BuildConfiguration($name, $output);

            $object->build_type = Functions::array_bc($data, 'build_type') ?? BuildOutputType::NCC_PACKAGE->value;
            $object->options = Functions::array_bc($data, 'options') ?? [];
            $object->define_constants = Functions::array_bc($data, 'define_constants') ?? [];
            $object->exclude_files = Functions::array_bc($data, 'exclude_files') ?? [];
            $object->pre_build = Functions::array_bc($data, 'pre_build') ?? [];
            $object->post_build = Functions::array_bc($data, 'post_build') ?? [];

            $dependencies = Functions::array_bc($data, 'dependencies');
            if($dependencies !== null)
            {
                foreach($dependencies as $item)
                {
                    $object->dependencies[] = Dependency::fromArray($item);
                }
            }

            return $object;
        }
    }