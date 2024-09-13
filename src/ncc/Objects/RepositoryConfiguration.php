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

    use InvalidArgumentException;
    use ncc\Classes\GiteaExtension\GiteaRepository;
    use ncc\Classes\GithubExtension\GithubRepository;
    use ncc\Classes\GitlabExtension\GitlabRepository;
    use ncc\Classes\PackagistExtension\PackagistRepository;
    use ncc\Enums\Types\AuthenticationType;
    use ncc\Enums\Types\RepositoryType;
    use ncc\Enums\Versions;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\UpdateSource\Repository;
    use ncc\Utilities\Functions;

    class RepositoryConfiguration implements BytecodeObjectInterface
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var string
         */
        private $host;

        /**
         * @var string
         * @see RepositoryType
         */
        private $type;

        /**
         * @var bool
         */
        private $ssl;

        /**
         * RemoteRepository constructor.
         *
         * @param string $name The unique name of the remote source. (e.g. 'github')
         * @param string $host The host of the service ncc should use with this source (gitlab.com, github.com, git.example.com:8080 etc...).
         * @param string $type The type of service ncc should use with this source (gitlab, github, etc...).
         * @param bool $ssl If SSL should be used when connecting to the service
         */
        public function __construct(string $name, string $host, string $type, bool $ssl=true)
        {
            $this->setName($name);
            $this->setHost($host);
            $this->setType($type);
            $this->setSsl($ssl);
        }

        /**
         * Returns the unique name of the remote source. (e.g. 'github')
         * 
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the unique name of the remote source. (e.g. 'github')
         * 
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = strtolower($name);
        }

        /**
         * Returns the type of service ncc should use with this source (gitlab, github, etc...).
         * 
         * @return string
         * @see RepositoryType
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * Sets the type of service ncc should use with this source (gitlab, github, etc...).
         * 
         * @param string $type
         * @see RepositoryType
         */
        public function setType(string $type): void
        {
            if(!in_array(strtolower($type), RepositoryType::ALL, true))
            {
                throw new InvalidArgumentException(sprintf('Invalid repository type \'%s\'', $type));
            }

            $this->type = $type;
        }

        /**
         * Returns the host of the service ncc should use with this source (gitlab.com, github.com, git.example.com:8080 etc...).
         * 
         * @return string
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Sets the host of the service ncc should use with this source (gitlab.com, github.com, git.example.com:8080 etc...).
         * 
         * @param string $host
         */
        public function setHost(string $host): void
        {
            $this->host = $host;
        }

        /**
         * Returns True if SSL should be used when connecting to the service
         *
         * @return bool
         */
        public function isSsl(): bool
        {
            return $this->ssl;
        }

        /**
         * Sets if SSL should be used when connecting to the service
         *
         * @param bool $ssl
         */
        public function setSsl(bool $ssl): void
        {
            $this->ssl = $ssl;
        }

        /**
         * Returns the archive URL for the ncc package of the specified group and project.
         * This is useful for downloading the package.
         *
         * @param string $vendor The vendor to get the package for (eg; "Nosial")
         * @param string $project The project to get the package for (eg; "ncc" or "libs/config")
         * @param string $version Optional. The version to get the package for. By default, it will get the latest version
         * @param AuthenticationType|null $authentication Optional. The authentication to use. If null, No authentication will be used.
         * @return RepositoryResult The url to the package archive
         * @throws AuthenticationException If the authentication is invalid
         * @throws NetworkException If there was an error getting the package
         * @throws NotSupportedException If the repository type does not support fetching packages
         */
        public function fetchPackage(string $vendor, string $project, string $version=Versions::LATEST->value, ?AuthenticationType $authentication=null, array $options=[]): RepositoryResult
        {
            return match(strtolower($this->type))
            {
                RepositoryType::GITHUB => GithubRepository::fetchPackage($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::GITLAB => GitlabRepository::fetchPackage($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::GITEA => GiteaRepository::fetchPackage($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::PACKAGIST => throw new NotSupportedException('Fetching ncc packages from Packagist is not supported'),
                default => throw new InvalidArgumentException(sprintf('Invalid repository type \'%s\'', $this->type)),
            };
        }

        /**
         * Returns the archive URL for the source code of the specified group and project.
         * This is useful for building the project from source.
         *
         * @param string $vendor The vendor to get the source for (eg; "Nosial")
         * @param string $project The project to get the source for (eg; "ncc" or "libs/config")
         * @param string $version Optional. The version to get the source for. By default, it will get the latest version
         * @param AuthenticationType|null $authentication Optional. The authentication to use. If null, No authentication will be used.
         * @return RepositoryResult The url to the source code archive
         * @throws AuthenticationException If the authentication is invalid
         * @throws NetworkException If there was an error getting the source
         */
        public function fetchSourceArchive(string $vendor, string $project, string $version=Versions::LATEST->value, ?AuthenticationType $authentication=null, array $options=[]): RepositoryResult
        {
            return match(strtolower($this->type))
            {
                RepositoryType::GITHUB => GithubRepository::fetchSourceArchive($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::GITLAB => GitlabRepository::fetchSourceArchive($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::GITEA => GiteaRepository::fetchSourceArchive($this, $vendor, $project, $version, $authentication, $options),
                RepositoryType::PACKAGIST => PackagistRepository::fetchSourceArchive($this, $vendor, $project, $version, $authentication, $options),
                default => throw new InvalidArgumentException(sprintf('Invalid repository type \'%s\'', $this->type)),
            };
        }

        /**
         * Returns the repository object used for a project configuration
         *
         * @return Repository
         */
        public function getProjectRepository(): Repository
        {
            return new Repository($this->name, $this->host, $this->type, $this->ssl);
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
         */
        public static function fromArray(array $data): self
        {
            $name = Functions::array_bc($data, 'name');
            $type = Functions::array_bc($data, 'type');
            $host = Functions::array_bc($data, 'host');
            $ssl = Functions::array_bc($data, 'ssl') ?? true;

            return new self($name, $host, $type, $ssl);
        }
    }