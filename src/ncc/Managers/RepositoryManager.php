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

    namespace ncc\Managers;

    use Exception;
    use InvalidArgumentException;
    use ncc\Enums\Scopes;
    use ncc\Exceptions\OperationException;
    use ncc\Utilities\Console;
    use ncc\Utilities\Resolver;
    use RuntimeException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Extensions\ZiProto\ZiProto;

    class RepositoryManager
    {
        /**
         * @var RepositoryConfiguration[]
         */
        private $repositories;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->repositories = [];

            if(is_file(PathFinder::getRepositoryDatabase()))
            {
                try
                {
                    foreach(ZiProto::decode(IO::fread(PathFinder::getRepositoryDatabase())) as $source)
                    {
                        $this->repositories[] = RepositoryConfiguration::fromArray($source);
                    }
                }
                catch(Exception $e)
                {
                    throw new RuntimeException(sprintf('Unable to load remote sources from disk \'%s\': %s', PathFinder::getRepositoryDatabase(), $e->getMessage()), $e->getCode(), $e);
                }
            }
        }

        /**
         * Returns an array of all the configured repositories
         *
         * @return RepositoryConfiguration[]
         */
        public function getRepositories(): array
        {
            return $this->repositories;
        }

        /**
         * Returns True if the repository exists
         *
         * @param string $input
         * @return bool
         */
        public function repositoryExists(string $input): bool
        {
            $input = strtolower($input);

            foreach($this->repositories as $source)
            {
                if($source->getName() === $input)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Adds new repository to the system
         *
         * @param RepositoryConfiguration $source
         * @param bool $update
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public function addRepository(RepositoryConfiguration $source, bool $update=true): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to add a new repository');
            }

            if($this->repositoryExists($source->getName()))
            {
                throw new InvalidArgumentException(sprintf('The remote source \'%s\' already exists', $source->getName()));
            }

            Console::outVerbose(sprintf('Adding repository \'%s\' as %s (type: %s)', $source->getHost(), $source->getName(), $source->getType()));
            $this->repositories[] = $source;

            if($update)
            {
                $this->updateDatabase();
            }
        }

        /**
         * Returns a repository configuration by name
         *
         * @param string $name
         * @return RepositoryConfiguration
         */
        public function getRepository(string $name): RepositoryConfiguration
        {
            $name = strtolower($name);

            foreach($this->repositories as $source)
            {
                if($source->getName() === $name)
                {
                    return $source;
                }
            }

            throw new InvalidArgumentException(sprintf('The remote source \'%s\' does not exist', $name));
        }

        /**
         * Removes an existing repository from the system and updates the database
         *
         * @param string $name
         * @param bool $update
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public function removeRepository(string $name, bool $update=true): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to delete a repository');
            }

            Console::outVerbose(sprintf('Removing repository \'%s\'', $name));

            $name = strtolower($name);
            foreach($this->repositories as $index => $source)
            {
                if($source->getName() === $name)
                {
                    unset($this->repositories[$index]);

                    if($update)
                    {
                        $this->updateDatabase();
                    }

                    return;
                }
            }

            throw new InvalidArgumentException(sprintf('The remote source \'%s\' does not exist', $name));
        }

        /**
         * Updates the repository database
         *
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public function updateDatabase(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to update the repository database');
            }

            Console::outVerbose(sprintf('Updating repository database with %d repositories', count($this->repositories)));

            $sources = [];
            foreach($this->repositories as $source)
            {
                $sources[] = $source->toArray(true);
            }

            IO::fwrite(PathFinder::getRepositoryDatabase(), ZiProto::encode($sources), 0644);
        }

        /**
         * Initializes the repository database, optionally with default repositories
         *
         * @param array $default_repositories
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public static function initializeDatabase(array $default_repositories=[]): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to initialize the repository database');
            }

            // Skip if the database already exists (or update it if we change the structure)
            if(is_file(PathFinder::getRepositoryDatabase()))
            {
                Console::outVerbose('Skipping repository database initialization, database already exists');
                return;
            }

            Console::outVerbose(sprintf('Initializing repository database with %d repositories', count($default_repositories)));

            $repository_manager = new RepositoryManager();
            foreach($default_repositories as $repository)
            {
                Console::outDebug(sprintf('Adding default repository \'%s\'', $repository['name']));
                $repository_manager->addRepository(RepositoryConfiguration::fromArray($repository), false);
            }

            $repository_manager->updateDatabase();
        }
    }