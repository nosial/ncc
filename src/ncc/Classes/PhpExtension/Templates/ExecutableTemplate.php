<?php
    /*
     * Copyright (c) Nosial 2022-2024, all rights reserved.
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

    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\SpecialConstants\AssemblyConstants;
    use ncc\Enums\Types\BuildOutputType;
    use ncc\Exceptions\IOException;
    use ncc\Interfaces\TemplateInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;

    class ExecutableTemplate implements TemplateInterface
    {

        /**
         * @inheritDoc
         * @throws IOException
         */
        public static function applyTemplate(ProjectManager $project_manager): void
        {
            foreach($project_manager->getProjectConfiguration()->getBuild()->getBuildConfigurations() as $build_configuration)
            {
                $executable_name = sprintf('%s-executable', $build_configuration);
                $configuration = $project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_configuration);

                // Skip if the executable version of the build configuration already exists
                if($project_manager->getProjectConfiguration()->getBuild()->buildConfigurationExists($executable_name))
                {
                    continue;
                }

                // Skip if the build configuration is not an ncc package that the executable can be based on
                if($configuration->getBuildType() !== BuildOutputType::NCC_PACKAGE->value)
                {
                    continue;
                }

                if(isset($configuration->getOptions()[BuildConfigurationOptions::COMPRESSION->value]))
                {
                    $output = dirname($configuration->getOutput()) . DIRECTORY_SEPARATOR . str_replace('-', '_', $executable_name);
                }
                else
                {
                    $output = dirname($configuration->getOutput()) . DIRECTORY_SEPARATOR . str_replace('-', '_', $executable_name) . '_gz';
                }

                // Create the executable build configuration
                $executable = new BuildConfiguration($executable_name, $output);
                $executable->setBuildType(BuildOutputType::EXECUTABLE->value);
                $executable->setOption(BuildConfigurationOptions::NCC_CONFIGURATION->value, $configuration->getName());
                $project_manager->getProjectConfiguration()->getBuild()->addBuildConfiguration($executable);
            }

            $project_manager->save();
        }
    }