<?php

    namespace ncc\Interfaces;

    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;

    interface CompilerInterface
    {
        /**
         * Public constructor
         *
         * @param ProjectConfiguration $project
         * @param string $path
         */
        public function __construct(ProjectConfiguration $project, string $path);

        /**
         * Prepares the package for the build process, this method is called before build()
         *
         * @param string $build_configuration The build configuration to use to build the project
         * @return void
         */
        public function prepare(string $build_configuration=BuildConfigurationValues::DefaultConfiguration): void;

        /**
         * Executes the compile process in the correct order and returns the finalized Package object
         *
         * @return Package|null
         * @throws AccessDeniedException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws UnsupportedRunnerException
         */
        public function build(): ?Package;

        /**
         * Compiles the components of the package
         *
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function compileComponents(): void;

        /**
         * Compiles the resources of the package
         *
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function compileResources(): void;

        /**
         * Compiles the execution policies of the package
         *
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws UnsupportedRunnerException
         */
        public function compileExecutionPolicies(): void;

        /**
         * Returns the current state of the package
         *
         * @return Package|null
         */
        public function getPackage(): ?Package;
    }