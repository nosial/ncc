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
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\TemplateInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Utilities\IO;

    class PhpLibraryTemplate implements TemplateInterface
    {
        /**
         * @inheritDoc
         */
        public static function applyTemplate(ProjectManager $project_manager): void
        {
            self::createClassTemplate($project_manager);
            self::writeMakefileTemplate($project_manager);
        }

        /**
         * @param ProjectManager $project_manager
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         */
        private static function createClassTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectSourcePath() . DIRECTORY_SEPARATOR . $project_manager->getProjectConfiguration()->getAssembly()->getName() . '.php',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'TemplateFiles' . DIRECTORY_SEPARATOR . 'class.php.tpl')
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