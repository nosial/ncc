<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Utilities\Functions;

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

        // TODO: Add a function to validate the object data

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