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
    use ncc\CLI\Commands\Repository\AddRepository;
    use ncc\CLI\Commands\Repository\DeleteRepository;
    use ncc\CLI\Commands\Repository\ListRepositories;

    class RepositoryCommand extends AbstractCommandHandler
    {
        public static function handle(array $argv): int
        {
            if(isset($argv['add']))
            {
                return AddRepository::handle($argv);
            }
            if(isset($argv['delete']) || isset($argv['del']))
            {
                return DeleteRepository::handle($argv);
            }
            if(isset($argv['list']) || isset($argv['ls']))
            {
                return ListRepositories::handle($argv);
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
                Console::out('Usage: ncc repository [command] [options]' . PHP_EOL);
                Console::out('Manage package repositories for ncc.' . PHP_EOL);
                Console::out('Commands:');
                Console::out('  add               Add a new repository');
                Console::out('  delete, del       Remove an existing repository');
                Console::out('  list, ls          List all configured repositories');
                Console::out(PHP_EOL . 'Use "ncc repository [command] --help" for more information about a command.');
                return;
            }

            switch($command)
            {
                case 'add':
                    Console::out('Usage: ncc repository add --name=<name> --type=<type> --host=<host> [--ssl]' . PHP_EOL);
                    Console::out('Adds a new package repository to the configuration.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) Repository name identifier');
                    Console::out('  --type, -t        (Required) Repository type (e.g., git, http)');
                    Console::out('  --host, -h        (Required) Repository host URL or domain');
                    Console::out('  --ssl             Enable SSL (default: true)');
                    Console::out('  --overwrite       Overwrite existing repository with the same name');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc repository add --name=myrepo --type=git --host=repo.example.com');
                    break;

                case 'delete':
                case 'del':
                    Console::out('Usage: ncc repository delete --name=<name>' . PHP_EOL);
                    Console::out('Removes a repository from the configuration.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) Name of the repository to delete');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc repository delete --name=myrepo');
                    break;

                case 'list':
                case 'ls':
                    Console::out('Usage: ncc repository list' . PHP_EOL);
                    Console::out('Lists all configured repositories.' . PHP_EOL);
                    Console::out('Displays both system and user repositories.');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc repository list');
                    break;

                default:
                    Console::error('Unknown command ' . $command);
                    break;
            }
        }
    }