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
         * @param string|null $bin_file
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

            if($object->ExecutionPolicy !== null)
                $object->ExecutionPolicy = ExecutionPolicy::fromArray($object->ExecutionPolicy);

            return $object;
        }
    }