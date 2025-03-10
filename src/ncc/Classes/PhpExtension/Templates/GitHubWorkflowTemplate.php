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

class GitHubWorkflowTemplate
{
    /**
     * @inheritDoc
     * @param ProjectManager $project_manager
     * @throws IOException
     * @throws PathNotFoundException
     */
    public static function applyTemplate(ProjectManager $project_manager): void
    {
        self::writeCiTemplate($project_manager);
    }

    /**
     * Writes the Makefile to the project directory
     *
     * @param ProjectManager $project_manager
     * @return void
     * @throws IOException
     * @throws PathNotFoundException
     */
    private static function writeCiTemplate(ProjectManager $project_manager): void
    {
        $ci_dir = $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . '.github' . DIRECTORY_SEPARATOR . 'workflows';

        if(!file_exists($ci_dir))
        {
            mkdir($ci_dir, 0777, true);
        }

        $template =  IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'github_ci.yml.tpl');
        $default_configuration = $project_manager->getProjectConfiguration()->getBuild()->getDefaultConfiguration();
        $builds = [];
        $releases = [];
        $downloads = [];
        foreach($project_manager->getProjectConfiguration()->getBuild()->getBuildConfigurations() as $build_name)
        {
            $build_template = IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'github_ci_build.yml.tpl');
            $build_template = str_replace('%TPL_BUILD_NAME%', $build_name, $build_template);
            $build_template = str_replace('%TPL_BUILD_OUTPUT%',
                $project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_name)->getOutput(),
                $build_template
            );
            $builds[$build_name] = $build_template;

            $download_template = IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'github_ci_download.yml.tpl');
            $download_template = str_replace('%TPL_BUILD_NAME%', $build_name, $download_template);
            $downloads[$build_name] = $download_template;

            $release_template = str_repeat(' ', 12) . $build_name . DIRECTORY_SEPARATOR . basename(
                $project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_name)->getOutput()
            );
            $releases[$build_name] = $release_template;
        }

        $build_jobs = '';
        foreach($builds as $name => $build_template)
        {
            $build_jobs .= $build_template . PHP_EOL;
        }

        $download_jobs = '';
        foreach($downloads as $name => $download_template)
        {
            $download_jobs .= $download_template . PHP_EOL;
        }

        $release_jobs = '';
        foreach($releases as $name => $release_template)
        {
            $release_jobs .= $release_template . PHP_EOL;
        }

        $template = str_replace('%TPL_BUILDS%', $build_jobs, $template);
        $template = str_replace('%TPL_DOWNLOAD_ARTIFACTS%', $download_jobs, $template);
        $template = str_replace('%TPL_ARTIFACT_FILES%', $release_jobs, $template);
        $template = str_replace('%TPL_BUILD_NAMES%', implode(', ', array_keys($builds)), $template);
        $template = str_replace('%TPL_DEFAULT_BUILD_CONFIGURATION%', $default_configuration, $template);
        $template = str_replace('%TPL_DEFAULT_ARTIFACT_BUILD_OUTPUT%', $default_configuration . DIRECTORY_SEPARATOR . basename(
                $project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($default_configuration)->getOutput()
        ), $template);

        IO::fwrite($ci_dir . DIRECTORY_SEPARATOR . 'ncc_workflow.yml',
            ConstantCompiler::compileConstants($project_manager->getProjectConfiguration(), $template)
        );
    }
}