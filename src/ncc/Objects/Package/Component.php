<?php

    namespace ncc\Objects\Package;

    use ncc\Utilities\Functions;

    class Component
    {
        /**
         * The name of the component or the file name of the component
         *
         * @var string
         */
        public $Name;

        /**
         * Flags associated with the component created by the compiler extension
         *
         * @var array
         */
        public $Flags;

        /**
         * The data type of the component
         *
         * @var string
         */
        public $DataType;

        /**
         * A sha1 hash checksum of the component, this will be compared against the data to determine
         * the integrity of the component to ensure that the component is not corrupted.
         *
         * @var string
         */
        public $Checksum;

        /**
         * The raw data of the component, this is to be processed by the compiler extension
         *
         * @var string
         */
        public $Data;

        /**
         * Validates the checksum of the component, returns false if the checksum or data is invalid or if the checksum
         * failed.
         *
         * @return bool
         */
        public function validateChecksum(): bool
        {
            if($this->Checksum === null)
                return false;

            if($this->Data === null)
                return false;

            if(hash('sha1', $this->Data) !== $this->Checksum)
                return false;

            return true;
        }

        /**
         * Returns an array representation of the component.
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->Name,
                ($bytecode ? Functions::cbc('flags') : 'flags') => $this->Flags,
                ($bytecode ? Functions::cbc('data_type') : 'data_type') => $this->DataType,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->Checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->Data,
            ];
        }

        /**
         * Constructs a new object from an array representation
         *
         * @param array $data
         * @return Component
         */
        public static function fromArray(array $data): self
        {
            $Object = new self();

            $Object->Name = Functions::array_bc($data, 'name');
            $Object->Flags = Functions::array_bc($data, 'flags');
            $Object->DataType = Functions::array_bc($data, 'data_type');
            $Object->Checksum = Functions::array_bc($data, 'checksum');
            $Object->Data = Functions::array_bc($data, 'data');

            return $Object;
        }
    }