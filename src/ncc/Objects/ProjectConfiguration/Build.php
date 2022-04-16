<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Build
    {
        /**
         * The source directory that the compiler will target to generate a build
         *
         * @var string
         */
        public $SourcePath;

        /**
         * The default configuration to use when building
         *
         * @var string
         */
        public $DefaultConfiguration;

        /**
         * An array of files to exclude from processing/bundling into the build output
         *
         * @var string[]
         */
        public $ExcludeFiles;

        /**
         * Build options to pass on to the compiler
         *
         * @var array
         */
        public $Options;

        /**
         * The installation scope for the package (System/User/Shared)
         *
         * @var [type]
         */
        public $Scope;

        /**
         * An array of constants to define by default
         *
         * @var string[]
         */
        public $DefineConstants;

        /**
         * An array of dependencies that are required by default
         *
         * @var Dependency[]
         */
        public $Dependencies;

        /**
         * An array of build configurations
         *
         * @var BuildConfiguration[]
         */
        public $Configurations;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->ExcludeFiles = [];
            $this->Options = [];
            $this->DefineConstants = [];
            $this->Dependencies = [];
            $this->Configurations = [];
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $ReturnResults = [];

            $ReturnResults[($bytecode ? Functions::cbc('source_path') : 'source_path')] = $this->SourcePath;
            $ReturnResults[($bytecode ? Functions::cbc('default_configuration') : 'default_configuration')] = $this->DefaultConfiguration;
            $ReturnResults[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->ExcludeFiles;
            $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;
            $ReturnResults[($bytecode ? Functions::cbc('scope') : 'scope')] = $this->Scope;
            $ReturnResults[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->DefineConstants;
            $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = [];

            foreach($this->Dependencies as $dependency)
            {
                $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')][] = $dependency->toArray($bytecode);
            }

            $ReturnResults[($bytecode ? Functions::cbc('configurations') : 'configurations')] = [];

            foreach($this->Configurations as $configuration)
            {
                $ReturnResults[($bytecode ? Functions::cbc('configurations') : 'configurations')][] = $configuration->toArray($bytecode);
            }

            return $ReturnResults;
        }

        /**
         * Returns an array
         *
         * @param array $data
         * @return Build
         */
        public static function fromArray(array $data): Build
        {
            $BuildObject = new Build();

            $BuildObject->SourcePath = Functions::array_bc($data, 'source_path');
            $BuildObject->DefaultConfiguration = Functions::array_bc($data, 'default_configuration');
            $BuildObject->ExcludeFiles = (Functions::array_bc($data, 'exclude_files') ?? []);
            $BuildObject->Options = (Functions::array_bc($data, 'options') ?? []);
            $BuildObject->Scope = Functions::array_bc($data, 'scope');
            $BuildObject->DefineConstants = (Functions::array_bc($data, 'define_constants') ?? []);

            if(Functions::array_bc($data, 'dependencies') !== null)
            {
                foreach(Functions::array_bc($data, 'dependencies') as $dependency)
                {
                    $BuildObject->Dependencies[] = Dependency::fromArray($dependency);
                }
            }

            if(Functions::array_bc($data, 'configurations') !== null)
            {
                foreach(Functions::array_bc($data, 'configurations') as $configuration)
                {
                    $BuildObject->Configurations[] = BuildConfiguration::fromArray($configuration);
                }
            }

            return $BuildObject;
        }
    }