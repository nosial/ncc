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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class SymlinkEntry implements BytecodeObjectInterface
    {
        /**
         * The name of the package that the symlink is for
         *
         * @var string
         */
        private $package;

        /**
         * The name of the execution policy to execute
         *
         * @var string
         */
        private $execution_policy_name;

        /**
         * Indicates if this symlink is currently registered by NCC
         *
         * @var bool
         */
        private $registered;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->execution_policy_name = 'main';
            $this->registered = false;
        }

        /**
         * @return string
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * @param string $package
         */
        public function setPackage(string $package): void
        {
            $this->package = $package;
        }

        /**
         * @return string
         */
        public function getExecutionPolicyName(): string
        {
            return $this->execution_policy_name;
        }

        /**
         * @param string $execution_policy_name
         */
        public function setExecutionPolicyName(string $execution_policy_name): void
        {
            $this->execution_policy_name = $execution_policy_name;
        }

        /**
         * @return bool
         */
        public function isRegistered(): bool
        {
            return $this->registered;
        }

        /**
         * @param bool $registered
         */
        public function setRegistered(bool $registered): void
        {
            $this->registered = $registered;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('package') : 'package') => $this->package,
                ($bytecode ? Functions::cbc('registered') : 'registered') => $this->registered,
                ($bytecode ? Functions::cbc('execution_policy_name') : 'execution_policy_name') => $this->execution_policy_name
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): SymlinkEntry
        {
            $entry = new SymlinkEntry();

            $entry->package = Functions::array_bc($data, 'package');
            $entry->registered = (bool)Functions::array_bc($data, 'registered');
            $entry->execution_policy_name = Functions::array_bc($data, 'execution_policy_name');

            return $entry;
        }

    }