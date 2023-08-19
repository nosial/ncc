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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class ExitHandlers implements BytecodeObjectInterface
    {
        /**
         * The handle to execute when the process exits with a success exit code
         *
         * @var ExitHandle|null
         */
        public $success;

        /**
         * The handle to execute when the process exits with a warning exit code
         *
         * @var ExitHandle|null
         */
        public $warning;

        /**
         * The handle to execute when the process exits with a error exit code
         *
         * @var ExitHandle|null
         */
        public $error;

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('success') : 'success') => $this->success?->toArray($bytecode),
                ($bytecode ? Functions::cbc('warning') : 'warning') => $this->warning?->toArray($bytecode),
                ($bytecode ? Functions::cbc('error') : 'error') => $this->error?->toArray($bytecode),
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->success = Functions::array_bc($data, 'success');
            if($object->success !== null)
            {
                $object->success = ExitHandle::fromArray($object->success);
            }

            $object->warning = Functions::array_bc($data, 'warning');
            if($object->warning !== null)
            {
                $object->warning = ExitHandle::fromArray($object->warning);
            }

            $object->error = Functions::array_bc($data, 'error');
            if($object->error !== null)
            {
                $object->error = ExitHandle::fromArray($object->error);
            }

            return $object;
        }
    }