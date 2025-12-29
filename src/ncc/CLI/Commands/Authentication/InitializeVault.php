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

    namespace ncc\CLI\Commands\Authentication;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Runtime;

    class InitializeVault extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            try
            {
                $authManager = Runtime::getAuthenticationManager();
                
                if($authManager->vaultExists())
                {
                    Console::error('Authentication vault already exists.');
                    Console::out('To reinitialize the vault, you must first delete the existing vault file.');
                    Console::out('Vault location: ' . $authManager->getDataDirectoryPath() . DIRECTORY_SEPARATOR . '.vault');
                    return 1;
                }

                Console::out('Initializing authentication vault...');
                Console::out('You will be prompted to create a master password.');
                Console::out('This password will be required to access all authentication entries.');
                Console::out(PHP_EOL . 'IMPORTANT: Keep this password secure and do not lose it!');
                Console::out('There is no way to recover the vault contents without the master password.' . PHP_EOL);

                $masterPassword = Console::getPassword('Enter master password: ');
                if(empty($masterPassword))
                {
                    Console::error('Master password cannot be empty.');
                    return 1;
                }

                $confirmPassword = Console::getPassword('Confirm master password: ');
                if($masterPassword !== $confirmPassword)
                {
                    Console::error('Passwords do not match.');
                    return 1;
                }

                // Initialize the vault by unlocking with the new password
                $authManager->unlock($masterPassword);
                $authManager->save();

                Console::out('Authentication vault initialized successfully.');
                return 0;
            }
            catch(Exception $e)
            {
                Console::error('Failed to initialize vault: ' . $e->getMessage());
                return 1;
            }
        }
    }
