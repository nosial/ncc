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

    namespace ncc\Classes\PhpExtension\Templates;

    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\ProjectOptions;
    use ncc\Enums\Runners;
    use ncc\Enums\SpecialConstants\AssemblyConstants;
    use ncc\Enums\Types\BuildOutputType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\TemplateInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\IO;

    class PhpUnitTemplate implements TemplateInterface
    {
        /**
         * Applies the necessary templates for the given project.
         *
         * @param ProjectManager $project_manager Manager responsible for handling project-related tasks.
         *
         * @return void
         */
        public static function applyTemplate(ProjectManager $project_manager): void
        {
            self::createPhpUnitBootstrapTemplate($project_manager);
            self::createPhpUnitTemplate($project_manager);
        }

        /**
         * Creates a PHPUnit template in the specified project directory.
         *
         * @param ProjectManager $project_manager The project manager instance containing project configuration and path details.
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         */
        private static function createPhpUnitTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'phpunit.xml',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'phpunit.xml.tpl')
                )
            );

            if(!file_exists($project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'tests'))
            {
                mkdir($project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'tests');
            }
        }

        /**
         * Creates the PHPUnit bootstrap template file for the given project.
         *
         * @param ProjectManager $project_manager The project manager instance handling project configuration and paths.
         * @return void
         */
        private static function createPhpUnitBootstrapTemplate(ProjectManager $project_manager): void
        {
            IO::fwrite(
                $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'bootstrap.php',
                ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(),
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php.tpl')
                )
            );
        }
    }