<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Utilities\Functions;

    class Resource
    {
        /**
         * The file/path name of the resource
         *
         * @var string
         */
        public $Name;

        /**
         * A sha1 hash checksum of the resource, this will be compared against the data to determine
         * the integrity of the resource to ensure that the resource is not corrupted.
         *
         * @var string
         */
        public $Checksum;

        /**
         * The raw data of the resource
         *
         * @var string
         */
        public $Data;

        /**
         * Validates the checksum of the resource, returns false if the checksum or data is invalid or if the checksum
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
         * Returns an array representation of the resource.
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->Name,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->Checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->Data,
            ];
        }

        /**
         * Constructs a new object from an array representation
         *
         * @param array $data
         * @return Resource
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Name = Functions::array_bc($data, 'name');
            $object->Checksum = Functions::array_bc($data, 'checksum');
            $object->Data = Functions::array_bc($data, 'data');

            return $object;
        }
    }