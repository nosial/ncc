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