<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Utilities\Functions;

    class Header
    {
        /**
         * The compiler extension information that was used to build the package
         *
         * @var Compiler
         */
        public $CompilerExtension;

        /**
         * An array of constants that are set when the package is imported or executed during runtime.
         *
         * @var array
         */
        public $RuntimeConstants;

        /**
         * The version of NCC that was used to compile the package, can be used for backwards compatibility
         *
         * @var string
         */
        public $CompilerVersion;

        /**
         * An array of options to pass on to the extension
         *
         * @var array|null
         */
        public $Options;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->CompilerExtension = new Compiler();
            $this->RuntimeConstants = [];
            $this->Options = [];
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
                ($bytecode ? Functions::cbc('compiler_extension') : 'compiler_extension') => $this->CompilerExtension->toArray($bytecode),
                ($bytecode ? Functions::cbc('runtime_constants') : 'runtime_constants') => $this->RuntimeConstants,
                ($bytecode ? Functions::cbc('compiler_version') : 'compiler_version') => $this->CompilerVersion,
                ($bytecode ? Functions::cbc('options') : 'options') => $this->Options,
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->CompilerExtension = Functions::array_bc($data, 'compiler_extension');
            $object->RuntimeConstants = Functions::array_bc($data, 'runtime_constants');
            $object->CompilerVersion = Functions::array_bc($data, 'compiler_version');
            $object->Options = Functions::array_bc($data, 'options');

            if($object->CompilerExtension !== null)
                $object->CompilerExtension = Compiler::fromArray($object->CompilerExtension);

            return $object;
        }
    }