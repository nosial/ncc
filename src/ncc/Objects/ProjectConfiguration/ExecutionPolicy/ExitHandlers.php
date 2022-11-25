<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration\ExecutionPolicy;

    use ncc\Utilities\Functions;

    class ExitHandlers
    {
        /**
         * The handle to execute when the process exits with a success exit code
         *
         * @var ExitHandle
         */
        public $Success;

        /**
         * The handle to execute when the process exits with a warning exit code
         *
         * @var ExitHandle
         */
        public $Warning;

        /**
         * The handle to execute when the process exits with a error exit code
         *
         * @var ExitHandle
         */
        public $Error;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('success') : 'success') => $this->Success?->toArray($bytecode),
                ($bytecode ? Functions::cbc('warning') : 'warning') => $this->Warning?->toArray($bytecode),
                ($bytecode ? Functions::cbc('error') : 'error') => $this->Error?->toArray($bytecode),
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return ExitHandlers
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Success = Functions::array_bc($data, 'success');
            if($object->Success !== null)
                $object->Success = ExitHandle::fromArray($object->Success);

            $object->Warning = Functions::array_bc($data, 'warning');
            if($object->Warning !== null)
                $object->Warning = ExitHandle::fromArray($object->Warning);

            $object->Error = Functions::array_bc($data, 'error');
            if($object->Error !== null)
                $object->Error = ExitHandle::fromArray($object->Error);

            return $object;
        }
    }