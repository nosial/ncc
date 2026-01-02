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
    use ncc\Exceptions\IOException;
    use ncc\Objects\Project;

    class BuildCommand extends AbstractCommandHandler
    {
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

            $projectPath = getcwd();
            if(isset($argv['path']))
            {
                $projectPath = $argv['path'];
            }
            $configuration = $argv['configuration'] ?? $argv['config'] ?? $argv['c'] ?? null;

            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            try
            {
                $compiler = Project::compilerFromFile($projectPath, $configuration);
                $outputPath = $compiler->compile(function(int $current, int $total, string $message) {
                    Console::inlineProgress($current, $total, $message);
                });
                Console::completeProgress("Build completed: " . $outputPath);
            }
            catch (IOException $e)
            {
                Console::clearInlineProgress();
                Console::error($e->getMessage());
                return 1;
            }

            return 0;
        }

        /**
         * Prints out the help menu for the build command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc build [options]' . PHP_EOL);
            Console::out('Builds the current project into an ncc package.' . PHP_EOL);
            Console::out('The build command compiles your project according to the project.json');
            Console::out('configuration file, creating a distributable .ncc package file.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --path=<path>           Path to the project directory (defaults to current directory)');
            Console::out('  --configuration=<name>  Specific build configuration to use');
            Console::out('  --config, -c            Alias for --configuration');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc build');
            Console::out('  ncc build --path=/path/to/project');
            Console::out('  ncc build --configuration=release');
        }
    }