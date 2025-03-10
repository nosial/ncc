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

    class CompressedTemplate implements TemplateInterface
    {

        /**
         * @inheritDoc
         * @throws IOException
         */
        public static function applyTemplate(ProjectManager $project_manager): void
        {
            // Create the release build configuration
            $release_compressed = new BuildConfiguration('release-compressed',
                'build' . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . AssemblyConstants::ASSEMBLY_PACKAGE->value . '.gz.ncc'
            );
            $release_compressed->setBuildType(BuildOutputType::NCC_PACKAGE->value);
            $release_compressed->setOption(BuildConfigurationOptions::COMPRESSION->value, BuildConfigurationOptions\CompressionOptions::HIGH->value);
            $project_manager->getProjectConfiguration()->getBuild()->addBuildConfiguration($release_compressed);

            // Create the debug build configuration
            $debug_compressed = new BuildConfiguration('debug-compressed',
                'build' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . AssemblyConstants::ASSEMBLY_PACKAGE->value . '.gz.ncc'
            );
            $debug_compressed->setBuildType(BuildOutputType::NCC_PACKAGE->value);
            $debug_compressed->setOption(BuildConfigurationOptions::COMPRESSION->value, BuildConfigurationOptions\CompressionOptions::HIGH->value);
            $debug_compressed->setDefinedConstant('DEBUG', '1');
            $project_manager->getProjectConfiguration()->getBuild()->addBuildConfiguration($debug_compressed);

            $project_manager->save();
        }
    }