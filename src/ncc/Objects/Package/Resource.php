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

    class Resource
    {
        /**
         * The file/path name of the resource
         *
         * @var string
         */
        public $Name;

        /**
         * A sha1 hash checksum of the resource, this will be compared against the data to determine
         * the integrity of the resource to ensure that the resource is not corrupted.
         *
         * @var string
         */
        private $Checksum;

        /**
         * The raw data of the resource
         *
         * @var string
         */
        public $Data;

        /**
         * Validates the checksum of the resource, returns false if the checksum or data is invalid or if the checksum
         * failed.
         *
         * @return bool
         */
        public function validateChecksum(): bool
        {
            if($this->Checksum === null)
                return false;

            if($this->Data === null)
                return false;

            if(hash('sha1', $this->Data, true) !== $this->Checksum)
                return false;

            return true;
        }

        /**
         * Updates the checksum of the resource
         *
         * @return void
         */
        public function updateChecksum(): void
        {
            $this->Checksum = null;

            if(gettype($this->Data) == 'string')
            {
                $this->Checksum = hash('sha1', $this->Data, true);
            }
        }

        /**
         * Returns an array representation of the resource.
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->Name,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->Checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->Data,
            ];
        }

        /**
         * Constructs a new object from an array representation
         *
         * @param array $data
         * @return Resource
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Name = Functions::array_bc($data, 'name');
            $object->Checksum = Functions::array_bc($data, 'checksum');
            $object->Data = Functions::array_bc($data, 'data');

            return $object;
        }
    }