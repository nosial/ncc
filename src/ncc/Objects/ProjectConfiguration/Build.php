<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\InvalidConstantNameException;
    use ncc\Exceptions\InvalidProjectBuildConfiguration;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

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
         * The execution policy to use as the main execution point
         *
         * @var string|null
         */
        public $Main;

        /**
         * An array of constants to define by default
         *
         * @var string[]
         */
        public $DefineConstants;

        /**
         * An array of execution policies to execute pre build
         *
         * @var string[]
         */
        public $PreBuild;

        /**
         * An array of execution policies to execute post build
         *
         * @var string[]
         */
        public $PostBuild;

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
         * Validates the build configuration object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidConstantNameException
         * @throws InvalidProjectBuildConfiguration
         */
        public function validate(bool $throw_exception=True): bool
        {
            // TODO: Implement validation for Configurations, Dependencies and ExcludedFiles

            // Check the defined constants
            foreach($this->DefineConstants as $name => $value)
            {
                if(!Validate::constantName($name))
                {
                    throw new InvalidConstantNameException('The name \'' . $name . '\' is not valid for a constant declaration, ');
                }
            }

            // Check for duplicate configuration names
            $build_configurations = [];
            foreach($this->Configurations as $configuration)
            {
                if(in_array($configuration->Name, $build_configurations))
                {
                    if($throw_exception)
                        throw new InvalidProjectBuildConfiguration('The build configuration \'' . $configuration->Name . '\' is already defined, build configuration names must be unique');

                    return false;
                }
            }

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

            foreach($this->Configurations as $configuration)
            {
                $build_configurations[] = $configuration->Name;
            }

            return $build_configurations;
        }

        /**
         * Returns the build configurations defined in the project configuration, throw an
         * exception if there is no such configuration defined in the project configuration
         *
         * @param string $name
         * @return BuildConfiguration
         * @throws BuildConfigurationNotFoundException
         */
        public function getBuildConfiguration(string $name): BuildConfiguration
        {
            if($name == BuildConfigurationValues::DefaultConfiguration)
                $name = $this->DefaultConfiguration;

            foreach($this->Configurations as $configuration)
            {
                if($configuration->Name == $name)
                {
                    return $configuration;
                }
            }

            throw new BuildConfigurationNotFoundException('The build configuration ' . $name . ' does not exist');
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

            if($this->SourcePath !== null)
                $ReturnResults[($bytecode ? Functions::cbc('source_path') : 'source_path')] = $this->SourcePath;
            if($this->DefaultConfiguration !== null)
                $ReturnResults[($bytecode ? Functions::cbc('default_configuration') : 'default_configuration')] = $this->DefaultConfiguration;
            if($this->ExcludeFiles !== null && count($this->ExcludeFiles) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('exclude_files') : 'exclude_files')] = $this->ExcludeFiles;
           if($this->Options !== null && count($this->Options) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;
           if($this->Scope !== null)
                $ReturnResults[($bytecode ? Functions::cbc('scope') : 'scope')] = $this->Scope;
           if($this->Main !== null)
                $ReturnResults[($bytecode ? Functions::cbc('main') : 'main')] = $this->Main;
           if($this->DefineConstants !== null && count($this->DefineConstants) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('define_constants') : 'define_constants')] = $this->DefineConstants;
           if($this->PreBuild !== null && count($this->PreBuild) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('pre_build') : 'pre_build')] = $this->PreBuild;
           if($this->PostBuild !== null && count($this->PostBuild) > 0)
                $ReturnResults[($bytecode ? Functions::cbc('post_build') : 'post_build')] = $this->PostBuild;
           if($this->Dependencies !== null && count($this->Dependencies) > 0)
           {
                $dependencies = [];
                foreach($this->Dependencies as $dependency)
                {
                     $dependencies[] = $dependency->toArray($bytecode);
                }
                $ReturnResults[($bytecode ? Functions::cbc('dependencies') : 'dependencies')] = $dependencies;
           }
           if($this->Configurations !== null && count($this->Configurations) > 0)
           {
                $configurations = [];
                foreach($this->Configurations as $configuration)
                {
                     $configurations[] = $configuration->toArray($bytecode);
                }
                $ReturnResults[($bytecode ? Functions::cbc('configurations') : 'configurations')] = $configurations;
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
            $BuildObject->Main = Functions::array_bc($data, 'main');
            $BuildObject->DefineConstants = (Functions::array_bc($data, 'define_constants') ?? []);
            $BuildObject->PreBuild = (Functions::array_bc($data, 'pre_build') ?? []);
            $BuildObject->PostBuild = (Functions::array_bc($data, 'post_build') ?? []);

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