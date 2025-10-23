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
    use ncc\CLI\Commands\Project\ApplyTemplate;
    use ncc\CLI\Commands\Project\CreateProject;
    use ncc\CLI\Commands\Project\ValidateProject;

    class ProjectCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['create']))
            {
                return CreateProject::handle($argv);
            }
            elseif(isset($argv['validate']))
            {
                return ValidateProject::handle($argv);
            }
            elseif(isset($argv['template']))
            {
                return ApplyTemplate::handle($argv);
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

        /**
         * Prints out the help menu for the project manager
         *
         * @param string|true|null $command The command to get help information about
         * @return void
         */
        public static function help(string|true|null $command=null): void
        {
            if($command === null || $command === true)
            {
                Console::out('Usage: ncc project [command] [options]' . PHP_EOL);
                Console::out('Commands:');
                Console::out('  create           Create a new ncc project');
                Console::out('  validate         Validates a given ncc project and gives inspection results');
                Console::out('  template         Apply automatic templates to your existing project');
                Console::out(PHP_EOL . 'Use "ncc project [command] --help" for more information about a command.');
                return;
            }

            switch($command)
            {
                case 'create':
                    Console::out('Usage: ncc project create --name=ProjectName --package=com.example.project_name' . PHP_EOL);
                    Console::out('The create command allows you to create a new php project with a ncc project file included,');
                    Console::out('once a project file is included, you can easily apply templates to automatically generate a');
                    Console::out('build system for your project.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  - name     (Required)   The name of the project/product');
                    Console::out('  - package  (Required)   The package name eg; com.example.project');
                    Console::out(PHP_EOL . 'Example Usage:');
                    break;

                case 'validate':
                    Console::out('Usage: ncc project validate [--path=path/to/project]');
                    Console::out('The validate command checks your project for any ');
                    Console::out('Options:');
                    Console::out('  - path     (Optional)   The path to the project, if not specified, the current working directory is used');
                    break;

                case 'template':
                    Console::out('Usage: ncc project template --name=TemplateName [--path=path/to/project]');
                    Console::out('Options:');
                    Console::out('  - name     (Required)   The name of the template to apply');
                    Console::out('  - path     (Optional)   The path to the project, if not specified, the current working directory is used');
                    break;

                default:
                    Console::error('Unknown command ' . $command);
                    break;
            }
        }
    }