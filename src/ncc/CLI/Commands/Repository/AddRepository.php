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

    namespace ncc\CLI\Commands\Repository;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Enums\RepositoryType;
    use ncc\Runtime;

    class AddRepository extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $name = $argv['name'] ?? $argv['n'] ?? null;
            $type = $argv['type'] ?? $argv['t'] ?? null;
            $host = $argv['host'] ?? $argv['h'] ?? null;
            $ssl = isset($argv['ssl']) ? (bool)$argv['ssl'] : true;
            $overwrite = isset($argv['overwrite']) ? (bool)$argv['overwrite'] : false;

            if(empty($name))
            {
                Console::error('Repository name is required. Use --name or -n to specify the name.');
                return 1;
            }

            if(empty($type))
            {
                Console::error('Repository type is required. Use --type or -t to specify the type.');
                return 1;
            }
            elseif(RepositoryType::tryFrom($type) === null)
            {
                Console::error(sprintf('Invalid repository type "%s". Valid types are: %s', $type, implode(', ', array_map(fn($t) => $t->value, RepositoryType::cases()))));
                return 1;
            }
            else
            {
                $type = RepositoryType::from($type);
            }

            if(empty($host))
            {
                Console::error('Repository host is required. Use --host or -h to specify the host.');
                return 1;
            }
            elseif(!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_NULL_ON_FAILURE) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE))
            {
                Console::error(sprintf('Invalid repository host "%s". Please provide a valid domain or IP address.', $host));
                return 1;
            }

            if(Runtime::repositoryExists($name))
            {
                if(!$overwrite)
                {
                    Console::error(sprintf('Repository "%s" already exists. Use --overwrite to replace it.', $name));
                    return 1;
                }

                Runtime::deleteRepository($name);
            }

            Runtime::getRepositoryManager()->addRepository($name, $type, $host, $ssl);
            Console::out(sprintf('Repository "%s" added successfully.', $name));
            return 0;
        }
    }