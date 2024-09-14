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
    use ncc\Enums\ConsoleColors;
    use ncc\Enums\Scopes;
    use ncc\Managers\RepositoryManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;

    class RepositoryMenu
    {
        /**
         * Displays the main menu for managing repositories
         *
         * @param array $args
         * @return int
         */
        public static function start(array $args): int
        {
            if(isset($args['add']))
            {
                try
                {
                    return self::addEntry($args);
                }
                catch(Exception $e)
                {
                    Console::outException('Error while adding repository.', $e, 1);
                    return 1;
                }
            }

            if(isset($args['export']))
            {
                try
                {
                    return self::exportEntry($args);
                }
                catch(Exception $e)
                {
                    Console::outException('Cannot export repository.', $e, 1);
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
                    Console::outException('Cannot remove repository.', $e, 1);
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
                    Console::outException('Cannot list entries.', $e, 1);
                    return 1;
                }
            }

            return self::displayOptions();
        }


        /**
         * Lists all the configured repositories
         *
         * @return int
         */
        private static function listEntries(): int
        {
            $sources = (new RepositoryManager())->getRepositories();

            if(count($sources) === 0)
            {
                Console::out('No remote sources defined.');
                return 0;
            }

            foreach($sources as $source)
            {
                $output = sprintf('%s (%s) [%s]',
                    $source->getName(),
                    Console::formatColor($source->getHost(), ConsoleColors::GREEN->value),
                    Console::formatColor($source->getType(), ConsoleColors::YELLOW->value)
                );

                if(!$source->isSsl())
                {
                    $output .= Console::formatColor('*', ConsoleColors::RED->value);
                }

                Console::out(' - ' .  $output);
            }

            Console::out(sprintf('Total: %d', count($sources)));
            return 0;
        }

        /**
         * Adds a new repository to the system.
         *
         * @param array $args
         * @return int
         */
        private static function addEntry(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You must be running as root to add a new repository', true, 1);
                return 1;
            }

            $name = $args['name'] ?? $args['n'] ?? null;
            $type = $args['type'] ?? $args['t'] ?? null;
            $host = $args['host'] ?? $args['h'] ?? null;
            $ssl = Functions::cbool($args['ssl'] ?? $args['s'] ?? true);

            if($name === null)
            {
                Console::outError(sprintf('Missing required argument \'%s\'.', 'name'), true, 1);
                return 1;
            }

            if($type === null)
            {
                Console::outError(sprintf('Missing required argument \'%s\'.', 'type'), true, 1);
                return 1;
            }

            if($host === null)
            {
                Console::outError(sprintf('Missing required argument \'%s\'.', 'host'), true, 1);
                return 1;
            }

            try
            {
                (new RepositoryManager())->addRepository(new RepositoryConfiguration($name, $host, $type, $ssl));
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Cannot add repository \'%s\'.', $name), $e, 1);
                return 1;
            }

            Console::out(sprintf('Repository \'%s\' added successfully.', $name));
            return 0;
        }

        /**
         * Exports the repository configuration to the console
         *
         * @param array $args
         * @return int
         */
        private static function exportEntry(array $args): int
        {
            $name = $args['name'] ?? $args['n'] ?? null;

            if($name === null)
            {
                Console::outError(sprintf('Missing required argument \'%s\'.', 'name'), true, 1);
                return 1;
            }

            $repository_manager = new RepositoryManager();

            if(!$repository_manager->repositoryExists($name))
            {
                Console::outError(sprintf('Repository \'%s\' does not exist.', $name), true, 1);
                return 1;
            }

            try
            {
                $repository = $repository_manager->getRepository($name);
                Console::out(json_encode($repository->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Cannot export repository \'%s\'.', $name), $e, 1);
                return 1;
            }

            return 0;
        }

        /**
         * Removes an existing repository from the system.
         *
         * @param array $args
         * @return int
         */
        private static function removeEntry(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You must be running as root to remove a repository', true, 1);
                return 1;
            }

            $name = $args['name'] ?? $args['n'] ?? null;

            if($name === null)
            {
                Console::outError(sprintf('Missing required argument \'%s\'.', 'name'), true, 1);
                return 1;
            }

            $repository_manager = new RepositoryManager();

            if(!$repository_manager->repositoryExists($name))
            {
                Console::outError(sprintf('Repository \'%s\' does not exist.', $name), true, 1);
                return 1;
            }

            try
            {
                $repository_manager->removeRepository($name);
            }
            catch(Exception $e)
            {
                Console::outException(sprintf('Cannot remove repository \'%s\'.', $name), $e, 1);
                return 1;
            }

            Console::out(sprintf('Repository \'%s\' removed successfully.', $name));
            return 0;
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            Console::out('Usage: ncc repo {command} [options]');

            Console::out('Options:');
            Console::outHelpSections([
                new CliHelpSection(['help'], 'Displays this help menu about the repository command'),
                new CliHelpSection(['add', '--name|-n', '--type|-t', '--host|-h', '--ssl|-s'], 'Adds a new repository to the system'),
                new CliHelpSection(['export', '--name|-n'], 'Prints out the repository configuration to the console (JSON)'),
                new CliHelpSection(['remove', '--name|-n'], 'Removes an repository from the system'),
                new CliHelpSection(['list'], 'Lists all configured repositories on your system'),
            ]);

            Console::out('Examples:');
            Console::out(' - ncc repo add --name github --type github --host api.github.com');
            Console::out(' - ncc repo add --name gitlab --type gitlab --host gitlab.com');
            Console::out(' - ncc repo add --name gitea --type gitea --host git.example.com --ssl false');
            Console::out(' - ncc repo remove --name github');
            Console::out(' - ncc repo list');
            Console::out('Note: You must have root privileges to add or remove repositories from the system.');

            return 0;
        }
    }