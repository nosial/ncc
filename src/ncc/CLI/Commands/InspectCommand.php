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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\IO;
    use ncc\Classes\PackageReader;

    class InspectCommand extends AbstractCommandHandler
    {
        /**
         * Prints out the help menu for the inspect command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc inspect --path=<package-file> [--component=<name>]' . PHP_EOL);
            Console::out('Displays detailed information about an ncc package.' . PHP_EOL);
            Console::out('This includes package metadata, version, components, resources,');
            Console::out('execution units, dependencies, and entry points.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --path=<file>       (Required) Path to the .ncc package file to inspect');
            Console::out('  --component=<name>  (Optional) Display the contents of a specific component');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc inspect --path=mypackage.ncc');
            Console::out('  ncc inspect --path=mypackage.ncc --component=Process.php');
        }

        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $packagePath = null;
            if(isset($argv['path']))
            {
                $packagePath = realpath($argv['path']);
            }
            else
            {
                Console::error('No package path specified, use --path to specify a package file to inspect');
                return 1;
            }

            if(!IO::exists($packagePath) || !IO::isFile($packagePath))
            {
                Console::error('The given path must be a ncc package file');
                return 1;
            }

            try
            {
                $packageReader = new PackageReader($packagePath);
            }
            catch(Exception $e)
            {
                Console::error($e->getMessage());
                return 1;
            }

            // If --component is specified, output the component contents and exit
            if(isset($argv['component']))
            {
                $componentName = $argv['component'];
                try
                {
                    $componentRef = $packageReader->findComponent($componentName);
                    if($componentRef === null)
                    {
                        Console::error('Component not found: ' . $componentName);
                        return 1;
                    }
                    
                    $data = $packageReader->readComponent($componentRef);
                    Console::out('=== Component: ' . $componentName . ' ===');
                    Console::out('Size: ' . strlen($data) . ' bytes');
                    Console::out('Compressed: ' . ($packageReader->getHeader()->isCompressed() ? 'Yes' : 'No'));
                    Console::out(PHP_EOL . '=== Content ===' . PHP_EOL);
                    Console::out($data);
                    return 0;
                }
                catch(Exception $e)
                {
                    Console::error('Failed to read component: ' . $e->getMessage());
                    return 1;
                }
            }

            // =============== PACKAGE STRUCTURE INFORMATION ===============
            Console::out('=== Package Structure ===');
            Console::out('Package Structure Version: ' . $packageReader->getPackageVersion());
            Console::out('Build Number: ' . bin2hex($packageReader->getHeader()->getBuildNumber()));
            Console::out('Compressed: ' . ($packageReader->getHeader()->isCompressed() ? 'Yes' : 'No'));
            Console::out('Statically Linked: ' . ($packageReader->getHeader()->isStaticallyLinked() ? 'Yes' : 'No'));
            
            $flags = $packageReader->getHeader()->getFlags();
            if(!empty($flags))
            {
                Console::out('Flags: ' . implode(', ', $flags));
            }

            // =============== ASSEMBLY INFORMATION ===============
            Console::out(PHP_EOL . '=== Assembly Information ===');
            Console::out('Package: ' . $packageReader->getAssembly()->getPackage());
            Console::out('Name: ' . $packageReader->getAssembly()->getName());
            Console::out('Version: ' . $packageReader->getAssembly()->getVersion());
            
            if($packageReader->getAssembly()->getDescription())
            {
                Console::out('Description: ' . $packageReader->getAssembly()->getDescription());
            }
            
            if($packageReader->getAssembly()->getAuthor())
            {
                Console::out('Author: ' . $packageReader->getAssembly()->getAuthor());
            }
            
            if($packageReader->getAssembly()->getOrganization())
            {
                Console::out('Organization: ' . $packageReader->getAssembly()->getOrganization());
            }
            
            if($packageReader->getAssembly()->getProduct())
            {
                Console::out('Product: ' . $packageReader->getAssembly()->getProduct());
            }
            
            if($packageReader->getAssembly()->getLicense())
            {
                Console::out('License: ' . $packageReader->getAssembly()->getLicense());
            }
            
            if($packageReader->getAssembly()->getUrl())
            {
                Console::out('URL: ' . $packageReader->getAssembly()->getUrl());
            }
            
            if($packageReader->getAssembly()->getCopyright())
            {
                Console::out('Copyright: ' . $packageReader->getAssembly()->getCopyright());
            }
            
            if($packageReader->getAssembly()->getTrademark())
            {
                Console::out('Trademark: ' . $packageReader->getAssembly()->getTrademark());
            }

            // =============== EXECUTION POINTS ===============
            Console::out(PHP_EOL . '=== Execution Points ===');
            if($packageReader->getHeader()->getEntryPoint())
            {
                Console::out('Main Entry Point: ' . $packageReader->getHeader()->getEntryPoint());
            }
            else
            {
                Console::out('Main Entry Point: None (Not Executable)');
            }
            
            if($packageReader->getHeader()->getWebEntryPoint())
            {
                Console::out('Web Entry Point: ' . $packageReader->getHeader()->getWebEntryPoint());
            }
            
            $preInstall = $packageReader->getHeader()->getPreInstall();
            if($preInstall)
            {
                Console::out('Pre-Install: ' . (is_array($preInstall) ? implode(', ', $preInstall) : $preInstall));
            }
            
            $postInstall = $packageReader->getHeader()->getPostInstall();
            if($postInstall)
            {
                Console::out('Post-Install: ' . (is_array($postInstall) ? implode(', ', $postInstall) : $postInstall));
            }

            // =============== DEPENDENCIES ===============
            $dependencies = $packageReader->getHeader()->getDependencyReferences();
            if(!empty($dependencies))
            {
                Console::out(PHP_EOL . '=== Dependencies (' . count($dependencies) . ') ===');
                foreach($dependencies as $dependency)
                {
                    Console::out('- ' . $dependency->getPackage() . '=' . $dependency->getVersion() . 
                        ($dependency->getSource() ? ' (Source: ' . $dependency->getSource() . ')' : ''));
                }
            }

            // =============== DEFINED CONSTANTS ===============
            $constants = $packageReader->getHeader()->getDefinedConstants();
            if($constants !== null && !empty($constants))
            {
                Console::out(PHP_EOL . '=== Defined Constants (' . count($constants) . ') ===');
                foreach($constants as $key => $value)
                {
                    Console::out('- ' . $key . ' = ' . (is_string($value) ? $value : json_encode($value)));
                }
            }

            // =============== REPOSITORIES ===============
            $repositories = $packageReader->getHeader()->getRepositories();
            if($repositories !== null && !empty($repositories))
            {
                Console::out(PHP_EOL . '=== Repositories (' . count($repositories) . ') ===');
                foreach($repositories as $repository)
                {
                    Console::out('- ' . $repository->getName() . ': ' . $repository->getHost());
                }
            }

            // =============== UPDATE SOURCE ===============
            $updateSource = $packageReader->getHeader()->getUpdateSource();
            if($updateSource !== null)
            {
                Console::out(PHP_EOL . '=== Update Source ===');
                Console::out('Repository: ' . $updateSource->getRepository());
                Console::out('Name: ' . $updateSource->getName());
                if($updateSource->getVersion())
                {
                    Console::out('Version: ' . $updateSource->getVersion());
                }
                if($updateSource->getOrganization())
                {
                    Console::out('Organization: ' . $updateSource->getOrganization());
                }
            }

            // =============== AUTOLOADER ===============
            $autoloader = $packageReader->getHeader()->getAutoloader();
            if($autoloader !== null && !empty($autoloader))
            {
                Console::out(PHP_EOL . '=== Autoloader (' . count($autoloader) . ' classes mapped) ===');
                foreach($autoloader as $className => $filePath)
                {
                    Console::out('- ' . $className . ' => ' . $filePath);
                }
            }

            // =============== COMPONENTS ===============
            $components = $packageReader->getComponentReferences();
            if(!empty($components))
            {
                Console::out(PHP_EOL . '=== Components (' . count($components) . ') ===');
                foreach($components as $component)
                {
                    Console::out('- ' . $component->getName() . ' (Size: ' . number_format($component->getSize()) . ' bytes)');
                }
            }

            // =============== RESOURCES ===============
            $resources = $packageReader->getResourceReferences();
            if(!empty($resources))
            {
                Console::out(PHP_EOL . '=== Resources (' . count($resources) . ') ===');
                foreach($resources as $resource)
                {
                    Console::out('- ' . $resource->getName() . ' (Size: ' . number_format($resource->getSize()) . ' bytes)');
                }
            }

            // =============== EXECUTION UNITS ===============
            $executionUnits = $packageReader->getExecutionUnitReferences();
            if(!empty($executionUnits))
            {
                Console::out(PHP_EOL . '=== Execution Units (' . count($executionUnits) . ') ===');
                foreach($executionUnits as $reference)
                {
                    $executionUnit = $packageReader->readExecutionUnit($reference);
                    Console::out('- ' . $executionUnit->getName());
                    Console::out('  Type: ' . $executionUnit->getType()->value);
                    Console::out('  Mode: ' . $executionUnit->getMode()->value);
                    Console::out('  Entry Point: ' . $executionUnit->getEntryPoint());
                    Console::out('  Working Directory: ' . $executionUnit->getWorkingDirectory());
                    
                    $requiredFiles = $executionUnit->getRequiredFiles();
                    if(!empty($requiredFiles))
                    {
                        Console::out('  Required Files: ' . implode(', ', $requiredFiles));
                    }
                }
            }

            return 0;
        }
    }