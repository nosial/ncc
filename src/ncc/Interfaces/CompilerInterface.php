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

namespace ncc\Interfaces;

    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
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
        public function prepare(string $build_configuration=BuildConfigurationValues::DEFAULT): void;

        /**
         * Executes the compile process in the correct order and returns the finalized Package object
         *
         * @return Package|null
         * @throws AccessDeniedException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
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
         */
        public function compileExecutionPolicies(): void;

        /**
         * Returns the current state of the package
         *
         * @return Package|null
         */
        public function getPackage(): ?Package;
    }