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

    use Exception;
    use ncc\Abstracts\AbstractAuthentication;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\PhpEncryption\Crypto;
    use ncc\Libraries\PhpEncryption\Exception\EnvironmentIsBrokenException;

    class AuthenticationManager
    {
        private string $dataDirectoryPath;
        private string $vaultPath;
        private bool $unlocked;
        /**
         * @var AbstractAuthentication[]
         */
        private array $entries;
        private ?string $masterPassword;

        /**
         * Initializes the authentication manager
         *
         * @param string $dataDirectoryPath The directory path where the vault file will be stored
         */
        public function __construct(string $dataDirectoryPath)
        {
            $this->dataDirectoryPath = $dataDirectoryPath;
            $this->vaultPath = $this->dataDirectoryPath . DIRECTORY_SEPARATOR . '.vault';
            $this->unlocked = false;
            $this->entries = [];
            $this->masterPassword = null;
        }

        /**
         * Gets the data directory path where the vault is stored
         *
         * @return string The data directory path
         */
        public function getDataDirectoryPath(): string
        {
            return $this->dataDirectoryPath;
        }

        /**
         * Adds an authentication entry to the vault
         *
         * @param string $name The unique identifier for this entry
         * @param AbstractAuthentication $entry The authentication entry to store
         * @throws OperationException If the vault is locked
         */
        public function addEntry(string $name, AbstractAuthentication $entry): void
        {
            if(!$this->isUnlocked())
            {
                throw new OperationException('Cannot add entry: Vault is locked');
            }

            $this->entries[$name] = $entry;
        }

        /**
         * Retrieves an authentication entry from the vault
         *
         * @param string $name The unique identifier of the entry to retrieve
         * @return AbstractAuthentication|null The authentication entry or null if not found
         * @throws OperationException If the vault is locked
         */
        public function getEntry(string $name): ?AbstractAuthentication
        {
            if(!$this->isUnlocked())
            {
                throw new OperationException('Cannot get entry: Vault is locked');
            }

            return $this->entries[$name] ?? null;
        }

        /**
         * Removes an authentication entry from the vault
         *
         * @param string $name The unique identifier of the entry to remove
         * @throws OperationException If the vault is locked
         */
        public function removeEntry(string $name): void
        {
            if(!$this->isUnlocked())
            {
                throw new OperationException('Cannot remove entry: Vault is locked');
            }

            unset($this->entries[$name]);
        }

        /**
         * Retrieves all authentication entries from the vault
         *
         * @return AbstractAuthentication[] Array of all stored authentication entries
         * @throws OperationException If the vault is locked
         */
        public function getAllEntries(): array
        {
            if(!$this->isUnlocked())
            {
                throw new OperationException('Cannot get entries: Vault is locked');
            }

            return $this->entries;
        }

        /**
         * Checks if the vault is currently unlocked
         *
         * @return bool True if the vault is unlocked, false otherwise
         */
        public function isUnlocked(): bool
        {
            return $this->unlocked;
        }

        /**
         * Checks if the vault file exists
         *
         * @return bool True if the vault file exists, false otherwise
         */
        public function vaultExists(): bool
        {
            return IO::exists($this->vaultPath);
        }

        /**
         * Unlocks the vault with the provided master password
         *
         * If the vault doesn't exist, it will be created. If it exists, the password
         * will be used to decrypt and load the stored entries.
         *
         * @param string $masterPassword The master password to unlock/create the vault
         * @throws IOException Thrown on file I/O errors
         * @throws OperationException If the vault file is not accessible or decryption fails
         */
        public function unlock(string $masterPassword): void
        {
            if(!$this->vaultExists())
            {
                $this->masterPassword = $masterPassword;
                $this->initializeVault();
                return;
            }

            if(!IO::isWritable($this->vaultPath) || !IO::isReadable($this->vaultPath))
            {
                throw new OperationException(sprintf('Vault file \'%s\' is not accessible', $this->vaultPath));
            }

            $encryptedData = IO::readFile($this->vaultPath);

            try
            {
                $decryptedData = json_decode(Crypto::decryptWithPassword($encryptedData, $masterPassword), true);
            }
            catch(Exception $e)
            {
                throw new OperationException('Failed to unlock vault: ' . $e->getMessage(), $e->getCode(), $e);
            }

            if(is_array($decryptedData))
            {
                // Reconstruct AbstractAuthentication objects from array data
                foreach($decryptedData as $name => $entryData)
                {
                    $this->entries[$name] = AbstractAuthentication::fromArray($entryData);
                }
            }

            $this->masterPassword = $masterPassword;
            $this->unlocked = true;
        }

        /**
         * Initializes a new encrypted vault file
         *
         * Creates an empty vault file encrypted with the master password.
         * The master password must be set before calling this method.
         *
         * @throws OperationException If the vault already exists or the data directory is not writable
         * @throws IOException Thrown on file I/O errors
         */
        private function initializeVault(): void
        {
            if($this->vaultExists())
            {
                throw new OperationException(sprintf('Vault already exists at \'%s\'', $this->vaultPath));
            }

            // Ensure the directory exists
            if(!IO::isDir($this->dataDirectoryPath))
            {
                // Check if we can create the directory
                try
                {
                    IO::mkdir($this->dataDirectoryPath);
                }
                catch(IOException $e)
                {
                    throw new OperationException(sprintf('Cannot create data directory \'%s\': %s', $this->dataDirectoryPath, $e->getMessage()), $e->getCode(), $e);
                }
            }

            // Check if we have write permission to the directory
            if(!IO::isWritable($this->dataDirectoryPath))
            {
                throw new OperationException(sprintf('Data directory \'%s\' is not writeable', $this->dataDirectoryPath));
            }

            try
            {
                IO::writeFile($this->vaultPath, Crypto::encryptWithPassword(json_encode([]), $this->masterPassword));
                // Set permissions: owner can read/write, others cannot access (0600)
                IO::chmod($this->vaultPath, 0600);
            }
            catch(EnvironmentIsBrokenException $e)
            {
                throw new OperationException('Failed to initialize vault: ' . $e->getMessage(), $e->getCode(), $e);
            }

            $this->unlocked = true;
        }

        /**
         * Saves the current vault entries to the encrypted vault file
         *
         * @return void
         * @throws OperationException If the vault is locked
         */
        public function save(): void
        {
            if(!$this->isUnlocked())
            {
                throw new OperationException('Cannot save vault: Vault is locked');
            }

            // Ensure the directory exists
            if(!IO::isDir($this->dataDirectoryPath))
            {
                // Check if we can create the directory
                try
                {
                    IO::mkdir($this->dataDirectoryPath);
                }
                catch(IOException $e)
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

            try
            {
                // Write the file
                IO::writeFile($this->vaultPath, Crypto::encryptWithPassword(json_encode(array_map(
                    function ($entry) {return $entry->toArray();}, $this->entries
                )), $this->masterPassword));

                // Set permissions: owner can read/write, others cannot access (0600)
                IO::chmod($this->vaultPath, 0600);
            }
            catch(IOException $e)
            {
                // Failed to write file, skip silently
                Logger::getLogger()->warning('Failed to write vault file: ' . $e->getMessage(), $e);
                return;
            }
            catch(EnvironmentIsBrokenException $e)
            {
                throw new OperationException('Failed to save vault: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }
        
        /**
         * Destructor - saves the vault if unlocked
         */
        public function __destruct()
        {
            if($this->unlocked)
            {
                try
                {
                    $this->save();
                }
                catch (OperationException $e)
                {
                    Logger::getLogger()->error('Failed to save vault on destruction: ' . $e->getMessage(), $e);
                }
            }
        }
    }