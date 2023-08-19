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

    namespace ncc\Objects;

    use ncc\Enums\DefinedRemoteSourceType;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class DefinedRemoteSource implements BytecodeObjectInterface
    {
        /**
         * The unique name of the remote source. (e.g. 'github')
         * Allows packages to be fetched using the name of the remote source.
         * eg: 'vendor/package:master@custom_source'
         *
         * @var string
         */
        public $name;

        /**
         * The type of service NCC should use with this source (gitlab, github, etc...).
         *
         * @var string|DefinedRemoteSourceType
         */
        public $type;

        /**
         * The host of the service NCC should use with this source (gitlab.com, github.com, git.example.com:8080 etc...).
         *
         * @var string
         */
        public $host;

        /**
         * If SSL should be used when connecting to the service
         *
         * @var bool
         */
        public $ssl;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
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
         * Constructs object from an array representation.
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $definedRemoteSource = new self();

            $definedRemoteSource->name = Functions::array_bc($data, 'name');
            $definedRemoteSource->type = Functions::array_bc($data, 'type');
            $definedRemoteSource->host = Functions::array_bc($data, 'host');
            $definedRemoteSource->ssl = Functions::array_bc($data, 'ssl');

            return $definedRemoteSource;
        }
    }