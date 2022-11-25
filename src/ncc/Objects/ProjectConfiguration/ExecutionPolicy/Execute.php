<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration\ExecutionPolicy;

    use ncc\Utilities\Functions;

    class Execute
    {
        /**
         * The target file to execute
         *
         * @var string
         */
        public $Target;

        /**
         * The working directory to execute the policy in, if not specified the
         * value "%CWD%" will be used as the default
         *
         * @var string|null
         */
        public $WorkingDirectory;

        /**
         * An array of options to pass on to the process
         *
         * @var array|null
         */
        public $Options;

        /**
         * Indicates if the output should be displayed or suppressed
         *
         * @var bool|null
         */
        public $Silent;

        /**
         * Indicates if the process should run in Tty mode (Overrides Silent mode)
         *
         * @var bool|null
         */
        public $Tty;

        /**
         * The number of seconds to wait before giving up on the process, will automatically execute the error handler
         * if one is set.
         *
         * @var int
         */
        public $Timeout;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Tty = false;
            $this->Silent = false;
            $this->Timeout = null;
            $this->WorkingDirectory = "%CWD%";
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
                ($bytecode ? Functions::cbc('target') : 'target') => $this->Target,
                ($bytecode ? Functions::cbc('working_directory') : 'working_directory') => $this->WorkingDirectory,
                ($bytecode ? Functions::cbc('options') : 'options') => $this->Options,
                ($bytecode ? Functions::cbc('silent') : 'silent') => $this->Silent,
                ($bytecode ? Functions::cbc('tty') : 'tty') => $this->Tty,
                ($bytecode ? Functions::cbc('timeout') : 'timeout') => $this->Timeout
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Execute
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Target = Functions::array_bc($data, 'target');
            $object->WorkingDirectory = Functions::array_bc($data, 'working_directory');
            $object->Options = Functions::array_bc($data, 'options');
            $object->Silent = Functions::array_bc($data, 'silent');
            $object->Tty = Functions::array_bc($data, 'tty');
            $object->Timeout = Functions::array_bc($data, 'timeout');

            return $object;
        }
    }