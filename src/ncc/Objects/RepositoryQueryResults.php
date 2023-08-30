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

    use ncc\Objects\RepositoryQueryResults\Files;

    class RepositoryQueryResults
    {
        /**
         * A collection of files that are available for download
         *
         * @var Files
         */
        private $files;

        /**
         * The version of the package returned by the query
         *
         * @var string|null
         */
        private $version;

        /**
         * The name of the release returned by the query
         *
         * @var string|null
         */
        private $release_name;

        /**
         * The description of the release returned by the query
         *
         * @var string|null
         */
        private $release_description;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->files = new Files();
        }

        /**
         * @return Files
         */
        public function getFiles(): Files
        {
            return $this->files;
        }

        /**
         * @param Files $files
         */
        public function setFiles(Files $files): void
        {
            $this->files = $files;
        }

        /**
         * @return string|null
         */
        public function getVersion(): ?string
        {
            return $this->version;
        }

        /**
         * @param string|null $version
         */
        public function setVersion(?string $version): void
        {
            $this->version = $version;
        }

        /**
         * @return string|null
         */
        public function getReleaseName(): ?string
        {
            return $this->release_name;
        }

        /**
         * @param string|null $release_name
         */
        public function setReleaseName(?string $release_name): void
        {
            $this->release_name = $release_name;
        }

        /**
         * @return string|null
         */
        public function getReleaseDescription(): ?string
        {
            return $this->release_description;
        }

        /**
         * @param string|null $release_description
         */
        public function setReleaseDescription(?string $release_description): void
        {
            $this->release_description = $release_description;
        }

    }