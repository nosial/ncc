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

    namespace ncc\CLI\Commands\Repository;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Runtime;

    class ListRepositories extends AbstractCommandHandler
    {
        private const string SYSTEM_FLAG = "\033[31m*\033[0m";

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

            foreach(Runtime::getSystemRepositoryManager()->getEntries() as $repository)
            {
                Console::out(sprintf(' %s%s (%s:%s)', $repository->getName(), self::SYSTEM_FLAG, $repository->getType()->value, $repository->getHost()));
            }

            foreach(Runtime::getUserRepositoryManager()?->getEntries() ?? [] as $repository)
            {
                Console::out(sprintf(' %s (%s:%s)', $repository->getName(), $repository->getType()->value, $repository->getHost()));
            }

            Console::out(sprintf('Total repositories: %d', count(Runtime::getRepositories())));

            return 0;
        }
    }