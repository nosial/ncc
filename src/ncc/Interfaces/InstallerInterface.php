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

    use Exception;
    use ncc\Exceptions\ComponentChecksumException;
    use ncc\Exceptions\ComponentDecodeException;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package;
    use ncc\Objects\Package\Component;

    interface InstallerInterface
    {
        /**
         * Public Constructor
         *
         * @param Package $package
         */
        public function __construct(Package $package);

        /**
         * Processes the component and optionally returns a string of the final component
         *
         * @param Component $component
         * @return string|null
         * @throws ComponentChecksumException
         * @throws ComponentDecodeException
         */
        public function processComponent(Package\Component $component): ?string;

        /**
         * Processes the resource and optionally returns a string of the final resource
         *
         * @param Package\Resource $resource
         * @return string|null
         * @throws
         */
        public function processResource(Package\Resource $resource): ?string;

        /**
         * Method called before the installation stage begins
         *
         * @param InstallationPaths $installationPaths
         * @throws Exception
         * @return void
         */
        public function preInstall(InstallationPaths $installationPaths): void;

        /**
         * Method called after the installation stage is completed and all the files have been installed
         *
         * @param InstallationPaths $installationPaths
         * @throws Exception
         * @return void
         */
        public function postInstall(InstallationPaths $installationPaths): void;
    }