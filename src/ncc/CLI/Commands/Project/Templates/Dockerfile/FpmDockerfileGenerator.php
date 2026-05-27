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

namespace ncc\CLI\Commands\Project\Templates\Dockerfile;

use ncc\Classes\Console;
use ncc\Libraries\fslib\IO;
use ncc\Interfaces\TemplateGeneratorInterface;
use ncc\Objects\Project;

class FpmDockerfileGenerator implements TemplateGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public static function generate(string $projectDirectory, Project $projectConfiguration): void
    {
        $assembly = $projectConfiguration->getAssembly();
        $packageName = $assembly->getPackage();
        $buildConfigName = $projectConfiguration->getDefaultBuild();
        $buildConfiguration = $projectConfiguration->getBuildConfiguration($buildConfigName);

        if($buildConfiguration === null)
        {
            Console::error(sprintf("Build configuration '%s' not found", $buildConfigName));
            return;
        }

        $buildOutput = $buildConfiguration->getOutput();

        $dockerfileTemplate = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'FpmDockerfile.tpl');

        $dockerfileContent = str_replace('${BUILD_OUTPUT}', $buildOutput, $dockerfileTemplate);
        $dockerfileContent = str_replace('${BUILD_CONFIGURATION}', $buildConfigName, $dockerfileContent);
        $dockerfileContent = str_replace('${PACKAGE_NAME}', $packageName, $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_NAME}', $assembly->getName(), $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_VERSION}', $assembly->getVersion(), $dockerfileContent);

        $dockerfilePath = $projectDirectory . DIRECTORY_SEPARATOR . 'Dockerfile';
        if(IO::exists($dockerfilePath))
        {
            IO::delete($dockerfilePath);
        }
        IO::writeFile($dockerfilePath, $dockerfileContent);
        Console::out(sprintf("Generated File: %s", $dockerfilePath));
    }
}