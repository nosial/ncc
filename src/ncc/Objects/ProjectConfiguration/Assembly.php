<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Abstracts\RegexPatterns;
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
            if(!preg_match(RegexPatterns::UUIDv4, $this->UUID))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The UUID is not a valid v4 UUID', 'Assembly.UUID');

                return false;
            }

            if(!Validate::version($this->Version))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The version number is invalid', 'Assembly.Version');

                return false;
            }

            if(!preg_match(RegexPatterns::PackageNameFormat, $this->Package))
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The package name is invalid', 'Assembly.Package');

                return false;
            }

            if(strlen($this->Name) > 126)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The name cannot be larger than 126 characters', 'Assembly.Name');

                return false;
            }

            if(strlen($this->Description) > 512)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The description cannot be larger than 512 characters', 'Assembly.Description');

                return false;
            }

            if(strlen($this->Company) > 126)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The company cannot be larger than 126 characters', 'Assembly.Company');

                return false;
            }

            if(strlen($this->Product) > 256)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The company cannot be larger than 256 characters', 'Assembly.Product');

                return false;
            }

            if(strlen($this->Copyright) > 256)
            {
                if($throw_exception)
                    throw new InvalidProjectConfigurationException('The copyright cannot be larger than 256 characters', 'Assembly.Copyright');

                return false;
            }

            if(strlen($this->Trademark) > 256)
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
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->Name,
                ($bytecode ? Functions::cbc('package') : 'package') => $this->Package,
                ($bytecode ? Functions::cbc('description') : 'description') => ($this->Description !== null && strlen($this->Description) > 0 ? $this->Description : null),
                ($bytecode ? Functions::cbc('company') : 'company') => ($this->Company !== null && strlen($this->Company) > 0 ? $this->Company : null),
                ($bytecode ? Functions::cbc('product') : 'product') => ($this->Product !== null && strlen($this->Product) > 0 ? $this->Product : null),
                ($bytecode ? Functions::cbc('copyright') : 'copyright') => ($this->Copyright !== null && strlen($this->Copyright) > 0 ? $this->Copyright : null),
                ($bytecode ? Functions::cbc('trademark') : 'trademark') => ($this->Trademark !== null && strlen($this->Trademark) > 0 ? $this->Trademark : null),
                ($bytecode ? Functions::cbc('version') : 'version') => $this->Version,
                ($bytecode ? Functions::cbc('uid') : 'uid') => $this->UUID,
            ];
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
            $AssemblyObject->UUID = Functions::array_bc($data, 'uid');

            return $AssemblyObject;
        }
    }