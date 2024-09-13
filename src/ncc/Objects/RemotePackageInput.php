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

    use InvalidArgumentException;
    use ncc\Enums\RegexPatterns;
    use ncc\Enums\Versions;

    class RemotePackageInput
    {

        /**
         * @var string
         */
        private $package;

        /**
         * @var string
         */
        private $vendor;

        /**
         * @var string
         */
        private $version;

        /**
         * @var string|null
         */
        private $branch;

        /**
         * @var string|null
         */
        private $repository;

        /**
         * Public Constructor & String Parser
         *
         * @param string $package The package name (eg; "ncc")
         * @param string $vendor The vendor name (eg; "Nosial")
         */
        public function __construct(string $package, string $vendor)
        {
            $this->package = $package;
            $this->vendor = $vendor;
            $this->version = Versions::LATEST->value;
        }

        /**
         * Returns the package name to use for the package
         *
         * @return string
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * Sets the package name to use for the package
         *
         * @param string $package
         */
        public function setPackage(string $package): void
        {
            $this->package = $package;
        }

        /**
         * Returns the vendor to use for the package
         *
         * @return string
         */
        public function getVendor(): string
        {
            return $this->vendor;
        }

        /**
         * Sets the vendor to use it for the package
         *
         * @param string $vendor
         */
        public function setVendor(string $vendor): void
        {
            $this->vendor = $vendor;
        }

        /**
         * Returns the version to use for the package
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * Sets the version to use for the package, if null, it will use the latest version
         *
         * @param string|null $version
         */
        public function setVersion(?string $version): void
        {
            $this->version = $version ?? Versions::LATEST->value;
        }

        /**
         * Returns the branch to use for the package
         *
         * @return string|null
         */
        public function getBranch(): ?string
        {
            return $this->branch;
        }

        /**
         * Sets the branch to use it for the package
         *
         * @param string|null $branch
         */
        public function setBranch(?string $branch): void
        {
            $this->branch = $branch;
        }

        /**
         * Optional. Returns the repository to use for the package
         *
         * @return string|null
         */
        public function getRepository(): ?string
        {
            return $this->repository;
        }

        /**
         * Sets the repository to use it for the package
         *
         * @param string|null $repository
         */
        public function setRepository(?string $repository): void
        {
            $this->repository = $repository;
        }

        /**
         * Returns a standard package name string representation
         *
         * @param bool $version
         * @return string
         */
        public function toStandard(bool $version=true): string
        {
            if($version)
            {
                return str_replace('-', '_', sprintf('com.%s.%s=%s', $this->vendor, $this->package, $this->version));
            }

            return str_replace('-', '_', sprintf('com.%s.%s', $this->vendor, $this->package));
        }

        /**
         * Returns a string representation of the input
         *
         * @return string
         */
        public function toString()
        {
            return $this->__toString();
        }

        /**
         * Returns a string representation of the input
         *
         * @return string
         */
        public function __toString(): string
        {
            $results = $this->vendor . '/' . $this->package;

            if($this->version !== null)
            {
                $results .= '=' . $this->version;
            }

            if($this->branch !== null)
            {
                $results .= ':' . $this->branch;
            }

            if($this->repository !== null)
            {
                $results .= '@' . $this->repository;
            }

            return $results;
        }

        /**
         * Parses the input string and returns a RemotePackageInput object
         *
         * @param string $input
         * @return RemotePackageInput
         */
        public static function fromString(string $input): RemotePackageInput
        {
            if (preg_match(RegexPatterns::REMOTE_PACKAGE, $input, $matches))
            {
                if ($matches['package'] === null || $matches['vendor'] === null)
                {
                    throw new InvalidArgumentException('package and vendor are required');
                }

                $object = new RemotePackageInput($matches['package'], $matches['vendor']);
                $object->version = empty($matches['version']) ? Versions::LATEST->value : $matches['version'];
                $object->branch = empty($matches['branch']) ? null : $matches['branch'];
                $object->repository = empty($matches['source']) ? null : $matches['source'];

                return $object;
            }

            throw new InvalidArgumentException(sprintf('Invalid remote package input: %s', $input));
        }
    }