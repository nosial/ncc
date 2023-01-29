<?php
/*
 * Copyright (c) Nosial 2022-2023, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Objects\ProjectConfiguration\UpdateSource;
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
         * The optional update source to where the package can be updated from
         *
         * @var UpdateSource|null
         */
        public $UpdateSource;

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
                ($bytecode ? Functions::cbc('compiler_extension') : 'compiler_extension') => $this->CompilerExtension->toArray(),
                ($bytecode ? Functions::cbc('runtime_constants') : 'runtime_constants') => $this->RuntimeConstants,
                ($bytecode ? Functions::cbc('compiler_version') : 'compiler_version') => $this->CompilerVersion,
                ($bytecode ? Functions::cbc('update_source') : 'update_source') => ($this->UpdateSource?->toArray($bytecode) ?? null),
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
            $object->UpdateSource = Functions::array_bc($data, 'update_source');
            $object->Options = Functions::array_bc($data, 'options');

            if($object->CompilerExtension !== null)
                $object->CompilerExtension = Compiler::fromArray($object->CompilerExtension);
            if($object->UpdateSource !== null)
                $object->UpdateSource = UpdateSource::fromArray($object->UpdateSource);

            return $object;
        }
    }