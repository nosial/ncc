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

    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class BuildConfiguration implements BytecodeObjectInterface
    {
        /**
         * The unique name of the build configuration
         *
         * @var string
         */
        public $name;

        /**
         * Options to pass onto the extension compiler
         *
         * @var array
         */
        public $options;

        /**
         * The build output path for the build configuration, eg; build/%BUILD.NAME%
         *
         * @var string
         */
        public $output_path;

        /**
         * An array of constants to define for the build when importing or executing.
         *
         * @var string[]
         */
        public $define_constants;

        /**
         * An array of files to exclude in this build configuration
         *
         * @var string[]
         */
        public $exclude_files;

        /**
         * An array of policies to execute pre-building the package
         *
         * @var string[]|string
         */
        public $pre_build;

        /**
         * An array of policies to execute post-building the package
         *
         * @var string
         */
        public $post_build;

        /**
         * Dependencies required for the build configuration, cannot conflict with the
         * default dependencies
         *
         * @var Dependency[]
         */
        public $dependencies;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->options = [];
            $this->output_path = 'build';
            $this->define_constants = [];
            $this->exclude_files = [];
            $this->pre_build = [];
            $this->post_build = [];
            $this->dependencies = [];
        }

        /**
         * Validates the BuildConfiguration object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!Validate::nameFriendly($this->name))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if(!Validate::pathName($this->output_path))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if($this->define_constants !== null && !is_array($this->define_constants))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if($this->exclude_files !== null && !is_array($this->exclude_files))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if($this->pre_build !== null && !is_array($this->pre_build))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if($this->post_build !== null && !is_array($this->post_build))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            if($this->dependencies !== null && !is_array($this->dependencies))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid build configuration name "%s"', $this->name));
                }

                return False;
            }

            /** @var Dependency $dependency */
            foreach($this->dependencies as $dependency)
            {
                try
                {
                    if (!$dependency->validate($throw_exception))
                    {
                        return False;
                    }
                }
                catch (ConfigurationException $e)
                {
                    if($throw_exception)
                    {
                        throw $e;
                    }

                    return False;
                }
            }

            return True;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->name !== null && $this->name !== '')
            {
                $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;
            }

            if($this->options !== null && count($this->options) > 0)
            {
                $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;
            }

            if($this->output_path !== null && $this->output_path !== '')
            {
                $results[($bytecode ? Functions::cbc('output_path') : 'output_path')] = $this->output_path;
            }

            if($this->define_constants !== null && count($this->define_constants) > 0)
            {
                $results[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->define_constants;
            }

            if($this->exclude_files !== null && count($this->exclude_files) > 0)
            {
                $results[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->exclude_files;
            }

            if($this->pre_build !== null && count($this->pre_build) > 0)
            {
                $results[($bytecode ? Functions::cbc('pre_build') : 'pre_build')] = $this->pre_build;
            }

            if($this->dependencies !== null && count($this->dependencies) > 0)
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
         */
        public static function fromArray(array $data): BuildConfiguration
        {
            $object = new BuildConfiguration();

            $object->name = Functions::array_bc($data, 'name');
            $object->options = Functions::array_bc($data, 'options');
            $object->output_path = Functions::array_bc($data, 'output_path');
            $object->define_constants = Functions::array_bc($data, 'define_constants');
            $object->exclude_files = Functions::array_bc($data, 'exclude_files');
            $object->pre_build = Functions::array_bc($data, 'pre_build');
            $object->post_build = Functions::array_bc($data, 'post_build');

            if(Functions::array_bc($data, 'dependencies') !== null)
            {
                foreach(Functions::array_bc($data, 'dependencies') as $item)
                {
                    $object->dependencies[] = Dependency::fromArray($item);
                }
            }

            return $object;
        }
    }