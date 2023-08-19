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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Repository implements BytecodeObjectInterface
    {
        /**
         * The name of the remote source to add
         *
         * @var string
         */
        public $name;

        /**
         * The type of client that is used to connect to the remote source
         *
         * @var string|null
         */
        public $type;

        /**
         * The host of the remote source
         *
         * @var string
         */
        public $host;

        /**
         * If SSL should be used
         *
         * @var bool
         */
        public $ssl;

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->name,
                ($bytecode ? Functions::cbc('type') : 'type') => $this->type,
                ($bytecode ? Functions::cbc('host') : 'host') => $this->host,
                ($bytecode ? Functions::cbc('ssl') : 'ssl') => $this->ssl
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Repository
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->type = Functions::array_bc($data, 'type');
            $object->host = Functions::array_bc($data, 'host');
            $object->ssl = Functions::array_bc($data, 'ssl');

            return $object;
        }
    }