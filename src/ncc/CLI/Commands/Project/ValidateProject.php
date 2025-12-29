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
    use ncc\Classes\Validate;
    use ncc\CLI\Commands\Helper;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Objects\Project;

    class ValidateProject extends AbstractCommandHandler
    {
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

            $projectPath = getcwd();
            if(isset($argv['path']))
            {
                $projectPath = $argv['path'];
            }

            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            $projectConfiguration = Project::fromFile($projectPath, true);

            try
            {
                $projectConfiguration->validate();
            }
            catch(InvalidPropertyException $e)
            {
                Console::error("Project configuration is invalid: " . $e->getMessage());
                return 1;
            }

            if($projectConfiguration->getAssembly()->getUrl() === null)
            {
                Console::out('Suggestion: Include a URL to your project using the `assembly.url` property');
            }
            elseif(!Validate::url($projectConfiguration->getAssembly()->getUrl()))
            {
                Console::out('Warning: The URL in `assembly.url` is not a valid URL');
            }

            if($projectConfiguration->getAssembly()->getAuthor() === null)
            {
                Console::out('Suggestion: Include an author field to your project using the `assembly.author` property');
            }

            if($projectConfiguration->getAssembly()->getDescription() === null)
            {
                Console::out('Suggestion: Include a description field to your project using the `assembly.description` property');
            }

            if($projectConfiguration->getAssembly()->getLicense() === null)
            {
                Console::out("Suggestion: include a license name field in your project such as 'BSD', 'MIT', 'APACHE_LICENSE 2.0', etc; using the `assembly.license` property");
            }

            return 0;
        }
    }