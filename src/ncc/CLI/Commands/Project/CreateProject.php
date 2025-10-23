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

    namespace ncc\CLI\Commands\Project;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Objects\Project;

    class CreateProject extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(!isset($argv['name']))
            {
                Console::error("Missing parameter: --name, please provide a name for the new project eg; --name=\"My Project\"");
                return 1;
            }

            if(!isset($argv['package']))
            {
                Console::error("Missing parameter: --package, please provide a package name for the new project eg; --package=\"com.example.myproject\"");
                return 1;
            }

            if(isset($argv['path']))
            {
                $projectPath = realpath(rtrim($argv['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $argv['name']);
            }
            else
            {
                $projectPath = getcwd() . DIRECTORY_SEPARATOR . $argv['name'];
            }

            if(file_exists($projectPath))
            {
                Console::error(sprintf('The path %s already exists', $projectPath));
                return 1;
            }

            // If the project path does not exist, and it failed to create it, throw an error.
            if(!@mkdir($projectPath, recursive: true))
            {
                Console::error(sprintf('Failed to create path %s', $projectPath));
                return 1;
            }

            $project = Project::createNew($argv['name'], $argv['package']);
            $sourcePath = $projectPath . DIRECTORY_SEPARATOR . 'project.yml';

            if(!file_exists($sourcePath) && !@mkdir($sourcePath, recursive: true))
            {
                Console::error(sprintf('Failed to create source path %s', $sourcePath));
            }

            $project->save($projectPath . DIRECTORY_SEPARATOR . 'project.yml');
            Console::out("Project created successfully at: " . $projectPath);

            return 0;
        }
    }