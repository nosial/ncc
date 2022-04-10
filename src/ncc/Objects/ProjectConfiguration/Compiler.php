<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Utilities\Functions;

    class Compiler
    {
        /**
         * The compiler extension that the project uses
         *
         * @var string
         */
        public $Extension;

        /**
         * The minimum version that is supported
         *
         * @var string
         */
        public $MinimumVersion;

        /**
         * The maximum version that is supported
         *
         * @var string
         */
        public $MaximumVersion;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('extension') : 'extension') => $this->Extension,
                ($bytecode ? Functions::cbc('minimum_version') : 'minimum_version') => $this->MinimumVersion,
                ($bytecode ? Functions::cbc('maximum_version') : 'maximum_version') => $this->MaximumVersion
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Compiler
         */
        public static function fromArray(array $data): Compiler
        {
            $CompilerObject = new Compiler();

            if(Functions::array_bc($data, 'extension') !== null)
            {
                $CompilerObject->Extension = Functions::array_bc($data, 'extension');
            }

            if(Functions::array_bc($data, 'maximum_version') !== null)
            {
                $CompilerObject->MaximumVersion = Functions::array_bc($data, 'maximum_version');
            }

            if(Functions::array_bc($data, 'minimum_version') !== null)
            {
                $CompilerObject->MinimumVersion = Functions::array_bc($data, 'minimum_version');
            }

            return $CompilerObject;
        }
    }