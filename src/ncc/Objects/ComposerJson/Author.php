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

    namespace ncc\Objects\ComposerJson;

    use ncc\Interfaces\SerializableObjectInterface;

    class Author implements SerializableObjectInterface
    {
        /**
         * The author's name. Usually their real name
         *
         * @var string|null
         */
        private $name;

        /**
         * The author's email address
         *
         * @var string|null
         */
        private $email;

        /**
         * URL to the author's website
         *
         * @var string|null
         */
        private $homepage;

        /**
         * The author's role in the project (eg. developer or translator)
         *
         * @var string|null
         */
        private $role;

        /**
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * @return string|null
         */
        public function getEmail(): ?string
        {
            return $this->email;
        }

        /**
         * @return string|null
         */
        public function getHomepage(): ?string
        {
            return $this->homepage;
        }

        /**
         * @return string|null
         */
        public function getRole(): ?string
        {
            return $this->role;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'email' => $this->email,
                'homepage' => $this->homepage,
                'role' => $this->role
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Author
        {
            $object = new self();

            if(isset($data['name']))
            {
                $object->name = $data['name'];
            }

            if(isset($data['email']))
            {
                $object->email = $data['email'];
            }

            if(isset($data['homepage']))
            {
                $object->homepage = $data['homepage'];
            }

            if(isset($data['role']))
            {
                $object->role = $data['role'];
            }

            return $object;
        }
    }