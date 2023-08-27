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

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\Execute;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\ExitHandlers;
    use ncc\Utilities\Functions;

    class ExecutionPolicy implements BytecodeObjectInterface
    {
        /**
         * The unique name of the execution policy
         *
         * @var string
         */
        private $name;

        /**
         * The name of a supported runner instance
         *
         * @var string
         */
        private $runner;

        /**
         * The message to display when the policy is invoked
         *
         * @var string|null
         */
        private $message;

        /**
         * The execution process of the policy
         *
         * @var Execute
         */
        private $execute;

        /**
         * The configuration for exit handling
         *
         * @var ExitHandlers
         */
        private $exit_handlers;

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function getRunner(): string
        {
            return $this->runner;
        }

        /**
         * @param string $runner
         */
        public function setRunner(string $runner): void
        {
            $this->runner = $runner;
        }

        /**
         * @return string|null
         */
        public function getMessage(): ?string
        {
            return $this->message;
        }

        /**
         * @param string|null $message
         */
        public function setMessage(?string $message): void
        {
            $this->message = $message;
        }

        /**
         * @return Execute
         */
        public function getExecute(): Execute
        {
            return $this->execute;
        }

        /**
         * @param Execute $execute
         */
        public function setExecute(Execute $execute): void
        {
            $this->execute = $execute;
        }

        /**
         * @return ExitHandlers
         */
        public function getExitHandlers(): ExitHandlers
        {
            return $this->exit_handlers;
        }

        /**
         * @param ExitHandlers $exit_handlers
         */
        public function setExitHandlers(ExitHandlers $exit_handlers): void
        {
            $this->exit_handlers = $exit_handlers;
        }

        /**
         * @return bool
         */
        public function validate(): bool
        {
            // TODO: Implement validation method
            return true;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if ($this->name !== null && $this->name !== '')
            {
                $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;
            }

            if ($this->runner !== null && $this->runner !== '')
            {
                $results[($bytecode ? Functions::cbc('runner') : 'runner')] = $this->runner;
            }

            if ($this->message !== null && $this->message !== '')
            {
                $results[($bytecode ? Functions::cbc('message') : 'message')] = $this->message;
            }

            if ($this->execute !== null)
            {
                $results[($bytecode ? Functions::cbc('execute') : 'execute')] = $this->execute->toArray($bytecode);
            }

            if ($this->exit_handlers !== null)
            {
                $results[($bytecode ? Functions::cbc('exit_handlers') : 'exit_handlers')] = $this->exit_handlers->toArray($bytecode);

            }
            return $results;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExecutionPolicy
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->runner = Functions::array_bc($data, 'runner');
            $object->message = Functions::array_bc($data, 'message');
            $object->execute = Functions::array_bc($data, 'execute');
            $object->exit_handlers = Functions::array_bc($data, 'exit_handlers');

            if($object->execute !== null)
            {
                $object->execute = Execute::fromArray($object->execute);
            }

            if($object->exit_handlers !== null)
            {
                $object->exit_handlers = ExitHandlers::fromArray($object->exit_handlers);
            }

            return $object;
        }
    }