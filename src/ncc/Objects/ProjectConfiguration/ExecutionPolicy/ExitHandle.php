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

    class ExitHandle
    {
        /**
         * The message to display when the handle is triggered
         *
         * @var string|null
         */
        public $Message;

        /**
         * Indicates if the process should exit if the handle is triggered,
         * by default NCC will choose the applicable value for this property,
         * for instance; if the exit handle is registered for "error", the
         * property will be set to true, otherwise for "success" and "warning"
         * the property will be false.
         *
         * @var bool|null
         */
        public $EndProcess;

        /**
         * The name of another execution policy to execute (optionally) when this exit handle is triggered
         *
         * @var string|null
         */
        public $Run;

        /**
         * The exit code that needs to be returned from the process to trigger this handle
         *
         * @var int
         */
        public $ExitCode;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $return_results = [];

            if($this->Message !== null)
                $return_results[($bytecode ? Functions::cbc('message') : 'message')] = $this->Message;

            if($this->EndProcess !== null)
                $return_results[($bytecode ? Functions::cbc('end_process') : 'end_process')] = $this->EndProcess;

            if($this->Run !== null)
                $return_results[($bytecode ? Functions::cbc('run') : 'run')] = $this->Run;

            /** @noinspection PhpCastIsUnnecessaryInspection */
            $return_results[($bytecode ? Functions::cbc('exit_code') : 'exit_code')] = (int)$this->ExitCode;

            return $return_results;
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return ExitHandle
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Message = Functions::array_bc($data, 'message');
            $object->EndProcess = Functions::array_bc($data, 'end_process');
            $object->Run = Functions::array_bc($data, 'run');
            $object->ExitCode = Functions::array_bc($data, 'exit_code');

            return $object;
        }
    }