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
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Assembly implements BytecodeObjectInterface
    {
        /**
         * The software name
         *
         * @var string
         */
        public $name;

        /**
         * The package name used to identify the package
         *
         * @var string
         */
        public $package;

        /**
         * The software description
         *
         * @var string|null
         */
        public $description;

        /**
         * @var string|null
         */
        public $company;

        /**
         * The product name
         *
         * @var string|null
         */
        public $product;

        /**
         * The copyright header for the product
         *
         * @var string|null
         */
        public $copyright;

        /**
         * Product trademark
         *
         * @var string|null
         */
        public $trademark;

        /**
         * Software version
         *
         * @var string
         */
        public $version;

        /**
         * Universally Unique Identifier
         *
         * @var string
         */
        public $uuid;

        /**
         * Validates the object information to detect possible errors
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!preg_match(RegexPatterns::UUID, $this->uuid))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The UUID is not a valid v4 UUID: %s, in property Assembly.UUID', $this->uuid));
                }

                return false;
            }

            if($this->version !== null && !Validate::version($this->version))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The version number is invalid: %s, in property Assembly.Version', $this->version));
                }

                return false;
            }

            if($this->package !== null && !preg_match(RegexPatterns::PACKAGE_NAME_FORMAT, $this->package))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The package name is invalid: %s, in property Assembly.Package', $this->package));
                }

                return false;
            }

            if($this->name !== null && strlen($this->name) > 126)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The name cannot be larger than 126 characters: %s, in property Assembly.Name', $this->name));
                }

                return false;
            }

            if($this->description !== null && strlen($this->description) > 512)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The description cannot be larger than 512 characters: %s, in property Assembly.Description', $this->description));
                }

                return false;
            }

            if($this->company !== null && strlen($this->company) > 126)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The company cannot be larger than 126 characters: %s, in property Assembly.Company', $this->company));
                }

                return false;
            }

            if($this->product !== null && strlen($this->product) > 256)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The product cannot be larger than 256 characters: %s, in property Assembly.Product', $this->product));
                }

                return false;
            }

            if($this->copyright !== null && strlen($this->copyright) > 256)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The copyright cannot be larger than 256 characters: %s, in property Assembly.Copyright', $this->company));
                }

                return false;
            }

            if($this->trademark !== null && strlen($this->trademark) > 256)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('The trademark cannot be larger than 256 characters: %s, in property Assembly.Trademark', $this->trademark));
                }

                return false;
            }

            return true;
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
         */
        public static function fromArray(array $data): Assembly
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->package = Functions::array_bc($data, 'package');
            $object->description = Functions::array_bc($data, 'description');
            $object->company = Functions::array_bc($data, 'company');
            $object->product = Functions::array_bc($data, 'product');
            $object->copyright = Functions::array_bc($data, 'copyright');
            $object->trademark = Functions::array_bc($data, 'trademark');
            $object->version = Functions::array_bc($data, 'version');
            $object->uuid = Functions::array_bc($data, 'uuid');

            return $object;
        }
    }