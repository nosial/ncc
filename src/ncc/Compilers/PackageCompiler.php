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
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\CompileException;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\Header;
    use ncc\Runtime;

    class PackageCompiler extends AbstractCompiler
    {
        /**
         * Stage 1: Creating package
         */
        private const int TOTAL_STAGES = 1; // TODO: implement this
        private bool $compressionEnabled;
        private int $compressionLevel;

        /**
         * @inheritDoc
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            \ncc\CLI\Logger::getLogger()->debug('Initializing PackageCompiler', 'PackageCompiler');
            
            parent::__construct($projectFilePath, $buildConfiguration);

            // Package-specific compression attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression']) && is_bool($this->getBuildConfiguration()->getOptions()['compression']))
            {
                // Enable/Disable compression
                $this->compressionEnabled = (bool)$this->getBuildConfiguration()->getOptions()['compression'];
                \ncc\CLI\Logger::getLogger()->verbose(sprintf('Compression: %s', $this->compressionEnabled ? 'enabled' : 'disabled'), 'PackageCompiler');
            }
            else
            {
                // By default, compression is always enabled.
                $this->compressionEnabled = true;
                \ncc\CLI\Logger::getLogger()->verbose('Compression: enabled (default)', 'PackageCompiler');
            }

            // Package-specific compression level attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression_level']) && is_int($this->getBuildConfiguration()->getOptions()['compression_level']))
            {
                $this->compressionLevel = (int)$this->getBuildConfiguration()->getOptions()['compression_level'];
                if($this->compressionLevel > 9)
                {
                    // Fallback to 9 if the value is greater than 9
                    \ncc\CLI\Logger::getLogger()->warning(sprintf('Compression level %d exceeds maximum, using 9', $this->compressionLevel), 'PackageCompiler');
                    $this->compressionLevel = 9;
                }
                elseif($this->compressionLevel < 1)
                {
                    // Fallback to 1 if the value is less than 1
                    \ncc\CLI\Logger::getLogger()->warning(sprintf('Compression level %d below minimum, using 1', $this->compressionLevel), 'PackageCompiler');
                    $this->compressionLevel = 1;
                }
                
                \ncc\CLI\Logger::getLogger()->verbose(sprintf('Compression level: %d', $this->compressionLevel), 'PackageCompiler');
            }
            else
            {
                // All other cases; default value is 9.
                $this->compressionLevel = 9;
                \ncc\CLI\Logger::getLogger()->verbose('Compression level: 9 (default)', 'PackageCompiler');
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
            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Starting package compilation to: %s', $this->getOutputPath()), 'PackageCompiler');
            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Static linking: %s', $this->isStaticallyLinked() ? 'enabled' : 'disabled'), 'PackageCompiler');
            
            // Initialize package writer
            $packageWriter = new PackageWriter($this->getOutputPath(), $overwrite);
            $dependencyReaders = null;

            if($this->isStaticallyLinked())
            {
                \ncc\CLI\Logger::getLogger()->verbose('Resolving dependency readers for static linking', 'PackageCompiler');
                $dependencyReaders = $this->getDependencyReaders();
                \ncc\CLI\Logger::getLogger()->verbose(sprintf('Resolved %d dependency readers', count($dependencyReaders)), 'PackageCompiler');
            }

            // Write until the package is closed
            while(!$packageWriter->isClosed())
            {
                // Switch to the correct writing mode and handle it.
                switch($packageWriter->getWritingMode())
                {
                    case WritingMode::HEADER:
                        \ncc\CLI\Logger::getLogger()->verbose('Writing package header', 'PackageCompiler');
                        // Write the header as a data entry only, section gets closed automatically
                        $packageWriter->writeData(msgpack_pack($this->createPackageHeader($dependencyReaders)->toArray()));
                        \ncc\CLI\Logger::getLogger()->debug('Package header written successfully', 'PackageCompiler');
                        break;

                    case WritingMode::ASSEMBLY:
                        \ncc\CLI\Logger::getLogger()->verbose('Writing package assembly', 'PackageCompiler');
                        // Write the assembly as a data entry only, section gets closed automatically
                        $packageWriter->writeData(msgpack_pack($this->getProjectConfiguration()->getAssembly()->toArray()));
                        \ncc\CLI\Logger::getLogger()->debug('Package assembly written successfully', 'PackageCompiler');
                        break;

                    case WritingMode::EXECUTION_UNITS:
                        \ncc\CLI\Logger::getLogger()->verbose(sprintf('Writing %d execution units', count($this->getRequiredExecutionUnits())), 'PackageCompiler');
                        
                        // Execution units can be multiple, write them named.
                        foreach($this->getRequiredExecutionUnits() as $executionUnitName)
                        {
                            $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($executionUnitName);
                            $packageWriter->writeData(msgpack_pack($executionUnit->toArray()), $executionUnit->getName());
                            \ncc\CLI\Logger::getLogger()->debug(sprintf('Written execution unit: %s', $executionUnitName), 'PackageCompiler');
                        }

                        // Close the section
                        $packageWriter->endSection();
                        \ncc\CLI\Logger::getLogger()->debug('Execution units section closed', 'PackageCompiler');
                        break;

                    case WritingMode::COMPONENTS:
                        $componentCount = count($this->getSourceComponents());
                        \ncc\CLI\Logger::getLogger()->verbose(sprintf('Writing %d source components', $componentCount), 'PackageCompiler');
                        
                        // Components ca be multiple, write them named
                        foreach($this->getSourceComponents() as $componentFilePath)
                        {
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
                                \ncc\CLI\Logger::getLogger()->error(sprintf('Invalid component path: %s', $componentFilePath), 'PackageCompiler');
                                throw new CompileException(sprintf('Invalid component path: %s (source path: %s)', $componentFilePath, $this->getSourcePath()));
                            }

                            $componentData = IO::readFile($componentFilePath);
                            $originalSize = strlen($componentData);
                            
                            if($this->compressionEnabled)
                            {
                                $componentData = gzdeflate($componentData, $this->compressionLevel);
                                $compressedSize = strlen($componentData);
                                \ncc\CLI\Logger::getLogger()->debug(sprintf('Compressed component %s: %d -> %d bytes (%.1f%%)', $componentName, $originalSize, $compressedSize, ($compressedSize / $originalSize) * 100), 'PackageCompiler');
                            }

                            $packageWriter->writeData($componentData, $componentName);
                        }

                        // If dependency linking is statically linked, we embed the package contents into our compiled package
                        if($this->isStaticallyLinked())
                        {
                            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Embedding %d dependency components for static linking', count($dependencyReaders)), 'PackageCompiler');
                            
                            // For each dependency, if we cannot resolve one of these dependencies the build fails
                            /** @var PackageReader $packageReader */
                            foreach($dependencyReaders as $packageReader)
                            {
                                // For each component reference
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getComponentReferences() as $componentName => $componentReference)
                                {
                                    $packageWriter->writeData($componentName, $packageReader->readComponent($componentReference));
                                    \ncc\CLI\Logger::getLogger()->debug(sprintf('Embedded dependency component: %s', $componentName), 'PackageCompiler');
                                }
                            }
                        }

                        // Close the section
                        $packageWriter->endSection();
                        \ncc\CLI\Logger::getLogger()->verbose(sprintf('Components section completed (%d components)', $componentCount), 'PackageCompiler');
                        break;

                    case WritingMode::RESOURCES:
                        $resourceCount = count($this->getSourceResources());
                        \ncc\CLI\Logger::getLogger()->verbose(sprintf('Writing %d source resources', $resourceCount), 'PackageCompiler');
                        
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
                                \ncc\CLI\Logger::getLogger()->error(sprintf('Invalid resource path: %s', $resourceFilePath), 'PackageCompiler');
                                throw new CompileException(sprintf('Invalid resource path: %s (source path: %s)', $resourceFilePath, $this->getSourcePath()));
                            }

                            $resourceData = IO::readFile($resourceFilePath);
                            $originalSize = strlen($resourceData);
                            
                            if($this->compressionEnabled)
                            {
                                $resourceData = gzdeflate($resourceData, $this->compressionLevel);
                                $compressedSize = strlen($resourceData);
                                \ncc\CLI\Logger::getLogger()->debug(sprintf('Compressed resource %s: %d -> %d bytes (%.1f%%)', $resourceName, $originalSize, $compressedSize, ($compressedSize / $originalSize) * 100), 'PackageCompiler');
                            }

                            $packageWriter->writeData($resourceData, $resourceName);
                        }

                        if($this->isStaticallyLinked())
                        {
                            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Embedding %d dependency resources for static linking', count($dependencyReaders)), 'PackageCompiler');
                            
                            /** @var PackageReader $packageReader */
                            foreach($dependencyReaders as $packageReader)
                            {
                                /** @var ComponentReference $componentReference */
                                foreach($packageReader->getResourceReferences() as $resourceName => $resourceReference)
                                {
                                    $packageWriter->writeData($resourceName, $packageReader->readResource($resourceReference));
                                    \ncc\CLI\Logger::getLogger()->debug(sprintf('Embedded dependency resource: %s', $resourceName), 'PackageCompiler');
                                }
                            }
                        }

                        $packageWriter->endSection();
                        \ncc\CLI\Logger::getLogger()->verbose(sprintf('Resources section completed (%d resources)', $resourceCount), 'PackageCompiler');
                        break;
                }
            }

            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Package compilation completed: %s', $this->getOutputPath()), 'PackageCompiler');
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
            \ncc\CLI\Logger::getLogger()->debug('Creating package header', 'PackageCompiler');
            
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
            
            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Header: build=%s, compressed=%s, static=%s', $this->getBuildNumber(), $this->compressionEnabled ? 'yes' : 'no', $header->isStaticallyLinked() ? 'yes' : 'no'), 'PackageCompiler');
            
            if(count($this->getBuildConfiguration()->getDefinitions()) > 0)
            {
                $header->setDefinedConstants($this->getBuildConfiguration()->getDefinitions());
                \ncc\CLI\Logger::getLogger()->verbose(sprintf('Added %d defined constants to header', count($this->getBuildConfiguration()->getDefinitions())), 'PackageCompiler');
            }

            // If dependency readers are provided, we need to match them against the required dependencies because this
            // result contains all resolved dependencies that a package may have (transitive dependencies).
            if($dependencyReaders !== null)
            {
                \ncc\CLI\Logger::getLogger()->debug(sprintf('Processing %d resolved dependency readers for header', count($dependencyReaders)), 'PackageCompiler');
                
                foreach($this->getDependencyReaders() as $packageReader)
                {
                    $packageName = $packageReader->getPackageConfiguration()->getName();
                    $packageVersion = $packageReader->getPackageConfiguration()->getVersion();

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        \ncc\CLI\Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName), 'PackageCompiler');
                        throw new CompileException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $packageReader->getPackageSource());
                    \ncc\CLI\Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion), 'PackageCompiler');
                }
            }
            // Otherwise, just add the dependencies as-is, during installation time they will be resolved regardless.
            else
            {
                \ncc\CLI\Logger::getLogger()->debug(sprintf('Processing %d package dependencies for header', count($this->getPackageDependencies())), 'PackageCompiler');
                
                foreach($this->getPackageDependencies() as $packageName => $packageSource)
                {
                    $packageVersion = Runtime::getPackageEntry($packageName, $packageSource->getVersion() ?? 'latest')?->getVersion() ?? 'latest';

                    // Ensure that there are no 'latest' versions when statically linking
                    if($this->isStaticallyLinked() && $packageVersion === 'latest')
                    {
                        \ncc\CLI\Logger::getLogger()->error(sprintf('Cannot statically link dependency "%s" with version "latest"', $packageName), 'PackageCompiler');
                        throw new CompileException(sprintf('Cannot statically link dependency "%s", the package is missing and a version could not be resolved', $packageName));
                    }

                    $header->addDependencyReference($packageName, $packageVersion, $packageSource);
                    \ncc\CLI\Logger::getLogger()->debug(sprintf('Added dependency reference: %s@%s', $packageName, $packageVersion), 'PackageCompiler');
                }
            }

            \ncc\CLI\Logger::getLogger()->verbose(sprintf('Package header created with %d dependency references', count($header->getDependencyReferences())), 'PackageCompiler');
            return $header;
        }
    }