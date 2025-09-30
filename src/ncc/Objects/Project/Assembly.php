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

    namespace ncc\Objects\Project;

    use InvalidArgumentException;
    use ncc\Classes\Validate;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;

    class Assembly implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private string $package;
        private string $version;
        private ?string $url;
        private ?string $license;
        private ?string $description;
        private ?string $author;
        private ?string $organization;
        private ?string $product;
        private ?string $copyright;
        private ?string $trademark;

        /**
         * Assembly constructor.
         *
         * @param array $data Associative array with keys: name, package, version, description, author, organization,
         *                    product, copyright, trademark
         */
        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? 'Project';
            $this->package = $data['package'] ?? 'com.example.project';
            $this->version = $data['version'] ?? '0.0.0';
            $this->url = $data['url'] ?? null;
            $this->license = $data['license'] ?? null;
            $this->description = $data['description'] ?? null;
            $this->author = $data['author'] ?? null;
            $this->organization = $data['organization'] ?? null;
            $this->product = $data['product'] ?? null;
            $this->copyright = $data['copyright'] ?? null;
            $this->trademark = $data['trademark'] ?? null;
        }

        /**
         * Returns the name of the project
         *
         * @return string The project name
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the name of the project
         *
         * @param string $name The project name to set
         * @throws InvalidArgumentException If the project name is empty
         */
        public function setName(string $name): void
        {
            if(empty($name))
            {
                throw new InvalidArgumentException('The project name cannot be empty');
            }

            $this->name = $name;
        }

        /**
         * Returns the package name
         *
         * @return string The name of the package
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * Sets the package name
         *
         * @param string $packageName The package name to set
         * @throws InvalidArgumentException If the package name is not valid
         */
        public function setPackage(string $packageName): void
        {
            if(!Validate::packageName($packageName))
            {
                throw new InvalidArgumentException('The package name is not valid');
            }

            $this->package = $packageName;
        }

        /**
         * Returns the version of the project
         *
         * @return string The project version
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * Sets the version of the project
         *
         * @param string $version The project version to set
         * @throws InvalidArgumentException If the version is not valid
         */
        public function setVersion(string $version): void
        {
            if(!Validate::version($version))
            {
                throw new InvalidArgumentException('The version is not valid');
            }

            $this->version = $version;
        }

        /**
         * Returns the URL of the project
         *
         * @return string|null The project URL or null if not set
         */
        public function getUrl(): ?string
        {
            return $this->url;
        }

        /**
         * Sets the URL of the project
         *
         * @param string|null $url The project URL to set or null to unset
         */
        public function setUrl(?string $url): void
        {
            if($url !== null && trim($url) === '')
            {
                $url = null;
            }

            $this->url = $url;
        }

        /**
         * Returns the license of the project
         *
         * @return string|null The project license or null if not set
         */
        public function getLicense(): ?string
        {
            return $this->license;
        }

        /**
         * Sets the license of the project
         *
         * @param string|null $license The project license to set or null to unset
         */
        public function setLicense(?string $license): void
        {
            if($license !== null && trim($license) === '')
            {
                $license = null;
            }

            $this->license = $license;
        }

        /**
         * Returns the description of the project
         *
         * @return string|null The project description or null if not set
         */
        public function getDescription(): ?string
        {
            return $this->description;
        }

        /**
         * Sets the description of the project
         *
         * @param string|null $description The project description to set or null to unset
         */
        public function setDescription(?string $description): void
        {
            if($description !== null && trim($description) === '')
            {
                $description = null;
            }

            $this->description = $description;
        }

        /**
         * Returns the author of the project
         *
         * @return string|null The project author or null if not set
         */
        public function getAuthor(): ?string
        {
            return $this->author;
        }

        /**
         * Sets the author of the project
         *
         * @param string|null $author The project author to set or null to unset
         */
        public function setAuthor(?string $author): void
        {
            if($author !== null && trim($author) === '')
            {
                $author = null;
            }

            $this->author = $author;
        }

        /**
         * Returns the organization associated with the project
         *
         * @return string|null The organization name or null if not set
         */
        public function getOrganization(): ?string
        {
            return $this->organization;
        }

        /**
         * Sets the organization associated with the project
         *
         * @param string|null $organization The organization name to set or null to unset
         */
        public function setOrganization(?string $organization): void
        {
            if($organization !== null && trim($organization) === '')
            {
                $organization = null;
            }

            $this->organization = $organization;
        }

        /**
         * Returns the product name associated with the project
         *
         * @return string|null The product name or null if not set
         */
        public function getProduct(): ?string
        {
            return $this->product;
        }

        /**
         * Sets the product name associated with the project
         *
         * @param string|null $product The product name to set or null to unset
         */
        public function setProduct(?string $product): void
        {
            if($product !== null && trim($product) === '')
            {
                $product = null;
            }

            $this->product = $product;
        }

        /**
         * Returns the copyright information of the project
         *
         * @return string|null The copyright information or null if not set
         */
        public function getCopyright(): ?string
        {
            return $this->copyright;
        }

        /**
         * Sets the copyright information of the project
         *
         * @param string|null $copyright The copyright information to set or null to unset
         */
        public function setCopyright(?string $copyright): void
        {
            if($copyright !== null && trim($copyright) === '')
            {
                $copyright = null;
            }

            $this->copyright = $copyright;
        }

        /**
         * Returns the trademark information of the project
         *
         * @return string|null The trademark information or null if not set
         */
        public function getTrademark(): ?string
        {
            return $this->trademark;
        }

        /**
         * Sets the trademark information of the project
         *
         * @param string|null $trademark The trademark information to set or null to unset
         */
        public function setTrademark(?string $trademark): void
        {
            if($trademark !== null && trim($trademark) === '')
            {
                $trademark = null;
            }

            $this->trademark = $trademark;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'package' => $this->package,
                'version' => $this->version,
                'url' => $this->url,
                'license' => $this->license,
                'description' => $this->description,
                'author' => $this->author,
                'organization' => $this->organization,
                'product' => $this->product,
                'copyright' => $this->copyright,
                'trademark' => $this->trademark
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Assembly
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public static function validateArray(array $data): void
        {
            if(!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '')
            {
                throw new InvalidPropertyException('assembly.name', 'The assembly name is required and cannot be empty');
            }

            if(!isset($data['package']) || !is_string($data['package']) || !Validate::packageName($data['package']))
            {
                throw new InvalidPropertyException('assembly.package', 'The assembly package is required and must be a valid package name');
            }

            if(!isset($data['version']) || !is_string($data['version']) || !Validate::version($data['version']))
            {
                throw new InvalidPropertyException('assembly.version', 'The assembly version is required and must be a valid version');
            }

            if(isset($data['url']) && (!is_string($data['url']) || trim($data['url']) === '' || !Validate::url($data['url'])))
            {
                throw new InvalidPropertyException('assembly.url', 'The assembly URL must be a non-empty string or null');
            }

            if(isset($data['license']) && (!is_string($data['license']) || trim($data['license']) === ''))
            {
                throw new InvalidPropertyException('assembly.license', 'The assembly license must be a non-empty string or null');
            }

            if(isset($data['description']) && (!is_string($data['description']) || trim($data['description']) === ''))
            {
                throw new InvalidPropertyException('assembly.description', 'The assembly description must be a non-empty string or null');
            }

            if(isset($data['author']) && (!is_string($data['author']) || trim($data['author']) === ''))
            {
                throw new InvalidPropertyException('assembly.author', 'The assembly author must be a non-empty string or null');
            }

            if(isset($data['organization']) && (!is_string($data['organization']) || trim($data['organization']) === ''))
            {
                throw new InvalidPropertyException('assembly.organization', 'The assembly organization must be a non-empty string or null');
            }

            if(isset($data['product']) && (!is_string($data['product']) || trim($data['product']) === ''))
            {
                throw new InvalidPropertyException('assembly.product', 'The assembly product must be a non-empty string or null');
            }

            if(isset($data['copyright']) && (!is_string($data['copyright']) || trim($data['copyright']) === ''))
            {
                throw new InvalidPropertyException('assembly.copyright', 'The assembly copyright must be a non-empty string or null');
            }

            if(isset($data['trademark']) && (!is_string($data['trademark']) || trim($data['trademark']) === ''))
            {
                throw new InvalidPropertyException('assembly.trademark', 'The assembly trademark must be a non-empty string or null');
            }
        }
    }