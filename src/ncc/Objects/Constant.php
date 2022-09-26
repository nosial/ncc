<?php

    namespace ncc\Objects;

    use ncc\Exceptions\ConstantReadonlyException;
    use ncc\Utilities\Resolver;

    class Constant
    {
        /**
         * The unique hash of the constant
         *
         * @var string
         */
        private $Hash;

        /**
         * The package that manages this constant
         *
         * @var string
         */
        private $Scope;

        /**
         * The name of the constant
         *
         * @var string
         */
        private $Name;

        /**
         * The value of the constant
         *
         * @var string
         */
        private $Value;

        /**
         * Indicates if the constant is readonly or not
         *
         * @var bool
         */
        private $Readonly;

        /**
         * Public Constructor
         *
         * @param string $scope
         * @param string $name
         * @param string $value
         * @param bool $readonly
         */
        public function __construct(string $scope, string $name, string $value, bool $readonly=false)
        {
            $this->Scope = $scope;
            $this->Name = $name;
            $this->Value = $value;
            $this->Readonly = $readonly;
            $this->Hash = Resolver::resolveConstantHash($this->Scope, $this->Name);
        }

        /**
         * Returns the constant value
         *
         * @return string
         */
        public function __toString(): string
        {
            return $this->Value;
        }

        /**
         * @return string
         */
        public function getValue(): string
        {
            return $this->Value;
        }

        /**
         * Gets the full name of the constant
         *
         * @return string
         */
        public function getFullName(): string
        {
            return Resolver::resolveFullConstantName($this->Scope, $this->Name);
        }

        /**
         * @param string $value
         * @param bool $readonly
         * @throws ConstantReadonlyException
         */
        public function setValue(string $value, bool $readonly=false): void
        {
            if($this->Readonly == true)
            {
                throw new ConstantReadonlyException('Cannot set value to the constant \'' .  $this->getFullName() .  '\', constant is readonly');
            }

            $this->Value = $value;
            $this->Readonly = $readonly;
        }

        /**
         * @return bool
         */
        public function isReadonly(): bool
        {
            return $this->Readonly;
        }

        /**
         * @return string
         */
        public function getHash(): string
        {
            return $this->Hash;
        }

        /**
         * @return string
         */
        public function getScope(): string
        {
            return $this->Scope;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->Name;
        }
    }