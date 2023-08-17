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

    use ncc\Utilities\Functions;

    class Component
    {
        /**
         * The name of the component or the file name of the component
         *
         * @var string
         */
        public $name;

        /**
         * Flags associated with the component created by the compiler extension
         *
         * @var array
         */
        public $flags;

        /**
         * The data type of the component
         *
         * @var string
         */
        public $data_types;

        /**
         * A sha1 hash checksum of the component, this will be compared against the data to determine
         * the integrity of the component to ensure that the component is not corrupted.
         *
         * @var string
         */
        private $checksum;

        /**
         * The raw data of the component, this is to be processed by the compiler extension
         *
         * @var mixed
         */
        public $data;

        /**
         * Validates the checksum of the component, returns false if the checksum or data is invalid or if the checksum
         * failed.
         *
         * @return bool
         */
        public function validate_checksum(): bool
        {
            if($this->checksum === null)
            {
                return true; // Return true if the checksum is empty
            }

            if($this->data === null)
            {
                return true; // Return true if the data is null
            }

            if(hash('sha1', $this->data, true) !== $this->checksum)
            {
                return false; // Return false if the checksum failed
            }

            return true;
        }

        /**
         * Updates the checksum of the resource
         *
         * @return void
         */
        public function updateChecksum(): void
        {
            $this->checksum = null;

            if(is_string($this->data))
            {
                $this->checksum = hash('sha1', $this->data, true);
            }
        }

        /**
         * Returns an array representation of the component.
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->name,
                ($bytecode ? Functions::cbc('flags') : 'flags') => $this->flags,
                ($bytecode ? Functions::cbc('data_type') : 'data_type') => $this->data_types,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->data,
            ];
        }

        /**
         * Constructs a new object from an array representation
         *
         * @param array $data
         * @return Component
         */
        public static function fromArray(array $data): self
        {
            $Object = new self();

            $Object->name = Functions::array_bc($data, 'name');
            $Object->flags = Functions::array_bc($data, 'flags');
            $Object->data_types = Functions::array_bc($data, 'data_type');
            $Object->checksum = Functions::array_bc($data, 'checksum');
            $Object->data = Functions::array_bc($data, 'data');

            return $Object;
        }
    }