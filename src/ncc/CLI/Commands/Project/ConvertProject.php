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

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Enums\ProjectType;

    class ConvertProject extends AbstractCommandHandler
    {

        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $projectPath = getcwd();
            if(isset($argv['path']))
            {
                $projectPath = $argv['path'];
            }

            $projectType = ProjectType::detectProjectType($projectPath);
            if(isset($argv['format']))
            {
                switch(strtolower($argv['format']))
                {
                    case 'composer':
                        $projectType = ProjectType::COMPOSER;
                        break;

                    case 'legacy':
                        $projectType = ProjectType::NCC_V2;
                        break;

                    default:
                        Console::error("Unknown format specified: " . $argv['format'] . ". Supported formats are: composer, legacy");
                        return 1;
                }
            }

            if($projectType === ProjectType::NONE)
            {
                Console::error("No project configuration file found to convert");
                return 1;
            }

            if($projectType === ProjectType::NCC)
            {
                Console::error("Project is already in NCC v3+ format");
                return 0;
            }

            $converter = $projectType->getConverter();
            if($converter === null)
            {
                Console::error("No converter available for project type: " . $projectType->name);
                return 1;
            }

            $projectFilePath = $projectType->getFilepath($projectPath);

            if($projectFilePath === null)
            {
                Console::error("Failed to locate project file for type: " . $projectType->name);
                return 1;
            }

            $outputPath = dirname($projectFilePath) . DIRECTORY_SEPARATOR . 'project.yml';

            try
            {
                $converter->convert($projectType->getFilePath($projectPath))->save($outputPath);
            }
            catch(Exception $e)
            {
                Console::error(sprintf('Failed to convert project (%d): %s', $e->getCode(), $e->getMessage()));
                return 1;
            }

            Console::out(sprintf('Successfully converted project: %s', $outputPath));
            return 0;
        }
    }