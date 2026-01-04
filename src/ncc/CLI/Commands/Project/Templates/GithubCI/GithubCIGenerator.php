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

namespace ncc\CLI\Commands\Project\Templates\GithubCI;

use ncc\Classes\Console;
use ncc\Classes\IO;
use ncc\Interfaces\TemplateGeneratorInterface;
use ncc\Objects\Project;

class GithubCIGenerator implements TemplateGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public static function generate(string $projectDirectory, Project $projectConfiguration): void
    {
        $targetFile = $projectDirectory . DIRECTORY_SEPARATOR . '.github' . DIRECTORY_SEPARATOR . 'workflows' . DIRECTORY_SEPARATOR . 'ci.yml';

        // Create .github/workflows directory if it doesn't exist
        if(!IO::exists(dirname($targetFile)))
        {
            IO::mkdir(dirname($targetFile));
        }

        // Remove the workflow file if it exists
        if(IO::exists($targetFile))
        {
            IO::rm($targetFile);
        }

        // Load templates
        $baseWorkflow = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'workflow.tpl');
        $buildJobTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'build-job.tpl');
        $downloadArtifactTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'download-artifact.tpl');
        $phpdocJobTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'phpdoc-job.tpl');
        $phpunitJobTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'phpunit-job.tpl');
        $releaseDocumentationJobTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'release-documentation-job.tpl');
        $releaseArtifactsJobTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'release-artifacts-job.tpl');

        // Get build configurations
        $buildConfigurations = $projectConfiguration->getBuildConfigurations();
        $defaultBuildConfig = $projectConfiguration->getDefaultBuild();
        $defaultBuildConfiguration = $projectConfiguration->getBuildConfiguration($defaultBuildConfig);

        // Generate build jobs
        $buildJobs = [];
        $buildNames = [];
        foreach($buildConfigurations as $buildConfig)
        {
            $buildJob = $buildJobTemplate;
            $buildJob = str_replace('${BUILD_NAME}', $buildConfig->getName(), $buildJob);
            $buildJob = str_replace('${BUILD_OUTPUT}', $buildConfig->getOutput(), $buildJob);
            $buildJobs[] = $buildJob;
            $buildNames[] = $buildConfig->getName();
        }

        // Generate download artifacts steps
        $downloadArtifacts = [];
        foreach($buildConfigurations as $buildConfig)
        {
            $downloadArtifact = $downloadArtifactTemplate;
            $downloadArtifact = str_replace('${BUILD_NAME}', $buildConfig->getName(), $downloadArtifact);
            $downloadArtifacts[] = $downloadArtifact;
        }

        // Replace placeholders in main workflow
        $baseWorkflow = str_replace('${BUILD_JOBS}', implode("\n", $buildJobs), $baseWorkflow);
        $baseWorkflow = str_replace('${BUILD_NAMES}', implode(', ', $buildNames), $baseWorkflow);
        $baseWorkflow = str_replace('${DEFAULT_BUILD_CONFIG}', $defaultBuildConfig, $baseWorkflow);
        $baseWorkflow = str_replace('${DEFAULT_BUILD_OUTPUT}', $defaultBuildConfiguration->getOutput(), $baseWorkflow);
        $baseWorkflow = str_replace('${DOWNLOAD_ARTIFACTS}', implode("\n", $downloadArtifacts), $baseWorkflow);

        // Handle optional PHPDoc job
        if(IO::exists($projectDirectory . DIRECTORY_SEPARATOR . 'phpdoc.dist.xml'))
        {
            $phpdocJob = str_replace('${DEFAULT_BUILD_CONFIG}', $defaultBuildConfig, $phpdocJobTemplate);
            $baseWorkflow = str_replace('${PHPDOC_JOB}', $phpdocJob, $baseWorkflow);
            $baseWorkflow = str_replace('${RELEASE_DOCUMENTATION_JOB}', $releaseDocumentationJobTemplate, $baseWorkflow);
        }
        else
        {
            $baseWorkflow = str_replace('${PHPDOC_JOB}', '', $baseWorkflow);
            $baseWorkflow = str_replace('${RELEASE_DOCUMENTATION_JOB}', '', $baseWorkflow);
        }

        // Handle optional PHPUnit job
        if(IO::exists($projectDirectory . DIRECTORY_SEPARATOR . 'phpunit.xml'))
        {
            $phpunitJob = str_replace('${BUILD_NAMES}', implode(', ', $buildNames), $phpunitJobTemplate);
            $phpunitJob = str_replace('${DEFAULT_BUILD_CONFIG}', $defaultBuildConfig, $phpunitJob);
            $phpunitJob = str_replace('${DEFAULT_BUILD_OUTPUT}', $defaultBuildConfiguration->getOutput(), $phpunitJob);
            $baseWorkflow = str_replace('${PHPUNIT_JOB}', $phpunitJob, $baseWorkflow);
        }
        else
        {
            $baseWorkflow = str_replace('${PHPUNIT_JOB}', '', $baseWorkflow);
        }

        // Handle release artifacts job
        $releaseArtifactsJob = str_replace('${BUILD_NAMES}', implode(', ', $buildNames), $releaseArtifactsJobTemplate);
        $releaseArtifactsJob = str_replace('${DOWNLOAD_ARTIFACTS}', implode("\n", $downloadArtifacts), $releaseArtifactsJob);
        $baseWorkflow = str_replace('${RELEASE_ARTIFACTS_JOB}', $releaseArtifactsJob, $baseWorkflow);

        // Write the workflow file
        IO::writeFile($targetFile, $baseWorkflow);
        Console::out(sprintf("Generated File: %s", $targetFile));
    }
}
