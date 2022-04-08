<?php

    namespace ncc\Objects\ProjectConfiguration;

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
         * @var \ncc\Objects\ProjectConfiguration\Dependency[]
         */
        public $Dependencies;

        /**
         * An array of build configurations
         *
         * @var \ncc\Objects\BuildConfiguration[]
         */
        public $Configurations;
    }