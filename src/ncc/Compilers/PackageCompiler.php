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
    use ncc\Classes\PackageWriter;
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\CompileException;
    use ncc\Exceptions\PackageException;
    use ncc\Objects\Package\DependencyReference;
    use ncc\Objects\Package\Header;

    class PackageCompiler extends AbstractCompiler
    {
        /**
         * Stage 1: Creating package
         */
        private const int TOTAL_STAGES = 1;
        private bool $compressionEnabled;
        private int $compressionLevel;

        /**
         * @inheritDoc
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            parent::__construct($projectFilePath, $buildConfiguration);

            // Package-specific compression attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression']) && is_bool($this->getBuildConfiguration()->getOptions()['compression']))
            {
                // Enable/Disable compression
                $this->compressionEnabled = (bool)$this->getBuildConfiguration()->getOptions()['compression'];
            }
            else
            {
                // By default, compression is always enabled.
                $this->compressionEnabled = true;
            }

            // Package-specific compression level attributes
            if(isset($this->getBuildConfiguration()->getOptions()['compression_level']) && is_int($this->getBuildConfiguration()->getOptions()['compression_level']))
            {
                $this->compressionLevel = (int)$this->getBuildConfiguration()->getOptions()['compression_level'];
                if($this->compressionLevel > 9)
                {
                    // Fallback to 9 if the value is greater than 9
                    $this->compressionLevel = 9;
                }
                elseif($this->compressionLevel < 1)
                {
                    // Fallback to 1 if the value is less than 1
                    $this->compressionLevel = 1;
                }
            }
            else
            {
                // All other cases; default value is 9.
                $this->compressionLevel = 9;
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
            try
            {
                // Initialize package writer
                $packageWriter = new PackageWriter($this->getOutputPath(), $overwrite);

                // Write until the package is closed
                while(!$packageWriter->isClosed())
                {
                    // Switch to the correct writing mode and handle it.
                   switch($packageWriter->getWritingMode())
                   {
                       case WritingMode::HEADER:
                           // Write the header as a data entry only, section gets closed automatically
                           $packageWriter->writeData(msgpack_pack($this->createPackageHeader()->toArray()));
                           break;

                       case WritingMode::ASSEMBLY:
                           // Write the assembly as a data entry only, section gets closed automatically
                           $packageWriter->writeData(msgpack_pack($this->getProjectConfiguration()->getAssembly()->toArray()));
                           break;

                       case WritingMode::EXECUTION_UNITS:
                           // Execution units can be multiple, write them named.
                           foreach($this->getRequiredExecutionUnits() as $executionUnitName)
                           {
                               $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($executionUnitName);
                               $packageWriter->writeData(msgpack_pack($executionUnit->toArray()), $executionUnit->getName());
                           }

                           // Close the section
                           $packageWriter->endSection();
                           break;

                       case WritingMode::COMPONENTS:
                           // Components ca be multiple, write them named
                           foreach($this->getComponents() as $componentFilePath)
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
                                   throw new CompileException(sprintf('Invalid component path: %s (source path: %s)', $componentFilePath, $this->getSourcePath()));
                               }

                               $componentData = file_get_contents($componentFilePath);
                               if($this->compressionEnabled)
                               {
                                      $componentData = gzdeflate($componentData, $this->compressionLevel);
                               }

                               $packageWriter->writeData($componentData, $componentName);
                           }

                           // Close the section
                           $packageWriter->endSection();
                           break;

                       case WritingMode::RESOURCES:
                           // Resources can be multiple, write them named.
                           foreach($this->getResources() as $resourceFilePath)
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
                                    throw new CompileException(sprintf('Invalid resource path: %s (source path: %s)', $resourceFilePath, $this->getSourcePath()));
                                }

                                $resourceData = file_get_contents($resourceFilePath);
                                if($this->compressionEnabled)
                                {
                                    $resourceData = gzdeflate($resourceData, $this->compressionLevel);
                                }

                                $packageWriter->writeData($resourceData, $resourceName);
                           }

                           $packageWriter->endSection();
                           break;
                   }
                }
            }
            catch (PackageException $e)
            {
                throw new CompileException(sprintf('Failed to open package writer for %s', $this->getOutputPath()), $e->getCode(), $e);
            }

            return $this->getOutputPath();
        }

        /**
         * Returns the package's header object built from the project's configuration
         *
         * @return Header THe package's header object
         */
        private function createPackageHeader(): Header
        {
            $header = new Header();
            $static = $this->isStaticallyLinked();

            $header->setBuildNumber($this->getBuildNumber());
            $header->setEntryPoint($this->getProjectConfiguration()->getEntryPoint());
            $header->setPostInstall($this->getProjectConfiguration()->getPostInstall());
            $header->setPreInstall($this->getProjectConfiguration()->getPreInstall());
            $header->setDependencyReferences(array_map(function ($dependency) use ($static) {
                return new DependencyReference($dependency, $static);
            }, $this->getProjectConfiguration()->getDependencies() ?? []));

            return $header;
        }
    }