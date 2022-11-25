<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use Exception;
    use ncc\Abstracts\Scopes;
    use ncc\Abstracts\Versions;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\InvalidCredentialsEntryException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Objects\Vault;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\ZiProto\ZiProto;

    class CredentialManager
    {
        /**
         * @var null
         */
        private $CredentialsPath;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->CredentialsPath = PathFinder::getDataPath(Scopes::System) . DIRECTORY_SEPARATOR . 'credentials.store';
        }

        /**
         * Determines if CredentialManager has correct access to manage credentials on the system
         *
         * @return bool
         */
        public function checkAccess(): bool
        {
            $ResolvedScope = Resolver::resolveScope();

            if($ResolvedScope !== Scopes::System)
            {
                return False;
            }

            return True;
        }

        /**
         * Constructs the store file if it doesn't exist on the system (First initialization)
         *
         * @return void
         * @throws AccessDeniedException
         * @throws IOException
         */
        public function constructStore(): void
        {
            // Do not continue the function if the file already exists, if the file is damaged a separate function
            // is to be executed to fix the damaged file.
            if(file_exists($this->CredentialsPath))
                return;

            if(!$this->checkAccess())
            {
                throw new AccessDeniedException('Cannot construct credentials store without system permissions');
            }

            $VaultObject = new Vault();
            $VaultObject->Version = Versions::CredentialsStoreVersion;

            IO::fwrite($this->CredentialsPath, ZiProto::encode($VaultObject->toArray()), 0600);
        }

        /**
         * Returns the vault object from the credentials store file.
         *
         * @return Vault
         * @throws AccessDeniedException
         * @throws IOException
         * @throws RuntimeException
         */
        public function getVault(): Vault
        {
            $this->constructStore();

            if(!$this->checkAccess())
            {
                throw new AccessDeniedException('Cannot read credentials store without system permissions');
            }

            try
            {
                $Vault = ZiProto::decode(IO::fread($this->CredentialsPath));
            }
            catch(Exception $e)
            {
                // TODO: Implement error-correction for corrupted credentials store.
                throw new RuntimeException($e->getMessage(), $e);
            }

            return Vault::fromArray($Vault);
        }

        /**
         * Saves the vault object to the credentials store
         *
         * @param Vault $vault
         * @return void
         * @throws AccessDeniedException
         * @throws IOException
         */
        public function saveVault(Vault $vault): void
        {
            if(!$this->checkAccess())
            {
                throw new AccessDeniedException('Cannot write to credentials store without system permissions');
            }

            IO::fwrite($this->CredentialsPath, ZiProto::encode($vault->toArray()), 0600);
        }

        /**
         * Registers an entry to the credentials store file
         *
         * @param Vault\Entry $entry
         * @return void
         * @throws AccessDeniedException
         * @throws InvalidCredentialsEntryException
         * @throws RuntimeException
         * @throws IOException
         */
        public function registerEntry(Vault\Entry $entry): void
        {
            if(!preg_match('/^[\w-]+$/', $entry->Alias))
            {
                throw new InvalidCredentialsEntryException('The property \'Alias\' must be alphanumeric (Regex error)');
            }

            // TODO: Implement more validation checks for the rest of the entry properties.
            // TODO: Implement encryption for entries that require encryption (For securing passwords and data)

            $Vault = $this->getVault();
            $Vault->Entries[] = $entry;

            $this->saveVault($Vault);
        }

        /**
         * @return null
         */
        public function getCredentialsPath(): ?string
        {
            return $this->CredentialsPath;
        }
    }