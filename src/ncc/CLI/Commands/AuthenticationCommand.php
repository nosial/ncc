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

    namespace ncc\CLI\Commands;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\CLI\Commands\Authentication\AddAuthentication;
    use ncc\CLI\Commands\Authentication\DeleteAuthentication;
    use ncc\CLI\Commands\Authentication\InitializeVault;
    use ncc\CLI\Commands\Authentication\ListAuthentication;

    class AuthenticationCommand extends AbstractCommandHandler
    {
        public static function handle(array $argv): int
        {
            if(isset($argv['init']))
            {
                return InitializeVault::handle($argv);
            }
            if(isset($argv['add']))
            {
                return AddAuthentication::handle($argv);
            }
            if(isset($argv['delete']) || isset($argv['del']))
            {
                return DeleteAuthentication::handle($argv);
            }
            if(isset($argv['list']) || isset($argv['ls']))
            {
                return ListAuthentication::handle($argv);
            }
            elseif(isset($argv['help']) || isset($argv['h']))
            {
                $helpCommand = $argv['help'] ?? $argv['h'] ?? null;
                // If help is just a boolean flag (true), treat as general help
                if($helpCommand === true)
                {
                    $helpCommand = null;
                }
                self::help($helpCommand);
                return 0;
            }

            self::help();
            return 0;
        }

        public static function help(string|true|null $command=null): void
        {
            if($command === null || $command === true)
            {
                Console::out('Usage: ncc authentication [command] [options]' . PHP_EOL);
                Console::out('Manage authentication entries in the encrypted vault.' . PHP_EOL);
                Console::out('Commands:');
                Console::out('  init              Initialize the authentication vault');
                Console::out('  add               Add a new authentication entry');
                Console::out('  delete, del       Remove an existing authentication entry');
                Console::out('  list, ls          List all authentication entries');
                Console::out(PHP_EOL . 'Use "ncc authentication [command] --help" for more information about a command.');
                return;
            }

            switch($command)
            {
                case 'init':
                    Console::out('Usage: ncc authentication init' . PHP_EOL);
                    Console::out('Initializes the authentication vault with a master password.' . PHP_EOL);
                    Console::out('The vault stores all authentication entries in encrypted form.');
                    Console::out('You will be prompted to enter a master password that will be');
                    Console::out('required to access the vault in future operations.');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc authentication init');
                    break;

                case 'add':
                    Console::out('Usage: ncc authentication add --name=<name> --type=<type> [options]' . PHP_EOL);
                    Console::out('Adds a new authentication entry to the vault.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) Unique identifier for this entry');
                    Console::out('  --type, -t        (Required) Authentication type:');
                    Console::out('                    - access-token: For API tokens/keys');
                    Console::out('                    - username-password: For username/password pairs');
                    Console::out('  --token           (For access-token) The access token value');
                    Console::out('  --username, -u    (For username-password) The username');
                    Console::out('  --password, -p    (For username-password) The password');
                    Console::out('  --overwrite       Overwrite existing entry with the same name');
                    Console::out(PHP_EOL . 'Examples:');
                    Console::out('  ncc authentication add --name=github-token --type=access-token');
                    Console::out('  ncc authentication add --name=myserver --type=username-password');
                    break;

                case 'delete':
                case 'del':
                    Console::out('Usage: ncc authentication delete --name=<name>' . PHP_EOL);
                    Console::out('Removes an authentication entry from the vault.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) Name of the entry to delete');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc authentication delete --name=github-token');
                    break;

                case 'list':
                case 'ls':
                    Console::out('Usage: ncc authentication list' . PHP_EOL);
                    Console::out('Lists all authentication entries stored in the vault.' . PHP_EOL);
                    Console::out('Displays entry names and types without revealing sensitive data.');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc authentication list');
                    break;

                default:
                    Console::error('Unknown command ' . $command);
                    break;
            }
        }
    }
