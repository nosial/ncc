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

    namespace ncc\Objects\ProjectConfiguration\UpdateSource;

    use ncc\Utilities\Functions;

    class Repository
    {
        /**
         * The name of the remote source to add
         *
         * @var string
         */
        public $Name;

        /**
         * The type of client that is used to connect to the remote source
         *
         * @var string|null
         */
        public $Type;

        /**
         * The host of the remote source
         *
         * @var string
         */
        public $Host;

        /**
         * If SSL should be used
         *
         * @var bool
         */
        public $SSL;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->Name,
                ($bytecode ? Functions::cbc('type') : 'type') => $this->Type,
                ($bytecode ? Functions::cbc('host') : 'host') => $this->Host,
                ($bytecode ? Functions::cbc('ssl') : 'ssl') => $this->SSL
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Repository
         */
        public static function fromArray(array $data): self
        {
            $obj = new self();
            $obj->Name = Functions::array_bc($data, 'name');
            $obj->Type = Functions::array_bc($data, 'type');
            $obj->Host = Functions::array_bc($data, 'host');
            $obj->SSL = Functions::array_bc($data, 'ssl');
            return $obj;
        }
    }