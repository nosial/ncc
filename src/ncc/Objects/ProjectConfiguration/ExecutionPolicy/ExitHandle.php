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

    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class ExitHandle implements BytecodeObjectInterface
    {
        /**
         * The name of another execution policy to execute (optionally) when this exit handle is triggered
         *
         * @var string
         */
        private $run;

        /**
         * The exit code that needs to be returned from the process to trigger this handle
         *
         * @var int
         */
        private $exit_code;

        /**
         * The message to display when the handle is triggered
         *
         * @var string|null
         */
        private $message;

        /**
         * Indicates if the process should exit if the handle is triggered,
         * by default NCC will choose the applicable value for this property,
         * for instance; if the exit handle is registered for "error", the
         * property will be set to true, otherwise for "success" and "warning"
         * the property will be false.
         *
         * @var bool
         */
        private $end_process;

        public function __construct(string $run, int $exit_code=0)
        {
            $this->run = $run;
            $this->exit_code = $exit_code;
            $this->end_process = false;
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
         * @return bool
         */
        public function getEndProcess(): bool
        {
            return $this->end_process;
        }

        /**
         * @param bool $end_process
         */
        public function setEndProcess(bool $end_process): void
        {
            $this->end_process = $end_process;
        }

        /**
         * @return string|null
         */
        public function getRun(): ?string
        {
            return $this->run;
        }

        /**
         * @param string|null $run
         */
        public function setRun(?string $run): void
        {
            $this->run = $run;
        }

        /**
         * @return int
         */
        public function getExitCode(): int
        {
            return $this->exit_code;
        }

        /**
         * @param int $exit_code
         */
        public function setExitCode(int $exit_code): void
        {
            $this->exit_code = $exit_code;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->message !== null)
            {
                $results[($bytecode ? Functions::cbc('message') : 'message')] = $this->message;
            }

            if($this->end_process !== null)
            {
                $results[($bytecode ? Functions::cbc('end_process') : 'end_process')] = $this->end_process;
            }

            if($this->run !== null)
            {
                $results[($bytecode ? Functions::cbc('run') : 'run')] = $this->run;
            }

            /** @noinspection PhpCastIsUnnecessaryInspection */
            $results[($bytecode ? Functions::cbc('exit_code') : 'exit_code')] = (int)$this->exit_code;

            return $results;
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): ExitHandle
        {
            $run = Functions::array_bc($data, 'run');
            if($run === null)
            {
                throw new ConfigurationException('Exit handle "run" property is required');
            }

            $object = new self($run, (Functions::array_bc($data, 'exit_code') ?? 0));

            $object->end_process = Functions::array_bc($data, 'end_process') ?? false;
            $object->message = Functions::array_bc($data, 'message');
            $object->run = Functions::array_bc($data, 'run');

            return $object;
        }
    }