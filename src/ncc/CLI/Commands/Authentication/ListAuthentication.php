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
    use ncc\Enums\AuthenticationType;
    use ncc\Runtime;

    class ListAuthentication extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
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
                Console::out('Vault unlocked successfully.' . PHP_EOL);

                $entries = $authManager->getAllEntries();

                if(empty($entries))
                {
                    Console::out('No authentication entries found in the vault.');
                    return 0;
                }

                Console::out(sprintf('Found %d authentication %s:', count($entries), count($entries) === 1 ? 'entry' : 'entries'));
                Console::out('');

                // Find the longest name for formatting
                $maxNameLength = max(array_map('strlen', array_keys($entries)));
                $maxNameLength = max($maxNameLength, strlen('NAME')); // At least as long as the header

                // Print header
                $header = sprintf('  %-' . $maxNameLength . 's  TYPE', 'NAME');
                Console::out($header);
                Console::out('  ' . str_repeat('-', $maxNameLength + 2 + strlen('TYPE')));

                // Print each entry
                foreach($entries as $name => $entry)
                {
                    $typeName = match($entry->getType())
                    {
                        AuthenticationType::ACCESS_TOKEN => 'Access Token',
                        AuthenticationType::USERNAME_PASSWORD => 'Username/Password',
                    };

                    Console::out(sprintf('  %-' . $maxNameLength . 's  %s', $name, $typeName));
                }

                Console::out('');
                return 0;
            }
            catch(Exception $e)
            {
                Console::error('Failed to list authentication entries: ' . $e->getMessage());
                return 1;
            }
        }
    }
