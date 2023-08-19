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

    class Execute implements BytecodeObjectInterface
    {
        /**
         * The target file to execute
         *
         * @var string
         */
        public $target;

        /**
         * The working directory to execute the policy in, if not specified the
         * value "%CWD%" will be used as the default
         *
         * @var string|null
         */
        public $working_directory;

        /**
         * Options to pass to the process
         *
         * @var array
         */
        public $options;

        /**
         * An array of environment variables to pass on to the process
         *
         * @var array|null
         */
        public $environment_variables;

        /**
         * Indicates if the output should be displayed or suppressed
         *
         * @var bool|null
         */
        public $silent;

        /**
         * Indicates if the process should run in Tty mode (Overrides Silent & Pty mode)
         *
         * @var bool|null
         */
        public $tty;

        /**
         * The number of seconds to wait before giving up on the process, will automatically execute the error handler
         * if one is set.
         *
         * @var int|null
         */
        public $timeout;

        /**
         * @var int|null
         */
        public $idle_timeout;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->tty = false;
            $this->silent = false;
            $this->timeout = null;
            $this->idle_timeout = null;
            $this->working_directory = "%CWD%";
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->target !== null)
            {
                $results[($bytecode ? Functions::cbc('target') : 'target')] = $this->target;
            }

            if($this->working_directory !== null)
            {
                $results[($bytecode ? Functions::cbc('working_directory') : 'working_directory')] = $this->working_directory;
            }

            if($this->options !== null)
            {
                $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;
            }

            if($this->environment_variables !== null)
            {
                $results[($bytecode ? Functions::cbc('environment_variables') : 'environment_variables')] = $this->environment_variables;
            }

            if($this->silent !== null)
            {
                $results[($bytecode ? Functions::cbc('silent') : 'silent')] = (bool)$this->silent;
            }

            if($this->tty !== null)
            {
                $results[($bytecode ? Functions::cbc('tty') : 'tty')] = (bool)$this->tty;
            }

            if($this->timeout !== null)
            {
                $results[($bytecode ? Functions::cbc('timeout') : 'timeout')] = (int)$this->timeout;
            }

            if($this->idle_timeout !== null)
            {
                $results[($bytecode ? Functions::cbc('idle_timeout') : 'idle_timeout')] = (int)$this->idle_timeout;
            }

            return $results;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Execute
        {
            $object = new self();

            $object->target = Functions::array_bc($data, 'target');
            $object->working_directory = Functions::array_bc($data, 'working_directory');
            $object->options = Functions::array_bc($data, 'options');
            $object->environment_variables = Functions::array_bc($data, 'environment_variables');
            $object->silent = Functions::array_bc($data, 'silent');
            $object->tty = Functions::array_bc($data, 'tty');
            $object->timeout = Functions::array_bc($data, 'timeout');
            $object->idle_timeout = Functions::array_bc($data, 'idle_timeout');

            return $object;
        }
    }