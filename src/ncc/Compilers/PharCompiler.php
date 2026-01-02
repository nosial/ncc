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

    namespace ncc\Compilers;

    use Exception;
    use ncc\Exceptions\OperationException;
    use Phar;
    use ncc\Abstracts\AbstractCompiler;
    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\PackageReader;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Libraries\pal\Autoloader;
    use ncc\Objects\ResolvedDependency;
    use function msgpack_pack;

    /**
     * PharCompiler creates PHP Archive (Phar) packages from ncc projects.
     * 
     * This compiler supports both dynamic and static builds:
     * - Dynamic builds: Require ncc runtime and use import() for dependencies
     * - Static builds: Self-contained with all dependencies embedded, no ncc required
     */
    class PharCompiler extends AbstractCompiler
    {
        private bool $compressionEnabled;
        private int $compressionLevel;
        private int $compressionType;
        private bool $skipExecution;

        /**
         * @inheritDoc
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            Logger::getLogger()->debug('Initializing PharCompiler');
            
            parent::__construct($projectFilePath, $buildConfiguration);

            // Phar-specific compression attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression']) && is_bool($this->getBuildConfiguration()->getOptions()['compression']))
            {
                $this->compressionEnabled = (bool)$this->getBuildConfiguration()->getOptions()['compression'];
                Logger::getLogger()->verbose(sprintf('Compression: %s', $this->compressionEnabled ? 'enabled' : 'disabled'));
            }
            else
            {
                $this->compressionEnabled = true;
                Logger::getLogger()->verbose('Compression: enabled (default)');
            }

            // Phar-specific compression level attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression_level']) && is_int($this->getBuildConfiguration()->getOptions()['compression_level']))
            {
                $this->compressionLevel = (int)$this->getBuildConfiguration()->getOptions()['compression_level'];
                if($this->compressionLevel > 9)
                {
                    Logger::getLogger()->warning(sprintf('Compression level %d exceeds maximum, using 9', $this->compressionLevel));
                    $this->compressionLevel = 9;
                }
                elseif($this->compressionLevel < 1)
                {
                    Logger::getLogger()->warning(sprintf('Compression level %d below minimum, using 1', $this->compressionLevel));
                    $this->compressionLevel = 1;
                }
                
                Logger::getLogger()->verbose(sprintf('Compression level: %d', $this->compressionLevel));
            }
            else
            {
                $this->compressionLevel = 9;
                Logger::getLogger()->verbose('Compression level: 9 (default)');
            }

            // Determine compression type (GZ by default)
            $this->compressionType = Phar::GZ;
            if(isset($this->getBuildConfiguration()->getOptions()['compression_type']))
            {
                $type = strtolower($this->getBuildConfiguration()->getOptions()['compression_type']);
                if($type === 'bz2' && extension_loaded('bz2'))
                {
                    $this->compressionType = Phar::BZ2;
                    Logger::getLogger()->verbose('Compression type: BZ2');
                }
                else
                {
                    Logger::getLogger()->verbose('Compression type: GZ (default)');
                }
            }

            // Check for skip_execution option
            if(isset($this->getBuildConfiguration()->getOptions()['skip_execution']) && is_bool($this->getBuildConfiguration()->getOptions()['skip_execution']))
            {
                $this->skipExecution = (bool)$this->getBuildConfiguration()->getOptions()['skip_execution'];
                Logger::getLogger()->verbose(sprintf('Skip execution: %s', $this->skipExecution ? 'enabled' : 'disabled'));
            }
            else
            {
                $this->skipExecution = false;
                Logger::getLogger()->verbose('Skip execution: disabled (default)');
            }
        }

        /**
         * @inheritDoc
         */
        public function compile(?callable $progressCallback=null, bool $overwrite=true): string
        {
            Logger::getLogger()->verbose(sprintf('Starting Phar compilation to: %s', $this->getOutputPath()));
            Logger::getLogger()->verbose(sprintf('Static linking: %s', $this->isStaticallyLinked() ? 'enabled' : 'disabled'));
            
            // Check if phar creation is allowed
            if(ini_get('phar.readonly'))
            {
                throw new OperationException('phar.readonly must be disabled in php.ini to create Phar archives. Set phar.readonly=0');
            }

            $outputPath = $this->getOutputPath();
            
            // Remove existing file if overwrite is enabled
            if(file_exists($outputPath))
            {
                if($overwrite)
                {
                    Logger::getLogger()->verbose('Removing existing Phar file');
                    unlink($outputPath);
                }
                else
                {
                    throw new OperationException('Output file already exists and overwrite is disabled');
                }
            }

            try
            {
                Logger::getLogger()->debug('Creating Phar archive');
                $phar = new Phar($outputPath);
                $phar->setAlias(basename($outputPath));
                $phar->startBuffering();

                $dependencyReaders = null;
                if($this->isStaticallyLinked())
                {
                    Logger::getLogger()->verbose('Resolving dependency readers for static linking');
                    $dependencyReaders = $this->getDependencyReaders();
                    Logger::getLogger()->verbose(sprintf('Resolved %d dependency readers', count($dependencyReaders)));
                }

                // Calculate total stages for progress tracking
                $componentCount = count($this->getSourceComponents());
                $executionUnitCount = count($this->getRequiredExecutionUnits());
                $resourceCount = count($this->getSourceResources());
                $dependencyCount = $this->isStaticallyLinked() ? count($dependencyReaders ?? []) : 0;
                $totalStages = 1 + $componentCount + $executionUnitCount + $resourceCount + $dependencyCount + 3; // +3 for autoloader, metadata, stub
                $currentStage = 0;

                // Add source components
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Adding source components');
                }
                $this->addSourceComponents($phar, $dependencyReaders, $progressCallback, $currentStage, $totalStages);
                $currentStage += $componentCount;

                // Add execution units
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Adding execution units');
                }
                $this->addExecutionUnits($phar, $progressCallback, $currentStage, $totalStages);
                $currentStage += $executionUnitCount;

                // Add resources
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Adding resources');
                }
                $this->addResources($phar, $progressCallback, $currentStage, $totalStages);
                $currentStage += $resourceCount;

                // Handle static linking - add dependency components
                if($this->isStaticallyLinked() && $dependencyReaders !== null)
                {
                    $currentStage++;
                    if($progressCallback !== null)
                    {
                        call_user_func($progressCallback, $currentStage, $totalStages, 'Adding static dependencies');
                    }
                    $this->addStaticDependencies($phar, $dependencyReaders, $progressCallback, $currentStage, $totalStages);
                    $currentStage += $dependencyCount;
                }

                // Generate and add autoloader
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Generating autoloader');
                }
                Logger::getLogger()->verbose('Generating autoloader mapping');
                $autoloaderMapping = $this->generateAutoloaderMapping($dependencyReaders);
                Logger::getLogger()->verbose(sprintf('Generated autoloader with %d class mappings', count($autoloaderMapping)));
                $phar->addFromString('.autoloader.php', $this->generateAutoloaderCode($autoloaderMapping));

                // Add metadata
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Adding metadata');
                }
                Logger::getLogger()->verbose('Setting Phar metadata');
                $metadata = $this->createMetadata($dependencyReaders);
                $phar->setMetadata($metadata);
                Logger::getLogger()->debug(sprintf('Metadata set with %d dependencies', count($metadata['dependencies'])));

                // Generate and set stub
                $currentStage++;
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage, $totalStages, 'Generating stub');
                }
                Logger::getLogger()->verbose('Generating Phar stub');
                $stub = $this->generateStub();
                $phar->setStub($stub);
                Logger::getLogger()->debug('Phar stub set');

                // Apply compression if enabled
                if($this->compressionEnabled)
                {
                    Logger::getLogger()->verbose('Applying compression to Phar files');
                    try
                    {
                        $phar->compressFiles($this->compressionType);
                        Logger::getLogger()->debug('Compression applied successfully');
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()->warning(sprintf('Failed to apply compression: %s', $e->getMessage()));
                    }
                }

                $phar->stopBuffering();
                
                // Make executable on Unix systems
                if(DIRECTORY_SEPARATOR === '/')
                {
                    chmod($outputPath, 0755);
                    Logger::getLogger()->debug('Set executable permissions');
                }

                Logger::getLogger()->verbose(sprintf('Phar compilation completed: %s', $outputPath));
                return $outputPath;
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('Failed to create Phar archive: %s', $e->getMessage()), $e->getCode(), $e);
            }
        }

        /**
         * Adds source components to the Phar archive.
         *
         * @param Phar $phar The Phar archive instance
         * @param array|null $dependencyReaders Resolved dependency readers for validation
         * @param callable|null $progressCallback Progress callback function
         * @param int $currentStage Current stage number
         * @param int $totalStages Total stages count
         */
        private function addSourceComponents(Phar $phar, ?array $dependencyReaders, ?callable $progressCallback, int $currentStage, int $totalStages): void
        {
            Logger::getLogger()->verbose(sprintf('Adding %d source components', count($this->getSourceComponents())));
            
            $assemblyName = $this->getProjectConfiguration()->getAssembly()->getName();
            $sourcePath = $this->getSourcePath();

            foreach($this->getSourceComponents() as $index => $componentPath)
            {
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage + $index + 1, $totalStages, sprintf('Adding component %d/%d', $index + 1, count($this->getSourceComponents())));
                }

                // Calculate relative path from source directory
                $relativePath = str_replace($sourcePath . DIRECTORY_SEPARATOR, '', $componentPath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                
                // Store with assembly name prefix to avoid conflicts
                $pharPath = $assemblyName . '/' . $relativePath;
                
                Logger::getLogger()->debug(sprintf('Adding component: %s -> %s', $componentPath, $pharPath));
                $phar->addFile($componentPath, $pharPath);
            }

            Logger::getLogger()->verbose(sprintf('Added %d source components', count($this->getSourceComponents())));
        }

        /**
         * Adds execution units to the Phar archive.
         *
         * @param Phar $phar The Phar archive instance
         * @param callable|null $progressCallback Progress callback function
         * @param int $currentStage Current stage number
         * @param int $totalStages Total stages count
         */
        private function addExecutionUnits(Phar $phar, ?callable $progressCallback, int $currentStage, int $totalStages): void
        {
            Logger::getLogger()->verbose(sprintf('Adding %d execution units', count($this->getRequiredExecutionUnits())));
            
            $assemblyName = $this->getProjectConfiguration()->getAssembly()->getName();

            foreach($this->getRequiredExecutionUnits() as $index => $executionUnitName)
            {
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage + $index + 1, $totalStages, sprintf('Adding execution unit %d/%d', $index + 1, count($this->getRequiredExecutionUnits())));
                }

                $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($executionUnitName);
                if($executionUnit === null)
                {
                    Logger::getLogger()->warning(sprintf('Execution unit not found: %s', $executionUnitName));
                    continue;
                }

                // Get execution unit data and resolve entry point paths (similar to PackageCompiler)
                $executionUnitData = $executionUnit->toArray();
                
                if($executionUnit->getType() === ExecutionUnitType::PHP)
                {
                    // Resolve the entry point to match where it ends up in the Phar
                    $realPath = realpath($this->getProjectPath() . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint());
                    
                    if($realPath !== false && in_array($realPath, $this->getSourceResources(), true))
                    {
                        if(str_starts_with($realPath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                        {
                            // File is inside source path - use relative path with assembly prefix
                            $relativePath = substr($realPath, strlen($this->getSourcePath()) + 1);
                            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                            $resolvedEntryPoint = $assemblyName . '/' . $relativePath;
                            $executionUnitData['entry'] = $resolvedEntryPoint;
                            Logger::getLogger()->debug(sprintf('Resolved entry point for %s: %s -> %s', $executionUnitName, $executionUnit->getEntryPoint(), $resolvedEntryPoint));
                        }
                        else
                        {
                            // File is outside source path - place at root with assembly prefix
                            $resolvedEntryPoint = $assemblyName . '/' . basename($realPath);
                            $executionUnitData['entry'] = $resolvedEntryPoint;
                            Logger::getLogger()->debug(sprintf('Resolved entry point for %s (outside source): %s -> %s', $executionUnitName, $executionUnit->getEntryPoint(), $resolvedEntryPoint));
                        }
                    }
                }
                
                // Serialize execution unit data
                $data = msgpack_pack($executionUnitData);
                
                // Store execution units with .units/ prefix and assembly name
                $pharPath = '.units/' . $assemblyName . '/' . $executionUnitName;
                
                Logger::getLogger()->debug(sprintf('Adding execution unit: %s (type: %s)', $executionUnitName, $executionUnit->getType()->value));
                $phar->addFromString($pharPath, $data);
            }

            Logger::getLogger()->verbose(sprintf('Added %d execution units', count($this->getRequiredExecutionUnits())));
        }

        /**
         * Adds resources to the Phar archive.
         *
         * @param Phar $phar The Phar archive instance
         * @param callable|null $progressCallback Progress callback function
         * @param int $currentStage Current stage number
         * @param int $totalStages Total stages count
         */
        private function addResources(Phar $phar, ?callable $progressCallback, int $currentStage, int $totalStages): void
        {
            $resourcePaths = $this->getSourceResources();
            Logger::getLogger()->verbose(sprintf('Adding %d resources', count($resourcePaths)));
            
            $assemblyName = $this->getProjectConfiguration()->getAssembly()->getName();

            foreach($resourcePaths as $index => $resourcePath)
            {
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage + $index + 1, $totalStages, sprintf('Adding resource %d/%d', $index + 1, count($resourcePaths)));
                }

                if(!file_exists($resourcePath))
                {
                    Logger::getLogger()->warning(sprintf('Resource file not found: %s', $resourcePath));
                    continue;
                }

                // Calculate relative path from source path (same as PackageCompiler)
                if(str_starts_with($resourcePath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                {
                    // File is inside source path, use relative path
                    $relativePath = substr($resourcePath, strlen($this->getSourcePath()) + 1);
                }
                else
                {
                    // File is outside source path, use just the filename
                    $relativePath = basename($resourcePath);
                }

                if(empty($relativePath) || $relativePath === false)
                {
                    Logger::getLogger()->warning(sprintf('Invalid resource path: %s', $resourcePath));
                    continue;
                }
                
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                
                // Store resources maintaining original directory structure (assembly_name/path)
                $pharPath = $assemblyName . '/' . $relativePath;
                
                Logger::getLogger()->debug(sprintf('Adding resource: %s -> %s', $resourcePath, $pharPath));
                
                if(is_dir($resourcePath))
                {
                    $phar->buildFromDirectory($resourcePath, '/^.+$/');
                }
                else
                {
                    $phar->addFile($resourcePath, $pharPath);
                }
            }

            Logger::getLogger()->verbose(sprintf('Added %d resources', count($resourcePaths)));
        }

        /**
         * Adds statically linked dependencies to the Phar archive.
         *
         * @param Phar $phar The Phar archive instance
         * @param array $dependencyReaders Resolved dependency readers
         * @param callable|null $progressCallback Progress callback function
         * @param int $currentStage Current stage number
         * @param int $totalStages Total stages count
         */
        private function addStaticDependencies(Phar $phar, array $dependencyReaders, ?callable $progressCallback, int $currentStage, int $totalStages): void
        {
            Logger::getLogger()->verbose(sprintf('Adding %d static dependencies', count($dependencyReaders)));

            /** @var ResolvedDependency $resolvedDependency */
            foreach($dependencyReaders as $depIndex => $resolvedDependency)
            {
                if($progressCallback !== null)
                {
                    call_user_func($progressCallback, $currentStage + $depIndex + 1, $totalStages, sprintf('Adding dependency %d/%d', $depIndex + 1, count($dependencyReaders)));
                }

                $packageReader = $resolvedDependency->getPackageReader();
                if($packageReader === null)
                {
                    Logger::getLogger()->warning('Skipping dependency with null package reader');
                    continue;
                }

                $depPackageName = $packageReader->getAssembly()->getPackage();
                $depAssemblyName = $packageReader->getAssembly()->getName();
                
                Logger::getLogger()->debug(sprintf('Processing dependency: %s (assembly: %s)', $depPackageName, $depAssemblyName));

                // Add dependency components
                foreach($packageReader->getComponentReferences() as $componentRef)
                {
                    try
                    {
                        $componentData = $packageReader->readComponent($componentRef);
                        // Store dependency components with their assembly name to avoid conflicts
                        $pharPath = $depAssemblyName . '/' . $componentRef->getName();
                        
                        Logger::getLogger()->debug(sprintf('Adding dependency component: %s', $pharPath));
                        $phar->addFromString($pharPath, $componentData);
                    }
                    catch(Exception $e)
                    {
                        Logger::getLogger()->warning(sprintf('Failed to add dependency component %s: %s', $componentRef->getName(), $e->getMessage()));
                    }
                }

                // Add dependency execution units if any
                $executionUnitRefs = $packageReader->getExecutionUnitReferences();
                if(count($executionUnitRefs) > 0)
                {
                    foreach($executionUnitRefs as $unitRef)
                    {
                        try
                        {
                            $unit = $packageReader->readExecutionUnit($unitRef);
                            $unitData = msgpack_pack($unit->toArray());
                            $pharPath = '.units/' . $depAssemblyName . '/' . $unitRef->getName();
                            
                            Logger::getLogger()->debug(sprintf('Adding dependency execution unit: %s', $pharPath));
                            $phar->addFromString($pharPath, $unitData);
                        }
                        catch(Exception $e)
                        {
                            Logger::getLogger()->warning(sprintf('Failed to add dependency execution unit %s: %s', $unitRef->getName(), $e->getMessage()));
                        }
                    }
                }

                // Add dependency resources if any
                $resourceRefs = $packageReader->getResourceReferences();
                if(count($resourceRefs) > 0)
                {
                    foreach($resourceRefs as $resourceRef)
                    {
                        try
                        {
                            $resourceData = $packageReader->readResource($resourceRef);
                            // Maintain directory structure without .resources prefix
                            $pharPath = $depAssemblyName . '/' . $resourceRef->getName();
                            
                            Logger::getLogger()->debug(sprintf('Adding dependency resource: %s', $pharPath));
                            $phar->addFromString($pharPath, $resourceData);
                        }
                        catch(Exception $e)
                        {
                            Logger::getLogger()->warning(sprintf('Failed to add dependency resource %s: %s', $resourceRef->getName(), $e->getMessage()));
                        }
                    }
                }
            }

            Logger::getLogger()->verbose(sprintf('Added %d static dependencies', count($dependencyReaders)));
        }

        /**
         * Generates the autoloader mapping array for the Phar.
         * 
         * Creates a class-to-file mapping using pal for source components and manually
         * parses embedded dependency components when statically linking.
         *
         * @param array|null $dependencyReaders Array of PackageReader instances for statically linked dependencies
         * @return array<string, string> The autoloader mapping array (class name => phar:// path)
         */
        private function generateAutoloaderMapping(?array $dependencyReaders=null): array
        {
            Logger::getLogger()->debug('Generating autoloader mapping');
            
            $pharAlias = basename($this->getOutputPath());
            $assemblyName = $this->getProjectConfiguration()->getAssembly()->getName();
            $baseDirectory = 'phar://' . $pharAlias . '/' . $assemblyName . '/';
            
            $mapping = [];
            
            // Generate mapping for source components
            if(count($this->getSourceComponents()) > 0)
            {
                Logger::getLogger()->verbose(sprintf('Generating autoloader mapping for %d source components', count($this->getSourceComponents())));
                
                // Use pal to generate autoloader array from source directory
                $sourceMapping = Autoloader::generateAutoloaderArray($this->getSourcePath(), [
                    'extensions' => ['php'],
                    'case_sensitive' => false,
                    'follow_symlinks' => false,
                    'include_static' => false
                ]);
                
                if($sourceMapping !== false && is_array($sourceMapping))
                {
                    foreach($sourceMapping as $className => $filePath)
                    {
                        // Convert absolute path to relative path from source directory
                        $relativePath = str_replace($this->getSourcePath() . DIRECTORY_SEPARATOR, '', $filePath);
                        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                        $mapping[$className] = $baseDirectory . $relativePath;
                    }
                }
                
                Logger::getLogger()->verbose(sprintf('Generated %d source class mappings via PAL', count($mapping)));
                
                // Fallback: parse component files directly if PAL returned empty
                if(empty($mapping))
                {
                    Logger::getLogger()->verbose('PAL autoloader generation returned empty, parsing component files directly');
                    
                    foreach($this->getSourceComponents() as $componentFilePath)
                    {
                        $componentData = IO::readFile($componentFilePath);
                        $classes = $this->parsePhpClasses($componentData);
                        
                        $relativePath = str_replace($this->getSourcePath() . DIRECTORY_SEPARATOR, '', $componentFilePath);
                        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                        $pharPath = $baseDirectory . $relativePath;
                        
                        foreach($classes as $className)
                        {
                            $mapping[$className] = $pharPath;
                        }
                    }
                    
                    Logger::getLogger()->verbose(sprintf('Generated %d source class mappings via fallback parser', count($mapping)));
                }
            }
            
            // If statically linking, add mappings for embedded dependencies
            if($this->isStaticallyLinked() && $dependencyReaders !== null && count($dependencyReaders) > 0)
            {
                Logger::getLogger()->verbose(sprintf('Processing %d dependency packages for autoloader', count($dependencyReaders)));
                
                /** @var ResolvedDependency $resolvedDependency */
                foreach($dependencyReaders as $resolvedDependency)
                {
                    $packageReader = $resolvedDependency->getPackageReader();
                    if($packageReader === null)
                    {
                        continue;
                    }
                    
                    $depAssemblyName = $packageReader->getAssembly()->getName();
                    $depBaseDirectory = 'phar://' . $pharAlias . '/' . $depAssemblyName . '/';
                    
                    Logger::getLogger()->debug(sprintf('Parsing components from dependency: %s', $depAssemblyName));
                    
                    $depMapping = $this->parsePackageComponents($packageReader, $depBaseDirectory);
                    $mapping = array_merge($mapping, $depMapping);
                    
                    Logger::getLogger()->verbose(sprintf('Added %d class mappings from %s', count($depMapping), $depAssemblyName));
                }
            }
            
            Logger::getLogger()->verbose(sprintf('Total autoloader mappings: %d classes', count($mapping)));
            return $mapping;
        }

        /**
         * Parses components from a PackageReader to extract class definitions.
         *
         * @param PackageReader $packageReader The package reader to parse components from
         * @param string $baseDirectory The base phar:// directory for this package
         * @return array<string, string> Mapping of class names to phar:// paths
         */
        private function parsePackageComponents(PackageReader $packageReader, string $baseDirectory): array
        {
            $mapping = [];
            
            foreach($packageReader->getComponentReferences() as $componentRef)
            {
                try
                {
                    $componentData = $packageReader->readComponent($componentRef);
                    $componentPath = $baseDirectory . $componentRef->getName();
                    
                    $classes = $this->parsePhpClasses($componentData);
                    
                    foreach($classes as $className)
                    {
                        $mapping[$className] = $componentPath;
                    }
                }
                catch(Exception $e)
                {
                    Logger::getLogger()->warning(sprintf('Failed to parse component %s: %s', $componentRef->getName(), $e->getMessage()));
                }
            }
            
            return $mapping;
        }

        /**
         * Parses PHP source code to extract class, interface, trait, and enum names.
         *
         * @param string $phpCode The PHP source code to parse
         * @return array<string> Array of fully qualified class names found in the code
         */
        private function parsePhpClasses(string $phpCode): array
        {
            $tokens = @token_get_all($phpCode);
            if(!is_array($tokens))
            {
                return [];
            }
            
            $classes = [];
            $namespace = '';
            $tokenCount = count($tokens);
            $bracketLevel = 0;
            $namespaceBracketLevel = 0;
            
            for($i = 0; $i < $tokenCount; $i++)
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === '{')
                    {
                        $bracketLevel++;
                    }
                    elseif($token === '}')
                    {
                        $bracketLevel--;
                        if($namespaceBracketLevel > 0 && $bracketLevel < $namespaceBracketLevel)
                        {
                            $namespace = '';
                            $namespaceBracketLevel = 0;
                        }
                    }
                    continue;
                }
                
                $tokenType = $token[0];
                
                // Handle namespace declarations
                if($tokenType === T_NAMESPACE)
                {
                    $namespace = $this->extractNamespaceFromTokens($tokens, $i);
                    
                    // Check if this is a bracketed namespace
                    $j = $i + 1;
                    while($j < $tokenCount)
                    {
                        if(!is_array($tokens[$j]))
                        {
                            if($tokens[$j] === '{')
                            {
                                $namespaceBracketLevel = $bracketLevel + 1;
                            }
                            break;
                        }
                        $j++;
                    }
                    continue;
                }
                
                // Handle class/interface/trait/enum definitions
                $allowedTypes = [T_CLASS, T_INTERFACE];
                
                if(defined('T_TRAIT'))
                {
                    $allowedTypes[] = T_TRAIT;
                }
                
                if(defined('T_ENUM'))
                {
                    $allowedTypes[] = T_ENUM;
                }
                
                if(in_array($tokenType, $allowedTypes))
                {
                    // Skip anonymous classes
                    if($this->isAnonymousClass($tokens, $i))
                    {
                        continue;
                    }
                    
                    // Skip ::class constants
                    if($this->isClassConstant($tokens, $i))
                    {
                        continue;
                    }
                    
                    $className = $this->extractClassNameFromTokens($tokens, $i);
                    if($className)
                    {
                        $classes[] = $namespace ? $namespace . '\\' . $className : $className;
                    }
                }
            }
            
            return array_unique($classes);
        }

        /**
         * Extracts namespace from tokens starting at a T_NAMESPACE token.
         *
         * @param array $tokens Token array
         * @param int $startPos Position of T_NAMESPACE token
         * @return string The namespace string
         */
        private function extractNamespaceFromTokens(array $tokens, int $startPos): string
        {
            $namespace = '';
            $i = $startPos + 1;
            
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === ';' || $token === '{')
                    {
                        break;
                    }
                    $i++;
                    continue;
                }
                
                if($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR)
                {
                    $namespace .= $token[1];
                }
                elseif(defined('T_NAME_QUALIFIED') && $token[0] === T_NAME_QUALIFIED)
                {
                    $namespace .= $token[1];
                }
                elseif(defined('T_NAME_FULLY_QUALIFIED') && $token[0] === T_NAME_FULLY_QUALIFIED)
                {
                    $namespace .= $token[1];
                }
                
                $i++;
            }
            
            return trim($namespace, '\\');
        }

        /**
         * Extracts class name from tokens starting at a class/interface/trait/enum token.
         *
         * @param array $tokens Token array
         * @param int $startPos Position of class-like token
         * @return string|null The class name or null if not found
         */
        private function extractClassNameFromTokens(array $tokens, int $startPos): ?string
        {
            $i = $startPos + 1;
            
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(is_array($token) && $token[0] === T_STRING)
                {
                    return $token[1];
                }
                
                $i++;
            }
            
            return null;
        }

        /**
         * Checks if a class token represents an anonymous class.
         *
         * @param array $tokens Token array
         * @param int $pos Position of T_CLASS token
         * @return bool True if anonymous class
         */
        private function isAnonymousClass(array $tokens, int $pos): bool
        {
            $i = $pos + 1;
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    if($token === '(' || $token === '{')
                    {
                        return true;
                    }
                    $i++;
                    continue;
                }
                
                if($token[0] === T_WHITESPACE)
                {
                    $i++;
                    continue;
                }
                
                if($token[0] === T_STRING)
                {
                    return false;
                }
                
                break;
            }
            
            return false;
        }

        /**
         * Checks if a class token is part of a ::class constant.
         *
         * @param array $tokens Token array
         * @param int $pos Position of T_CLASS token
         * @return bool True if ::class constant
         */
        private function isClassConstant(array $tokens, int $pos): bool
        {
            $i = $pos - 1;
            while($i >= 0)
            {
                $token = $tokens[$i];
                
                if(is_array($token) && $token[0] === T_WHITESPACE)
                {
                    $i--;
                    continue;
                }
                
                if(defined('T_DOUBLE_COLON') && is_array($token) && $token[0] === T_DOUBLE_COLON)
                {
                    return true;
                }
                
                if(!is_array($token) && $token === ':')
                {
                    if($i > 0 && !is_array($tokens[$i - 1]) && $tokens[$i - 1] === ':')
                    {
                        return true;
                    }
                }
                
                break;
            }
            
            return false;
        }

        /**
         * Generates the PHP autoloader code that will be embedded in the Phar.
         *
         * @param array $mapping The autoloader mapping array
         * @return string The PHP autoloader code
         */
        private function generateAutoloaderCode(array $mapping): string
        {
            $mappingExport = var_export($mapping, true);
            
            return <<<PHP
<?php
/**
 * Auto-generated autoloader for Phar package
 */

\$GLOBALS['__phar_autoload_map'] = {$mappingExport};

spl_autoload_register(function(\$class) {
    if(isset(\$GLOBALS['__phar_autoload_map'][\$class])) {
        \$file = \$GLOBALS['__phar_autoload_map'][\$class];
        if(file_exists(\$file)) {
            require_once \$file;
            return true;
        }
    }
    return false;
}, true, true);
PHP;
        }

        /**
         * Creates metadata array for the Phar.
         *
         * @param array|null $dependencyReaders Resolved dependency readers
         * @return array The metadata array
         */
        private function createMetadata(?array $dependencyReaders=null): array
        {
            $metadata = [
                'assembly' => $this->getProjectConfiguration()->getAssembly()->toArray(),
                'build_number' => $this->getBuildNumber(),
                'statically_linked' => $this->isStaticallyLinked(),
                'entry_point' => $this->getProjectConfiguration()->getEntryPoint(),
                'web_entry_point' => $this->getProjectConfiguration()->getWebEntryPoint(),
                'dependencies' => [],
            ];

            // Add dependency information
            if($dependencyReaders !== null)
            {
                foreach($dependencyReaders as $resolvedDependency)
                {
                    $packageReader = $resolvedDependency->getPackageReader();
                    if($packageReader === null)
                    {
                        continue;
                    }

                    $packageName = $packageReader->getAssembly()->getPackage();
                    $packageVersion = $packageReader->getAssembly()->getVersion();

                    $metadata['dependencies'][$packageName] = [
                        'version' => $packageVersion,
                        'source' => $resolvedDependency->getPackageSource() ? $resolvedDependency->getPackageSource()->toArray() : null,
                    ];
                }
            }
            else
            {
                foreach($this->getPackageDependencies() as $packageName => $packageSource)
                {
                    $metadata['dependencies'][$packageName] = [
                        'version' => $packageSource->getVersion() ?? 'latest',
                        'source' => $packageSource->toArray(),
                    ];
                }
            }

            return $metadata;
        }

        /**
         * Generates the Phar stub (entry point script).
         *
         * @return string The stub code
         */
        private function generateStub(): string
        {
            if($this->skipExecution)
            {
                return $this->generateMinimalStub();
            }
            elseif($this->isStaticallyLinked())
            {
                return $this->generateStaticStub();
            }
            else
            {
                return $this->generateDynamicStub();
            }
        }

        /**
         * Generates a minimal stub for self-contained Phar with no entry points.
         * Used when 'skip_execution' option is enabled.
         *
         * @return string The stub code
         */
        private function generateMinimalStub(): string
        {
            $pharAlias = basename($this->getOutputPath());
            
            if($this->isStaticallyLinked())
            {
                // Static build - no ncc required
                return <<<PHP
#!/usr/bin/env php
<?php
/**
 * Auto-generated Phar stub for self-contained PHP archive
 * This archive has no entry points and is intended to be used as a library.
 */

Phar::mapPhar('{$pharAlias}');

// Load autoloader
require 'phar://{$pharAlias}/.autoloader.php';

__HALT_COMPILER(); ?>
PHP;
            }
            else
            {
                // Dynamic build - requires ncc and imports dependencies
                return <<<PHP
#!/usr/bin/env php
<?php
/**
 * Auto-generated Phar stub for dynamic library archive
 * This archive requires ncc runtime to resolve dependencies.
 */

Phar::mapPhar('{$pharAlias}');

// Ensure ncc runtime is available
require 'ncc';

try {
    // Load autoloader
    require 'phar://{$pharAlias}/.autoloader.php';
    
    // Get metadata from this archive
    \$pharPath = Phar::running(false);
    if(!\$pharPath) {
        \$pharPath = __FILE__;
    }
    \$phar = new Phar(\$pharPath);
    \$meta = \$phar->getMetadata();
    
    // Import dependencies using ncc runtime
    foreach(\$meta['dependencies'] as \$packageName => \$depInfo) {
        if(\$depInfo['version'] !== null) {
            import(\$packageName, \$depInfo['version']);
        } else {
            import(\$packageName);
        }
    }
    
} catch(Exception \$e) {
    if(php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg') {
        fwrite(STDERR, "Error loading dependencies: " . \$e->getMessage() . "\\n");
        if(getenv('DEBUG')) {
            fwrite(STDERR, \$e->getTraceAsString() . "\\n");
        }
    }
}

__HALT_COMPILER(); ?>
PHP;
            }
        }

        /**
         * Generates the stub for statically linked Phar (no ncc required).
         *
         * @return string The stub code
         */
        private function generateStaticStub(): string
        {
            $pharAlias = basename($this->getOutputPath());
            
            return <<<PHP
#!/usr/bin/env php
<?php
/**
 * Auto-generated Phar stub for statically linked package
 * This archive is self-contained and does not require ncc runtime.
 */

Phar::mapPhar('{$pharAlias}');

// Load autoloader
require 'phar://{$pharAlias}/.autoloader.php';

// Get metadata from this archive
\$pharPath = Phar::running(false);
if(!\$pharPath) {
    \$pharPath = __FILE__;
}
\$phar = new Phar(\$pharPath);
\$meta = \$phar->getMetadata();

// Determine execution environment
\$isCli = (php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg');

// Get entry point execution unit name
\$executionUnitName = null;
if(\$isCli) {
    \$executionUnitName = \$meta['entry_point'] ?? null;
    if(\$executionUnitName === null) {
        fwrite(STDERR, "Error: No CLI entry point defined in package.\\n");
        exit(1);
    }
} else {
    \$executionUnitName = \$meta['web_entry_point'] ?? \$meta['entry_point'] ?? null;
    if(\$executionUnitName === null) {
        http_response_code(500);
        echo "Error: No entry point defined in package.";
        exit(1);
    }
}

// Load execution unit to get actual entry file
try {
    \$assemblyName = \$meta['assembly']['name'] ?? 'unknown';
    \$unitPath = 'phar://{$pharAlias}/.units/' . \$assemblyName . '/' . \$executionUnitName;
    
    if(!file_exists(\$unitPath)) {
        throw new Exception("Execution unit file not found: " . \$executionUnitName);
    }
    
    \$unitData = msgpack_unpack(file_get_contents(\$unitPath));
    \$entryPoint = \$unitData['entry'] ?? null;
    
    if(\$entryPoint === null) {
        throw new Exception("Execution unit has no entry point: " . \$executionUnitName);
    }
    
    \$entryFile = 'phar://{$pharAlias}/' . \$entryPoint;
    
    if(!file_exists(\$entryFile)) {
        throw new Exception("Entry point file not found: " . \$entryPoint);
    }
    
    if(\$isCli) {
        // CLI execution
        \$_SERVER['argv'] = array_slice(\$argv ?? [], 1);
        \$_SERVER['argc'] = count(\$_SERVER['argv']);
    }
    
    require \$entryFile;
    
} catch(Exception \$e) {
    if(\$isCli) {
        fwrite(STDERR, "Error: " . \$e->getMessage() . "\\n");
        if(getenv('DEBUG')) {
            fwrite(STDERR, \$e->getTraceAsString() . "\\n");
        }
        exit(1);
    } else {
        http_response_code(500);
        echo "Internal Server Error: " . htmlspecialchars(\$e->getMessage());
        if(getenv('DEBUG')) {
            echo "\\n<pre>" . htmlspecialchars(\$e->getTraceAsString()) . "</pre>";
        }
        exit(1);
    }
}

__HALT_COMPILER(); ?>
PHP;
        }

        /**
         * Generates the stub for dynamically linked Phar (requires ncc).
         *
         * @return string The stub code
         */
        private function generateDynamicStub(): string
        {
            $pharAlias = basename($this->getOutputPath());
            
            return <<<PHP
#!/usr/bin/env php
<?php
/**
 * Auto-generated Phar stub for dynamically linked package
 * This archive requires ncc runtime to resolve dependencies.
 */

Phar::mapPhar('{$pharAlias}');

// Ensure ncc runtime is available
require 'ncc';

try {
    // Load autoloader
    require 'phar://{$pharAlias}/.autoloader.php';
    
    // Get metadata from this archive
    \$pharPath = Phar::running(false);
    if(!\$pharPath) {
        \$pharPath = __FILE__;
    }
    \$phar = new Phar(\$pharPath);
    \$meta = \$phar->getMetadata();
    
    // Import dependencies using ncc runtime
    foreach(\$meta['dependencies'] as \$packageName => \$depInfo) {
        if(\$depInfo['version'] !== null) {
            import(\$packageName, \$depInfo['version']);
        } else {
            import(\$packageName);
        }
    }
    
    // Determine execution environment
    \$isCli = (php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg');
    
    // Get entry point execution unit name
    \$executionUnitName = null;
    if(\$isCli) {
        \$executionUnitName = \$meta['entry_point'] ?? null;
        if(\$executionUnitName === null) {
            fwrite(STDERR, "Error: No CLI entry point defined in package.\\n");
            exit(1);
        }
    } else {
        \$executionUnitName = \$meta['web_entry_point'] ?? \$meta['entry_point'] ?? null;
        if(\$executionUnitName === null) {
            http_response_code(500);
            echo "Error: No entry point defined in package.";
            exit(1);
        }
    }
    
    // Load execution unit to get actual entry file
    \$assemblyName = \$meta['assembly']['name'] ?? 'unknown';
    \$unitPath = 'phar://{$pharAlias}/.units/' . \$assemblyName . '/' . \$executionUnitName;
    
    if(!file_exists(\$unitPath)) {
        throw new Exception("Execution unit file not found: " . \$executionUnitName);
    }
    
    \$unitData = msgpack_unpack(file_get_contents(\$unitPath));
    \$entryPoint = \$unitData['entry'] ?? null;
    
    if(\$entryPoint === null) {
        throw new Exception("Execution unit has no entry point: " . \$executionUnitName);
    }
    
    \$entryFile = 'phar://{$pharAlias}/' . \$entryPoint;
    
    if(!file_exists(\$entryFile)) {
        throw new Exception("Entry point file not found: " . \$entryPoint);
    }
    
    if(\$isCli) {
        // CLI execution
        \$_SERVER['argv'] = array_slice(\$argv ?? [], 1);
        \$_SERVER['argc'] = count(\$_SERVER['argv']);
    }
    
    require \$entryFile;
    
} catch(Exception \$e) {
    if(php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg') {
        fwrite(STDERR, "Error: " . \$e->getMessage() . "\\n");
        if(getenv('DEBUG')) {
            fwrite(STDERR, \$e->getTraceAsString() . "\\n");
        }
        exit(1);
    } else {
        http_response_code(500);
        echo "Internal Server Error: " . htmlspecialchars(\$e->getMessage());
        if(getenv('DEBUG')) {
            echo "\\n<pre>" . htmlspecialchars(\$e->getTraceAsString()) . "</pre>";
        }
        exit(1);
    }
}

__HALT_COMPILER(); ?>
PHP;
        }
    }
