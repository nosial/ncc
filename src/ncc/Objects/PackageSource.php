<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\Objects;

    use InvalidArgumentException;
    use ncc\Classes\Utilities;

    class PackageSource
    {
        private string $organization;
        private string $name;
        private string $version;
        private string $repository;

        /**
         * PackageSource constructor.
         *
         * @param string $sourceString The package string in the format "organization/name=version@repository".
         * @throws InvalidArgumentException If the package string is invalid.
         */
        public function __construct(string $sourceString)
        {
            $parsedPackage = Utilities::parsePackageSource($sourceString);
            if($parsedPackage === null)
            {
                throw new InvalidArgumentException("Invalid package string");
            }

            $this->organization = $parsedPackage['organization'];
            $this->name = $parsedPackage['package_name'];
            $this->version = $parsedPackage['version'];
            $this->repository = $parsedPackage['repository'];
        }

        /**
         * Get the organization of the package.
         *
         * @return string The organization of the package.
         */
        public function getOrganization(): string
        {
            return $this->organization;
        }

        /**
         * Get the name of the package.
         *
         * @return string The name of the package.
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Get the version of the package.
         *
         * @return string The version of the package.
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * Get the repository name.
         *
         * @return string The repository name.
         */
        public function getRepository(): string
        {
            return $this->repository;
        }

        /**
         * Convert the PackageSource object back to its string representation.
         *
         * @return string The package string in the format "organization/name=version@repository".
         */
        public function __toString(): string
        {
            if($this->version === 'latest' || empty($this->version))
            {
                // `latest` is redundant, so omit it
                return "{$this->organization}/{$this->name}@{$this->repository}";
            }

            return "{$this->organization}/{$this->name}={$this->version}@{$this->repository}";
        }
    }