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
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\ExecutionUnitException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Project;

    class BuildCommand extends AbstractCommandHandler
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

            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            try
            {
                Console::out(Project::compilerFromFile($projectPath)->build());
            }
            catch (CompileException | ExecutionUnitException | IOException $e)
            {
                Console::error($e->getMessage());
            }

            return 0;
        }
    }