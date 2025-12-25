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

    use ncc\Abstracts\AbstractCompiler;
    use ncc\Classes\IO;
    use ncc\Classes\PackageReader;
    use ncc\Classes\PackageWriter;
    use ncc\CLI\Logger;
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\CompileException;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\Header;
    use ncc\Runtime;

    class PackageCompiler extends AbstractCompiler
    {
        /**
         * Compilation stages
         */
        private const int STAGE_HEADER = 1;
        private const int STAGE_ASSEMBLY = 2;
        private const int STAGE_EXECUTION_UNITS = 3;
        private const int STAGE_COMPONENTS = 4;
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
         * Gets whether compression is enabled.
         *
         * @return bool True if compression is enabled, false otherwise.
         */
        public function getCompressionEnabled(): bool
        {
            return $this->compressionEnabled;
        }

        /**
         * Gets the compression level.
         *
         * @return int The compression level.
         */
        public function getCompressionLevel(): int
        {
            return $this->compressionLevel;
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
                        // Write the header as a data entry only, section gets closed automatically
                        $packageWriter->writeData(msgpack_pack($this->createPackageHeader($dependencyReaders)->toArray()));
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
                        
                        // Execution units can be multiple, write them named.
                        foreach($this->getRequiredExecutionUnits() as $executionUnitName)
                        {
                            $currentStage++;
                            if($progressCallback !== null)
                            {
                                $progressCallback($currentStage, $totalStages, sprintf('Writing execution unit: %s', $executionUnitName));
                            }
                            $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($executionUnitName);
                            $packageWriter->writeData(msgpack_pack($executionUnit->toArray()), $executionUnit->getName());
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
                                throw new CompileException(sprintf('Invalid component path: %s (source path: %s)', $componentFilePath, $this->getSourcePath()));
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

                            $packageWriter->writeData($componentData, $componentName);
                        }

                        // If dependency linking is statically linked, we embed the package contents into our compiled package
                        if($this->isStaticallyLinked())
                        {
                            Logger::getLogger()->verbose(sprintf('Embedding %d dependency components for static linking', count($dependencyReaders)));
                            
                            // For each dependency, if we cannot resolve one of these dependencies the build fails
                            /** @var PackageReader $packageReader */
                            foreach($dependencyReaders as $packageReader)
                            {
                                $currentStage++;
                                if($progressCallback !== null)
                                {
                                    $progressCallback($currentStage, $totalStages, sprintf('Embedding dependency: %s', $packageReader->getPackageName()));
                                }
                                
                                // For each component reference
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getComponentReferences() as $componentName => $componentReference)
                                {
                                    $packageWriter->writeData($componentName, $packageReader->readComponent($componentReference));
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
                                throw new CompileException(sprintf('Invalid resource path: %s (source path: %s)', $resourceFilePath, $this->getSourcePath()));
                            }

                            $resourceData = IO::readFile($resourceFilePath);
                            $originalSize = strlen($resourceData);
                            
                            if($this->compressionEnabled)
                            {
                                $resourceData = gzdeflate($resourceData, $this->compressionLevel);
                                $compressedSize = strlen($resourceData);
                                Logger::getLogger()->debug(sprintf('Compressed resource %s: %d -> %d bytes (%.1f%%)', $resourceName, $originalSize, $compressedSize, ($compressedSize / $originalSize) * 100));
                            }

                            $packageWriter->writeData($resourceData, $resourceName);
                        }

                        if($this->isStaticallyLinked())
                        {
                            Logger::getLogger()->verbose(sprintf('Embedding %d dependency resources for static linking', count($dependencyReaders)));
                            
                            /** @var PackageReader $packageReader */
                            foreach($dependencyReaders as $packageReader)
                            {
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getResourceReferences() as $resourceName => $resourceReference)
                                {
                                    $packageWriter->writeData($resourceName, $packageReader->readResource($resourceReference));
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
         * @throws CompileException thrown if a dependency cannot be resolved when statically linking
         */
        private function createPackageHeader(?array $dependencyReaders=null): Header
        {
            Logger::getLogger()->debug('Creating package header');
            
            $header = new Header();

            // General header information
            $header->setBuildNumber($this->getBuildNumber());
            $header->setCompressed($this->compressionEnabled);
            $header->setStaticallyLinked($this->getBuildConfiguration()?->getOptions()['static'] ?? false);
            $header->setEntryPoint($this->getProjectConfiguration()->getEntryPoint());
            $header->setWebEntryPoint($this->getProjectConfiguration()->getWebEntryPoint());
            $header->setPostInstall($this->getProjectConfiguration()->getPostInstall());
            $header->setPreInstall($this->getProjectConfiguration()->getPreInstall());
            $header->setUpdateSource($this->getProjectConfiguration()->getUpdateSource());
            $header->setRepositories($this->getProjectConfiguration()->getRepository());
            
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
                
                foreach($this->getDependencyReaders() as $packageReader)
                {
                    $packageName = $packageReader->getPackageConfiguration()->getName();
                    $packageVersion = $packageReader->getPackageConfiguration()->getVersion();

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName));
                        throw new CompileException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $packageReader->getPackageSource());
                    Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion));
                }
            }
            // Otherwise, just add the dependencies as-is, during installation time they will be resolved regardless.
            else
            {
                Logger::getLogger()->debug(sprintf('Processing %d package dependencies for header', count($this->getPackageDependencies())));
                
                foreach($this->getPackageDependencies() as $packageName => $packageSource)
                {
                    $packageVersion = Runtime::getPackageEntry($packageName, $packageSource->getVersion() ?? 'latest')?->getVersion() ?? 'latest';

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName));
                        throw new CompileException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $packageSource);
                    Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion));
                }
            }

            Logger::getLogger()->verbose(sprintf('Package header created with %d dependency references', count($header->getDependencyReferences())));
            return $header;
        }
    }