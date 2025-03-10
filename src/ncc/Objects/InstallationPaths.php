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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    class InstallationPaths
    {
        /**
         * The path of where the package will be installed at
         *
         * @var string
         */
        private $installation_path;

        /**
         * @param string $installation_path
         */
        public function __construct(string $installation_path)
        {
            $this->installation_path = $installation_path;
        }

        /**
         * Returns the data path where NCC's metadata & runtime information is stored
         *
         * @return string
         */
        public function getDataPath(): string
        {
            return $this->installation_path . DIRECTORY_SEPARATOR . 'ncc';
        }

        /**
         * Returns the source path for where the package resides
         *
         * @return string
         */
        public function getSourcePath(): string
        {
            return $this->installation_path . DIRECTORY_SEPARATOR . 'src';
        }

        /**
         * Returns the path for where executables are located
         *
         * @return string
         */
        public function getBinPath(): string
        {
            return $this->installation_path . DIRECTORY_SEPARATOR . 'bin';
        }

        /**
         * @return string
         */
        public function getInstallationpath(): string
        {
            return $this->installation_path;
        }
    }