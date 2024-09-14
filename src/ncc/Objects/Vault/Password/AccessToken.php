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

    class AccessToken implements AuthenticationInterface
    {
        /**
         * The entry's access token
         * 
         * @var string
         */
        private $access_token;

        /**
         * Public constructor
         *
         * @param string $access_token
         */
        public function __construct(string $access_token)
        {
            $this->access_token = $access_token;
        }

        /**
         * @param string $AccessToken
         */
        public function setAccessToken(string $AccessToken): void
        {
            $this->access_token = $AccessToken;
        }

        /**
         * @return string
         */
        public function getAccessToken(): string
        {
            return $this->access_token;
        }

        /**
         * @inheritDoc
         */
        public function getAuthenticationType(): string
        {
            // TODO: Could return the enum case here
            return AuthenticationType::ACCESS_TOKEN->value;
        }

        /**
         * Returns a string representation of the object
         *
         * @return string
         */
        public function __toString(): string
        {
            return $this->access_token;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('authentication_type') : 'authentication_type') => AuthenticationType::ACCESS_TOKEN->value,
                ($bytecode ? Functions::cbc('access_token') : 'access_token') => $this->access_token,
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): AccessToken
        {
            $access_token = Functions::array_bc($data, 'access_token');

            if($access_token === null)
            {
                throw new ConfigurationException('Missing access token');
            }

            return new self($access_token);
        }
    }