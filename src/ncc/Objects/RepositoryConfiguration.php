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

    namespace ncc\Objects;

    use ncc\Abstracts\AbstractAuthentication;
    use ncc\Abstracts\AbstractRepository;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;

    class RepositoryConfiguration implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private RepositoryType $type;
        private string $host;
        private bool $ssl;

        /**
         * RepositoryConfiguration constructor.
         *
         * @param string $name The name of the repository configuration
         * @param RepositoryType $type The type of the repository (e.g., GITHUB, GITLAB)
         * @param string $host The host URL of the repository (e.g., "github.com")
         * @param bool $ssl Whether to use SSL (HTTPS) for connections. Default is true.
         */
        public function __construct(string $name, RepositoryType $type, string $host, bool $ssl=true)
        {
            $this->name = $name;
            $this->type = $type;
            $this->host = $host;
            $this->ssl = $ssl;
        }

        /**
         * Gets the name of the repository configuration.
         *
         * @return string The name of the repository configuration.
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Gets the type of the repository.
         *
         * @return RepositoryType The type of the repository.
         */
        public function getType(): RepositoryType
        {
            return $this->type;
        }

        /**
         * Gets the host URL of the repository.
         *
         * @return string The host URL of the repository.
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Checks if SSL (HTTPS) is enabled for connections to the repository.
         *
         * @return bool True if SSL is enabled, false otherwise.
         */
        public function isSslEnabled(): bool
        {
            return $this->ssl;
        }

        /**
         * Gets the base URL of the repository, including the protocol (http or https).
         *
         * @return string The base URL of the repository.
         */
        public function getBaseUrl(): string
        {
            return ($this->ssl ? 'https://' : 'http://') . $this->host;
        }

        /**
         * Creates an instance of the repository client based on the configuration.
         *
         * @param AbstractAuthentication|null $authentication Optional authentication method for the repository.
         * @return AbstractRepository An instance of the repository client.
         */
        public function createClient(?AbstractAuthentication $authentication=null): AbstractRepository
        {
            return AbstractRepository::fromConfiguration($this, $authentication);
        }

        /**
         * Magic method to convert the repository configuration to a string.
         * This returns the base URL of the repository.
         *
         * @return string The base URL of the repository.
         */
        public function __toString(): string
        {
            return $this->getBaseUrl();
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'type' => $this->type->value,
                'host' => $this->host,
                'ssl' => $this->ssl
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): RepositoryConfiguration
        {
            return new self(
                $data['name'],
                RepositoryType::tryFrom($data['type'] ?? '') ?? RepositoryType::GITHUB,
                $data['host'],
                $data['ssl']
            );
        }

        /**
         * @inheritDoc
         */
        public static function validateArray(array $data): void
        {
            if(!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '')
            {
                throw new InvalidPropertyException('repository.name', 'The repository name is required and cannot be empty');
            }

            if(!isset($data['type']) || !is_string($data['type']) || RepositoryType::tryFrom($data['type']) === null)
            {
                throw new InvalidPropertyException('repository.type', 'The repository type is required and must be a valid RepositoryType');
            }

            if(!isset($data['host']) || !is_string($data['host']) || trim($data['host']) === '')
            {
                throw new InvalidPropertyException('repository.host', 'The repository host is required and cannot be empty');
            }

            if(isset($data['ssl']) && !is_bool($data['ssl']))
            {
                throw new InvalidPropertyException('repository.ssl', 'The repository SSL flag must be a boolean value');
            }
        }

        public function validate(): void
        {
            // TODO: Implement validate() method.
        }
    }