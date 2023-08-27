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
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
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
        private $store_path;


        /**
         * @var Vault
         */
        private $vault;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->store_path = PathFinder::getDataPath(Scopes::SYSTEM) . DIRECTORY_SEPARATOR . 'credentials.store';

            try
            {
                $this->loadVault();
            }
            catch(Exception $e)
            {
                unset($e);
            }

            if($this->vault === null)
            {
                $this->vault = new Vault();
            }
        }

        /**
         * Constructs the store file if it doesn't exist on the system (First initialization)
         *
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         */
        public function constructStore(): void
        {
            Console::outDebug(sprintf('constructing credentials store at %s', $this->store_path));

            // Do not continue the function if the file already exists, if the file is damaged a separate function
            // is to be executed to fix the damaged file.
            if(file_exists($this->store_path))
            {
                return;
            }

            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Cannot construct credentials store without system permissions');
            }

            $VaultObject = new Vault();
            $VaultObject->version = Versions::CREDENTIALS_STORE_VERSION;

            IO::fwrite($this->store_path, ZiProto::encode($VaultObject->toArray()), 0744);
        }

        /**
         * Loads the vault from the disk
         *
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         * @throws RuntimeException
         */
        private function loadVault(): void
        {
            Console::outDebug(sprintf('loading credentials store from %s', $this->store_path));

            if($this->vault !== null)
            {
                return;
            }

            if(!file_exists($this->store_path))
            {
                $this->vault = new Vault();
                return;
            }

            $VaultArray = ZiProto::decode(IO::fread($this->store_path));
            $VaultObject = Vault::fromArray($VaultArray);

            if($VaultObject->version !== Versions::CREDENTIALS_STORE_VERSION)
            {
                throw new RuntimeException('Credentials store version mismatch');
            }

            $this->vault = $VaultObject;
        }

        /**
         * Saves the vault to the disk
         *
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         */
        public function saveVault(): void
        {
            Console::outDebug(sprintf('saving credentials store to %s', $this->store_path));

            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Cannot save credentials store without system permissions');
            }

            IO::fwrite($this->store_path, ZiProto::encode($this->vault->toArray()), 0744);
        }

        /**
         * @return Vault|null
         */
        public function getVault(): ?Vault
        {
            return $this->vault;
        }
    }