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

    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\ZiProto\ZiProto;
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

            $magic_bytes = fread($this->package_file, 7);
            $header = '';
            $diameter_hit = false;
            $this->header_length = 7;

            // Check for the magic bytes "ncc_pkg"
            if($magic_bytes !== 'ncc_pkg')
            {
                throw new IOException(sprintf('File \'%s\' is not a valid package file (invalid magic bytes)', $file_path));
            }

            // Read everything after "ncc_pkg" up until the delimiter (0x1F 0x1F)
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
         * Gets a resource from the package
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
            return fread($this->package_file, (int)$location[1]);
        }

        /**
         * Returns the package's assembly
         *
         * @return Assembly
         * @throws ConfigurationException
         */
        public function getAssembly(): Assembly
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY]['@assembly']))
            {
                throw new ConfigurationException('Package does not contain an assembly');
            }

            return Assembly::fromArray(ZiProto::decode($this->get('@assembly')));
        }

        /**
         * Returns the package's metadata
         *
         * @return Metadata
         * @throws ConfigurationException
         */
        public function getMetadata(): Metadata
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY]['@metadata']))
            {
                throw new ConfigurationException('Package does not contain metadata');
            }

            return Metadata::fromArray(ZiProto::decode($this->get('@metadata')));
        }

        /**
         * Optional. Returns the package's installer
         *
         * @return Installer|null
         */
        public function getInstaller(): ?Installer
        {
            if(!isset($this->headers[PackageStructure::DIRECTORY]['@installer']))
            {
                return null;
            }

            return Installer::fromArray(ZiProto::decode($this->get('@installer')));
        }

        /**
         * Returns the package's dependencies
         *
         * @return array
         */
        public function getDependencies(): array
        {
            $dependencies = [];
            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
            {
                if(str_starts_with($name, '@dependencies:'))
                {
                    $dependencies[] = str_replace('@dependencies:', '', $name);
                }
            }

            return $dependencies;
        }

        /**
         * Returns a dependency from the package
         *
         * @param string $name
         * @return array
         * @throws ConfigurationException
         */
        public function getDependency(string $name): array
        {
            $dependency_name = sprintf('@dependencies:%s', $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$dependency_name]))
            {
                throw new ConfigurationException(sprintf('Dependency \'%s\' not found in package', $name));
            }

            return ZiProto::decode($this->get('@dependencies:' . $name));
        }

        /**
         * Returns an array of execution units from the package
         *
         * @return array
         */
        public function getExecutionUnits(): array
        {
            $execution_units = [];
            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
            {
                if(str_starts_with($name, '@execution_units:'))
                {
                    $execution_units[] = str_replace('@execution_units:', '', $name);
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
            $execution_unit_name = sprintf('@execution_units:%s', $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$execution_unit_name]))
            {
                throw new ConfigurationException(sprintf('Execution unit \'%s\' not found in package', $name));
            }

            return ExecutionUnit::fromArray(ZiProto::decode($this->get($execution_unit_name)));
        }

        /**
         * Returns the package's components
         *
         * @return array
         */
        public function getComponents(): array
        {
            $components = [];
            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
            {
                if(str_starts_with($name, '@components:'))
                {
                    $components[] = str_replace('@components:', '', $name);
                }
            }

            return $components;
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
            $component_name = sprintf('@components:%s', $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$component_name]))
            {
                throw new ConfigurationException(sprintf('Component \'%s\' not found in package', $name));
            }

            return Component::fromArray(ZiProto::decode($this->get('@components:' . $name)));
        }

        /**
         * Returns an array of resources from the package
         *
         * @return array
         */
        public function getResources(): array
        {
            $resources = [];
            foreach($this->headers[PackageStructure::DIRECTORY] as $name => $location)
            {
                if(str_starts_with($name, '@resources:'))
                {
                    $resources[] = str_replace('@resources:', '', $name);
                }
            }

            return $resources;
        }

        /**
         * Returns a resource from the package
         *
         * @param string $name
         * @return string
         * @throws ConfigurationException
         */
        public function getResource(string $name): string
        {
            $resource_name = sprintf('@resources:%s', $name);
            if(!isset($this->headers[PackageStructure::DIRECTORY][$resource_name]))
            {
                throw new ConfigurationException(sprintf('Resource \'%s\' not found in package', $name));
            }

            return $this->get('@resources:' . $name);
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