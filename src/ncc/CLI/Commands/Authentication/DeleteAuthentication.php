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

    namespace ncc\CLI\Commands\Authentication;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Runtime;

    class DeleteAuthentication extends AbstractCommandHandler
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

            if(empty($name))
            {
                Console::error('Entry name is required. Use --name or -n to specify the name.');
                return 1;
            }

            try
            {
                $authManager = Runtime::getAuthenticationManager();
                
                if(!$authManager->vaultExists())
                {
                    Console::error('Authentication vault does not exist.');
                    return 1;
                }

                // Unlock the vault
                $masterPassword = Console::getPassword('Enter vault master password: ');
                $authManager->unlock($masterPassword);
                Console::out('Vault unlocked successfully.');

                // Check if entry exists
                if($authManager->getEntry($name) === null)
                {
                    Console::error(sprintf('Authentication entry "%s" does not exist.', $name));
                    return 1;
                }

                $authManager->removeEntry($name);
                $authManager->save();

                Console::out(sprintf('Authentication entry "%s" deleted successfully.', $name));
                return 0;
            }
            catch(Exception $e)
            {
                Console::error('Failed to delete authentication entry: ' . $e->getMessage());
                return 1;
            }
        }
    }
