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

    class ExitHandle implements BytecodeObjectInterface
    {
        /**
         * The message to display when the handle is triggered
         *
         * @var string|null
         */
        public $message;

        /**
         * Indicates if the process should exit if the handle is triggered,
         * by default NCC will choose the applicable value for this property,
         * for instance; if the exit handle is registered for "error", the
         * property will be set to true, otherwise for "success" and "warning"
         * the property will be false.
         *
         * @var bool|null
         */
        public $end_process;

        /**
         * The name of another execution policy to execute (optionally) when this exit handle is triggered
         *
         * @var string|null
         */
        public $run;

        /**
         * The exit code that needs to be returned from the process to trigger this handle
         *
         * @var int
         */
        public $exit_code;

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
         */
        public static function fromArray(array $data): ExitHandle
        {
            $object = new self();

            $object->message = Functions::array_bc($data, 'message');
            $object->end_process = Functions::array_bc($data, 'end_process');
            $object->run = Functions::array_bc($data, 'run');
            $object->exit_code = Functions::array_bc($data, 'exit_code');

            return $object;
        }
    }