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

    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Functions;

    class ExecutionUnit implements BytecodeObjectInterface
    {
        /**
         * @var string
         */
        private $id;

        /**
         * @var ExecutionPolicy
         */
        private $execution_policy;

        /**
         * @var string
         */
        private $data;

        /**
         * ExecutionUnit constructor.
         *
         * @param ExecutionPolicy $execution_policy
         * @param string $data
         * @noinspection InterfacesAsConstructorDependenciesInspection
         */
        public function __construct(ExecutionPolicy $execution_policy, string $data)
        {
            $this->execution_policy = $execution_policy;
            $this->data = $data;
            $this->id = hash('sha1', $this->execution_policy->getName());
        }

        /**
         * Returns the ID of the execution unit (sha1)
         *
         * @return string
         */
        public function getId(): string
        {
            return $this->id;
        }

        /**
         * Returns the execution policy of the execution unit
         *
         * @return ExecutionPolicy
         */
        public function getExecutionPolicy(): ExecutionPolicy
        {
            return $this->execution_policy;
        }

        /**
         * Returns the executable data of the execution unit
         *
         * @return string
         */
        public function getData(): string
        {
            return $this->data;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('execution_policy') : 'execution_policy') => $this->execution_policy->toArray($bytecode),
                ($bytecode ? Functions::cbc('data') : 'data') => Base64::encode($this->data),
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExecutionUnit
        {
            $execution_policy = Functions::array_bc($data, 'execution_policy');
            $execution_data = Functions::array_bc($data, 'data');

            if($execution_policy === null)
            {
                throw new ConfigurationException('Missing execution policy for execution unit');
            }

            if($execution_data === null)
            {
                throw new ConfigurationException('Missing execution data for execution unit');
            }

            return new self(ExecutionPolicy::fromArray($execution_policy), Base64::decode($execution_data));
        }
    }