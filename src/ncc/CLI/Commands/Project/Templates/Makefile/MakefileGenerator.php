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

    namespace ncc\CLI\Commands\Project\Templates\Makefile;

    use ncc\Classes\Console;
    use ncc\Libraries\fslib\IO;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Objects\Project;

    class MakefileGenerator implements TemplateGeneratorInterface
    {
        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $targetFile = $projectDirectory . DIRECTORY_SEPARATOR . 'Makefile';

            // Remove the Makefile if it exists
            if(IO::exists($targetFile))
            {
                IO::delete($targetFile);
            }

            // Create a basic Makefile
            $baseMakefile = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Makefile.tpl');
            $buildStep = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Makefile-BuildStep.tpl');

            // Apply configurations
            $baseMakefile = str_replace('${BUILD_OUTPUTS}', implode(' ', array_map(function($buildConfiguration) {
                return $buildConfiguration->getOutput();
            }, $projectConfiguration->getBuildConfigurations())), $baseMakefile);
            $extraPhony = [];
            $cleanCommands = [];

            $buildSteps = [];
            foreach($projectConfiguration->getBuildConfigurations() as $buildConfiguration)
            {
                $buildStepConfiguration = $buildStep;
                $buildStepConfiguration = str_replace('${BUILD_OUTPUT}', $buildConfiguration->getOutput(), $buildStepConfiguration);
                $buildStepConfiguration = str_replace('${BUILD_NAME}', $buildConfiguration->getName(), $buildStepConfiguration);

                $buildSteps[] = $buildStepConfiguration;
                $cleanCommands[] = sprintf("\trm -f %s\n", $buildConfiguration->getOutput());
            }

            $baseMakefile = str_replace('${BUILD_STEPS}', implode("\n", $buildSteps), $baseMakefile);

            if(IO::exists($projectDirectory . DIRECTORY_SEPARATOR . 'phpunit.xml'))
            {
                $baseMakefile = str_replace('${PHPUNIT_TARGET}', "\ntest:\n\tphpunit --configuration phpunit.xml\n", $baseMakefile);
                $extraPhony[] = 'test';
            }
            else
            {
                $baseMakefile = str_replace('${PHPUNIT_TARGET}', '', $baseMakefile);
            }

            if(IO::exists($projectDirectory . DIRECTORY_SEPARATOR . 'phpdoc.dist.xml'))
            {
                $baseMakefile = str_replace('${PHPDOC_TARGET}', "\ndocs:\n\tphpdoc --config phpdoc.dist.xml\n", $baseMakefile);
                $extraPhony[] = 'docs';
                $cleanCommands[] = "\trm -rf target/docs\n";
                $cleanCommands[] = "\trm -rf target/cache\n";
            }
            else
            {
                $baseMakefile = str_replace('${PHPDOC_TARGET}', '', $baseMakefile);
            }

            if(count($extraPhony) > 0)
            {
                $baseMakefile = str_replace('${EXTRA_PHONY}', ' ' . implode(' ', $extraPhony), $baseMakefile);
            }
            else
            {
                $baseMakefile = str_replace('${EXTRA_PHONY}', '', $baseMakefile);
            }

            $baseMakefile = str_replace('${CLEAN_COMMANDS}', implode('', $cleanCommands), $baseMakefile);

            IO::writeFile($targetFile, $baseMakefile);
            Console::out(sprintf("Generated File: %s", $targetFile));
        }
    }