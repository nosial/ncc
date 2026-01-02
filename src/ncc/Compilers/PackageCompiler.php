<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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
    use ncc\Abstracts\AbstractCompiler;
    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\PackageReader;
    use ncc\Classes\PackageWriter;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\pal\Autoloader;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\Header;
    use ncc\Objects\ResolvedDependency;
    use ncc\Runtime;

    class PackageCompiler extends AbstractCompiler
    {
        private const int TOTAL_STAGES = 4;
        private bool $compressionEnabled;
        private int $compressionLevel;

        /**
         * @inheritDoc
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            Logger::getLogger()->debug('Initializing PackageCompiler');
            parent::__construct($projectFilePath, $buildConfiguration);

            // Package-specific compression attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression']) && is_bool($this->getBuildConfiguration()->getOptions()['compression']))
            {
                // Enable/Disable compression
                $this->compressionEnabled = (bool)$this->getBuildConfiguration()->getOptions()['compression'];
                Logger::getLogger()->verbose(sprintf('Compression: %s', $this->compressionEnabled ? 'enabled' : 'disabled'));
            }
            else
            {
                // By default, compression is always enabled.
                $this->compressionEnabled = true;
                Logger::getLogger()->verbose('Compression: enabled (default)');
            }

            // Package-specific compression level attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression_level']) && is_int($this->getBuildConfiguration()->getOptions()['compression_level']))
            {
                $this->compressionLevel = (int)$this->getBuildConfiguration()->getOptions()['compression_level'];
                if($this->compressionLevel > 9)
                {
                    // Fallback to 9 if the value is greater than 9
                    Logger::getLogger()->warning(sprintf('Compression level %d exceeds maximum, using 9', $this->compressionLevel));
                    $this->compressionLevel = 9;
                }
                elseif($this->compressionLevel < 1)
                {
                    // Fallback to 1 if the value is less than 1
                    Logger::getLogger()->warning(sprintf('Compression level %d below minimum, using 1', $this->compressionLevel));
                    $this->compressionLevel = 1;
                }
                
                Logger::getLogger()->verbose(sprintf('Compression level: %d', $this->compressionLevel));
            }
            else
            {
                // All other cases; default value is 9.
                $this->compressionLevel = 9;
                Logger::getLogger()->verbose('Compression level: 9 (default)');
            }
        }

        /**
         * @inheritDoc
         */
        public function compile(?callable $progressCallback=null, bool $overwrite=true): string
        {
            Logger::getLogger()->verbose(sprintf('Starting package compilation to: %s', $this->getOutputPath()));
            Logger::getLogger()->verbose(sprintf('Static linking: %s', $this->isStaticallyLinked() ? 'enabled' : 'disabled'));
            
            // Initialize package writer
            $packageWriter = new PackageWriter($this->getOutputPath(), $overwrite);
            $dependencyReaders = null;

            if($this->isStaticallyLinked())
            {
                Logger::getLogger()->verbose('Resolving dependency readers for static linking');
                $dependencyReaders = $this->getDependencyReaders();
                Logger::getLogger()->verbose(sprintf('Resolved %d dependency readers', count($dependencyReaders)));
            }

            // Generate autoloader mapping before writing package
            Logger::getLogger()->verbose('Generating autoloader mapping');
            $autoloaderMapping = $this->generateAutoloaderMapping($dependencyReaders);
            Logger::getLogger()->verbose(sprintf('Generated autoloader with %d class mappings', count($autoloaderMapping)));

            // Calculate total stages dynamically based on content
            $componentCount = count($this->getSourceComponents());
            $executionUnitCount = count($this->getRequiredExecutionUnits());
            $dependencyCount = $this->isStaticallyLinked() ? count($dependencyReaders ?? []) : 0;
            $totalStages = self::TOTAL_STAGES + $componentCount + $executionUnitCount + $dependencyCount;
            $currentStage = 0;

            // Write until the package is closed
            while(!$packageWriter->isClosed())
            {
                // Switch to the correct writing mode and handle it.
                switch($packageWriter->getWritingMode())
                {
                    case WritingMode::HEADER:
                        $currentStage++;
                        if($progressCallback !== null)
                        {
                            $progressCallback($currentStage, $totalStages, 'Writing package header');
                        }
                        Logger::getLogger()->verbose('Writing package header');
                        
                        // Create header and set autoloader
                        $header = $this->createPackageHeader($dependencyReaders);
                        $header->setAutoloader($autoloaderMapping);
                        Logger::getLogger()->debug(sprintf('Set autoloader with %d mappings in header', count($autoloaderMapping)));
                        
                        // Write the header as a data entry only, section gets closed automatically
                        $packageWriter->writeData(msgpack_pack($header->toArray()));
                        Logger::getLogger()->debug('Package header written successfully');
                        break;

                    case WritingMode::ASSEMBLY:
                        $currentStage++;
                        if($progressCallback !== null)
                        {
                            $progressCallback($currentStage, $totalStages, 'Writing assembly information');
                        }
                        Logger::getLogger()->verbose('Writing package assembly');
                        // Write the assembly as a data entry only, section gets closed automatically
                        $packageWriter->writeData(msgpack_pack($this->getProjectConfiguration()->getAssembly()->toArray()));
                        Logger::getLogger()->debug('Package assembly written successfully');
                        break;

                    case WritingMode::EXECUTION_UNITS:
                        $currentStage++;
                        if($progressCallback !== null)
                        {
                            $progressCallback($currentStage, $totalStages, sprintf('Writing %d execution units', count($this->getRequiredExecutionUnits())));
                        }
                        Logger::getLogger()->verbose(sprintf('Writing %d execution units', count($this->getRequiredExecutionUnits())));
                        
                        // Get the main package assembly name to match resource namespacing
                        $mainPackageName = $this->getProjectConfiguration()->getAssembly()->getName();
                        
                        // Execution units can be multiple, write them named.
                        foreach($this->getRequiredExecutionUnits() as $executionUnitName)
                        {
                            $currentStage++;
                            if($progressCallback !== null)
                            {
                                $progressCallback($currentStage, $totalStages, sprintf('Writing execution unit: %s', $executionUnitName));
                            }
                            $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($executionUnitName);
                            
                            // Resolve the entry point path to match where it ends up in the package
                            $executionUnitData = $executionUnit->toArray();
                            if($executionUnit->getType() === ExecutionUnitType::PHP)
                            {
                                // Find the actual file path that was added to sourceResources
                                $realPath = realpath($this->getProjectPath() . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint());
                                
                                // Try to find this file in the sourceResources array
                                if($realPath !== false && in_array($realPath, $this->getSourceResources(), true))
                                {
                                    // File is in sourceResources, resolve to path relative to source directory
                                    if(str_starts_with($realPath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                                    {
                                        // File is inside source path - use relative path with assembly prefix
                                        $resolvedEntryPoint = $mainPackageName . '/' . substr($realPath, strlen($this->getSourcePath()) + 1);
                                        $executionUnitData['entry'] = $resolvedEntryPoint;
                                        Logger::getLogger()->debug(sprintf('Resolved entry point for %s: %s -> %s', $executionUnitName, $executionUnit->getEntryPoint(), $resolvedEntryPoint));
                                    }
                                    else
                                    {
                                        // File is outside source path - use basename with assembly prefix
                                        $resolvedEntryPoint = $mainPackageName . '/' . basename($realPath);
                                        $executionUnitData['entry'] = $resolvedEntryPoint;
                                        Logger::getLogger()->debug(sprintf('Resolved entry point for %s (outside source): %s -> %s', $executionUnitName, $executionUnit->getEntryPoint(), $resolvedEntryPoint));
                                    }
                                }
                                else
                                {
                                    Logger::getLogger()->debug(sprintf('Entry point for %s kept as-is (not in resources or not found): %s', $executionUnitName, $executionUnit->getEntryPoint()));
                                }
                            }
                            
                            $packageWriter->writeData(msgpack_pack($executionUnitData), $executionUnit->getName());
                            Logger::getLogger()->debug(sprintf('Written execution unit: %s', $executionUnitName));
                        }

                        // Close the section
                        $packageWriter->endSection();
                        Logger::getLogger()->debug('Execution units section closed');
                        break;

                    case WritingMode::COMPONENTS:
                        $currentStage++;
                        if($progressCallback !== null)
                        {
                            $progressCallback($currentStage, $totalStages, sprintf('Writing %d package components', $componentCount));
                        }
                        Logger::getLogger()->verbose(sprintf('Writing %d source components', $componentCount));
                        
                        // Get the main package assembly name to namespace its components
                        $mainPackageName = $this->getProjectConfiguration()->getAssembly()->getName();
                        
                        // Components ca be multiple, write them named
                        foreach($this->getSourceComponents() as $componentFilePath)
                        {
                            $currentStage++;
                            
                            // Check if component is within source path
                            if(str_starts_with($componentFilePath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                            {
                                // File is inside source path, use relative path
                                $componentName = substr($componentFilePath, strlen($this->getSourcePath()) + 1);
                            }
                            else
                            {
                                // File is outside source path, use just the filename
                                $componentName = basename($componentFilePath);
                            }

                            if(empty($componentName) || $componentName === false)
                            {
                                Logger::getLogger()->error(sprintf('Invalid component path: %s', $componentFilePath));
                                throw new OperationException(sprintf('Invalid component path: %s (source path: %s)', $componentFilePath, $this->getSourcePath()));
                            }

                            if($progressCallback !== null)
                            {
                                $progressCallback($currentStage, $totalStages, sprintf('Writing component: %s', $componentName));
                            }

                            $componentData = IO::readFile($componentFilePath);
                            $originalSize = strlen($componentData);
                            
                            if($this->compressionEnabled)
                            {
                                $componentData = gzdeflate($componentData, $this->compressionLevel);
                                $compressedSize = strlen($componentData);
                                Logger::getLogger()->debug(sprintf('Compressed component %s: %d -> %d bytes (%.1f%%)', $componentName, $originalSize, $compressedSize, ($compressedSize / $originalSize) * 100));
                            }

                            // Namespace the component under the main package's assembly name
                            $namespacedComponentName = $mainPackageName . '/' . $componentName;
                            $packageWriter->writeData($componentData, $namespacedComponentName);
                        }

                        // If dependency linking is statically linked, we embed the package contents into our compiled package
                        if($this->isStaticallyLinked())
                        {
                            Logger::getLogger()->verbose(sprintf('Embedding %d dependency components for static linking', count($dependencyReaders)));
                            
                            // For each dependency, if we cannot resolve one of these dependencies the build fails
                            foreach($dependencyReaders as $resolvedDependency)
                            {
                                $packageReader = $resolvedDependency->getPackageReader();
                                if($packageReader === null)
                                {
                                    continue;
                                }
                                
                                $currentStage++;
                                if($progressCallback !== null)
                                {
                                    $progressCallback($currentStage, $totalStages, sprintf('Embedding dependency: %s', $packageReader->getPackageName()));
                                }
                                
                                // Component names from the dependency already include their assembly prefix,
                                // so we write them as-is without adding another prefix
                                
                                // For each component reference
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getComponentReferences() as $componentName => $componentReference)
                                {
                                    // Read the component data (already decompressed)
                                    $componentData = $packageReader->readComponent($componentReference);
                                    $originalSize = strlen($componentData);
                                    
                                    // Re-compress if target package uses compression
                                    if($this->compressionEnabled)
                                    {
                                        $componentData = gzdeflate($componentData, $this->compressionLevel);
                                        $compressedSize = strlen($componentData);
                                        Logger::getLogger()->debug(sprintf('Compressed embedded component %s: %d -> %d bytes (%.1f%%)', $componentName, $originalSize, $compressedSize, ($compressedSize / $originalSize) * 100));
                                    }
                                    
                                    // Component name already includes assembly prefix from original package, use as-is
                                    $packageWriter->writeData($componentData, $componentName);
                                    Logger::getLogger()->debug(sprintf('Embedded dependency component: %s', $componentName));
                                }
                            }
                        }

                        // Close the section
                        $packageWriter->endSection();
                        Logger::getLogger()->verbose(sprintf('Components section completed (%d components)', $componentCount));
                        break;

                    case WritingMode::RESOURCES:
                        $resourceCount = count($this->getSourceResources());
                        Logger::getLogger()->verbose(sprintf('Writing %d source resources', $resourceCount));
                        
                        // Get the main package assembly name to namespace its resources
                        $mainPackageName = $this->getProjectConfiguration()->getAssembly()->getName();
                        
                        // Resources can be multiple, write them named.
                        foreach($this->getSourceResources() as $resourceFilePath)
                        {
                            // Check if resource is within source path
                            if(str_starts_with($resourceFilePath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                            {
                                // File is inside source path, use relative path
                                $resourceName = substr($resourceFilePath, strlen($this->getSourcePath()) + 1);
                            }
                            else
                            {
                                // File is outside source path, use just the filename
                                $resourceName = basename($resourceFilePath);
                            }

                            if(empty($resourceName) || $resourceName === false)
                            {
                                Logger::getLogger()->error(sprintf('Invalid resource path: %s', $resourceFilePath));
                                throw new OperationException(sprintf('Invalid resource path: %s (source path: %s)', $resourceFilePath, $this->getSourcePath()));
                            }

                            $resourceData = IO::readFile($resourceFilePath);
                            $originalSize = strlen($resourceData);
                            
                            if($this->compressionEnabled)
                            {
                                $resourceData = gzdeflate($resourceData, $this->compressionLevel);
                                $compressedSize = strlen($resourceData);
                                $compressionRatio = $originalSize > 0 ? ($compressedSize / $originalSize) * 100 : 0;
                                Logger::getLogger()->debug(sprintf('Compressed resource %s: %d -> %d bytes (%.1f%%)', $resourceName, $originalSize, $compressedSize, $compressionRatio));
                            }

                            // Namespace the resource under the main package's assembly name
                            $namespacedResourceName = $mainPackageName . '/' . $resourceName;
                            $packageWriter->writeData($resourceData, $namespacedResourceName);
                        }

                        if($this->isStaticallyLinked())
                        {
                            Logger::getLogger()->verbose(sprintf('Embedding %d dependency resources for static linking', count($dependencyReaders)));
                            
                            foreach($dependencyReaders as $resolvedDependency)
                            {
                                $packageReader = $resolvedDependency->getPackageReader();
                                if($packageReader === null)
                                {
                                    continue;
                                }
                                
                                // Resource names from the dependency already include their assembly prefix,
                                // so we write them as-is without adding another prefix
                                
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getResourceReferences() as $resourceName => $resourceReference)
                                {
                                    // Read the resource data (already decompressed)
                                    $resourceData = $packageReader->readResource($resourceReference);
                                    $originalSize = strlen($resourceData);
                                    
                                    // Re-compress if target package uses compression
                                    if($this->compressionEnabled)
                                    {
                                        $resourceData = gzdeflate($resourceData, $this->compressionLevel);
                                        $compressedSize = strlen($resourceData);
                                        $compressionRatio = $originalSize > 0 ? ($compressedSize / $originalSize) * 100 : 0;
                                        Logger::getLogger()->debug(sprintf('Compressed embedded resource %s: %d -> %d bytes (%.1f%%)', $resourceName, $originalSize, $compressedSize, $compressionRatio));
                                    }
                                    
                                    // Resource name already includes assembly prefix from original package, use as-is
                                    $packageWriter->writeData($resourceData, $resourceName);
                                    Logger::getLogger()->debug(sprintf('Embedded dependency resource: %s', $resourceName));
                                }
                            }
                        }

                        $packageWriter->endSection();
                        Logger::getLogger()->verbose(sprintf('Resources section completed (%d resources)', $resourceCount));
                        break;
                }
            }

            Logger::getLogger()->verbose(sprintf('Package compilation completed: %s', $this->getOutputPath()));
            return $this->getOutputPath();
        }

        /**
         * Returns the package's header object built from the project's configuration
         *
         * @return Header THe package's header object
         */
        private function createPackageHeader(?array $dependencyReaders=null): Header
        {
            Logger::getLogger()->debug('Creating package header');
            
            $header = new Header();

            // General header information
            $header->setBuildNumber($this->getBuildNumber());
            $header->setCompressed($this->compressionEnabled);
            $header->setStaticallyLinked($this->isStaticallyLinked());
            $header->setEntryPoint($this->getProjectConfiguration()->getEntryPoint());
            $header->setWebEntryPoint($this->getProjectConfiguration()->getWebEntryPoint());
            $header->setPostInstall($this->getProjectConfiguration()->getPostInstall());
            $header->setPreInstall($this->getProjectConfiguration()->getPreInstall());
            $header->setUpdateSource($this->getProjectConfiguration()->getUpdateSource());
            $header->setRepositories($this->getProjectConfiguration()->getRepositories());
            
            Logger::getLogger()->verbose(sprintf('Header: build=%s, compressed=%s, static=%s', $this->getBuildNumber(), $this->compressionEnabled ? 'yes' : 'no', $header->isStaticallyLinked() ? 'yes' : 'no'));
            
            if(count($this->getBuildConfiguration()->getDefinitions()) > 0)
            {
                $header->setDefinedConstants($this->getBuildConfiguration()->getDefinitions());
                Logger::getLogger()->verbose(sprintf('Added %d defined constants to header', count($this->getBuildConfiguration()->getDefinitions())));
            }

            // If dependency readers are provided, we need to match them against the required dependencies because this
            // result contains all resolved dependencies that a package may have (transitive dependencies).
            if($dependencyReaders !== null)
            {
                Logger::getLogger()->debug(sprintf('Processing %d resolved dependency readers for header', count($dependencyReaders)));
                
                $addedDependencies = [];
                
                foreach($this->getDependencyReaders() as $resolvedDependency)
                {
                    $packageReader = $resolvedDependency->getPackageReader();
                    if($packageReader === null)
                    {
                        continue;
                    }
                    
                    // Use the package identifier (e.g., com.example.package) instead of friendly name
                    $packageName = $packageReader->getAssembly()->getPackage();
                    $packageVersion = $packageReader->getAssembly()->getVersion();

                    // Skip if already added (prevent duplicates)
                    if(isset($addedDependencies[$packageName]))
                    {
                        Logger::getLogger()->debug(sprintf('Skipping duplicate dependency: %s', $packageName));
                        continue;
                    }

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName));
                        throw new OperationException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $resolvedDependency->getPackageSource());
                    $addedDependencies[$packageName] = true;
                    Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion));
                }
            }
            // Otherwise, just add the dependencies as-is, during installation time they will be resolved regardless.
            else
            {
                Logger::getLogger()->debug(sprintf('Processing %d package dependencies for header', count($this->getPackageDependencies())));
                
                foreach($this->getPackageDependencies() as $packageName => $packageSource)
                {
                    // Try to get the exact version from the source first
                    $sourceVersion = $packageSource->getVersion();
                    
                    // If source has a specific version (not 'latest' or null), use it
                    if($sourceVersion !== null && $sourceVersion !== 'latest')
                    {
                        $packageVersion = $sourceVersion;
                    }
                    // Otherwise try to resolve from installed packages
                    else
                    {
                        $packageVersion = Runtime::getPackageEntry($packageName, $sourceVersion ?? 'latest')?->getVersion() ?? 'latest';
                    }

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName));
                        throw new OperationException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $packageSource);
                    Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion));
                }
            }

            Logger::getLogger()->verbose(sprintf('Package header created with %d dependency references', count($header->getDependencyReferences())));
            return $header;
        }

        /**
         * Generates the autoloader mapping array for the package.
         * 
         * Creates a class-to-file mapping using pal for source components and manually
         * parses embedded dependency components when statically linking. The mapping
         * uses ncc:// protocol paths for runtime autoloading.
         *
         * @param array|null $dependencyReaders Array of PackageReader instances for statically linked dependencies
         * @return array<string, string> The autoloader mapping array (class name => ncc:// path)
         */
        private function generateAutoloaderMapping(?array $dependencyReaders=null): array
        {
            Logger::getLogger()->debug('Generating autoloader mapping');
            
            $packageName = $this->getProjectConfiguration()->getAssembly()->getPackage();
            $assemblyName = $this->getProjectConfiguration()->getAssembly()->getName();
            $baseDirectory = 'ncc://' . $packageName . '/' . $assemblyName . '/';
            
            Logger::getLogger()->debug(sprintf('Main package: %s, assembly: %s, baseDirectory: %s', $packageName, $assemblyName, $baseDirectory));
            
            // Generate mapping for source components using pal
            $mapping = [];
            
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
                    // Convert file paths to ncc:// protocol paths
                    foreach($sourceMapping as $className => $filePath)
                    {
                        // Make path relative to source directory
                        if(str_starts_with($filePath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                        {
                            $relativePath = substr($filePath, strlen($this->getSourcePath()) + 1);
                            $mapping[$className] = $baseDirectory . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                            Logger::getLogger()->debug(sprintf('Mapped class: %s => %s', $className, $mapping[$className]));
                        }
                    }
                }
                
                Logger::getLogger()->verbose(sprintf('Generated %d source class mappings via PAL', count($mapping)));
                
                // Fallback: If PAL didn't find any classes, parse component files directly
                // This handles cases where files are not organized in PAL-compatible structure
                if(empty($mapping))
                {
                    Logger::getLogger()->verbose('PAL autoloader generation returned empty, parsing component files directly');
                    
                    foreach($this->getSourceComponents() as $componentFilePath)
                    {
                        try
                        {
                            // Read the component file
                            $componentData = IO::readFile($componentFilePath);
                            
                            // Determine the component name (relative path for mapping)
                            if(str_starts_with($componentFilePath, $this->getSourcePath() . DIRECTORY_SEPARATOR))
                            {
                                $componentName = substr($componentFilePath, strlen($this->getSourcePath()) + 1);
                            }
                            else
                            {
                                $componentName = basename($componentFilePath);
                            }
                            
                            $componentPath = $baseDirectory . str_replace(DIRECTORY_SEPARATOR, '/', $componentName);
                            
                            // Parse the PHP code to extract class names
                            $classes = $this->parsePhpClasses($componentData);
                            
                            foreach($classes as $className)
                            {
                                $mapping[$className] = $componentPath;
                                Logger::getLogger()->debug(sprintf('Mapped class (fallback): %s => %s', $className, $componentPath));
                            }
                        }
                        catch(Exception $e)
                        {
                            Logger::getLogger()->warning(sprintf('Failed to parse component %s: %s', $componentFilePath, $e->getMessage()));
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
                    
                    $depPackageName = $packageReader->getAssembly()->getPackage();
                    // For statically linked dependencies, components already have assembly name prefixed
                    // in their component names, so use the package root as base directory
                    $depAssemblyName = $packageReader->getAssembly()->getName();
                    
                    Logger::getLogger()->debug(sprintf('Parsing components from dependency: %s', $depPackageName));
                    
                    // For static linking, components from dependencies are stored with assembly name prefixed
                    // (e.g., "laravel/collections/Collection.php"), so we only need the package root
                    $depBaseDirectory = 'ncc://' . $packageName . '/';
                    $depMapping = $this->parsePackageComponents($packageReader, $depBaseDirectory);
                    $mapping = array_merge($mapping, $depMapping);
                    
                    Logger::getLogger()->verbose(sprintf('Added %d class mappings from %s', count($depMapping), $depPackageName));
                }
            }
            
            Logger::getLogger()->verbose(sprintf('Total autoloader mappings: %d classes', count($mapping)));
            return $mapping;
        }

        /**
         * Parses components from a PackageReader to extract class definitions.
         * 
         * Reads each component from the package, tokenizes the PHP code, and extracts
         * class, interface, trait, and enum definitions with their namespaces.
         *
         * @param PackageReader $packageReader The package reader to parse components from
         * @param string $baseDirectory The base ncc:// directory for this package
         * @return array<string, string> Mapping of class names to ncc:// paths
         */
        private function parsePackageComponents(PackageReader $packageReader, string $baseDirectory): array
        {
            $mapping = [];
            
            foreach($packageReader->getComponentReferences() as $componentRef)
            {
                try
                {
                    // Read the component data
                    $componentData = $packageReader->readComponent($componentRef);
                    $componentName = $componentRef->getName();
                    Logger::getLogger()->debug(sprintf('Component name from reader: %s, baseDirectory: %s', $componentName, $baseDirectory));
                    $componentPath = $baseDirectory . $componentName;
                    
                    // Parse the PHP code to extract class names
                    $classes = $this->parsePhpClasses($componentData);
                    
                    foreach($classes as $className)
                    {
                        $mapping[$className] = $componentPath;
                        Logger::getLogger()->debug(sprintf('Mapped dependency class: %s => %s', $className, $componentPath));
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
         * Uses PHP tokenizer to analyze the code and extract fully qualified class names.
         * This is similar to pal's parseSourceFile but works with string content instead of files.
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
                        if(!is_array($tokens[$j]) && $tokens[$j] === '{')
                        {
                            $namespaceBracketLevel = $bracketLevel + 1;
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
                        $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
                        $classes[] = $fullClassName;
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
            // Look ahead for class name or opening parenthesis/brace
            $i = $pos + 1;
            while($i < count($tokens))
            {
                $token = $tokens[$i];
                
                if(!is_array($token))
                {
                    // If we hit ( or { before a T_STRING, it's anonymous
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
            // Look backward for double colon
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
                    // Check for ::
                    if($i > 0 && !is_array($tokens[$i - 1]) && $tokens[$i - 1] === ':')
                    {
                        return true;
                    }
                }
                
                break;
            }
            
            return false;
        }
    }
