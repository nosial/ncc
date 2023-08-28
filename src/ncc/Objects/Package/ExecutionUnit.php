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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\Functions;

    class ExecutionUnit implements BytecodeObjectInterface
    {
        /**
         * @var string|null
         */
        private $id;

        /**
         * The execution policy for this execution unit
         *
         * @var ExecutionPolicy
         */
        private $execution_policy;

        /**
         * The data of the unit to execute
         *
         * @var string
         */
        private $data;

        /**
         * @return string
         */
        public function getId(): string
        {
            if($this->id === null)
            {
                $this->id = hash('sha1', $this->execution_policy->getName());
            }

            return $this->id;
        }

        /**
         * @return ExecutionPolicy
         */
        public function getExecutionPolicy(): ExecutionPolicy
        {
            return $this->execution_policy;
        }

        /**
         * @param ExecutionPolicy $execution_policy
         */
        public function setExecutionPolicy(ExecutionPolicy $execution_policy): void
        {
            $this->execution_policy = $execution_policy;
        }

        /**
         * @return string
         */
        public function getData(): string
        {
            return $this->data;
        }

        /**
         * @param string $data
         */
        public function setData(string $data): void
        {
            $this->data = $data;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('execution_policy') : 'execution_policy') => $this->execution_policy->toArray($bytecode),
                ($bytecode ? Functions::cbc('data') : 'data') => $this->data,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExecutionUnit
        {
            $object = new self();

            $object->execution_policy = Functions::array_bc($data, 'execution_policy');
            $object->data = Functions::array_bc($data, 'data');

            if($object->execution_policy !== null)
            {
                $object->execution_policy = ExecutionPolicy::fromArray($object->execution_policy);
            }

            return $object;
        }
    }