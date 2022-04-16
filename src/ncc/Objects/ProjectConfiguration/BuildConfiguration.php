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
         * Indicates if the libraries and resources for the build configuration are statically linked
         *
         * @var bool
         */
        public $StaticLinking;

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
         * Indicates if one or more constants cannot be defined, it should result in a runtime error.
         *
         * @var bool
         */
        public $StrictConstants;

        /**
         * An array of files to exclude in this build configuration
         *
         * @var string[]
         */
        public $ExcludeFiles;

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
            $this->StaticLinking = false;
            $this->OutputPath = 'build';
            $this->DefineConstants = [];
            $this->StrictConstants = false;
            $this->ExcludeFiles = [];
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

            $ReturnResults[($bytecode ? Functions::cbc('name') : 'name')] = $this->Name;
            $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;
            $ReturnResults[($bytecode ? Functions::cbc('static_linking') : 'static_linking')] = $this->StaticLinking;
            $ReturnResults[($bytecode ? Functions::cbc('output_path') : 'output_path')] = $this->OutputPath;
            $ReturnResults[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->DefineConstants;
            $ReturnResults[($bytecode ? Functions::cbc('strict_constants') : 'strict_constants')] = $this->StrictConstants;
            $ReturnResults[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->ExcludeFiles;
            $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = [];

            foreach($this->Dependencies as $dependency)
            {
                $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')][] = $dependency->toArray();
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

            if(Functions::array_bc($data, 'name') !== null)
            {
                $BuildConfigurationObject->Name = Functions::array_bc($data, 'name');
            }
            
            if(Functions::array_bc($data, 'options') !== null)
            {
                $BuildConfigurationObject->Options = Functions::array_bc($data, 'options');
            }

            if(Functions::array_bc($data, 'static_linking') !== null)
            {
                $BuildConfigurationObject->StaticLinking = Functions::array_bc($data, 'static_linking');
            }

            if(Functions::array_bc($data, 'output_path') !== null)
            {
                $BuildConfigurationObject->OutputPath = Functions::array_bc($data, 'output_path');
            }

            if(Functions::array_bc($data, 'define_constants') !== null)
            {
                $BuildConfigurationObject->DefineConstants = Functions::array_bc($data, 'define_constants');
            }

            if(Functions::array_bc($data, 'strict_constants') !== null)
            {
                $BuildConfigurationObject->StrictConstants = Functions::array_bc($data, 'strict_constants');
            }

            if(Functions::array_bc($data, 'exclude_files') !== null)
            {
                $BuildConfigurationObject->ExcludeFiles = Functions::array_bc($data, 'exclude_files');
            }

            if(Functions::array_bc($data, 'dependencies') !== null)
            {
                foreach(Functions::array_bc($data, 'dependencies') as $item)
                {
                    $BuildConfigurationObject->Dependencies[] = Dependency::fromArray($item);
                }
            }

            return $BuildConfigurationObject;
        }
    }