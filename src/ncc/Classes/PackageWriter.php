<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    namespace ncc\Classes;

    use InvalidArgumentException;
    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\PackageDirectory;
    use ncc\Enums\PackageStructure;
    use ncc\Enums\PackageStructureVersions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Utilities\Console;
    use ncc\Utilities\ConsoleProgressBar;

    class PackageWriter
    {
        /**
         * @var array
         */
        private $headers;

        /**
         * @var resource
         */
        private $tempFile;

        /**
         * @var resource
         */
        private $packageFile;

        /**
         * @var string;
         */
        private $temporaryPath;

        /**
         * @var bool
         */
        private $dataWritten;

        /**
         * PackageWriter constructor.
         *
         * @throws IOException
         */
        public function __construct(string $filePath, bool $overwrite=true)
        {
            if(!$overwrite && is_file($filePath))
            {
                throw new IOException(sprintf('File \'%s\' already exists', $filePath));
            }

            if(is_file($filePath))
            {
                unlink($filePath);
            }

            if(is_file($filePath . '.tmp'))
            {
                unlink($filePath . '.tmp');
            }

            // Create the parent directory if it doesn't exist
            if(!is_dir(dirname($filePath)))
            {
                if (!mkdir($concurrentDirectory = dirname($filePath), 0777, true) && !is_dir($concurrentDirectory))
                {
                    throw new IOException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }

            touch($filePath);
            touch($filePath . '.tmp');

            $this->dataWritten = false;
            $this->temporaryPath = $filePath . '.tmp';
            $this->tempFile = @fopen($this->temporaryPath, 'wb'); // Create a temporary data file
            $this->packageFile = @fopen($filePath, 'wb');
            $this->headers = [
                PackageStructure::FILE_VERSION->value => PackageStructureVersions::_2_0->value,
                PackageStructure::FLAGS->value => [],
                PackageStructure::DIRECTORY->value => []
            ];

            if($this->tempFile === false || $this->packageFile === false)
            {
                throw new IOException(sprintf('Failed to open file \'%s\'', $filePath));
            }
        }

        /**
         * Returns the package file version
         *
         * @return string
         */
        public function getFileVersion(): string
        {
            return (string)$this->headers[PackageStructure::FILE_VERSION->value];
        }

        /**
         * Sets the package file version
         *
         * @param string $version
         * @return void
         */
        public function setFileVersion(string $version): void
        {
            $this->headers[PackageStructure::FILE_VERSION->value] = $version;
        }

        /**
         * Returns the package flags
         *
         * @return array
         */
        public function getFlags(): array
        {
            return (array)$this->headers[PackageStructure::FLAGS->value];
        }

        /**
         * Sets the package flags
         *
         * @param string[]|PackageFlags[] $flags
         * @return void
         * @throws IOException
         */
        public function setFlags(array $flags): void
        {
            if($this->dataWritten)
            {
                throw new IOException('Cannot set flags after data has been written to the package');
            }

            foreach($flags as $flag)
            {
                if(is_string($flag))
                {
                    $flag = PackageFlags::tryFrom($flag);
                    if($flag === null)
                    {
                        throw new InvalidArgumentException(sprintf('Unexpected flag: %s', $flag));
                    }
                }

                $this->headers[PackageStructure::FLAGS->value] = $flag->value;
            }
        }

        /**
         * Adds a flag to the package
         *
         * @param PackageFlags|string $flag
         * @return void
         * @throws IOException
         */
        public function addFlag(PackageFlags|string $flag): void
        {
            if(is_string($flag))
            {
                $flag = PackageFlags::tryFrom($flag);
                if($flag === null)
                {
                    throw new InvalidArgumentException(sprintf('Unexpected flag: %s', $flag));
                }
            }

            if($this->dataWritten)
            {
                throw new IOException('Cannot add a flag after data has been written to the package');
            }

            if(!in_array($flag, $this->headers[PackageStructure::FLAGS->value], true))
            {
                $this->headers[PackageStructure::FLAGS->value][] = $flag->value;
            }
        }

        /**
         * Removes a flag from the package
         *
         * @param string $flag
         * @return void
         * @throws IOException
         */
        public function removeFlag(PackageFlags|string $flag): void
        {
            if(is_string($flag))
            {
                $flag = PackageFlags::tryFrom($flag);
                if($flag === null)
                {
                    throw new InvalidArgumentException(sprintf('Unexpected flag: %s', $flag));
                }
            }

            if($this->dataWritten)
            {
                throw new IOException('Cannot remove a flag after data has been written to the package');
            }

            $this->headers[PackageStructure::FLAGS->value] = array_diff($this->headers[PackageStructure::FLAGS->value], [$flag->value]);
        }

        /**
         * Adds a file to the package by writing to the temporary data file
         *
         * @param string $name
         * @param string $data
         * @return array
         */
        public function add(string $name, string $data): array
        {
            if(isset($this->headers[PackageStructure::DIRECTORY->value][$name]))
            {
                return explode(':', $this->headers[PackageStructure::DIRECTORY->value][$name]);
            }

            if(in_array(PackageFlags::COMPRESSION->value, $this->headers[PackageStructure::FLAGS->value], true))
            {
                if(in_array(PackageFlags::LOW_COMPRESSION->value, $this->headers[PackageStructure::FLAGS->value], true))
                {
                    $data = gzcompress($data, 1);
                }
                else if(in_array(PackageFlags::MEDIUM_COMPRESSION->value, $this->headers[PackageStructure::FLAGS->value], true))
                {
                    $data = gzcompress($data, 6);
                }
                else if(in_array(PackageFlags::HIGH_COMPRESSION->value, $this->headers[PackageStructure::FLAGS->value], true))
                {
                    $data = gzcompress($data, 9);
                }
                else
                {
                    $data = gzcompress($data);
                }
            }

            $pointer = sprintf("%d:%d", ftell($this->tempFile), strlen($data));
            $this->headers[PackageStructure::DIRECTORY->value][$name] = $pointer;
            $this->dataWritten = true;
            fwrite($this->tempFile, $data);

            return explode(':', $pointer);
        }

        /**
         * Adds a pointer to the package
         *
         * @param string $name
         * @param int $offset
         * @param int $length
         * @return void
         */
        public function addPointer(string $name, int $offset, int $length): void
        {
            if(isset($this->headers[PackageStructure::DIRECTORY->value][$name]))
            {
                return;
            }

            $this->headers[PackageStructure::DIRECTORY->value][$name] = sprintf("%d:%d", $offset, $length);
        }

        /**
         * Sets the assembly of the package
         *
         * @param Assembly $assembly
         * @return array
         */
        public function setAssembly(Assembly $assembly): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::ASSEMBLY->value), ZiProto::encode($assembly->toArray(true)));
        }

        /**
         * Adds the metadata to the package
         *
         * @param Metadata $metadata
         * @return array
         */
        public function setMetadata(Metadata $metadata): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::METADATA->value), ZiProto::encode($metadata->toArray(true)));
        }

        /**
         * Sets the installer information of the package
         *
         * @param Installer $installer
         * @return array
         */
        public function setInstaller(Installer $installer): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::INSTALLER->value), ZiProto::encode($installer->toArray(true)));
        }

        /**
         * Adds a dependency configuration to the package
         *
         * @param Dependency $dependency
         * @return array
         */
        public function addDependencyConfiguration(Dependency $dependency): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::DEPENDENCIES->value, $dependency->getName()), ZiProto::encode($dependency->toArray(true)));
        }

        /**
         * Adds an execution unit to the package
         *
         * @param ExecutionUnit $unit
         * @return array
         */
        public function addExecutionUnit(ExecutionUnit $unit): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::EXECUTION_UNITS->value, $unit->getExecutionPolicy()->getName()), ZiProto::encode($unit->toArray(true)));
        }

        /**
         * Adds a component to the package
         *
         * @param Component $component
         * @return array
         */
        public function addComponent(Component $component): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::COMPONENTS->value, $component->getName()), ZiProto::encode($component->toArray(true)));
        }

        /**
         * Adds a resource to the package
         *
         * @param Resource $resource
         * @return array
         */
        public function addResource(Resource $resource): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::RESOURCES->value, $resource->getName()), ZiProto::encode($resource->toArray(true)));
        }

        /**
         * Maps a class to a component in the package
         *
         * @param string $class
         * @param int $offset
         * @param int $length
         * @return void
         */
        public function mapClass(string $class, int $offset, int $length): void
        {
            $this->addPointer(sprintf('@%s:%s', PackageDirectory::CLASS_POINTER->value, $class), $offset, $length);
        }

        /**
         * Merges the contents of a package reader into the package writer
         *
         * @param PackageReader $reader
         * @return void
         * @throws ConfigurationException
         */
        public function merge(PackageReader $reader): void
        {
            $progress_bar = new ConsoleProgressBar(sprintf('Merging %s', $reader->getAssembly()->getPackage()), count($reader->getDirectory()));
            $processedResources = [];

            foreach($reader->getDirectory() as $name => $pointer)
            {
                $progress_bar->setMiscText($name, true);

                switch((int)substr(explode(':', $name, 2)[0], 1))
                {
                    case PackageDirectory::METADATA->value:
                    case PackageDirectory::ASSEMBLY->value:
                    case PackageDirectory::INSTALLER->value:
                    case PackageDirectory::EXECUTION_UNITS->value:
                        Console::outDebug(sprintf('Skipping %s', $name));
                        break;

                    default:
                        if(isset($processedResources[$pointer]))
                        {
                            Console::outDebug(sprintf('Merging %s as a pointer', $name));
                            $this->addPointer($name, (int)$processedResources[$pointer][0], (int)$processedResources[$pointer][1]);
                            break;
                        }

                        Console::outDebug(sprintf('Merging %s', $name));
                        $processedResources[$pointer] = $this->add($name, $reader->get($name));
                }

                $progress_bar->increaseValue(1, true);
            }

            $progress_bar->setMiscText('done', true);
            unset($progress_bar);
        }

        /**
         * Finalizes the package by writing the magic bytes, header length, delimiter, headers, and data to the file
         *
         * @return void
         * @throws IOException
         */
        public function close(): void
        {
            if(!is_resource($this->packageFile) || !is_resource($this->tempFile))
            {
                throw new IOException('Package is already closed');
            }

            // Close the temporary data file
            fclose($this->tempFile);

            // Write the magic bytes "ncc_pkg" to the package and the header
            fwrite($this->packageFile,  'ncc_pkg');
            fwrite($this->packageFile, ZiProto::encode($this->headers));
            fwrite($this->packageFile, "\x1F\x1F\x1F\x1F");

            // Copy the temporary data file to the package
            $temp_file = fopen($this->temporaryPath, 'rb');
            stream_copy_to_stream($temp_file, $this->packageFile);

            // End the package by writing the end-of-package delimiter (0xFFAA55F0)
            fwrite($this->packageFile, "\xFF\xAA\x55\xF0");

            // Close the file handles
            fclose($this->packageFile);
            fclose($temp_file);

            unlink($this->temporaryPath);

            $this->packageFile = null;
            $this->tempFile = null;
        }

        /**
         * Closes the package when the object is destroyed
         */
        public function __destruct()
        {
            try
            {
                $this->close();
            }
            catch(IOException $e)
            {
                // Ignore
            }
            finally
            {
                if(is_resource($this->packageFile))
                {
                    fclose($this->packageFile);
                }

                if(is_resource($this->tempFile))
                {
                    fclose($this->tempFile);
                }
            }
        }
    }