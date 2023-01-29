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

namespace ncc\CLI;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class HelpMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public static function start($args): void
        {
            $basic_ascii = false;

            if(isset($args['basic-ascii']))
            {
                $basic_ascii = true;
            }

            // TODO: Make copyright not hard-coded.
            print(Functions::getBanner(NCC_VERSION_BRANCH . ' ' . NCC_VERSION_NUMBER, 'Copyright (c) 2022-2022 Nosial', $basic_ascii) . PHP_EOL);

            Console::out('Usage: ncc COMMAND [options]');
            Console::out('Alternative Usage: ncc.php --ncc-cli=COMMAND [options]' . PHP_EOL);
            Console::out('Nosial Code Compiler / Project Toolkit' . PHP_EOL);

            self::displayMainOptions();
            self::displayManagementCommands();
            self::displayMainCommands();
        }

        /**
         * Displays the main options section
         *
         * @return void
         */
        private static function displayMainOptions(): void
        {
            Console::out('Options:');
            Console::outHelpSections([
                new CliHelpSection(['{command} --help'], 'Displays help information about a specific command'),
                new CliHelpSection(['-v', '--version'], 'Display NCC version information'),
                new CliHelpSection(['-l', '--log-level={silent|debug|verbose|info|warn|error|fatal}'], 'Set the logging level', 'info'),
                new CliHelpSection(['--basic-ascii'], 'Uses basic ascii characters'),
                new CliHelpSection(['--no-color'], 'Omits the use of colors'),
                new CliHelpSection(['--no-banner'], 'Omits displaying the NCC ascii banner')
            ]);
        }

        /**
         * Displays the management commands section
         *
         * @return void
         */
        private static function displayManagementCommands(): void
        {
            Console::out('Management Commands:');
            Console::outHelpSections([
                new CliHelpSection(['project'], 'Manages the current project'),
                new CliHelpSection(['package'], 'Manages the package system'),
                new CliHelpSection(['cred'], 'Manages credentials'),
                new CliHelpSection(['config'], 'Changes NCC configuration values'),
                new CliHelpSection(['source'], 'Manages remote sources'),
            ]);
        }

        /**
         * Displays the main commands section
         *
         * @return void
         */
        private static function displayMainCommands(): void
        {
            Console::out('Commands:');
            Console::outHelpSections([
                new CliHelpSection(['build'], 'Builds the current project'),
                new CliHelpSection(['exec'], 'Executes the main entrypoint of a package')
            ]);
        }
    }