<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use ncc\Abstracts\Scopes;
    use ncc\Abstracts\Versions;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\InvalidCredentialsEntryException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Objects\Vault;
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
         * @throws RuntimeException
         */
        public function constructStore(): void
        {
            // Do not continue the function if the file already exists, if the file is damaged a seperate function
            // is to be executed to fix the damaged file.
            if(file_exists($this->CredentialsPath))
                return;

            if(!$this->checkAccess())
            {
                throw new AccessDeniedException('Cannot construct credentials store without system permissions');
            }

            $VaultObject = new Vault();
            $VaultObject->Version = Versions::CredentialsStoreVersion;

            // TODO: Set proper permissions for root access only for the file
            if(!@file_put_contents($this->CredentialsPath, ZiProto::encode($VaultObject->toArray())))
            {
                throw new RuntimeException('Cannot create file \'' . $this->CredentialsPath . '\'');
            }
        }

        /**
         * Returns the vault object from the credentials store file.
         *
         * @return Vault
         * @throws AccessDeniedException
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
                $Vault = ZiProto::decode(file_get_contents($this->CredentialsPath));
            }
            catch(\Exception $e)
            {
                // TODO: Implement error-correction for corrupted credentials store.
                throw new RuntimeException($e->getMessage(), $e);
            }

            $Vault = Vault::fromArray($Vault);

            return $Vault;
        }

        /**
         * Saves the vault object to the credentials store
         *
         * @param Vault $vault
         * @return void
         * @throws AccessDeniedException
         */
        public function saveVault(Vault $vault)
        {
            if(!$this->checkAccess())
            {
                throw new AccessDeniedException('Cannot write to credentials store without system permissions');
            }

            file_put_contents($this->CredentialsPath, ZiProto::encode($vault->toArray()));
        }

        /**
         * Registers an entry to the credentials store file
         *
         * @param Vault\Entry $entry
         * @return void
         * @throws AccessDeniedException
         * @throws InvalidCredentialsEntryException
         * @throws RuntimeException
         */
        public function registerEntry(Vault\Entry $entry)
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
        public function getCredentialsPath()
        {
            return $this->CredentialsPath;
        }
    }