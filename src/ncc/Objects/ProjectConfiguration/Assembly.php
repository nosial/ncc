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
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Assembly
    {
        /**
         * The software name
         *
         * @var string
         */
        public $Name;

        /**
         * The package name used to identify the package
         *
         * @var string
         */
        public $Package;

        /**
         * The software description
         *
         * @var string|null
         */
        public $Description;

        /**
         * @var string|null
         */
        public $Company;

        /**
         * The product name
         *
         * @var string|null
         */
        public $Product;

        /**
         * The copyright header for the product
         *
         * @var string|null
         */
        public $Copyright;

        /**
         * Product trademark
         *
         * @var string|null
         */
        public $Trademark;

        /**
         * Software version
         *
         * @var string
         */
        public $Version;

        /**
         * Universally Unique Identifier
         *
         * @var string
         */
        public $UUID;

        /**
         * Validates the object information to detect possible errors
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidProjectConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!preg_match(RegexPatterns::UUID, $this->UUID))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The UUID is not a valid v4 UUID', 'Assembly.UUID');
                return false;
            }

            if($this->Version !== null && !Validate::version($this->Version))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The version number is invalid', 'Assembly.Version');

                return false;
            }

            if($this->Package !== null && !preg_match(RegexPatterns::PACKAGE_NAME_FORMAT, $this->Package))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The package name is invalid', 'Assembly.Package');

                return false;
            }

            if($this->Name !== null && strlen($this->Name) > 126)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The name cannot be larger than 126 characters', 'Assembly.Name');

                return false;
            }

            if($this->Description !== null && strlen($this->Description) > 512)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The description cannot be larger than 512 characters', 'Assembly.Description');

                return false;
            }

            if($this->Company !== null && strlen($this->Company) > 126)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The company cannot be larger than 126 characters', 'Assembly.Company');

                return false;
            }

            if($this->Product !== null && strlen($this->Product) > 256)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The company cannot be larger than 256 characters', 'Assembly.Product');

                return false;
            }

            if($this->Copyright !== null && strlen($this->Copyright) > 256)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The copyright cannot be larger than 256 characters', 'Assembly.Copyright');

                return false;
            }

            if($this->Trademark !== null && strlen($this->Trademark) > 256)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The trademark cannot be larger than 256 characters', 'Assembly.Trademark');

                return false;
            }

            return true;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $return_results = [];

            if($this->Name !== null && strlen($this->Name) > 0)
                $return_results[($bytecode ? Functions::cbc('name') : 'name')] = $this->Name;

            if($this->Package !== null && strlen($this->Package) > 0)
                $return_results[($bytecode ? Functions::cbc('package') : 'package')] = $this->Package;

            if($this->Description !== null && strlen($this->Description) > 0)
                $return_results[($bytecode ? Functions::cbc('description') : 'description')] = $this->Description;

            if($this->Company !== null && strlen($this->Company) > 0)
                $return_results[($bytecode ? Functions::cbc('company') : 'company')] = $this->Company;

            if($this->Product !== null && strlen($this->Product) > 0)
                $return_results[($bytecode ? Functions::cbc('product') : 'product')] = $this->Product;

            if($this->Copyright !== null && strlen($this->Copyright) > 0)
                $return_results[($bytecode ? Functions::cbc('copyright') : 'copyright')] = $this->Copyright;

            if($this->Trademark !== null && strlen($this->Trademark) > 0)
                $return_results[($bytecode ? Functions::cbc('trademark') : 'trademark')] = $this->Trademark;

            if($this->Version !== null && strlen($this->Version) > 0)
                $return_results[($bytecode ? Functions::cbc('version') : 'version')] = $this->Version;

            if($this->UUID !== null && strlen($this->UUID) > 0)
                $return_results[($bytecode ? Functions::cbc('uuid') : 'uuid')] = $this->UUID;

            return $return_results;
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Assembly
         */
        public static function fromArray(array $data): Assembly
        {
            $AssemblyObject = new Assembly();

            $AssemblyObject->Name = Functions::array_bc($data, 'name');
            $AssemblyObject->Package = Functions::array_bc($data, 'package');
            $AssemblyObject->Description = Functions::array_bc($data, 'description');
            $AssemblyObject->Company = Functions::array_bc($data, 'company');
            $AssemblyObject->Product = Functions::array_bc($data, 'product');
            $AssemblyObject->Copyright = Functions::array_bc($data, 'copyright');
            $AssemblyObject->Trademark = Functions::array_bc($data, 'trademark');
            $AssemblyObject->Version = Functions::array_bc($data, 'version');
            $AssemblyObject->UUID = Functions::array_bc($data, 'uuid');

            return $AssemblyObject;
        }
    }