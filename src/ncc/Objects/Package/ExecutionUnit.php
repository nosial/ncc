<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\Functions;

    class ExecutionUnit
    {
        /**
         * @var string|null
         */
        private $ID;

        /**
         * The execution policy for this execution unit
         *
         * @var ExecutionPolicy
         */
        public $ExecutionPolicy;

        /**
         * The data of the unit to execute
         *
         * @var string
         */
        public $Data;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('execution_policy') : 'execution_policy') => $this->ExecutionPolicy->toArray($bytecode),
                ($bytecode ? Functions::cbc('data') : 'data') => $this->Data,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->ExecutionPolicy = Functions::array_bc($data, 'execution_policy');
            $object->Data = Functions::array_bc($data, 'data');

            return $object;
        }

        /**
         * @return string
         */
        public function getID(): string
        {
            if($this->ID == null)
                $this->ID = hash('sha1', $this->ExecutionPolicy->Name);
            return $this->ID;
        }

    }