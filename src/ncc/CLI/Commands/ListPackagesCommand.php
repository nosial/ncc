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

    namespace ncc\CLI\Commands;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Runtime;

    class ListPackagesCommand extends AbstractCommandHandler
    {
        /**
         * Prints out the help menu for the list command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc list' . PHP_EOL);
            Console::out('Lists all installed ncc packages that can be located.' . PHP_EOL);
            Console::out('Displays both user-level and system-level package locations.');
            Console::out(PHP_EOL . 'Example:');
            Console::out('  ncc list');
        }

        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $userPackageManager = Runtime::getUserPackageManager();
            $systemPackageManager = Runtime::getSystemPackageManager();

            if($userPackageManager !== null)
            {
                Console::out("User Level Packages: " . $userPackageManager->getDataDirectoryPath());
            }

            Console::out('System Packages: ' . $systemPackageManager->getDataDirectoryPath());
            return 0;
        }
    }