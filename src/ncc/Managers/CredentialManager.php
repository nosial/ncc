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

    use ncc\Enums\Scopes;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\Vault;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Extensions\ZiProto\ZiProto;

    class CredentialManager
    {
        /**
         * @var Vault
         */
        private $vault;

        /**
         * Public Constructor
         *
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public function __construct()
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to access the credentials storage file');
            }

            if(!is_file(PathFinder::getCredentialStorage()))
            {
                $this->vault = new Vault();
                return;
            }

            $this->vault = Vault::fromArray(ZiProto::decode(IO::fread(PathFinder::getCredentialStorage())));
        }

        /**
         * Returns the Vault object
         *
         * @return Vault
         */
        public function getVault(): Vault
        {
            return $this->vault;
        }

        /**
         * Saves the vault to the disk
         *
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public function save(): void
        {
            Console::outVerbose(sprintf('Saving credentials store to %s', PathFinder::getCredentialStorage()));

            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to modify the credentials storage file');
            }

            IO::fwrite(PathFinder::getCredentialStorage(), ZiProto::encode($this->vault->toArray(true)), 0600);
        }

        /**
         * Initializes the credential storage file if it doesn't exist
         *
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public static function initializeCredentialStorage(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                throw new OperationException('You must have root privileges to initialize the credentials storage file');
            }

            if(is_file(PathFinder::getCredentialStorage()))
            {
                Console::outVerbose('Skipping credentials store initialization, store already exists');
                return;
            }

            Console::outVerbose(sprintf('Initializing credentials store at %s', PathFinder::getCredentialStorage()));
            IO::fwrite(PathFinder::getCredentialStorage(), ZiProto::encode((new Vault())->toArray(true)), 0600);
        }
    }