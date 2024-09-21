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

    class CliTemplate implements TemplateInterface
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
                new ExecutionPolicy('main_policy', Runners::PHP->value, new ExecutionPolicy\Execute('main'))
            );

            $project_manager->getProjectConfiguration()->getBuild()->setMain('main_policy');
            $project_manager->getProjectConfiguration()->getProject()->addOption(ProjectOptions::CREATE_SYMLINK->value, true);

            // Create the release build configuration
            $release_executable = new BuildConfiguration('release_executable',
                'build' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . AssemblyConstants::ASSEMBLY_NAME->value
            );
            $release_executable->setBuildType(BuildOutputType::EXECUTABLE->value);
            $release_executable->setOption(BuildConfigurationOptions::NCC_CONFIGURATION->value, 'release');
            $project_manager->getProjectConfiguration()->getBuild()->addBuildConfiguration($release_executable);

            // Create the debug build configuration
            $debug_executable = new BuildConfiguration('debug_executable',
                'build' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . AssemblyConstants::ASSEMBLY_NAME->value
            );
            $debug_executable->setDefinedConstant('DEBUG', '1');
            $debug_executable->setBuildType(BuildOutputType::EXECUTABLE->value);
            $debug_executable->setOption(BuildConfigurationOptions::NCC_CONFIGURATION->value, 'debug');
            $project_manager->getProjectConfiguration()->getBuild()->addBuildConfiguration($debug_executable);

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
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'Program.php.tpl')
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
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'main.php.tpl')
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
                    IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'Makefile.tpl')
                )
            );
        }
    }