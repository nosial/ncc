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

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Enums\RegexPatterns;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Interfaces\ValidatableObjectInterface;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Assembly implements BytecodeObjectInterface, ValidatableObjectInterface
    {
        /**
         * Universally Unique Identifier
         *
         * @var string
         */
        private $uuid;

        /**
         * The software name
         *
         * @var string
         */
        private $name;

        /**
         * The package name used to identify the package
         *
         * @var string
         */
        private $package;

        /**
         * Software version
         *
         * @var string
         */
        private $version;

        /**
         * The software description
         *
         * @var string|null
         */
        private $description;

        /**
         * @var string|null
         */
        private $company;

        /**
         * The product name
         *
         * @var string|null
         */
        private $product;

        /**
         * The copyright header for the product
         *
         * @var string|null
         */
        private $copyright;

        /**
         * Product trademark
         *
         * @var string|null
         */
        private $trademark;

        /**
         * Assembly constructor.
         */
        public function __construct(string $name, string $package, string $version='1.0.0', ?string $uuid=null)
        {
            $this->name = $name;
            $this->package = $package;
            $this->version = $version;

            if($uuid === null)
            {
                $this->uuid = Uuid::v4()->toRfc4122();
            }
            else
            {
                $this->uuid = $uuid;
            }
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * @param string $package
         */
        public function setPackage(string $package): void
        {
            $this->package = $package;
        }

        /**
         * @return string|null
         */
        public function getDescription(): ?string
        {
            return $this->description;
        }

        /**
         * @param string|null $description
         */
        public function setDescription(?string $description): void
        {
            $this->description = $description;
        }

        /**
         * @return string|null
         */
        public function getCompany(): ?string
        {
            return $this->company;
        }

        /**
         * @param string|null $company
         */
        public function setCompany(?string $company): void
        {
            $this->company = $company;
        }

        /**
         * @return string|null
         */
        public function getProduct(): ?string
        {
            return $this->product;
        }

        /**
         * @param string|null $product
         */
        public function setProduct(?string $product): void
        {
            $this->product = $product;
        }

        /**
         * @return string|null
         */
        public function getCopyright(): ?string
        {
            return $this->copyright;
        }

        /**
         * @param string|null $copyright
         */
        public function setCopyright(?string $copyright): void
        {
            $this->copyright = $copyright;
        }

        /**
         * @return string|null
         */
        public function getTrademark(): ?string
        {
            return $this->trademark;
        }

        /**
         * @param string|null $trademark
         */
        public function setTrademark(?string $trademark): void
        {
            $this->trademark = $trademark;
        }

        /**
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * @param string $version
         */
        public function setVersion(string $version): void
        {
            $this->version = $version;
        }

        /**
         * @return string
         */
        public function getUuid(): string
        {
            return $this->uuid;
        }

        /**
         * @param string $uuid
         */
        public function setUuid(string $uuid): void
        {
            $this->uuid = $uuid;
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            if(!preg_match(RegexPatterns::UUID->value, $this->uuid))
            {
                throw new ConfigurationException(sprintf('The UUID is not a valid v4 UUID: %s, in property assembly.uuid', $this->uuid));
            }

            if($this->version !== null && !Validate::version($this->version))
            {
                throw new ConfigurationException(sprintf('The version number is invalid: %s, in property assembly.version', $this->version));
            }

            if($this->package !== null && !preg_match(RegexPatterns::PACKAGE_NAME_FORMAT->value, $this->package))
            {
                throw new ConfigurationException(sprintf('The package name is invalid: %s, in property assembly.package', $this->package));
            }

            if($this->name !== null && strlen($this->name) > 126)
            {
                throw new ConfigurationException(sprintf('The name cannot be larger than 126 characters: %s, in property assembly.name', $this->name));
            }

            if($this->description !== null && strlen($this->description) > 512)
            {
                throw new ConfigurationException(sprintf('The description cannot be larger than 512 characters: %s, in property assembly.description', $this->description));
            }

            if($this->company !== null && strlen($this->company) > 126)
            {
                throw new ConfigurationException(sprintf('The company cannot be larger than 126 characters: %s, in property assembly.company', $this->company));
            }

            if($this->product !== null && strlen($this->product) > 256)
            {
                throw new ConfigurationException(sprintf('The product cannot be larger than 256 characters: %s, in property assembly.product', $this->product));
            }

            if($this->copyright !== null && strlen($this->copyright) > 256)
            {
                throw new ConfigurationException(sprintf('The copyright cannot be larger than 256 characters: %s, in property assembly.copyright', $this->company));
            }

            if($this->trademark !== null && strlen($this->trademark) > 256)
            {
                throw new ConfigurationException(sprintf('The trademark cannot be larger than 256 characters: %s, in property assembly.trademark', $this->trademark));
            }
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->name !== null && $this->name !== '')
            {
                $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;
            }

            if($this->package !== null && $this->package !== '')
            {
                $results[($bytecode ? Functions::cbc('package') : 'package')] = $this->package;
            }

            if($this->description !== null && $this->description !== '')
            {
                $results[($bytecode ? Functions::cbc('description') : 'description')] = $this->description;
            }

            if($this->company !== null && $this->company !== '')
            {
                $results[($bytecode ? Functions::cbc('company') : 'company')] = $this->company;
            }

            if($this->product !== null && $this->product !== '')
            {
                $results[($bytecode ? Functions::cbc('product') : 'product')] = $this->product;
            }

            if($this->copyright !== null && $this->copyright !== '')
            {
                $results[($bytecode ? Functions::cbc('copyright') : 'copyright')] = $this->copyright;
            }

            if($this->trademark !== null && $this->trademark !== '')
            {
                $results[($bytecode ? Functions::cbc('trademark') : 'trademark')] = $this->trademark;
            }

            if($this->version !== null && $this->version !== '')
            {
                $results[($bytecode ? Functions::cbc('version') : 'version')] = $this->version;
            }

            if($this->uuid !== null && $this->uuid !== '')
            {
                $results[($bytecode ? Functions::cbc('uuid') : 'uuid')] = $this->uuid;
            }

            return $results;
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Assembly
        {
            $name = Functions::array_bc($data, 'name');
            $package = Functions::array_bc($data, 'package');

            if($name === null)
            {
                throw new ConfigurationException('The property \'assembly.name\' must not be null.');
            }

            if($package === null)
            {
                throw new ConfigurationException('The property \'assembly.package\' must not be null.');
            }

            $object = new self($name, $package, Functions::array_bc($data, 'version'), Functions::array_bc($data, 'uuid'));

            $object->description = Functions::array_bc($data, 'description');
            $object->company = Functions::array_bc($data, 'company');
            $object->product = Functions::array_bc($data, 'product');
            $object->copyright = Functions::array_bc($data, 'copyright');
            $object->trademark = Functions::array_bc($data, 'trademark');

            return $object;
        }
    }