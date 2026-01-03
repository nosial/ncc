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

    namespace ncc\CLI\Commands\Project\Templates\Phpunit;

    use ncc\Classes\Console;
    use ncc\Classes\IO;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Objects\Project;

    class PhpunitGenerator implements TemplateGeneratorInterface
    {
        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $targetFile = $projectDirectory . DIRECTORY_SEPARATOR . 'phpunit.xml';
            $targetBootstrapFile = $projectDirectory . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'bootstrap.php';

            if(IO::exists($targetFile))
            {
                IO::rm($targetFile);
            }

            if(IO::exists($targetBootstrapFile))
            {
                IO::rm($targetBootstrapFile);
            }

            if(!IO::exists(dirname($targetBootstrapFile)))
            {
                IO::mkdir(dirname($targetBootstrapFile));
            }

            $basePhpunit = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'phpunit.tpl');
            $baseBootstrap = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.tpl');

            $defaultOutputPath = '../' .  $projectConfiguration->getBuildConfiguration($projectConfiguration->getDefaultBuild())->getOutput();
            $baseBootstrap = str_replace('${DEFAULT_BUILD_OUTPUT}', $defaultOutputPath, $baseBootstrap);
            $basePhpunit = str_replace('${ASSEMBLY.NAME}', $projectConfiguration->getAssembly()->getName(), $basePhpunit);

            IO::writeFile($targetFile, $basePhpunit);
            Console::out(sprintf("Generated File: %s", $targetFile));

            IO::writeFile($targetBootstrapFile, $baseBootstrap);
            Console::out(sprintf("Generated File: %s", $targetBootstrapFile));
        }
    }