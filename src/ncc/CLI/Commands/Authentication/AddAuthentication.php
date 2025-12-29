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
    use ncc\Objects\Authentication\AccessToken;
    use ncc\Objects\Authentication\UsernamePassword;
    use ncc\Runtime;

    class AddAuthentication extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                // Delegate to parent's help command
                return 0;
            }

            $name = $argv['name'] ?? $argv['n'] ?? null;
            $type = $argv['type'] ?? $argv['t'] ?? null;
            $overwrite = isset($argv['overwrite']) ? (bool)$argv['overwrite'] : false;

            if(empty($name))
            {
                Console::error('Entry name is required. Use --name or -n to specify the name.');
                return 1;
            }

            if(empty($type))
            {
                Console::error('Authentication type is required. Use --type or -t to specify the type.');
                Console::out('Valid types are: access-token, username-password');
                return 1;
            }

            // Normalize the type
            $type = strtolower(str_replace('_', '-', $type));
            
            try
            {
                $authManager = Runtime::getAuthenticationManager();
                
                if(!$authManager->vaultExists())
                {
                    Console::error('Authentication vault does not exist.');
                    Console::out('Please initialize the vault first using: ncc authentication init');
                    return 1;
                }

                // Unlock the vault
                $masterPassword = Console::getPassword('Enter vault master password: ');
                $authManager->unlock($masterPassword);
                Console::out('Vault unlocked successfully.');

                // Check if entry already exists
                if($authManager->getEntry($name) !== null && !$overwrite)
                {
                    Console::error(sprintf('Authentication entry "%s" already exists. Use --overwrite to replace it.', $name));
                    return 1;
                }

                switch($type)
                {
                    case 'access-token':
                    case 'token':
                        $token = $argv['token'] ?? null;
                        if(empty($token))
                        {
                            $token = Console::getPassword('Enter access token: ');
                            if(empty($token))
                            {
                                Console::error('Access token cannot be empty.');
                                return 1;
                            }
                        }
                        $authEntry = new AccessToken($token);
                        break;

                    case 'username-password':
                    case 'password':
                        $username = $argv['username'] ?? $argv['u'] ?? null;
                        $password = $argv['password'] ?? $argv['p'] ?? null;

                        if(empty($username))
                        {
                            $username = Console::prompt('Enter username: ');
                            if(empty($username))
                            {
                                Console::error('Username cannot be empty.');
                                return 1;
                            }
                        }

                        if(empty($password))
                        {
                            $password = Console::getPassword('Enter password: ');
                            if(empty($password))
                            {
                                Console::error('Password cannot be empty.');
                                return 1;
                            }
                        }

                        $authEntry = new UsernamePassword($username, $password);
                        break;

                    default:
                        Console::error(sprintf('Invalid authentication type "%s".', $type));
                        Console::out('Valid types are: access-token, username-password');
                        return 1;
                }

                if($overwrite && $authManager->getEntry($name) !== null)
                {
                    $authManager->removeEntry($name);
                    Console::out(sprintf('Existing entry "%s" will be overwritten.', $name));
                }

                $authManager->addEntry($name, $authEntry);
                $authManager->save();

                Console::out(sprintf('Authentication entry "%s" added successfully.', $name));
                return 0;
            }
            catch(Exception $e)
            {
                Console::error('Failed to add authentication entry: ' . $e->getMessage());
                return 1;
            }
        }
    }
