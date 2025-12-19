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
    use ncc\Interfaces\SerializableInterface;
    use ncc\Libraries\semver\VersionParser;

    class PackageSource implements SerializableInterface
    {
        private string $organization;
        private string $name;
        private ?string $version;
        private ?string $repository;

        /**
         * PackageSource constructor.
         *
         * @param string|null $sourceString The package string in the format "organization/name=version@repository".
         */
        public function __construct(?string $sourceString=null)
        {
            if(is_null($sourceString))
            {
                $this->organization = 'organization';
                $this->name = 'name';
                $this->version = 'latest';
                $this->repository = 'repository';
                return;
            }

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
         * Sets the organization name of the package
         *
         * @param string $organization The organization name of the package to set
         * @throws InvalidArgumentException thrown if the organization name is empty
         */
        public function setOrganization(string $organization): void
        {
            if(strlen($organization) === 0)
            {
                throw new InvalidArgumentException('The organization name cannot be empty otherwise an invalid package source may be formed');
            }

            $this->organization = $organization;
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

        public function setName(string $name): void
        {
            if(strlen($name) === 0)
            {
                throw new InvalidArgumentException('The name of the package source cannot be empty');
            }

            $this->name = $name;
        }

        /**
         * Get the version of the package.
         *
         * @return string|null The version of the package, or null if not set.
         */
        public function getVersion(): ?string
        {
            return $this->version;
        }

        /**
         * Sets the version of the package source
         *
         * @param string|null $version The version of the package source to set, or null to unset
         * @throws InvalidArgumentException thrown if the version is empty or invalid
         */
        public function setVersion(?string $version): void
        {
            if($version !== null && strlen($version) === 0)
            {
                throw new InvalidArgumentException('The package version cannot be empty, it must be a valid SemVer version, "latest", or null');
            }

            if($version !== null && strtolower($version) === 'latest')
            {
                $this->version = 'latest';
                return;
            }

            // Validate the version to be a valid SemVer structure
            if($version !== null)
            {
                if(!(new VersionParser())->isValid($version))
                {
                    throw new InvalidArgumentException(sprintf('The package version "%s" is not a valid SemVer version', $version));
                }
            }

            $this->version = $version;
        }

        /**
         * Get the repository name.
         *
         * @return string|null The repository name, or null if not set.
         */
        public function getRepository(): ?string
        {
            return $this->repository;
        }

        /**
         * Sets the repository of the package source
         *
         * @param string|null $repository The repository to set to the package source, or null to unset
         * @return void
         */
        public function setRepository(?string $repository): void
        {
            if($repository !== null && strlen($repository) === 0)
            {
                throw new InvalidArgumentException('The repository name cannot be empty');
            }

            $this->repository = $repository;
        }

        /**
         * Convert the PackageSource object to an associative array.
         *
         * @return array The associative array containing package source data.
         */
        public function toArray(): array
        {
            return [
                'organization' => $this->organization,
                'name' => $this->name,
                'version' => $this->version,
                'repository' => $this->repository,
            ];
        }

        /**
         * Create a PackageSource object from an associative array.
         *
         * @param array $data The associative array containing package source data.
         * @return PackageSource The created PackageSource object.
         */
        public static function fromArray(array $data): PackageSource
        {
            $packageSource = new PackageSource("dummy/dummy");
            $packageSource->setOrganization($data['organization']);
            $packageSource->setName($data['name']);
            $packageSource->setVersion($data['version'] ?? null);
            $packageSource->setRepository($data['repository'] ?? null);

            return $packageSource;
        }


        /**
         * Convert the PackageSource object back to its string representation.
         *
         * @return string The package string in various formats: "organization/name=version@repository", "organization/name@repository", "organization/name=version", or "organization/name".
         */
        public function __toString(): string
        {
            $result = "{$this->organization}/{$this->name}";

            // Add version if not 'latest', not null, and not empty
            if($this->version !== null && $this->version !== 'latest' && $this->version !== '')
            {
                $result .= "={$this->version}";
            }

            // Add repository if set
            if($this->repository !== null)
            {
                $result .= "@{$this->repository}";
            }

            return $result;
        }
    }