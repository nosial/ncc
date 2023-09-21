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

    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\PackageDirectory;
    use ncc\Enums\PackageStructure;
    use ncc\Enums\PackageStructureVersions;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Extensions\ZiProto\ZiProto;

    class PackageWriter
    {
        /**
         * @var array
         */
        private $headers;

        /**
         * @var resource
         */
        private $temp_file;

        /**
         * @var resource
         */
        private $package_file;

        /**
         * @var string;
         */
        private $temporary_path;

        /**
         * @var bool
         */
        private $data_written;

        /**
         * PackageWriter constructor.
         *
         * @throws IOException
         */
        public function __construct(string $file_path, bool $overwrite=true)
        {
            if(!$overwrite && is_file($file_path))
            {
                throw new IOException(sprintf('File \'%s\' already exists', $file_path));
            }

            if(is_file($file_path))
            {
                unlink($file_path);
            }

            if(is_file($file_path . '.tmp'))
            {
                unlink($file_path . '.tmp');
            }

            // Create the parent directory if it doesn't exist
            if(!is_dir(dirname($file_path)))
            {
                if (!mkdir($concurrentDirectory = dirname($file_path), 0777, true) && !is_dir($concurrentDirectory))
                {
                    throw new IOException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }

            touch($file_path);
            touch($file_path . '.tmp');

            $this->data_written = false;
            $this->temporary_path = $file_path . '.tmp';
            $this->temp_file = @fopen($this->temporary_path, 'wb'); // Create a temporary data file
            $this->package_file = @fopen($file_path, 'wb');
            $this->headers = [
                PackageStructure::FILE_VERSION => PackageStructureVersions::_2_0,
                PackageStructure::FLAGS => [],
                PackageStructure::DIRECTORY => []
            ];

            if($this->temp_file === false || $this->package_file === false)
            {
                throw new IOException(sprintf('Failed to open file \'%s\'', $file_path));
            }
        }

        /**
         * Returns the package file version
         *
         * @return string
         */
        public function getFileVersion(): string
        {
            return (string)$this->headers[PackageStructure::FILE_VERSION];
        }

        /**
         * Sets the package file version
         *
         * @param string $version
         * @return void
         */
        public function setFileVersion(string $version): void
        {
            $this->headers[PackageStructure::FILE_VERSION] = $version;
        }

        /**
         * Returns the package flags
         *
         * @return array
         */
        public function getFlags(): array
        {
            return (array)$this->headers[PackageStructure::FLAGS];
        }

        /**
         * Sets the package flags
         *
         * @param array $flags
         * @return void
         * @throws IOException
         */
        public function setFlags(array $flags): void
        {
            if($this->data_written)
            {
                throw new IOException('Cannot set flags after data has been written to the package');
            }

            $this->headers[PackageStructure::FLAGS] = $flags;
        }

        /**
         * Adds a flag to the package
         *
         * @param string $flag
         * @return void
         * @throws IOException
         */
        public function addFlag(string $flag): void
        {
            if($this->data_written)
            {
                throw new IOException('Cannot add a flag after data has been written to the package');
            }

            if(!in_array($flag, $this->headers[PackageStructure::FLAGS], true))
            {
                $this->headers[PackageStructure::FLAGS][] = $flag;
            }
        }

        /**
         * Removes a flag from the package
         *
         * @param string $flag
         * @return void
         * @throws IOException
         */
        public function removeFlag(string $flag): void
        {
            if($this->data_written)
            {
                throw new IOException('Cannot remove a flag after data has been written to the package');
            }

            $this->headers[PackageStructure::FLAGS] = array_diff($this->headers[PackageStructure::FLAGS], [$flag]);
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
            if(isset($this->headers[PackageStructure::DIRECTORY][$name]))
            {
                return explode(':', $this->headers[PackageStructure::DIRECTORY][$name]);
            }

            if(in_array(PackageFlags::COMPRESSION, $this->headers[PackageStructure::FLAGS], true))
            {
                if(in_array(PackageFlags::LOW_COMPRESSION, $this->headers[PackageStructure::FLAGS], true))
                {
                    $data = gzcompress($data, 1);
                }
                else if(in_array(PackageFlags::MEDIUM_COMPRESSION, $this->headers[PackageStructure::FLAGS], true))
                {
                    $data = gzcompress($data, 6);
                }
                else if(in_array(PackageFlags::HIGH_COMPRESSION, $this->headers[PackageStructure::FLAGS], true))
                {
                    $data = gzcompress($data, 9);
                }
                else
                {
                    $data = gzcompress($data);
                }
            }

            $pointer = sprintf("%d:%d", ftell($this->temp_file), strlen($data));
            $this->headers[PackageStructure::DIRECTORY][$name] = $pointer;
            $this->data_written = true;
            fwrite($this->temp_file, $data);

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
            if(isset($this->headers[PackageStructure::DIRECTORY][$name]))
            {
                return;
            }

            $this->headers[PackageStructure::DIRECTORY][$name] = sprintf("%d:%d", $offset, $length);
        }

        /**
         * Sets the assembly of the package
         *
         * @param Assembly $assembly
         * @return array
         */
        public function setAssembly(Assembly $assembly): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::ASSEMBLY), ZiProto::encode($assembly->toArray(true)));
        }

        /**
         * Adds the metadata to the package
         *
         * @param Metadata $metadata
         * @return array
         */
        public function setMetadata(Metadata $metadata): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::METADATA), ZiProto::encode($metadata->toArray(true)));
        }

        /**
         * Sets the installer information of the package
         *
         * @param Installer $installer
         * @return array
         */
        public function setInstaller(Installer $installer): array
        {
            return $this->add(sprintf('@%s', PackageDirectory::INSTALLER), ZiProto::encode($installer->toArray(true)));
        }

        /**
         * Adds a dependency configuration to the package
         *
         * @param Dependency $dependency
         * @return array
         */
        public function addDependencyConfiguration(Dependency $dependency): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::DEPENDENCIES, $dependency->getName()), ZiProto::encode($dependency->toArray(true)));
        }

        /**
         * Adds an execution unit to the package
         *
         * @param ExecutionUnit $unit
         * @return array
         */
        public function addExecutionUnit(ExecutionUnit $unit): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::EXECUTION_UNITS, $unit->getExecutionPolicy()->getName()), ZiProto::encode($unit->toArray(true)));
        }

        /**
         * Adds a component to the package
         *
         * @param Component $component
         * @return array
         */
        public function addComponent(Component $component): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::COMPONENTS, $component->getName()), ZiProto::encode($component->toArray(true)));
        }

        /**
         * Adds a resource to the package
         *
         * @param Resource $resource
         * @return array
         */
        public function addResource(Resource $resource): array
        {
            return $this->add(sprintf('@%s:%s', PackageDirectory::RESOURCES, $resource->getName()), ZiProto::encode($resource->toArray(true)));
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
            $this->addPointer(sprintf('@%s:%s', PackageDirectory::CLASS_POINTER, $class), $offset, $length);
        }

        /**
         * Finalizes the package by writing the magic bytes, header length, delimiter, headers, and data to the file
         *
         * @return void
         * @throws IOException
         */
        public function close(): void
        {
            if(!is_resource($this->package_file) || !is_resource($this->temp_file))
            {
                throw new IOException('Package is already closed');
            }

            // Close the temporary data file
            fclose($this->temp_file);

            // Write the magic bytes "ncc_pkg" to the package and the header
            fwrite($this->package_file,  'ncc_pkg');
            fwrite($this->package_file, ZiProto::encode($this->headers));
            fwrite($this->package_file, "\x1F\x1F\x1F\x1F");

            // Copy the temporary data file to the package
            $temp_file = fopen($this->temporary_path, 'rb');
            stream_copy_to_stream($temp_file, $this->package_file);

            // End the package by writing the end-of-package delimiter (0xFFAA55F0)
            fwrite($this->package_file, "\xFF\xAA\x55\xF0");

            // Close the file handles
            fclose($this->package_file);
            fclose($temp_file);

            unlink($this->temporary_path);

            $this->package_file = null;
            $this->temp_file = null;
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
                if(is_resource($this->package_file))
                {
                    fclose($this->package_file);
                }

                if(is_resource($this->temp_file))
                {
                    fclose($this->temp_file);
                }
            }
        }
    }