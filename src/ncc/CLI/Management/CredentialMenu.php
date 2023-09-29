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

    namespace ncc\CLI\Management;

    use Exception;
    use ncc\Enums\Scopes;
    use ncc\Managers\CredentialManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\Vault\Password\AccessToken;
    use ncc\Objects\Vault\Password\UsernamePassword;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;

    class CredentialMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return int
         * @noinspection DuplicatedCode
         */
        public static function start($args): int
        {
            if(isset($args['add']))
            {
                try
                {
                    return self::addEntry($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Cannot add entry: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['remove']))
            {
                try
                {
                    return self::removeEntry($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Cannot remove entry: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['list']))
            {
                try
                {
                    return self::listEntries();
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Cannot list entries: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['test']))
            {
                try
                {
                    return self::testEntry($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Cannot test entry: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            return self::displayOptions();
        }

        /**
         * Tests an entry authentication
         *
         * @param $args
         * @return int
         */
        public static function testEntry($args): int
        {
            $name = $args['name'] ?? $args['alias'] ?? null;

            if($name === null)
            {
                Console::outError('Please specify a name or alias for the entry.', true, 1);
                return 1;
            }

            $entry = (new CredentialManager())->getVault()?->getEntry($name);

            if($entry === null)
            {
                Console::out('Entry not found.', true, 1);
                return 1;
            }

            if($entry->isEncrypted())
            {
                $tries = 0;

                while(true)
                {
                    if (!$entry->unlock(Console::passwordInput('Password/Secret: ')))
                    {
                        $tries++;
                        if ($tries >= 3)
                        {
                            Console::outError('Too many failed attempts.', true, 1);
                            return 1;
                        }

                        Console::outError(sprintf('Invalid password, %d attempts remaining.', 3 - $tries));
                    }
                    else
                    {
                        Console::out('Authentication successful.');
                        return 1;
                    }
                }
            }
            else
            {
                Console::out('Authentication always successful, entry is not encrypted.');
            }

            return 0;
        }

        /**
         * Prints the list of entries in the vault
         *
         * @return int
         */
        public static function listEntries(): int
        {
            $entries = (new CredentialManager())->getVault()?->getEntries();

            if(count($entries) === 0)
            {
                Console::out('No entries found.');
                return 0;
            }

            Console::out('Entries:');
            foreach($entries as $entry)
            {
                Console::out(sprintf(' - %s %s', $entry->getName(), $entry->isEncrypted() ? ' (encrypted)' : ''));
            }

            Console::out('Total: ' . count($entries));
            return 0;
        }

        /**
         * @param $args
         * @return int
         */
        public static function addEntry($args): int
        {
            $ResolvedScope = Resolver::resolveScope();

            if($ResolvedScope !== Scopes::SYSTEM)
            {
                Console::outError('Insufficient permissions to add entries');
            }

            // Really dumb-proofing this
            $name = $args['alias'] ?? $args['name'] ?? null;
            $auth_type = $args['auth-type'] ?? $args['auth'] ?? null;
            $username = $args['username'] ?? $args['usr'] ?? null;
            $password = $args['password'] ?? $args['pwd'] ?? null;
            $token = $args['token'] ?? $args['pat'] ?? $args['private-token'] ?? null;
            $encrypt = !isset($args['no-encryption']);

            if($name === null)
            {
                $name = Console::getInput('Enter a name for the entry: ');
            }

            if($auth_type === null)
            {
                $auth_type = Console::getInput('Enter the authentication type (login or pat): ');
            }

            if($auth_type === 'login')
            {
                if($username === null)
                {
                    $username = Console::getInput('Username: ');
                }

                if($password === null)
                {
                    $password = Console::passwordInput('Password: ');
                }
            }
            elseif($auth_type === 'pat')
            {
                if($token === null)
                {
                    $token = Console::passwordInput('Token: ');
                }
            }
            else
            {
                Console::outError('Invalid authentication type');
                return 1;
            }

            if($name === null)
            {
                Console::outError('You must specify a name for the entry (alias, name)', true, 1);
                return 1;
            }

            if($auth_type === null)
            {
                Console::outError('You must specify an authentication type for the entry (auth-type, auth)', true, 1);
                return 1;
            }

            $encrypt = Functions::cbool($encrypt);

            switch($auth_type)
            {
                case 'login':
                    if($username === null)
                    {
                        Console::outError('You must specify a username for the entry (username, usr)', true, 1);
                        return 1;
                    }

                    if($password === null)
                    {
                        Console::outError('You must specify a password for the entry (password, pwd)', true, 1);
                        return 1;
                    }

                    $pass_object = new UsernamePassword($username, $password);
                    break;

                case 'pat':
                    if($token === null)
                    {
                        Console::outError('You must specify a token for the entry (token, pat, private-token)', true, 1);
                        return 1;
                    }

                    $pass_object = new AccessToken($token);
                    break;

                default:
                    Console::outError('Invalid authentication type specified', true, 1);
                    return 1;
            }

            $credential_manager = new CredentialManager();
            if(!$credential_manager->getVault()?->addEntry($name, $pass_object, $encrypt))
            {
                Console::outError('Failed to add entry, entry already exists.', true, 1);
                return 1;
            }

            try
            {
                $credential_manager->save();
            }
            catch(Exception $e)
            {
                Console::outException('Failed to save vault', $e, 1);
                return 1;
            }

            Console::out('Successfully added entry', true, 0);
            return 0;
        }

        /**
         * Removes an existing entry from the vault.
         *
         * @param $args
         * @return int
         */
        private static function removeEntry($args): int
        {
            $ResolvedScope = Resolver::resolveScope();

            if($ResolvedScope !== Scopes::SYSTEM)
            {
                Console::outError('Insufficient permissions to remove entries');
            }

            $name = $args['alias'] ?? $args['name'] ?? null;

            if($name === null)
            {
                $name = Console::getInput('Enter the name of the entry to remove: ');
            }

            $credential_manager = new CredentialManager();
            if(!$credential_manager->getVault()?->deleteEntry($name))
            {
                Console::outError('Failed to remove entry, entry does not exist.', true, 1);
                return 1;
            }

            try
            {
                $credential_manager->save();
            }
            catch(Exception $e)
            {
                Console::outException('Failed to save vault', $e, 1);
                return 1;
            }

            Console::out('Successfully removed entry', true, 0);
            return 0;
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            Console::out('Usage: ncc cred {command} [options]');
            Console::out('Options:');
            Console::outHelpSections([
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['add'], 'Adds a new entry to the vault (See below)'),
                new CliHelpSection(['remove', '--name'], 'Removes an entry from the vault'),
                new CliHelpSection(['list'], 'Lists all entries in the vault'),
            ]);
            Console::out((string)null);

            Console::out('If you are adding a new entry, you can run the add command in interactive mode');
            Console::out('or you can specify the options below' . PHP_EOL);

            Console::out('Add Options:');
            Console::outHelpSections([
                new CliHelpSection(['--name'], 'The name of the entry'),
                new CliHelpSection(['--auth-type', '--auth'], 'The type of authentication (login, pat)'),
                new CliHelpSection(['--no-encryption'], 'Omit encryption to the entry (By default it\'s encrypted)', true),
            ]);

            Console::out('   login authentication type options:');
            Console::outHelpSections([
                new CliHelpSection(['--username', '--usr'], 'The username for the entry'),
                new CliHelpSection(['--password', '--pwd'], 'The password for the entry'),
            ]);

            Console::out('   pat authentication type options:');
            Console::outHelpSections([
                new CliHelpSection(['--token', '--pat',], 'The private access token for the entry', true),
            ]);

            Console::out('Authentication Types:');
            Console::out('   login');
            Console::out('   pat' . PHP_EOL);

            Console::out('Examples:');
            Console::out('   ncc cred add --alias "My Alias" --auth-type login --username "myusername" --password "mypassword"');
            Console::out('   ncc cred add --alias "My Alias" --auth-type pat --token "mytoken" --no-encryption');
            Console::out('   ncc cred remove --alias "My Alias"');

            return 0;
        }
    }