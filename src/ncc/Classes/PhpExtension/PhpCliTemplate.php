<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    namespace ncc\Classes\PhpExtension;

    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Enums\Options\ProjectOptions;
    use ncc\Enums\Runners;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\TemplateInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\IO;

    class PhpCliTemplate implements TemplateInterface
    {
        /**
         * @inheritDoc
         * @param ProjectManager $project_manager
         * @throws IOException
         * @throws PathNotFoundException
         * @throws ConfigurationException
         */
        public static function applyTemplate(ProjectManager $project_manager): void
        {
            $project_manager->getProjectConfiguration()->addExecutionPolicy(
                new ExecutionPolicy('main_policy', Runners::PHP, new ExecutionPolicy\Execute('main'))
            );

            $project_manager->getProjectConfiguration()->getBuild()->setMain('main_policy');
            $project_manager->getProjectConfiguration()->getProject()->addOption(ProjectOptions::CREATE_SYMLINK, true);

            self::writeProgramTemplate($project_manager);
            self::writeMainEntryTemplate($project_manager);
            self::writeMakefileTemplate($project_manager);

            $project_manager->save();
        }

        /**
         * Writes the Program.php file to the project source directory
         *
         * @param ProjectManager $project_manager
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         */
        private static function writeProgramTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectSourcePath() . DIRECTORY_SEPARATOR . 'Program.php',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'TemplateFiles' . DIRECTORY_SEPARATOR . 'Program.php.tpl')
                )
            );
        }

        /**
         * Writes the main.php file to the project directory
         *
         * @param ProjectManager $project_manager
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         */
        private static function writeMainEntryTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'main',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'TemplateFiles' . DIRECTORY_SEPARATOR . 'main.php.tpl')
                )
            );
        }

        /**
         * Writes the Makefile to the project directory
         *
         * @param ProjectManager $project_manager
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         */
        private static function writeMakefileTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'Makefile',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'TemplateFiles' . DIRECTORY_SEPARATOR . 'Makefile.tpl')
                )
            );
        }
    }