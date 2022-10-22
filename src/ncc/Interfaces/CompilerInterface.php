<?php

    namespace ncc\Interfaces;

    use ncc\Abstracts\Options\BuildConfigurationValues;

    interface CompilerInterface
    {
        /**
         * Prepares the package for the build process, this method is called before build()
         *
         * @param array $options Options to pass on to the compiler
         * @param string $path The path that the project file is located in (project.json)
         * @param string $build_configuration The build configuration to use to build the project
         * @return void
         */
        public function prepare(array $options, string $path, string $build_configuration=BuildConfigurationValues::DefaultConfiguration): void;

        /**
         * Builds the package, returns the output path of the build
         *
         * @param array $options Options to pass on to the compiler
         * @param string $path The path that the project file is located in (project.json)
         * @return string Returns the output path of the build
         */
        public function build(array $options, string $path): string;
    }