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

    namespace ncc\CLI\Commands\Project;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\CLI\Commands\Helper;
    use ncc\CLI\Commands\Project\Templates\GithubCI\GithubCIGenerator;
    use ncc\CLI\Commands\Project\Templates\Makefile\MakefileGenerator;
    use ncc\CLI\Commands\Project\Templates\Phpdoc\PhpdocGenerator;
    use ncc\CLI\Commands\Project\Templates\Phpunit\PhpunitGenerator;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Objects\Project;

    class ApplyTemplate extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $projectPath = $argv['path'] ?? $argv['p'] ?? null;
            $template = $argv['generate'] ?? $argv['g'] ?? null;

            if($template === null || $template === '1')
            {
                Console::error("No template specified, use the --generate|-g option to specify a template");
                return 1;
            }

            if($projectPath === null)
            {
                $projectPath = getcwd();
            }

            // Resolve project configuration path
            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            // Load the project configuration
            try
            {
                $projectConfiguration = Project::fromFile($projectPath, true);
            }
            catch(Exception $e)
            {
                Console::error("Failed to load project configuration: " . $e->getMessage());
                return 1;
            }

            // Validate the project configuration
            try
            {
                $projectConfiguration->validate();
            }
            catch(InvalidPropertyException $e)
            {
                Console::error("Project configuration is invalid: " . $e->getMessage());
                return 1;
            }

            switch(strtolower($template))
            {
                case 'phpunit':
                case 'tests':
                case 'test':
                    PhpunitGenerator::generate(dirname($projectPath), $projectConfiguration);
                    break;

                case 'phpdoc':
                case 'docs':
                case 'doc':
                    PhpdocGenerator::generate(dirname($projectPath), $projectConfiguration);
                    break;

                case 'makefile':
                case 'make':
                    MakefileGenerator::generate(dirname($projectPath), $projectConfiguration);
                    break;

                case 'github-ci':
                case 'github':
                    GithubCIGenerator::generate(dirname($projectPath), $projectConfiguration);
                    break;

                default:
                    Console::error("Unknown template: " . $template);
                    return 1;
            }

            Console::out("Template '" . $template . "' generated successfully.");
            return 0;
        }
    }