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

    namespace ncc\CLI\Commands\Project\Templates\Commandline;

    use ncc\Classes\Console;
    use ncc\Classes\IO;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Objects\Project;

    class CommandlineTemplate implements TemplateGeneratorInterface
    {

        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $targetEntryPath = $projectDirectory . DIRECTORY_SEPARATOR . 'main';

            if(IO::exists($targetEntryPath))
            {
                IO::rm($targetEntryPath);
            }

            $targetEntry = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'main.tpl');
            $targetEntry = str_replace('${PACKAGE_NAME}', $projectConfiguration->getAssembly()->getPackage(), $targetEntry);
            $targetEntry = str_replace('${PROJECT_NAME}', $projectConfiguration->getAssembly()->getName(), $targetEntry);

            IO::writeFile($targetEntryPath, $targetEntry);
            Console::out('Generated File: ' . $targetEntryPath);

            if(!$projectConfiguration->executionUnitExists('main'))
            {
                $executionUnit = new Project\ExecutionUnit([
                    'name' => 'main',
                    'entry' => 'main'
                ]);
                $projectConfiguration->addExecutionUnit($executionUnit);
            }

            $projectConfiguration->setEntryPoint('main');

            $projectConfiguration->save($projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
            Console::out('Modified File: ' . $projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
        }
    }