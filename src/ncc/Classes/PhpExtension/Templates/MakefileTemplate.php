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

use ncc\Classes\NccExtension\ConstantCompiler;
use ncc\Exceptions\IOException;
use ncc\Exceptions\PathNotFoundException;
use ncc\Managers\ProjectManager;
use ncc\Utilities\IO;

class MakefileTemplate
{
    /**
     * @inheritDoc
     * @param ProjectManager $project_manager
     * @throws IOException
     * @throws PathNotFoundException
     */
    public static function applyTemplate(ProjectManager $project_manager): void
    {
        self::writeMakefileTemplate($project_manager);
    }

    /**
     * Writes the Makefile template for the given project.
     *
     * @param ProjectManager $project_manager The project manager containing project configurations.
     * @throws IOException If there is an error reading or writing files.
     * @throws PathNotFoundException If a required file path is not found.
     */
    private static function writeMakefileTemplate(ProjectManager $project_manager): void
    {
        $makefile_template = IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'Makefile.tpl');
        $builds = [];

        foreach($project_manager->getProjectConfiguration()->getBuild()->getBuildConfigurations() as $build_name)
        {
            $builds[$build_name] = str_replace('%TPL_BUILD_NAME%', $build_name, IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'make_build.tpl'));
        }

        $default_build = $project_manager->getProjectConfiguration()->getBuild()->getDefaultConfiguration();
        $makefile_template = str_replace('%TPL_DEFAULT_BUILD_CONFIGURATION%', $default_build, $makefile_template);
        $makefile_template = str_replace('%TPL_DEFAULT_BUILD_PATH%', $project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($default_build)->getOutput(), $makefile_template);
        $makefile_template = str_replace('%TPL_BUILD_NAMES%', implode(' ', array_keys($builds)), $makefile_template);

        $build_template = '';
        foreach($builds as $name => $template)
        {
            $build_template .= $template . PHP_EOL;
        }

        $makefile_template = str_replace('%TPL_BUILDS%', $build_template, $makefile_template);
        IO::fwrite(
            $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'Makefile',
            ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(), $makefile_template)
        );
    }
}