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

    namespace ncc\CLI\Commands\Project;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Libraries\fslib\IO;
    use ncc\CLI\Commands\Helper;
    use ncc\Libraries\fslib\IOException;
    use ncc\Libraries\Process\Process;
    use ncc\Libraries\Yaml\Yaml;
    use ncc\Objects\Project;

    class DockerUpdate extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                // Delegate to parent's help command
                return 0;
            }

            // Get project path
            $projectPath = $argv['path'] ?? getcwd();
            $projectPath = Helper::resolveProjectConfigurationPath($projectPath);
            
            if($projectPath === null)
            {
                Console::error("No project configuration file found");
                return 1;
            }

            $projectDirectory = dirname($projectPath);

            // Check if docker-compose.yml exists
            $dockerComposePath = $projectDirectory . DIRECTORY_SEPARATOR . 'docker-compose.yml';
            if(!IO::exists($dockerComposePath))
            {
                Console::error("docker-compose.yml not found in project directory");
                Console::out("Generate it first using: ncc project --generate=docker");
                return 1;
            }

            // Parse docker-compose.yml to get container name
            try
            {
                $dockerComposeContent = IO::readFile($dockerComposePath);
                $dockerCompose = Yaml::parse($dockerComposeContent);
                
                if(!isset($dockerCompose['services']) || !is_array($dockerCompose['services']))
                {
                    Console::error("No services found in docker-compose.yml");
                    return 1;
                }
                
                // Find the service that builds from the local Dockerfile
                $containerName = null;
                $serviceName = null;
                
                foreach($dockerCompose['services'] as $name => $service)
                {
                    if(!is_array($service))
                    {
                        continue;
                    }
                    
                    // Check if this service builds from local Dockerfile
                    $buildsLocally = false;
                    
                    if(isset($service['build']))
                    {
                        if(is_string($service['build']))
                        {
                            // Simple build path like "build: ."
                            $buildsLocally = ($service['build'] === '.' || $service['build'] === './');
                        }
                        elseif(is_array($service['build']))
                        {
                            // Build with context and/or dockerfile specified
                            $context = $service['build']['context'] ?? null;
                            $dockerfile = $service['build']['dockerfile'] ?? 'Dockerfile';
                            
                            // Check if context is local and dockerfile is in project
                            if($context === '.' || $context === './' || $context === null)
                            {
                                $buildsLocally = true;
                            }
                        }
                    }
                    
                    if($buildsLocally && isset($service['container_name']))
                    {
                        $containerName = $service['container_name'];
                        $serviceName = $name;
                        break;
                    }
                }
                
                if($containerName === null)
                {
                    Console::error("Could not find a service with local Dockerfile build and container_name in docker-compose.yml");
                    return 1;
                }
                
                Console::out(sprintf("Detected service: %s", $serviceName));
                Console::out(sprintf("Detected container name: %s", $containerName));
            }
            catch(Exception $e)
            {
                Console::error("Failed to parse docker-compose.yml: " . $e->getMessage());
                return 1;
            }

            // Get build configuration
            $configuration = $argv['configuration'] ?? $argv['config'] ?? null;


            try
            {
                // Load project to get package name
                $projectConfiguration = Project::fromFile($projectPath);
                $packageName = $projectConfiguration->getAssembly()->getPackage();

                // By defualt compile the web_release if it's set.
                if($configuration === null && $projectConfiguration->buildConfigurationExists('web_release'))
                {
                    $configuration = 'web_release';
                }

                // Step 1: Build the project locally
                Console::out("Building project...");
                $compiler = Project::compilerFromFile($projectPath, $configuration);
                $outputPath = $compiler->compile(function(int $current, int $total, string $message) {
                    Console::inlineProgress($current, $total, $message);
                });
                Console::completeProgress("Build completed: " . $outputPath);

                // Step 2: Check if container is running
                Console::out(sprintf("Checking if container '%s' is running...", $containerName));
                
                $checkProcess = new Process(['docker', 'inspect', '-f', '{{.State.Running}}', $containerName]);
                $checkProcess->run();
                
                if(!$checkProcess->isSuccessful() || trim($checkProcess->getOutput()) !== 'true')
                {
                    Console::error(sprintf("Container '%s' is not running or does not exist", $containerName));
                    return 1;
                }

                Console::out(sprintf("Container '%s' is running", $containerName));

                // Step 3: Transfer the compiled package to the container
                Console::out("Transferring package to container...");
                $containerPath = '/tmp/' . basename($outputPath);
                
                $copyProcess = new Process(['docker', 'cp', $outputPath, sprintf('%s:%s', $containerName, $containerPath)]);
                $copyProcess->run();
                
                if(!$copyProcess->isSuccessful())
                {
                    Console::error("Failed to copy package to container");
                    Console::out($copyProcess->getErrorOutput());
                    return 1;
                }

                Console::out(sprintf("Package transferred to container at: %s", $containerPath));

                // Step 4: Uninstall old package in container
                Console::out(sprintf("Uninstalling old package '%s' in container...", $packageName));
                
                $uninstallProcess = new Process(['docker', 'exec', $containerName, 'ncc', 'uninstall', sprintf('--package=%s', $packageName), '--yes']);
                $uninstallProcess->run();
                
                // Show uninstall output
                $uninstallOutput = trim($uninstallProcess->getOutput());
                if(!empty($uninstallOutput))
                {
                    foreach(explode("\n", $uninstallOutput) as $line)
                    {
                        Console::out("  " . $line);
                    }
                }
                
                // Don't fail if uninstall fails - package might not be installed yet
                if(!$uninstallProcess->isSuccessful())
                {
                    Console::warning("Uninstall returned non-zero exit code (package may not have been installed)");
                }

                // Step 5: Install new package in container
                Console::out("Installing new package in container...");
                
                $installProcess = new Process(['docker', 'exec', $containerName, 'ncc', 'install', sprintf('--package=%s', $containerPath), '--yes']);
                $installProcess->run();
                
                // Show install output
                $installOutput = trim($installProcess->getOutput());
                if(!empty($installOutput))
                {
                    foreach(explode("\n", $installOutput) as $line)
                    {
                        Console::out("  " . $line);
                    }
                }
                
                if(!$installProcess->isSuccessful())
                {
                    Console::error("Failed to install package in container");
                    $errorOutput = trim($installProcess->getErrorOutput());
                    if(!empty($errorOutput))
                    {
                        Console::out($errorOutput);
                    }
                    return 1;
                }

                // Step 6: Cleanup - Remove the temporary package file from container
                Console::out("Cleaning up temporary files...");
                
                $cleanupProcess = new Process(['docker', 'exec', $containerName, 'rm', $containerPath]);
                $cleanupProcess->run();
                
                if(!$cleanupProcess->isSuccessful())
                {
                    Console::warning("Failed to remove temporary package file from container");
                }

                Console::out(sprintf("\nSuccessfully updated package '%s' in container '%s'", $packageName, $containerName));
                Console::out("The changes should take effect immediately.");

                return 0;
            }
            catch(IOException $e)
            {
                Console::clearInlineProgress();
                Console::error("Build failed: " . $e->getMessage());
                return 1;
            }
            catch(Exception $e)
            {
                Console::clearInlineProgress();
                Console::error("Update failed: " . $e->getMessage());
                return 1;
            }
        }
    }
