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

    use ncc\Exceptions\InvalidBuildConfigurationException;
    use ncc\Exceptions\InvalidDependencyConfiguration;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class BuildConfiguration
    {
        /**
         * The unique name of the build configuration
         *
         * @var string
         */
        public $Name;

        /**
         * Options to pass onto the extension compiler
         *
         * @var array
         */
        public $Options;

        /**
         * The build output path for the build configuration, eg; build/%BUILD.NAME%
         *
         * @var string
         */
        public $OutputPath;

        /**
         * An array of constants to define for the build when importing or executing.
         *
         * @var string[]
         */
        public $DefineConstants;

        /**
         * An array of files to exclude in this build configuration
         *
         * @var string[]
         */
        public $ExcludeFiles;

        /**
         * An array of policies to execute pre-building the package
         *
         * @var string[]|string
         */
        public $PreBuild;

        /**
         * An array of policies to execute post-building the package
         *
         * @var string
         */
        public $PostBuild;

        /**
         * Dependencies required for the build configuration, cannot conflict with the
         * default dependencies
         *
         * @var Dependency[]
         */
        public $Dependencies;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Options = [];
            $this->OutputPath = 'build';
            $this->DefineConstants = [];
            $this->ExcludeFiles = [];
            $this->PreBuild = [];
            $this->PostBuild = [];
            $this->Dependencies = [];
        }

        /**
         * Validates the BuildConfiguration object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidBuildConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!Validate::nameFriendly($this->Name))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('Invalid build configuration name "%s"', $this->Name));

                return False;
            }

            if(!Validate::pathName($this->OutputPath))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'output_path\' contains an invalid path name in %s', $this->Name));

                return False;
            }

            if($this->DefineConstants !== null && !is_array($this->DefineConstants))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'define_constants\' must be an array in %s', $this->Name));

                return False;
            }

            if($this->ExcludeFiles !== null && !is_array($this->ExcludeFiles))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'exclude_files\' must be an array in %s', $this->Name));

                return False;
            }

            if($this->PreBuild !== null && !is_array($this->PreBuild))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'pre_build\' must be an array in %s', $this->Name));

                return False;
            }

            if($this->PostBuild !== null && !is_array($this->PostBuild))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'post_build\' must be an array in %s', $this->Name));

                return False;
            }

            if($this->Dependencies !== null && !is_array($this->Dependencies))
            {
                if($throw_exception)
                    throw new InvalidBuildConfigurationException(sprintf('\'dependencies\' must be an array in %s', $this->Name));

                return False;
            }

            /** @var Dependency $dependency */
            foreach($this->Dependencies as $dependency)
            {
                try
                {
                    if (!$dependency->validate($throw_exception))
                        return False;
                }
                catch (InvalidDependencyConfiguration $e)
                {
                    if($throw_exception)
                        throw new InvalidBuildConfigurationException(sprintf('Invalid dependency configuration in %s: %s', $this->Name, $e->getMessage()));

                    return False;
                }
            }

            return True;
        }

        /**
         * Returns an array representation of the object
         *
         * @param boolean $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $ReturnResults = [];

            if($this->Name !== null && strlen($this->Name) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('name') : 'name')] = $this->Name;
            if($this->Options !== null && count($this->Options) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;
            if($this->OutputPath !== null && strlen($this->OutputPath) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('output_path') : 'output_path')] = $this->OutputPath;
            if($this->DefineConstants !== null && count($this->DefineConstants) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->DefineConstants;
            if($this->ExcludeFiles !== null && count($this->ExcludeFiles) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->ExcludeFiles;
            if($this->PreBuild !== null && count($this->PreBuild) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('pre_build') : 'pre_build')] = $this->PreBuild;
            if($this->Dependencies !== null && count($this->Dependencies) > 0)
            {
                $Dependencies = [];
                foreach($this->Dependencies as $Dependency)
                {
                    $Dependencies[] = $Dependency->toArray($bytecode);
                }
                $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = $Dependencies;
            }

            return $ReturnResults;
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return BuildConfiguration
         */
        public static function fromArray(array $data): BuildConfiguration
        {
            $BuildConfigurationObject = new BuildConfiguration();

            $BuildConfigurationObject->Name = Functions::array_bc($data, 'name');
            $BuildConfigurationObject->Options = Functions::array_bc($data, 'options');
            $BuildConfigurationObject->OutputPath = Functions::array_bc($data, 'output_path');
            $BuildConfigurationObject->DefineConstants = Functions::array_bc($data, 'define_constants');
            $BuildConfigurationObject->ExcludeFiles = Functions::array_bc($data, 'exclude_files');
            $BuildConfigurationObject->PreBuild = Functions::array_bc($data, 'pre_build');
            $BuildConfigurationObject->PostBuild = Functions::array_bc($data, 'post_build');

            if(Functions::array_bc($data, 'dependencies') !== null)
            {
                foreach(Functions::array_bc($data, 'dependencies') as $item)
                    $BuildConfigurationObject->Dependencies[] = Dependency::fromArray($item);
            }

            return $BuildConfigurationObject;
        }
    }