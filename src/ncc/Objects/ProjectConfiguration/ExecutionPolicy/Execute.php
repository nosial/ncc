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

    class Execute
    {
        /**
         * The target file to execute
         *
         * @var string
         */
        public $Target;

        /**
         * The working directory to execute the policy in, if not specified the
         * value "%CWD%" will be used as the default
         *
         * @var string|null
         */
        public $WorkingDirectory;

        /**
         * Options to pass to the process
         *
         * @var array
         */
        public $Options;

        /**
         * An array of environment variables to pass on to the process
         *
         * @var array|null
         */
        public $EnvironmentVariables;

        /**
         * Indicates if the output should be displayed or suppressed
         *
         * @var bool|null
         */
        public $Silent;

        /**
         * Indicates if the process should run in Tty mode (Overrides Silent & Pty mode)
         *
         * @var bool|null
         */
        public $Tty;

        /**
         * The number of seconds to wait before giving up on the process, will automatically execute the error handler
         * if one is set.
         *
         * @var int|null
         */
        public $Timeout;

        /**
         * @var int|null
         */
        public $IdleTimeout;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Tty = false;
            $this->Silent = false;
            $this->Timeout = null;
            $this->IdleTimeout = null;
            $this->WorkingDirectory = "%CWD%";
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->Target !== null)
                $results[($bytecode ? Functions::cbc("target") : "target")] = $this->Target;

            if($this->WorkingDirectory !== null)
                $results[($bytecode ? Functions::cbc("working_directory") : "working_directory")] = $this->WorkingDirectory;

            if($this->Options !== null)
                $results[($bytecode ? Functions::cbc("options") : "options")] = $this->Options;

            if($this->EnvironmentVariables !== null)
                $results[($bytecode ? Functions::cbc("environment_variables") : "environment_variables")] = $this->EnvironmentVariables;

            if($this->Silent !== null)
                $results[($bytecode ? Functions::cbc("silent") : "silent")] = (bool)$this->Silent;

            if($this->Tty !== null)
                $results[($bytecode ? Functions::cbc("tty") : "tty")] = (bool)$this->Tty;

            if($this->Timeout !== null)
                $results[($bytecode ? Functions::cbc("timeout") : "timeout")] = (int)$this->Timeout;

            if($this->IdleTimeout !== null)
                $results[($bytecode ? Functions::cbc("idle_timeout") : "idle_timeout")] = (int)$this->IdleTimeout;

            return $results;
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Execute
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Target = Functions::array_bc($data, 'target');
            $object->WorkingDirectory = Functions::array_bc($data, 'working_directory');
            $object->Options = Functions::array_bc($data, 'options');
            $object->EnvironmentVariables = Functions::array_bc($data, 'environment_variables');
            $object->Silent = Functions::array_bc($data, 'silent');
            $object->Tty = Functions::array_bc($data, 'tty');
            $object->Timeout = Functions::array_bc($data, 'timeout');
            $object->IdleTimeout = Functions::array_bc($data, 'idle_timeout');

            return $object;
        }
    }