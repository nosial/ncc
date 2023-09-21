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

    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Repository implements BytecodeObjectInterface
    {
        /**
         * The name of the remote source to add
         *
         * @var string
         */
        private $name;

        /**
         * The type of client that is used to connect to the remote source
         *
         * @var string|null
         */
        private $type;

        /**
         * The host of the remote source
         *
         * @var string
         */
        private $host;

        /**
         * If SSL should be used
         *
         * @var bool
         */
        private $ssl;

        public function __construct(string $name, string $host, ?string $type=null, bool $ssl=false)
        {
            $this->name = $name;
            $this->host = $host;
            $this->type = $type;
            $this->ssl = $ssl;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return string|null
         */
        public function getType(): ?string
        {
            return $this->type;
        }

        /**
         * @param string|null $type
         */
        public function setType(?string $type): void
        {
            $this->type = $type;
        }

        /**
         * @return string
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * @param string $host
         */
        public function setHost(string $host): void
        {
            $this->host = $host;
        }

        /**
         * @return bool
         */
        public function isSsl(): bool
        {
            return $this->ssl;
        }

        /**
         * @param bool $ssl
         */
        public function setSsl(bool $ssl): void
        {
            $this->ssl = $ssl;
        }

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
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Repository
        {
            $name = Functions::array_bc($data, 'name');
            $type = Functions::array_bc($data, 'type');
            $host = Functions::array_bc($data, 'host');
            $ssl = Functions::array_bc($data, 'ssl') ?? false;

            if($name === null)
            {
                throw new ConfigurationException("The UpdateSource's Repository property requires 'main'");
            }

            if($type === null)
            {
                throw new ConfigurationException("The UpdateSource's Repository property requires 'type'");
            }

            if($host === null)
            {
                throw new ConfigurationException("The UpdateSource's Repository property requires 'host'");
            }

            return new self($name, $host, $type, $ssl);
        }
    }