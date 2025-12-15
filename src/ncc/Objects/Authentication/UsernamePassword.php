<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\Objects\Authentication;

    use ncc\Abstracts\AbstractAuthentication;
    use ncc\Enums\AuthenticationType;
    use ncc\Interfaces\SerializableInterface;

    class UsernamePassword extends AbstractAuthentication
    {
        private string $username;
        private string $password;

        /**
         * Public Constructor
         *
         * @param string $username The username
         * @param string $password The password
         */
        public function __construct(string $username, string $password)
        {
            parent::__construct(AuthenticationType::USERNAME_PASSWORD);
            $this->username = $username;
            $this->password = $password;
        }

        /**
         * Get the username
         *
         * @return string
         */
        public function getUsername(): string
        {
            return $this->username;
        }

        /**
         * Get the password
         *
         * @return string
         */
        public function getPassword(): string
        {
            return $this->password;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'type' => $this->getType()->value,
                'username' => $this->username,
                'password' => $this->password
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): UsernamePassword
        {
            return new UsernamePassword($data['username'], $data['password']);
        }
    }