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

    namespace ncc\Classes;

    use InvalidArgumentException;
    use ncc\Enums\RepositoryType;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\RepositoryConfiguration;

    class RepositoryManager
    {
        private string $dataDirectoryPath;
        private string $repositoriesPath;
        /**
         * @var RepositoryConfiguration[]
         */
        private array $entries;

        /**
         * Public Constructor
         *
         * @param string $dataDirectoryPath
         */
        public function __construct(string $dataDirectoryPath)
        {
            Logger::getLogger()?->debug(sprintf('Initializing RepositoryManager for: %s', $dataDirectoryPath));
            
            $this->dataDirectoryPath = $dataDirectoryPath;
            $this->repositoriesPath = $this->dataDirectoryPath . DIRECTORY_SEPARATOR . 'repositories.json';
            $this->entries = [];

            if(IO::isFile($this->repositoriesPath))
            {
                Logger::getLogger()?->verbose('Loading repositories configuration');
                $json = IO::readFile($this->repositoriesPath);
                $data = json_decode($json, true);
                if(is_array($data))
                {
                    foreach($data as $entry)
                    {
                        $repo = RepositoryConfiguration::fromArray($entry);
                        $this->entries[$repo->getName()] = $repo;
                        Logger::getLogger()?->debug(sprintf('Loaded repository: %s (%s)', $repo->getName(), $repo->getType()->value));
                    }
                    Logger::getLogger()?->verbose(sprintf('Loaded %d repositories', count($this->entries)));
                }
            }
            else
            {
                Logger::getLogger()?->verbose('No repositories configuration found');
            }
        }

        /**
         * Returns the data directory path
         *
         * @return string
         */
        public function getDataDirectoryPath(): string
        {
            return $this->dataDirectoryPath;
        }

        /**
         * Returns the repositories file path
         *
         * @return string
         */
        public function getRepositoriesPath(): string
        {
            return $this->repositoriesPath;
        }

        /**
         * Adds a new repository configuration to the repository manager
         *
         * @param string $name The repository name
         * @param RepositoryType $type The repository type
         * @param string $host The repository host
         * @param bool $ssl True if SSL is enabled
         * @throws InvalidArgumentException Thrown if the repository already exists
         */
        public function addRepository(string $name, RepositoryType $type, string $host, bool $ssl): void
        {
            Logger::getLogger()?->verbose(sprintf('Adding repository: %s (type: %s, host: %s)', $name, $type->value, $host));
            
            if(isset($this->entries[$name]))
            {
                throw new InvalidArgumentException(sprintf("The repository %s already exists", $name));
            }

            $this->entries[$name] = new RepositoryConfiguration($name, $type, $host, $ssl);
            Logger::getLogger()?->debug(sprintf('Repository added: %s', $name));
        }

        /**
         * Adds a new repository configuration to the repository manager
         *
         * @param RepositoryConfiguration $configuration The repository configuration
         * @throws InvalidArgumentException Thrown if the repository already exists
         */
        public function addConfiguration(RepositoryConfiguration $configuration): void
        {
            if($this->repositoryExists($configuration->getName()))
            {
                throw new InvalidArgumentException(sprintf("The repository %s already exists", $configuration->getName()));
            }

            $this->entries[$configuration->getName()] = $configuration;
        }

        /**
         * Removes a repository from the repository manager
         *
         * @param string $name The repository name
         * @return bool True if the repository was removed, false if it did not exist
         */
        public function removeRepository(string $name): bool
        {
            Logger::getLogger()?->verbose(sprintf('Removing repository: %s', $name));
            
            if(!isset($this->entries[$name]))
            {
                Logger::getLogger()?->debug(sprintf('Repository not found: %s', $name));
                return false;
            }

            unset($this->entries[$name]);
            Logger::getLogger()?->debug(sprintf('Repository removed: %s', $name));
            return true;
        }

        /**
         * Checks if a repository exists in the repository manager
         *
         * @param string $name The repository name
         * @return bool True if the repository exists, false otherwise
         */
        public function repositoryExists(string $name): bool
        {
            return isset($this->entries[$name]);
        }

        /**
         * Returns a repository configuration by name
         *
         * @param string $name The repository name
         * @return RepositoryConfiguration|null The repository configuration, or null if it does not exist
         */
        public function getRepository(string $name): ?RepositoryConfiguration
        {
            if(!$this->repositoryExists($name))
            {
                return null;
            }

            return $this->entries[$name];
        }

        /**
         * Returns all the repository configurations in the repository manager
         *
         * @return RepositoryConfiguration[] All the entries
         */
        public function getEntries(): array
        {
            return array_values($this->entries);
        }

        /**
         * Saves the repository configurations to the repositories file
         *
         * @return void
         */
        public function save(): void
        {
            $data = [];
            foreach($this->entries as $entry)
            {
                $data[] = $entry->toArray();
            }

            // Ensure the directory exists
            if(!IO::isDirectory($this->dataDirectoryPath))
            {
                // Check if we can create the directory
                try
                {
                    IO::createDirectory($this->dataDirectoryPath);
                }
                catch(IOException)
                {
                    // Cannot create directory, skip saving (likely a read-only system directory)
                    return;
                }
            }

            // Check if we have write permission to the directory
            if(!IO::isWritable($this->dataDirectoryPath))
            {
                // No write permission, skip saving (likely a read-only system directory)
                return;
            }

            // Write the file
            try
            {
                IO::writeFile($this->repositoriesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                // Set permissions: owner can read/write, others can only read (0644)
                IO::chmod($this->repositoriesPath, 0644);
            }
            catch(IOException $e)
            {
                // Failed to write file, skip silently
                return;
            }
        }

        /**
         * Destructor - saves the repository configurations
         */
        public function __destruct()
        {
            $this->save();
        }
    }