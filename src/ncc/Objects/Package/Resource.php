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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Resource implements BytecodeObjectInterface
    {
        /**
         * The file/path name of the resource
         *
         * @var string
         */
        private $name;

        /**
         * A sha1 hash checksum of the resource, this will be compared against the data to determine
         * the integrity of the resource to ensure that the resource is not corrupted.
         *
         * @var string
         */
        private $checksum;

        /**
         * The raw data of the resource
         *
         * @var string
         */
        private $data;

        /**
         * Validates the checksum of the resource, returns false if the checksum or data is invalid or if the checksum
         * failed.
         *
         * @return bool
         */
        public function validateChecksum(): bool
        {
            if($this->checksum === null)
            {
                return false;
            }

            if($this->data === null)
            {
                return false;
            }

            return hash_equals($this->checksum, hash('sha1', $this->data, true));
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
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function getChecksum(): string
        {
            return $this->checksum;
        }

        /**
         * @param string $checksum
         */
        public function setChecksum(string $checksum): void
        {
            $this->checksum = $checksum;
        }

        /**
         * @return string
         */
        public function getData(): string
        {
            return $this->data;
        }

        /**
         * @param string $data
         */
        public function setData(string $data): void
        {
            $this->data = $data;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->name,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->data,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->checksum = Functions::array_bc($data, 'checksum');
            $object->data = Functions::array_bc($data, 'data');

            return $object;
        }
    }