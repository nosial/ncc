<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ExecutionPointers;

    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\Functions;

    class ExecutionPointer
    {
        /**
         * @var string
         */
        public $ID;

        /**
         * The execution policy for this execution unit
         *
         * @var ExecutionPolicy
         */
        public $ExecutionPolicy;

        /**
         * The file pointer for where the target script should be executed
         *
         * @var string
         */
        public $FilePointer;

        /**
         * Public Constructor with optional ExecutionUnit parameter to construct object from
         *
         * @param ExecutionUnit|null $unit
         */
        public function __construct(?ExecutionUnit $unit=null, ?string $bin_file=null)
        {
            if($unit == null)
                return;

            $this->ID = $unit->getID();
            $this->ExecutionPolicy = $unit->ExecutionPolicy;
            $this->FilePointer = $bin_file;
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
                ($bytecode ? Functions::cbc('id') : 'id') => $this->ID,
                ($bytecode ? Functions::cbc('execution_policy') : 'execution_policy') => $this->ExecutionPolicy->toArray($bytecode),
                ($bytecode ? Functions::cbc('file_pointer') : 'file_pointer') => $this->FilePointer,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return ExecutionPointer
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->ID = Functions::array_bc($data, 'id');
            $object->ExecutionPolicy = Functions::array_bc($data, 'execution_policy');
            $object->FilePointer = Functions::array_bc($data, 'file_pointer');

            return $object;
        }
    }