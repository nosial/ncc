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

    use ncc\Enums\SpecialConstants\RuntimeConstants;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Execute implements BytecodeObjectInterface
    {
        /**
         * The target file to execute
         *
         * @var string
         */
        private $target;

        /**
         * The working directory to execute the policy in, if not specified the
         * value "%CWD%" will be used as the default
         *
         * @var string
         */
        private $working_directory;

        /**
         * Options to pass to the process
         *
         * @var array
         */
        private $options;

        /**
         * An array of environment variables to pass on to the process
         *
         * @var array
         */
        private $environment_variables;

        /**
         * Indicates if the output should be displayed or suppressed
         *
         * @var bool
         */
        private $silent;

        /**
         * Indicates if the process should run in Tty mode (Overrides Silent & Pty mode)
         *
         * @var bool
         */
        private $tty;

        /**
         * The number of seconds to wait before giving up on the process, will automatically execute the error handler
         * if one is set.
         *
         * @var int|null
         */
        private $timeout;

        /**
         * @var int|null
         */
        private $idle_timeout;

        /**
         * Public Constructor
         */
        public function __construct(string $target, ?string $working_directory)
        {
            $this->target = $target;
            $this->working_directory = $working_directory ?? RuntimeConstants::CWD;
            $this->options = [];
            $this->environment_variables = [];
            $this->silent = false;
            $this->tty = true;
        }

        /**
         * Gets the target file to execute
         *
         * @return string
         */
        public function getTarget(): string
        {
            return $this->target;
        }

        /**
         * Sets the target file to execute
         *
         * @param string $target
         */
        public function setTarget(string $target): void
        {
            $this->target = $target;
        }

        /**
         * Returns the working directory to execute the policy in, if not specified the value "%CWD%" will be used as
         * the default
         *
         * @return string
         */
        public function getWorkingDirectory(): string
        {
            return $this->working_directory ?? RuntimeConstants::CWD;
        }

        /**
         * Sets the working directory to execute the policy in, if not specified, the value "%CWD%" will be used as
         * the default
         *
         * @param string|null $working_directory
         */
        public function setWorkingDirectory(?string $working_directory): void
        {
            $this->working_directory = $working_directory ?? RuntimeConstants::CWD;
        }

        /**
         * @return array
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * @param array $options
         */
        public function setOptions(array $options): void
        {
            $this->options = $options;
        }

        /**
         * @return array
         */
        public function getEnvironmentVariables(): array
        {
            return $this->environment_variables;
        }

        /**
         * @param array $environment_variables
         */
        public function setEnvironmentVariables(array $environment_variables): void
        {
            $this->environment_variables = $environment_variables;
        }

        /**
         * @return bool
         */
        public function isSilent(): bool
        {
            return $this->silent;
        }

        /**
         * @param bool $silent
         */
        public function setSilent(bool $silent): void
        {
            $this->silent = $silent;
        }

        /**
         * @return bool
         */
        public function isTty(): bool
        {
            return $this->tty;
        }

        /**
         * @param bool $tty
         */
        public function setTty(bool $tty): void
        {
            $this->tty = $tty;
        }

        /**
         * @return int|null
         */
        public function getTimeout(): ?int
        {
            return $this->timeout;
        }

        /**
         * @param int|null $timeout
         */
        public function setTimeout(?int $timeout): void
        {
            $this->timeout = $timeout;
        }

        /**
         * @return int|null
         */
        public function getIdleTimeout(): ?int
        {
            return $this->idle_timeout;
        }

        /**
         * @param int|null $idle_timeout
         */
        public function setIdleTimeout(?int $idle_timeout): void
        {
            $this->idle_timeout = $idle_timeout;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            $results[($bytecode ? Functions::cbc('working_directory') : 'working_directory')] = $this->working_directory;
            $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;
            $results[($bytecode ? Functions::cbc('environment_variables') : 'environment_variables')] = $this->environment_variables;
            $results[($bytecode ? Functions::cbc('silent') : 'silent')] = (bool)$this->silent;
            $results[($bytecode ? Functions::cbc('tty') : 'tty')] = (bool)$this->tty;
            $results[($bytecode ? Functions::cbc('timeout') : 'timeout')] = (int)$this->timeout;
            $results[($bytecode ? Functions::cbc('idle_timeout') : 'idle_timeout')] = (int)$this->idle_timeout;

            if($this->target !== null)
            {
                $results[($bytecode ? Functions::cbc('target') : 'target')] = $this->target;
            }

            return $results;
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Execute
        {
            $target = Functions::array_bc($data, 'target');

            if($target === null)
            {
                throw new ConfigurationException("The ExecutionPolicy's Execute target is required");
            }

            $object = new self($target, Functions::array_bc($data, 'working_directory'));

            $object->options = Functions::array_bc($data, 'options') ?? [];
            $object->environment_variables = Functions::array_bc($data, 'environment_variables') ?? [];
            $object->silent = Functions::array_bc($data, 'silent') ?? false;
            $object->tty = Functions::array_bc($data, 'tty') ?? true;
            $object->timeout = Functions::array_bc($data, 'timeout');
            $object->idle_timeout = Functions::array_bc($data, 'idle_timeout');

            return $object;
        }
    }