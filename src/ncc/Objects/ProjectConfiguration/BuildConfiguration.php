<?php

    namespace ncc\Objects\ProjectConfiguration;

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
         * @var \ncc\Objects\ProjectConfiguration\Dependency[]
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

            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('name') : 'name')] = $this->Name;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('options') : 'options')] = $this->Options;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('static_linking') : 'static_linking')] = $this->StaticLinking;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('output_path') : 'output_path')] = $this->OutputPath;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('define_constants') : 'define_constants')] = $this->DefineConstants;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('strict_constants') : 'strict_constants')] = $this->StrictConstants;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('exclude_files') : 'exclude_files')] = $this->ExcludeFiles;
            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('dependencies') : 'dependencies')] = [];

            foreach($this->Dependencies as $dependency)
            {
                $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('dependencies') : 'dependencies')][] = $dependency->toArray();
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

            if(\ncc\Utilities\Functions::array_bc($data, 'name') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'name');
            }
            
            if(\ncc\Utilities\Functions::array_bc($data, 'options') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'options');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'static_linking') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'static_linking');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'output_path') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'output_path');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'define_constants') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'define_constants');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'strict_constants') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'strict_constants');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'exclude_files') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'exclude_files');
            }

            if(\ncc\Utilities\Functions::array_bc($data, 'dependencies') !== null)
            {
                $BuildConfigurationObject = \ncc\Utilities\Functions::array_bc($data, 'dependencies');
            }

            return $BuildConfigurationObject;
        }
    }