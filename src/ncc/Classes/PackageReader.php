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
    use RuntimeException;
    use ncc\Enums\PackageStructure;

    class PackageReader
    {
        /**
         * @var array
         */
        private $headers;

        /**
         * @var int
         */
        private $header_length;

        /**
         * @var resource
         */
        private $package_file;

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

            $this->package_file = fopen($file_path, 'rb');
            if($this->package_file === false)
            {
                throw new IOException(sprintf('Failed to open file \'%s\'', $file_path));
            }

            $pre_header = '';
            $diameter_hit = false;

            // Dynamically calculate header length until "ncc_pkg" is found
            while (!feof($this->package_file))
            {
                $char = fread($this->package_file, 1);
                $pre_header .= $char;

                if (str_ends_with($pre_header, 'ncc_pkg'))
                {
                    break;
                }
            }

            // Calculate header length including "ncc_pkg"
            $this->header_length = strlen($pre_header);

            // Read everything after "ncc_pkg" up until the delimiter (0x1F 0x1F)
            $header = '';
            while(!feof($this->package_file))
            {
                $this->header_length++;
                $header .= fread($this->package_file, 1);

                if(str_ends_with($header, "\x1F\x1F"))
                {
                    $diameter_hit = true;
                    $header = substr($header, 0, -2);
                    break;
                }

                // Stop at 100MB
                if($this->header_length >= 100000000)
                {
                    throw new IOException(sprintf('File \'%s\' is not a valid package file (header is too large)', $file_path));
                }
            }

            if(!$diameter_hit)
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (invalid header)', $file_path));
            }

            $this->headers = ZiProto::decode($header);
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
            return $this->headers[PackageStructure::FILE_VERSION];
        }

        /**
         * Returns an array of flags from the package
         *
         * @return array
         */
        public function getFlags(): array
        {
            return $this->headers[PackageStructure::FLAGS];
        }

        /**
         * Returns a flag from the package
         *
         * @param string $name
         * @return bool
         */
        public function getFlag(string $name): bool
        {
            return in_array($name, $this->headers[PackageStructure::FLAGS], true);
        }

        /**
         * Returns the directory of the package
         *
         * @return array
         */
        public function getDirectory(): array
        {
            return $this->headers[PackageStructure::DIRECTORY];
        }

        /**
         * Returns a resource from the package by name
         *
         * @param string $name
         * @return string
         */
        public function get(string $name): string
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY][$name]))
            {
                throw new RuntimeException(sprintf('File \'%s\' not found in package', $name));
            }

            $location = explode(':', $this->headers[PackageStructure::DIRECTORY][$name]);
            fseek($this->package_file, ($this->header_length + (int)$location[0]));

            if(in_array(PackageFlags::COMPRESSION, $this->headers[PackageStructure::FLAGS], true))
            {
                return gzuncompress(fread($this->package_file, (int)$location[1]));
            }

            return fread($this->package_file, (int)$location[1]);
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
            fseek($this->package_file, ($this->header_length + $pointer));
            return fread($this->package_file, $length);
        }

        /**
         * Returns the package's assembly
         *
         * @return Assembly
         * @throws ConfigurationException
         */
        public function getAssembly(): Assembly
        {
            $directory = sprintf('@%s', PackageDirectory::ASSEMBLY);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY][$directory]))
            {
                throw new ConfigurationException('Package does not contain an assembly');
            }

            $assembly = Assembly::fromArray(ZiProto::decode($this->get($directory)));
            $this->cache[$directory] = $assembly;
            return $assembly;
        }

        /**
         * Returns the package's metadata
         *
         * @return Metadata
         * @throws ConfigurationException
         */
        public function getMetadata(): Metadata
        {
            $directory = sprintf('@%s', PackageDirectory::METADATA);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY][$directory]))
            {
                throw new ConfigurationException('Package does not contain metadata');
            }

            $metadata = Metadata::fromArray(ZiProto::decode($this->get($directory)));
            $this->cache[$directory] = $metadata;
            return $metadata;
        }

        /**
         * Optional. Returns the package's installer
         *
         * @return Installer|null
         */
        public function getInstaller(): ?Installer
        {
            $directory = sprintf('@%s', PackageDirectory::INSTALLER);

            if(isset($this->cache[$directory]))
            {
                return $this->cache[$directory];
            }

            if(!isset($this->headers[PackageStructure::DIRECTORY][$directory]))
            {
                return null;
            }

            $installer = Installer::fromArray(ZiProto::decode($this->get($directory)));
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
            $directory = sprintf('@%s:', PackageDirectory::DEPENDENCIES);

            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
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
         */
        public function getDependency(string $name): Dependency
        {
            $dependency_name = sprintf('@%s:%s', PackageDirectory::DEPENDENCIES, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$dependency_name]))
            {
                throw new ConfigurationException(sprintf('Dependency \'%s\' not found in package', $name));
            }

            return Dependency::fromArray(ZiProto::decode($this->get($dependency_name)));
        }

        /**
         * Returns a dependency from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Dependency
         * @throws ConfigurationException
         */
        public function getDependencyByPointer(int $pointer, int $length): Dependency
        {
            return Dependency::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
        }

        /**
         * Returns an array of execution units from the package
         *
         * @return array
         */
        public function getExecutionUnits(): array
        {
            $execution_units = [];
            $directory = sprintf('@%s:', PackageDirectory::EXECUTION_UNITS);

            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
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
         */
        public function getExecutionUnit(string $name): ExecutionUnit
        {
            $execution_unit_name = sprintf('@%s:%s', PackageDirectory::EXECUTION_UNITS, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$execution_unit_name]))
            {
                throw new ConfigurationException(sprintf('Execution unit \'%s\' not found in package', $name));
            }

            return ExecutionUnit::fromArray(ZiProto::decode($this->get($execution_unit_name)));
        }

        /**
         * Returns an execution unit from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return ExecutionUnit
         * @throws ConfigurationException
         */
        public function getExecutionUnitByPointer(int $pointer, int $length): ExecutionUnit
        {
            return ExecutionUnit::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
        }

        /**
         * Returns the package's component pointers
         *
         * @return array
         */
        public function getComponents(): array
        {
            $components = [];
            $directory = sprintf('@%s:', PackageDirectory::COMPONENTS);

            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
            {
                if(str_starts_with($name, $directory))
                {
                    $components[] = str_replace($directory, '', $name);
                }
            }

            return $components;
        }

        public function getClassMap(): array
        {
            $class_map = [];
            $directory = sprintf('@%s:', PackageDirectory::CLASS_POINTER);

            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
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
         */
        public function getComponent(string $name): Component
        {
            $component_name = sprintf('@%s:%s', PackageDirectory::COMPONENTS, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$component_name]))
            {
                throw new ConfigurationException(sprintf('Component \'%s\' not found in package', $name));
            }

            return Component::fromArray(ZiProto::decode($this->get($component_name)));
        }

        /**
         * Returns a component from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Component
         * @throws ConfigurationException
         */
        public function getComponentByPointer(int $pointer, int $length): Component
        {
            return Component::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
        }

        /**
         * Returns a component from the package by a class pointer
         *
         * @param string $class
         * @return Component
         * @throws ConfigurationException
         */
        public function getComponentByClass(string $class): Component
        {
            $class_name = sprintf('@%s:%s', PackageDirectory::CLASS_POINTER, $class);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$class_name]))
            {
                throw new ConfigurationException(sprintf('Class map \'%s\' not found in package', $class));
            }

            return Component::fromArray(ZiProto::decode($this->get($class_name)));
        }

        /**
         * Returns an array of resource pointers from the package
         *
         * @return array
         */
        public function getResources(): array
        {
            $resources = [];
            $directory = sprintf('@%s:', PackageDirectory::RESOURCES);

            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
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
         */
        public function getResource(string $name): Resource
        {
            $resource_name = sprintf('@%s:%s', PackageDirectory::RESOURCES, $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$resource_name]))
            {
                throw new ConfigurationException(sprintf('Resource \'%s\' not found in package', $name));
            }

            return Resource::fromArray(ZiProto::decode($this->get($resource_name)));
        }

        /**
         * Returns a resource from the package by pointer
         *
         * @param int $pointer
         * @param int $length
         * @return Resource
         * @throws ConfigurationException
         */
        public function getResourceByPointer(int $pointer, int $length): Resource
        {
            return Resource::fromArray(ZiProto::decode($this->getByPointer($pointer, $length)));
        }

        /**
         * PackageReader destructor.
         */
        public function __destruct()
        {
            if(is_resource($this->package_file))
            {
                fclose($this->package_file);
            }
        }
    }