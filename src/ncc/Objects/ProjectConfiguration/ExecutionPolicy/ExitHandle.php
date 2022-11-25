<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration\ExecutionPolicy;

    use ncc\Utilities\Functions;

    class ExitHandle
    {
        /**
         * The message to display when the handle is triggered
         *
         * @var string|null
         */
        public $Message;

        /**
         * Indicates if the process should exit if the handle is triggered,
         * by default NCC will choose the applicable value for this property,
         * for instance; if the exit handle is registered for "error", the
         * property will be set to true, otherwise for "success" and "warning"
         * the property will be false.
         *
         * @var bool|null
         */
        public $EndProcess;

        /**
         * The name of another execution policy to execute (optionally) when this exit handle is triggered
         *
         * @var string|null
         */
        public $Run;

        /**
         * The exit code that needs to be returned from the process to trigger this handle
         *
         * @var int
         */
        public $ExitCode;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('message') : 'message') => $this->Message,
                ($bytecode ? Functions::cbc('end_process') : 'end_process') => $this->EndProcess,
                ($bytecode ? Functions::cbc('run') : 'run') => $this->Run,
                ($bytecode ? Functions::cbc('exit_code') : 'exit_code') => $this->ExitCode,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return ExitHandle
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Message = Functions::array_bc($data, 'message');
            $object->EndProcess = Functions::array_bc($data, 'end_process');
            $object->Run = Functions::array_bc($data, 'run');
            $object->ExitCode = Functions::array_bc($data, 'exit_code');

            return $object;
        }
    }