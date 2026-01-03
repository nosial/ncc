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

    namespace ncc\CLI\Commands\Project\Templates\Phpdoc;

    use ncc\Classes\Console;
    use ncc\Classes\IO;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Objects\Project;

    class PhpdocGenerator implements TemplateGeneratorInterface
    {

        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $targetFile = $projectDirectory . DIRECTORY_SEPARATOR . 'phpdoc.dist.xml';

            // Remove the Makefile if it exists
            if(IO::exists($targetFile))
            {
                IO::rm($targetFile);
            }

            // Create a basic Makefile
            $baseConfigurationFile = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'phpdoc.tpl');
            $baseConfigurationFile = str_replace('${ASSEMBLY.NAME}', $projectConfiguration->getAssembly()->getName(), $baseConfigurationFile);
            $baseConfigurationFile = str_replace('${SOURCE_PATH}', $projectConfiguration->getSourcePath(), $baseConfigurationFile);

            IO::writeFile($targetFile, $baseConfigurationFile);
            Console::out(sprintf('Generated File: %s', $targetFile));
        }
    }