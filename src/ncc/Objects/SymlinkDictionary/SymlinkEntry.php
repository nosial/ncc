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

    namespace ncc\Objects\SymlinkDictionary;

    use ncc\Utilities\Functions;

    class SymlinkEntry
    {
        /**
         * The name of the package that the symlink is for
         *
         * @var string
         */
        public $Package;

        /**
         * The name of the execution policy to execute
         *
         * @var string
         */
        public $ExecutionPolicyName;

        /**
         * Indicates if this symlink is currently registered by NCC
         *
         * @var bool
         */
        public $Registered;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->ExecutionPolicyName = 'main';
            $this->Registered = false;
        }

        /**
         * Returns a string representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('package') : 'package') => $this->Package,
                ($bytecode ? Functions::cbc('registered') : 'registered') => $this->Registered,
                ($bytecode ? Functions::cbc('execution_policy_name') : 'execution_policy_name') => $this->ExecutionPolicyName
            ];
        }

        /**
         * Constructs a new SymlinkEntry from an array representation
         *
         * @param array $data
         * @return SymlinkEntry
         */
        public static function fromArray(array $data): SymlinkEntry
        {
            $entry = new SymlinkEntry();

            $entry->Package = Functions::array_bc($data, 'package');
            $entry->Registered = (bool)Functions::array_bc($data, 'registered');
            $entry->ExecutionPolicyName = Functions::array_bc($data, 'execution_policy_name');

            return $entry;
        }

    }