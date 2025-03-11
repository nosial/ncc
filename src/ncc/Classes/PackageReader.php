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

    use Exception;
    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\PackageDirectory;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IntegrityException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Extensions\ZiProto\ZiProto;
    use RuntimeException;
    use ncc\Enums\PackageStructure;

    class PackageReader
    {
        /**
         * @var int
         */
        private $packageOffset;

        /**
         * @var int
         */
        private $packageLength;

        /**
         * @var int
         */
        private $headerOffset;

        /**
         * @var int
         */
        private $headerLength;

        /**
         * @var int
         */
        private $dataOffset;

        /**
         * @var int
         */
        private $dataLength;

        /**
         * @var array
         */
        private $headers;

        /**
         * @var resource
         */
        private $packageFile;

        /**
         * @var string
         */
        private $packagePath;

        /**
         * @var array
         */
        private $cache;

        /**
         * PackageReader constructor.
         *
         * @param string $file_path
         * @throws IOException
         */
        public function __construct(string $file_path)
        {
            if (!is_file($file_path))
            {
                throw new IOException(sprintf('File \'%s\' does not exist', $file_path));
            }

            $this->packagePath = $file_path;
            $this->packageFile = fopen($file_path, 'rb');

            if($this->packageFile === false)
            {
                throw new IOException(sprintf('Failed to open file \'%s\'', $file_path));
            }

            // Package begin: ncc_pkg
            // Start of header: after ncc_pkg
            // End of header: \x1F\x1F
            // Start of data: after \x1F\x1F
            // End of data: \xFF\xAA\x55\xF0

            // First find the offset of the package by searching for the magic bytes "ncc_pkg"
            $this->packageOffset = 0;
            while(!feof($this->packageFile))
            {
                $buffer = fread($this->packageFile, 1024);
                $buffer_length = strlen($buffer);
                $this->packageOffset += $buffer_length;

                if (($position = strpos($buffer, "ncc_pkg")) !== false)
                {
                    $this->packageOffset -= $buffer_length - $position;
                    $this->packageLength = 7; // ncc_pkg
                    $this->headerOffset = $this->packageOffset + 7;
                    break;
                }
            }

            // Check for sanity reasons
            if($this->packageOffset === null || $this->packageLength === null)
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (missing magic bytes)', $file_path));
            }

            // Seek the header until the end of headers byte sequence (1F 1F 1F 1F)
            fseek($this->packageFile, $this->headerOffset);
            while (!feof($this->packageFile))
            {
                $this->headers .= fread($this->packageFile, 1024);

                // Search for the position of "1F 1F 1F 1F" within the buffer
                if (($position = strpos($this->headers, "\x1F\x1F\x1F\x1F")) !== false)
                {
                    $this->headers = substr($this->headers, 0, $position);
                    $this->headerLength = strlen($this->headers);
                    $this->packageLength += $this->headerLength + 4;
                    $this->dataOffset = $this->headerOffset + $this->headerLength + 4;
                    break;
                }

                if (strlen($this->headers) >= 100000000)
                {
                    throw new IOException(sprintf('File \'%s\' is not a valid package file (header is too large)', $file_path));
                }
            }

            try
            {
                $this->headers = ZiProto::decode($this->headers);
            }
            catch(Exception $e)
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (corrupted header)', $file_path), $e);
            }

            if(!isset($this->headers[PackageStructure::FILE_VERSION->value]))
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (invalid header)', $file_path));
            }

            // Seek the data until the end of the package (FF AA 55 F0)
            fseek($this->packageFile, $this->dataOffset);
            $buffer = '';
            while(!feof($this->packageFile))
            {
                $current_chunk = fread($this->packageFile, 1024);
                $this->dataLength += strlen($current_chunk);
                $buffer .= $current_chunk;

                // If we detect the end-of-data byte sequence
                if (($position = strpos($buffer, "\xFF\xAA\x55\xF0")) !== false)
                {
                    $this->dataLength -= strlen($buffer) - $position;
                    $this->packageLength += $this->dataLength + 4;
                    break;
                }

                // Check if the buffer is 1MB or larger
                if(strlen($buffer) > 1048576)
                {
                    // Remove the first 512kb of the buffer
                    $buffer = substr($buffer, 512000);
                }

            }

            if($this->dataLength === null || $this->dataLength === 0)
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (missing end of package)', $file_path));
            }

            $this->cache = [];
        }

        /**
         * Returns the package headers
         *
         * @return array
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * Returns the package file version
         *
         * @return string
         */
        public function getFileVersion(): string
        {
            return $this->headers[PackageStructure::FILE_VERSION->value];
        }

        /**
         * Returns an array of flags from the package
         *
         * @return array
         */
        public function getFlags(): array
        {
            return $this->headers[PackageStructure::FLAGS->value];
        }

        /**
         * Returns a flag from the package
         *
         * @param string $name
         * @return bool
         */
        public function getFlag(string $name): bool
        {
            return in_array($name, $this->headers[PackageStructure::FLAGS->value], true);
        }

        /**
         * Returns the directory of the package
         *
         * @return array
         */
        public function getDirectory(): array
        {
            return $this->headers[PackageStructure::DIRECTORY->value];
        }

        /**
         * Returns a resource from the package by name
         *
         * @param string $name
         * @return string
         */
        public function get(string $name): string
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$name]))
            {
                throw new RuntimeException(sprintf('File \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            $location = explode(':', $this->headers[PackageStructure::DIRECTORY->value][$name]);
            fseek($this->packageFile, ($this->dataOffset + (int)$location[0]));

            if(in_array(PackageFlags::COMPRESSION->value, $this->headers[PackageStructure::FLAGS->value], true))
            {
                return gzuncompress(fread($this->packageFile, (int)$location[1]));
            }

            return fread($this->packageFile, (int)$location[1]);
        }

        /**
         * Returns a resource pointer from the package by name
         *
         * @param string $name
         * @return int[]
         */
        public function getPointer(string $name): array
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$name]))
            {
                throw new RuntimeException(sprintf('Resource \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            $location = explode(':', $this->headers[PackageStructure::DIRECTORY->value][$name]);
            return [(int)$location[0], (int)$location[1]];
        }

        /**
         * Returns True if the package contains a resource by name
         *
         * @param string $name
         * @return bool
         */
        public function exists(string $name): bool
        {
            return isset($this->headers[PackageStructure::DIRECTORY->value][$name]);
        }

        /**
         * Returns a resource from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return string
         */
        public function getByPointer(int $pointer, int $length): string
        {
            fseek($this->packageFile, ($this->headerLength + $pointer));
            return fread($this->packageFile, $length);
        }

        /**
         * Returns the package's assembly
         *
         * @return Assembly
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getAssembly(): Assembly
        {
            $directory = sprintf('@%s', PackageDirectory::ASSEMBLY->value);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$directory]))
            {
                throw new ConfigurationException(sprintf('Assembly object not found in package \'%s\'', $this->packagePath));
            }

            try
            {
                $assembly = Assembly::fromArray(ZiProto::decode($this->get($directory)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode assembly from package \'%s\' using ZiProto: %s', $this->packagePath, $e->getMessage()), $e);
            }

            $this->cache[$directory] = $assembly;
            return $assembly;
        }

        /**
         * Returns the package's metadata
         *
         * @return Metadata
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getMetadata(): Metadata
        {
            $directory = sprintf('@%s', PackageDirectory::METADATA->value);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$directory]))
            {
                throw new ConfigurationException(sprintf('Metadata object not found in package \'%s\'', $this->packagePath));
            }

            try
            {
                $metadata = Metadata::fromArray(ZiProto::decode($this->get($directory)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode metadata from package \'%s\' using ZiProto: %s', $this->packagePath, $e->getMessage()), $e);
            }

            foreach($this->getFlags() as $flag)
            {
                $metadata->setOption($flag, true);
            }

            $this->cache[$directory] = $metadata;
            return $metadata;
        }

        /**
         * Optional. Returns the package's installer
         *
         * @return Installer|null
         * @throws IntegrityException
         */
        public function getInstaller(): ?Installer
        {
            $directory = sprintf('@%s', PackageDirectory::INSTALLER->value);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$directory]))
            {
                return null;
            }

            try
            {
                $installer = Installer::fromArray(ZiProto::decode($this->get($directory)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode installer from package \'%s\' using ZiProto: %s', $this->packagePath, $e->getMessage()), $e);
            }

            $this->cache[$directory] = $installer;
            return $installer;
        }

        /**
         * Returns the package's dependencies
         *
         * @return array
         */
        public function getDependencies(): array
        {
            $dependencies = [];
            $directory = sprintf('@%s:', PackageDirectory::DEPENDENCIES->value);

            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $dependencies[] = str_replace($directory, '', $name);
                }
            }

            return $dependencies;
        }

        /**
         * Returns a dependency from the package
         *
         * @param string $name
         * @return Dependency
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getDependency(string $name): Dependency
        {
            $dependency_name = sprintf('@%s:%s', PackageDirectory::DEPENDENCIES->value, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$dependency_name]))
            {
                throw new ConfigurationException(sprintf('Dependency \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            try
            {
                return Dependency::fromArray(ZiProto::decode($this->get($dependency_name)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode dependency \'%s\' from package \'%s\' using ZiProto: %s', $name, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns a dependency from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Dependency
         * @throws IntegrityException
         */
        public function getDependencyByPointer(int $pointer, int $length): Dependency
        {
            try
            {
                return Dependency::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode dependency from pointer \'%s\' with length \'%s\' from package \'%s\' using ZiProto: %s', $pointer, $length, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns an array of execution units from the package
         *
         * @return array
         */
        public function getExecutionUnits(): array
        {
            $execution_units = [];
            $directory = sprintf('@%s:', PackageDirectory::EXECUTION_UNITS->value);

            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $execution_units[] = str_replace($directory, '', $name);
                }
            }

            return $execution_units;
        }

        /**
         * Returns an execution unit from the package
         *
         * @param string $name
         * @return ExecutionUnit
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getExecutionUnit(string $name): ExecutionUnit
        {
            $execution_unit_name = sprintf('@%s:%s', PackageDirectory::EXECUTION_UNITS->value, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$execution_unit_name]))
            {
                throw new ConfigurationException(sprintf('Execution unit \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            try
            {
                return ExecutionUnit::fromArray(ZiProto::decode($this->get($execution_unit_name)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode execution unit \'%s\' from package file \'%s\' using ZiProto: %s', $name, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Checks if an execution unit with the specified name exists in the package.
         *
         * @param string $name The name of the execution unit to check.
         * @return bool True if the execution unit exists, false otherwise.
         */
        public function executionUnitExists(string $name): bool
        {
            return isset($this->headers[PackageStructure::DIRECTORY->value][sprintf('@%s:%s', PackageDirectory::EXECUTION_UNITS->value, $name)]);
        }

        /**
         * Returns an execution unit from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return ExecutionUnit
         * @throws IntegrityException
         */
        public function getExecutionUnitByPointer(int $pointer, int $length): ExecutionUnit
        {
            try
            {
                return ExecutionUnit::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode execution unit from pointer \'%s\' with length \'%s\' from package \'%s\' using ZiProto: %s', $pointer, $length, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns the package's component pointers
         *
         * @return array
         */
        public function getComponents(): array
        {
            $components = [];
            $directory = sprintf('@%s:', PackageDirectory::COMPONENTS->value);

            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $components[] = str_replace($directory, '', $name);
                }
            }

            return $components;
        }

        /**
         * Returns the package's class map
         *
         * @return array
         */
        public function getClassMap(): array
        {
            $class_map = [];
            $directory = sprintf('@%s:', PackageDirectory::CLASS_POINTER->value);

            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $class_map[] = str_replace($directory, '', $name);
                }
            }

            return $class_map;
        }

        /**
         * Returns a component from the package
         *
         * @param string $name
         * @return Component
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getComponent(string $name): Component
        {
            $component_name = sprintf('@%s:%s', PackageDirectory::COMPONENTS->value, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$component_name]))
            {
                throw new ConfigurationException(sprintf('Component \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            try
            {
                return Component::fromArray(ZiProto::decode($this->get($component_name)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode component \'%s\' from package \'%s\' using ZiProto: %s', $name, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns a component from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Component
         * @throws IntegrityException
         */
        public function getComponentByPointer(int $pointer, int $length): Component
        {
            try
            {
                return Component::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode component from pointer \'%s\' with length \'%s\' from package \'%s\' using ZiProto: %s', $pointer, $length, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns a component from the package by a class pointer
         *
         * @param string $class
         * @return Component
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getComponentByClass(string $class): Component
        {
            $class_name = sprintf('@%s:%s', PackageDirectory::CLASS_POINTER->value, $class);
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$class_name]))
            {
                throw new ConfigurationException(sprintf('Class map \'%s\' not found in package \'%s\'', $class, $this->packagePath));
            }

            try
            {
                return Component::fromArray(ZiProto::decode($this->get($class_name)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode component from class pointer \'%s\' from package \'%s\' using ZiProto: %s', $class, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns an array of resource pointers from the package
         *
         * @return array
         */
        public function getResources(): array
        {
            $resources = [];
            $directory = sprintf('@%s:', PackageDirectory::RESOURCES->value);

            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $resources[] = str_replace($directory, '', $name);
                }
            }

            return $resources;
        }

        /**
         * Returns a resource from the package
         *
         * @param string $name
         * @return Resource
         * @throws ConfigurationException
         * @throws IntegrityException
         */
        public function getResource(string $name): Resource
        {
            $resource_name = sprintf('@%s:%s', PackageDirectory::RESOURCES->value, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY->value][$resource_name]))
            {
                throw new ConfigurationException(sprintf('Resource \'%s\' not found in package \'%s\'', $name, $this->packagePath));
            }

            try
            {
                return Resource::fromArray(ZiProto::decode($this->get($resource_name)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode resource \'%s\' from package \'%s\' using ZiProto: %s', $name, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Returns a resource from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Resource
         * @throws IntegrityException
         */
        public function getResourceByPointer(int $pointer, int $length): Resource
        {
            try
            {
                return Resource::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
            }
            catch(Exception $e)
            {
                throw new IntegrityException(sprintf('Failed to decode resource from pointer \'%s\' with length \'%s\' from package \'%s\' using ZiProto: %s', $pointer, $length, $this->packagePath, $e->getMessage()), $e);
            }
        }

        /**
         * Searches the package's directory for a file that matches the given filename
         *
         * @param string $filename
         * @return string|false
         */
        public function find(string $filename): string|false
        {
            foreach($this->headers[PackageStructure::DIRECTORY->value] as $name => $location)
            {
                if(str_ends_with($name, $filename))
                {
                    return $name;
                }
            }

            return false;
        }

        /**
         * Returns the offset of the package
         *
         * @return int
         */
        public function getPackageOffset(): int
        {
            return $this->packageOffset;
        }

        /**
         * @return int
         */
        public function getPackageLength(): int
        {
            return $this->packageLength;
        }

        /**
         * @return int
         */
        public function getHeaderOffset(): int
        {
            return $this->headerOffset;
        }

        /**
         * @return int
         */
        public function getHeaderLength(): int
        {
            return $this->headerLength;
        }

        /**
         * @return int
         */
        public function getDataOffset(): int
        {
            return $this->dataOffset;
        }

        /**
         * @return int
         */
        public function getDataLength(): int
        {
            return $this->dataLength;
        }

        /**
         * Returns the checksum of the package
         *
         * @param string $hash
         * @param bool $binary
         * @return string
         */
        public function getChecksum(string $hash='crc32b', bool $binary=false): string
        {
            $checksum = hash($hash, '', $binary);

            fseek($this->packageFile, $this->packageOffset);
            $bytes_left = $this->packageLength;

            while ($bytes_left > 0)
            {
                $buffer = fread($this->packageFile, min(1024, $bytes_left));
                $buffer_length = strlen($buffer);
                $bytes_left -= $buffer_length;
                $checksum = hash($hash, ($checksum . $buffer), $binary);

                if ($buffer_length === 0)
                {
                    break;
                }
            }

            return $checksum;
        }

        /**
         * @param string $path
         * @return void
         * @throws IOException
         */
        public function saveCopy(string $path): void
        {
            $destination = fopen($path, 'wb');

            if ($destination === false)
            {
                throw new IOException(sprintf('Failed to open file \'%s\'', $path));
            }

            fseek($this->packageFile, $this->packageOffset);
            $remaining_bytes = $this->packageLength;

            while($remaining_bytes > 0)
            {
                $bytes_to_read = min($remaining_bytes, 4096);
                $data = fread($this->packageFile, $bytes_to_read);

                if ($data === false)
                {
                    throw new IOException('Failed to read from package file');
                }

                $written_bytes = fwrite($destination, $data, $bytes_to_read);

                if ($written_bytes === false)
                {
                    throw new IOException(sprintf('Failed to write to file \'%s\'', $path));
                }

                $remaining_bytes -= $written_bytes;
            }

            fclose($destination);

            if((new PackageReader($path))->getChecksum() !== $this->getChecksum())
            {
                throw new IOException(sprintf('Failed to save package copy to \'%s\', checksum mismatch', $path));
            }
        }

        /**
         * PackageReader destructor.
         */
        public function __destruct()
        {
            if(is_resource($this->packageFile))
            {
                fclose($this->packageFile);
            }
        }
    }