<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Objects;

    use ncc\Exceptions\IntegrityException;
    use ncc\Utilities\Resolver;

    class Constant
    {
        /**
         * The unique hash of the constant
         *
         * @var string
         */
        private $hash;

        /**
         * The package that manages this constant
         *
         * @var string
         */
        private $scope;

        /**
         * The name of the constant
         *
         * @var string
         */
        private $name;

        /**
         * The value of the constant
         *
         * @var string
         */
        private $value;

        /**
         * Indicates if the constant is readonly or not
         *
         * @var bool
         */
        private $readonly;

        /**
         * Public Constructor
         *
         * @param string $scope
         * @param string $name
         * @param string $value
         * @param bool $readonly
         */
        public function __construct(string $scope, string $name, string $value, bool $readonly=false)
        {
            $this->scope = $scope;
            $this->name = $name;
            $this->value = $value;
            $this->readonly = $readonly;
            $this->hash = Resolver::resolveConstantHash($this->scope, $this->name);
        }

        /**
         * Returns the constant value
         *
         * @return string
         */
        public function __toString(): string
        {
            return $this->value;
        }

        /**
         * @return string
         */
        public function getValue(): string
        {
            return $this->value;
        }

        /**
         * Gets the full name of the constant
         *
         * @return string
         */
        public function getFullName(): string
        {
            return Resolver::resolveFullConstantName($this->scope, $this->name);
        }

        /**
         * @param string $value
         * @param bool $readonly
         * @throws IntegrityException
         */
        public function setValue(string $value, bool $readonly=false): void
        {
            if($this->readonly)
            {
                throw new IntegrityException('Cannot set value to the constant \'' .  $this->getFullName() .  '\', constant is readonly');
            }

            $this->value = $value;
            $this->readonly = $readonly;
        }

        /**
         * @return bool
         */
        public function isReadonly(): bool
        {
            return $this->readonly;
        }

        /**
         * @return string
         */
        public function getHash(): string
        {
            return $this->hash;
        }

        /**
         * @return string
         */
        public function getScope(): string
        {
            return $this->scope;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }
    }