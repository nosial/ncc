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

            if($object->ExecutionPolicy !== null)
                $object->ExecutionPolicy = ExecutionPolicy::fromArray($object->ExecutionPolicy);

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