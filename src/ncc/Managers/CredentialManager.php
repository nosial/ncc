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
    use ncc\Enums\Scopes;
    use ncc\Enums\Versions;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Objects\Vault;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\ZiProto\ZiProto;

    class CredentialManager
    {
        /**
         * @var string
         */
        private $CredentialsPath;


        /**
         * @var Vault
         */
        private $Vault;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->CredentialsPath = PathFinder::getDataPath(Scopes::SYSTEM) . DIRECTORY_SEPARATOR . 'credentials.store';
            $this->Vault = null;

            try
            {
                $this->loadVault();
            }
            catch(Exception $e)
            {
                unset($e);
            }

            if($this->Vault == null)
                $this->Vault = new Vault();
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
            Console::outDebug(sprintf('constructing credentials store at %s', $this->CredentialsPath));

            // Do not continue the function if the file already exists, if the file is damaged a separate function
            // is to be executed to fix the damaged file.
            if(file_exists($this->CredentialsPath))
                return;

            if(Resolver::resolveScope() !== Scopes::SYSTEM)
                throw new AccessDeniedException('Cannot construct credentials store without system permissions');

            $VaultObject = new Vault();
            $VaultObject->Version = Versions::CREDENTIALS_STORE_VERSION;

            IO::fwrite($this->CredentialsPath, ZiProto::encode($VaultObject->toArray()), 0744);
        }

        /**
         * Loads the vault from the disk
         *
         * @return void
         * @throws AccessDeniedException
         * @throws IOException
         * @throws RuntimeException
         * @throws FileNotFoundException
         */
        private function loadVault(): void
        {
            Console::outDebug(sprintf('loading credentials store from %s', $this->CredentialsPath));

            if($this->Vault !== null)
                return;

            if(!file_exists($this->CredentialsPath))
            {
                $this->Vault = new Vault();
                return;
            }

            $VaultArray = ZiProto::decode(IO::fread($this->CredentialsPath));
            $VaultObject = Vault::fromArray($VaultArray);

            if($VaultObject->Version !== Versions::CREDENTIALS_STORE_VERSION)
                throw new RuntimeException('Credentials store version mismatch');

            $this->Vault = $VaultObject;
        }

        /**
         * Saves the vault to the disk
         *
         * @return void
         * @throws AccessDeniedException
         * @throws IOException
         * @noinspection PhpUnused
         */
        public function saveVault(): void
        {
            Console::outDebug(sprintf('saving credentials store to %s', $this->CredentialsPath));

            if(Resolver::resolveScope() !== Scopes::SYSTEM)
                throw new AccessDeniedException('Cannot save credentials store without system permissions');

            IO::fwrite($this->CredentialsPath, ZiProto::encode($this->Vault->toArray()), 0744);
        }


        /**
         * @return string
         * @noinspection PhpUnused
         */
        public function getCredentialsPath(): string
        {
            return $this->CredentialsPath;
        }

        /**
         * @return Vault|null
         */
        public function getVault(): ?Vault
        {
            return $this->Vault;
        }
    }