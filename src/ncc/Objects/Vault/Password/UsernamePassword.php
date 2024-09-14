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

    use ncc\Enums\Types\AuthenticationType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\AuthenticationInterface;
    use ncc\Utilities\Functions;

    class UsernamePassword implements AuthenticationInterface
    {
        /**
         * The entry's username
         *
         * @var string
         */
        private $username;

        /**
         * The entry's password
         *
         * @var string
         */
        private $password;

        /**
         * Public constructor
         *
         * @param string $username
         * @param string $password
         */
        public function __construct(string $username, string $password)
        {
            $this->username = $username;
            $this->password = $password;
        }

        /**
         * @return string
         * @noinspection PhpUnused
         */
        public function getUsername(): string
        {
            return $this->username;
        }

        /**
         * @param string $username
         */
        public function setUsername(string $username): void
        {
            $this->username = $username;
        }

        /**
         * @return string
         * @noinspection PhpUnused
         */
        public function getPassword(): string
        {
            return $this->password;
        }

        /**
         * @param string $password
         */
        public function setPassword(string $password): void
        {
            $this->password = $password;
        }

        /**
         * @inheritDoc
         */
        public function getAuthenticationType(): string
        {
            // TODO: Could return the enum here
            return AuthenticationType::USERNAME_PASSWORD->value;
        }

        /**
         * Returns a string representation of the object
         *
         * @return string
         */
        public function __toString(): string
        {
            return sprintf('%s:%s', $this->username, $this->password);
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('authentication_type') : 'authentication_type') => AuthenticationType::USERNAME_PASSWORD->value,
                ($bytecode ? Functions::cbc('username') : 'username') => $this->username,
                ($bytecode ? Functions::cbc('password') : 'password') => $this->password,
            ];
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): self
        {
            $username = Functions::array_bc($data, 'username');
            $password = Functions::array_bc($data, 'password');

            if($username === null)
            {
                throw new ConfigurationException('Missing username');
            }

            if($password === null)
            {
                throw new ConfigurationException('Missing password');
            }

            return new self($username, $password);
        }
    }