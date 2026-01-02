<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Abstracts;

    use InvalidArgumentException;
    use ncc\Enums\AuthenticationType;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\Authentication\AccessToken;
    use ncc\Objects\Authentication\UsernamePassword;

    abstract class AbstractAuthentication implements SerializableInterface
    {
        private AuthenticationType $type;

        /**
         * Public Constructor
         *
         * @param AuthenticationType $type The authentication type of this authentication entry
         */
        public function __construct(AuthenticationType $type)
        {
            $this->type = $type;
        }

        /**
         * Get the authentication type of this entry
         *
         * @return AuthenticationType The authentication type
         */
        public function getType() : AuthenticationType
        {
            return $this->type;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): SerializableInterface
        {
            if(!isset($data['type']))
            {
                throw new InvalidArgumentException('Authentication data must contain a type field');
            }

            return match(AuthenticationType::from($data['type']))
            {
                AuthenticationType::USERNAME_PASSWORD => UsernamePassword::fromArray($data),
                AuthenticationType::ACCESS_TOKEN => AccessToken::fromArray($data),
            };
        }
    }