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

class DockerfileGenerator implements TemplateGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public static function generate(string $projectDirectory, Project $projectConfiguration): void
    {
        // Check if webEntryPoint is configured
        if($projectConfiguration->getWebEntryPoint() === null)
        {
            Console::error("Cannot generate Dockerfile: webEntryPoint is not configured in project.json");
            Console::out("To use this template, add a 'web_entry_point' field in your project configuration.");
            return;
        }

        $webEntryPoint = $projectConfiguration->getWebEntryPoint();
        $assembly = $projectConfiguration->getAssembly();
        $packageName = $assembly->getPackage();
        
        // Check if 'web_release' build configuration exists, use it if available
        $buildConfigName = $projectConfiguration->getDefaultBuild();
        $webBuildConfiguration = $projectConfiguration->getBuildConfiguration('web_release');
        if($webBuildConfiguration !== null)
        {
            $buildConfigName = 'web_release';
        }
        
        $buildConfiguration = $projectConfiguration->getBuildConfiguration($buildConfigName);
        $buildOutput = $buildConfiguration->getOutput();

        // Find the execution unit and get its entry point file
        $executionUnits = $projectConfiguration->getExecutionUnits();
        $webEntryPointFile = null;
        $executionUnit = null;
        
        if($executionUnits !== null)
        {
            foreach($executionUnits as $unit)
            {
                if($unit->getName() === $webEntryPoint)
                {
                    $executionUnit = $unit;
                    $webEntryPointFile = $unit->getEntryPoint();
                    break;
                }
            }
        }

        if($webEntryPointFile === null || $executionUnit === null)
        {
            Console::error(sprintf("Cannot find execution unit '%s' in project configuration", $webEntryPoint));
            return;
        }

        // Resolve the entry point file path
        $entryPointPath = $webEntryPointFile;
        
        // First, check if file exists relative to project directory
        $fullPath = $projectDirectory . DIRECTORY_SEPARATOR . $entryPointPath;
        if(!IO::exists($fullPath))
        {
            // Try with .php extension
            if(!str_ends_with($entryPointPath, '.php') && IO::exists($fullPath . '.php'))
            {
                $entryPointPath .= '.php';
                $fullPath .= '.php';
            }
            // If still not found, try prepending source path
            elseif(!str_starts_with($entryPointPath, '/'))
            {
                $entryPointPath = $projectConfiguration->getSourcePath() . '/' . $webEntryPointFile;
                $fullPath = $projectDirectory . DIRECTORY_SEPARATOR . $entryPointPath;
                
                // Try with .php extension again
                if(!IO::exists($fullPath))
                {
                    if(!str_ends_with($entryPointPath, '.php') && IO::exists($fullPath . '.php'))
                    {
                        $entryPointPath .= '.php';
                        $fullPath .= '.php';
                    }
                }
            }
        }
        
        // Final verification
        if(!IO::exists($fullPath))
        {
            Console::error(sprintf("Entry point file does not exist: %s", $fullPath));
            Console::out(sprintf("Tried locations relative to project root: %s, %s", 
                $webEntryPointFile, 
                $projectConfiguration->getSourcePath() . '/' . $webEntryPointFile));
            Console::out("Please verify the execution unit's 'entry' property points to a valid file.");
            return;
        }
        
        // Use the resolved path
        $webEntryPointFile = $entryPointPath;

        // Load templates
        $dockerfileTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'Dockerfile.tpl');
        $nginxTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'nginx.conf.tpl');
        $supervisordTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'supervisord.conf.tpl');
        $entrypointTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'docker-entrypoint.sh.tpl');
        $dockerComposeTemplate = IO::readFile(__DIR__ . DIRECTORY_SEPARATOR . 'docker-compose.yml.tpl');

        // Replace placeholders in Dockerfile
        $dockerfileContent = str_replace('${BUILD_OUTPUT}', $buildOutput, $dockerfileTemplate);
        $dockerfileContent = str_replace('${BUILD_CONFIGURATION}', $buildConfigName, $dockerfileContent);
        $dockerfileContent = str_replace('${PACKAGE_NAME}', $packageName, $dockerfileContent);
        $dockerfileContent = str_replace('${WEB_ENTRY_POINT}', $webEntryPoint, $dockerfileContent);
        $dockerfileContent = str_replace('${WEB_ENTRY_POINT_FILE}', $webEntryPointFile, $dockerfileContent);
        
        // Add required files copy commands if specified
        $requiredFilesCopy = '';
        if($executionUnit->getRequiredFiles() !== null && count($executionUnit->getRequiredFiles()) > 0)
        {
            $copyCommands = [];
            foreach($executionUnit->getRequiredFiles() as $file)
            {
                $copyCommands[] = sprintf('COPY --from=builder /app/%s /var/www/html/%s', $file, $file);
            }
            $requiredFilesCopy = implode("\n", $copyCommands);
        }
        $dockerfileContent = str_replace('${REQUIRED_FILES_COPY}', $requiredFilesCopy, $dockerfileContent);
        
        // Set working directory if specified
        $workingDir = $executionUnit->getWorkingDirectory() ?? '/var/www/html';
        $dockerfileContent = str_replace('${WORKING_DIRECTORY}', $workingDir, $dockerfileContent);
        
        // Replace assembly metadata placeholders (handle optional fields)
        $dockerfileContent = str_replace('${ASSEMBLY_NAME}', $assembly->getName(), $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_VERSION}', $assembly->getVersion(), $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_ORGANIZATION}', $assembly->getOrganization() ?? '', $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_AUTHOR}', $assembly->getAuthor() ?? '', $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_DESCRIPTION}', $assembly->getDescription() ?? '', $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_URL}', $assembly->getUrl() ?? '', $dockerfileContent);
        $dockerfileContent = str_replace('${ASSEMBLY_LICENSE}', $assembly->getLicense() ?? '', $dockerfileContent);

        // Replace placeholders in nginx config (if any)
        $nginxContent = $nginxTemplate;

        // Replace placeholders in supervisord config (if any)
        $supervisordContent = $supervisordTemplate;

        // Replace placeholders in entrypoint script (if any)
        $entrypointContent = $entrypointTemplate;

        // Replace placeholders in docker-compose.yml
        $dockerComposeContent = str_replace('${PACKAGE_NAME}', $packageName, $dockerComposeTemplate);
        $dockerComposeContent = str_replace('${ASSEMBLY_VERSION}', $assembly->getVersion(), $dockerComposeContent);
        
        // Build environment variables section for docker-compose
        $envVars = [];
        $envVars[] = '      - PHP_MEMORY_LIMIT=256M';
        $envVars[] = '      - PHP_MAX_EXECUTION_TIME=60';
        
        // Add custom environment variables from execution unit
        if($executionUnit->getEnvironment() !== null)
        {
            foreach($executionUnit->getEnvironment() as $key => $value)
            {
                $envVars[] = sprintf('      - %s=%s', $key, $value);
            }
        }
        
        $dockerComposeContent = str_replace('${ENVIRONMENT_VARIABLES}', implode("\n", $envVars), $dockerComposeContent);
        
        // Build volumes section for required files
        $volumesSection = '';
        if($executionUnit->getRequiredFiles() !== null && count($executionUnit->getRequiredFiles()) > 0)
        {
            $volumes = [];
            $volumes[] = '    volumes:';
            foreach($executionUnit->getRequiredFiles() as $file)
            {
                $volumes[] = sprintf('      - ./%s:/app/%s:ro', $file, $file);
            }
            $volumesSection = implode("\n", $volumes);
        }
        else
        {
            $volumesSection = '    # volumes:';
        }
        
        $dockerComposeContent = str_replace('${VOLUMES_SECTION}', $volumesSection, $dockerComposeContent);

        // Write Dockerfile
        $dockerfilePath = $projectDirectory . DIRECTORY_SEPARATOR . 'Dockerfile';
        if(IO::exists($dockerfilePath))
        {
            IO::delete($dockerfilePath);
        }
        IO::writeFile($dockerfilePath, $dockerfileContent);
        Console::out(sprintf("Generated File: %s", $dockerfilePath));

        // Write nginx.conf
        $nginxPath = $projectDirectory . DIRECTORY_SEPARATOR . 'nginx.conf';
        if(IO::exists($nginxPath))
        {
            IO::delete($nginxPath);
        }
        IO::writeFile($nginxPath, $nginxContent);

        // Write supervisord.conf
        $supervisordPath = $projectDirectory . DIRECTORY_SEPARATOR . 'supervisord.conf';
        if(IO::exists($supervisordPath))
        {
            IO::delete($supervisordPath);
        }
        IO::writeFile($supervisordPath, $supervisordContent);
        Console::out(sprintf("Generated File: %s", $supervisordPath));

        // Write File($nginxPath, $nginxContent);
        Console::out(sprintf("Generated File: %s", $nginxPath));

        // Write docker-entrypoint.sh
        $entrypointPath = $projectDirectory . DIRECTORY_SEPARATOR . 'docker-entrypoint.sh';
        if(IO::exists($entrypointPath))
        {

        // Write docker-compose.yml
        $dockerComposePath = $projectDirectory . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        if(IO::exists($dockerComposePath))
        {
            IO::delete($dockerComposePath);
        }
        IO::writeFile($dockerComposePath, $dockerComposeContent);
        Console::out(sprintf("Generated File: %s", $dockerComposePath));
            IO::delete($entrypointPath);
        }
        IO::writeFile($entrypointPath, $entrypointContent);
        Console::out(sprintf("Generated File: %s", $entrypointPath));
    }
}
