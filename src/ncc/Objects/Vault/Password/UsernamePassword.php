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

    namespace ncc\Objects\Vault\Password;

    use ncc\Abstracts\AuthenticationType;
    use ncc\Interfaces\PasswordInterface;
    use ncc\Utilities\Functions;

    class UsernamePassword implements PasswordInterface
    {
        /**
         * The entry's username
         *
         * @var string
         */
        private $Username;

        /**
         * The entry's password
         *
         * @var string
         */
        private $Password;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('authentication_type') : 'authentication_type') => AuthenticationType::UsernamePassword,
                ($bytecode ? Functions::cbc('username') : 'username') => $this->Username,
                ($bytecode ? Functions::cbc('password') : 'password') => $this->Password,
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $instance = new self();

            $instance->Username = Functions::array_bc($data, 'username');
            $instance->Password = Functions::array_bc($data, 'password');

            return $instance;
        }

        /**
         * @return string
         * @noinspection PhpUnused
         */
        public function getUsername(): string
        {
            return $this->Username;
        }

        /**
         * @return string
         * @noinspection PhpUnused
         */
        public function getPassword(): string
        {
            return $this->Password;
        }

        /**
         * @inheritDoc
         */
        public function getAuthenticationType(): string
        {
            return AuthenticationType::UsernamePassword;
        }

        /**
         * Returns a string representation of the object
         *
         * @return string
         */
        public function __toString(): string
        {
            return $this->Password;
        }

        /**
         * @param string $Username
         */
        public function setUsername(string $Username): void
        {
            $this->Username = $Username;
        }

        /**
         * @param string $Password
         */
        public function setPassword(string $Password): void
        {
            $this->Password = $Password;
        }
    }