<?php
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use Exception;
    use ncc\Enums\EncoderType;
    use ncc\Enums\PackageStructureVersions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\Package\Header;
    use ncc\Objects\Package\Installer;
    use ncc\Objects\Package\MagicBytes;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\ZiProto\ZiProto;

    class Package implements BytecodeObjectInterface
    {
        /**
         * The parsed magic bytes of the package into an object representation
         *
         * @var MagicBytes
         */
        private $magic_bytes;

        /**
         * The true header of the package
         *
         * @var Header
         */
        private $header;

        /**
         * The assembly object of the package
         *
         * @var Assembly
         */
        private $assembly;

        /**
         * An array of dependencies that the package depends on
         *
         * @var Dependency[]
         */
        private $dependencies;

        /**
         * The Main Execution Policy object for the package if the package is an executable package.
         *
         * @var string|null
         */
        private $main_execution_policy;

        /**
         * The installer object that is used to install the package if the package is install-able
         *
         * @var Installer|null
         */
        private $installer;

        /**
         * An array of execution units defined in the package
         *
         * @var ExecutionUnit[]
         */
        private $execution_units;

        /**
         * An array of resources that the package depends on
         *
         * @var Resource[]
         */
        private $resources;

        /**
         * An array of components for the package
         *
         * @var Component[]
         */
        private $components;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->magic_bytes = new MagicBytes();
            $this->header = new Header();
            $this->assembly = new Assembly();
            $this->execution_units = [];
            $this->components = [];
            $this->dependencies = [];
            $this->resources = [];
        }

        /**
         * Adds a dependency to the package
         *
         * @param Dependency $dependency
         * @return void
         */
        public function addDependency(Dependency $dependency): void
        {
            foreach($this->dependencies as $dep)
            {
                if($dep->getName() === $dependency->getName())
                {
                    $this->removeDependency($dep->getName());
                    break;
                }
            }

            $this->dependencies[] = $dependency;
        }

        /**
         * Removes a dependency from the build
         *
         * @param string $name
         * @return void
         */
        private function removeDependency(string $name): void
        {
            foreach($this->dependencies as $key => $dep)
            {
                if($dep->getName() === $name)
                {
                    unset($this->dependencies[$key]);
                    return;
                }
            }
        }

        /**
         * @return MagicBytes
         */
        public function getMagicBytes(): MagicBytes
        {
            return $this->magic_bytes;
        }

        /**
         * @param MagicBytes $magic_bytes
         */
        public function setMagicBytes(MagicBytes $magic_bytes): void
        {
            $this->magic_bytes = $magic_bytes;
        }

        /**
         * @return Header
         */
        public function getHeader(): Header
        {
            return $this->header;
        }

        /**
         * @param Header $header
         */
        public function setHeader(Header $header): void
        {
            $this->header = $header;
        }

        /**
         * @return Assembly
         */
        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        /**
         * @param Assembly $assembly
         */
        public function setAssembly(Assembly $assembly): void
        {
            $this->assembly = $assembly;
        }

        /**
         * @return array|Dependency[]
         */
        public function getDependencies(): array
        {
            return $this->dependencies;
        }

        /**
         * @param array|Dependency[] $dependencies
         */
        public function setDependencies(array $dependencies): void
        {
            $this->dependencies = $dependencies;
        }

        /**
         * @return string|null
         */
        public function getMainExecutionPolicy(): ?string
        {
            return $this->main_execution_policy;
        }

        /**
         * @param string|null $main_execution_policy
         */
        public function setMainExecutionPolicy(?string $main_execution_policy): void
        {
            $this->main_execution_policy = $main_execution_policy;
        }

        /**
         * @return Installer|null
         */
        public function getInstaller(): ?Installer
        {
            return $this->installer;
        }

        /**
         * @param Installer|null $installer
         */
        public function setInstaller(?Installer $installer): void
        {
            $this->installer = $installer;
        }

        /**
         * @return array|ExecutionUnit[]
         */
        public function getExecutionUnits(): array
        {
            return $this->execution_units;
        }

        /**
         * @param array|ExecutionUnit[] $execution_units
         */
        public function setExecutionUnits(array $execution_units): void
        {
            $this->execution_units = $execution_units;
        }

        /**
         * @return array|Resource[]
         */
        public function getResources(): array
        {
            return $this->resources;
        }

        /**
         * @param array|Resource[] $resources
         */
        public function setResources(array $resources): void
        {
            $this->resources = $resources;
        }

        /**
         * @param Resource $resource
         * @return void
         */
        public function addResource(Resource $resource): void
        {
            foreach($this->resources as $res)
            {
                if($res->getName() === $resource->getName())
                {
                    $this->removeResource($res->getName());
                    break;
                }
            }

            $this->resources[] = $resource;
        }

        /**
         * @param string $name
         * @return void
         */
        private function removeResource(string $name): void
        {
            foreach($this->resources as $key => $res)
            {
                if($res->getName() === $name)
                {
                    unset($this->resources[$key]);
                    return;
                }
            }
        }

        /**
         * @return array|Component[]
         */
        public function getComponents(): array
        {
            return $this->components;
        }

        /**
         * @param array|Component[] $components
         */
        public function setComponents(array $components): void
        {
            $this->components = $components;
        }

        /**
         * @param Component $component
         * @return void
         */
        public function addComponent(Component $component): void
        {
            foreach($this->components as $comp)
            {
                if($comp->getName() === $component->getName())
                {
                    $this->removeComponent($comp->getName());
                    break;
                }
            }

            $this->components[] = $component;
        }

        /**
         * @param string $name
         * @return void
         */
        public function removeComponent(string $name): void
        {
            foreach($this->components as $key => $comp)
            {
                if($comp->getName() === $name)
                {
                    unset($this->components[$key]);
                    return;
                }
            }
        }

        /**
         * Validates the package object and returns True if the package contains the correct information
         *
         * Returns false if the package contains incorrect information which can cause
         * an error when compiling the package.
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            // Validate the MagicBytes constructor
            if($this->magic_bytes === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The MagicBytes property is required and cannot be null');
                }

                return false;
            }

            // Validate the assembly object
            if($this->assembly === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The Assembly property is required and cannot be null');
                }

                return false;
            }

            if(!$this->assembly->validate($throw_exception))
            {
                return false;
            }

            // All checks have passed
            return true;
        }

        /**
         * Attempts to find the execution unit with the given name
         *
         * @param string $name
         * @return ExecutionUnit|null
         */
        public function getExecutionUnit(string $name): ?ExecutionUnit
        {
            foreach($this->execution_units as $unit)
            {
                if($unit->getExecutionPolicy()->getName() === $name)
                {
                    return $unit;
                }
            }

            return null;
        }

        /**
         * Writes the package contents to disk
         *
         * @param string $output_path
         * @return void
         * @throws IOException
         */
        public function save(string $output_path): void
        {
            $package_contents = $this->magic_bytes->toString() . ZiProto::encode($this->toArray(true));
            IO::fwrite($output_path, $package_contents, 0777);
        }

        /**
         * Attempts to parse the specified package path and returns the object representation
         * of the package, including with the MagicBytes representation that is in the
         * file headers.
         *
         * @param string $path
         * @return Package
         * @throws PackageException
         * @throws PathNotFoundException
         */
        public static function load(string $path): Package
        {
            if(!is_file($path) || !is_readable($path))
            {
                throw new PathNotFoundException($path);
            }

            $handle = fopen($path, "rb");
            $header = fread($handle, 256); // Read the first 256 bytes of the file
            fclose($handle);

            if(stripos($header, 'NCC_PACKAGE') === 0)
            {
                throw new PackageException(sprintf("The package '%s' does not appear to be a valid NCC Package (Missing Header)", $path));
            }

            // Extract the package structure version
            $package_structure_version = strtoupper(substr($header, 11, 3));

            if(!in_array($package_structure_version, PackageStructureVersions::ALL, true))
            {
                throw new PackageException(sprintf("The package '%s' does not appear to be a valid NCC Package (Unsupported Package Structure Version)", $path));
            }

            // Extract the package encoding type and package type
            $encoding_header = strtoupper(substr($header, 14, 5));
            $encoding_type = substr($encoding_header, 0, 3);
            $package_type = substr($encoding_header, 3, 2);

            $magic_bytes = new MagicBytes();
            $magic_bytes->setPackageStructureVersion($package_structure_version);

            // Determine the encoding type
            switch($encoding_type)
            {
                case '300':
                    $magic_bytes->setEncoder(EncoderType::ZI_PROTO);
                    $magic_bytes->setCompressed(false);
                    $magic_bytes->setCompressed(false);
                    break;

                case '301':
                    $magic_bytes->setEncoder(EncoderType::ZI_PROTO);
                    $magic_bytes->setCompressed(true);
                    $magic_bytes->setEncrypted(false);
                    break;

                case '310':
                    $magic_bytes->setEncoder(EncoderType::ZI_PROTO);
                    $magic_bytes->setCompressed(false);
                    $magic_bytes->setEncrypted(true);
                    break;

                case '311':
                    $magic_bytes->setEncoder(EncoderType::ZI_PROTO);
                    $magic_bytes->setCompressed(true);
                    $magic_bytes->setEncrypted(true);
                    break;

                default:
                    throw new PackageException(sprintf("The package '%s' does not appear to be a valid NCC Package (Unsupported Encoding Type)", $path));
            }

            // Determine the package type
            switch($package_type)
            {
                case '40':
                    $magic_bytes->setInstallable(true);
                    $magic_bytes->setExecutable(false);
                    break;

                case '41':
                    $magic_bytes->setInstallable(false);
                    $magic_bytes->setExecutable(true);
                    break;

                case '42':
                    $magic_bytes->setInstallable(true);
                    $magic_bytes->setExecutable(true);
                    break;

                default:
                    throw new PackageException(sprintf("The package '%s' does not appear to be a valid NCC Package (Unsupported Package Type)", $path));
            }

            // Assuming all is good, load the entire fire into memory and parse its contents
            try
            {
                $package = self::fromArray(ZiProto::decode(substr(IO::fread($path), strlen($magic_bytes->toString()))));
            }
            catch(Exception $e)
            {
                throw new PackageException(sprintf("The package '%s' does not appear to be a valid NCC Package (Invalid Package Contents)", $path), $e);
            }

            $package->magic_bytes = $magic_bytes;
            return $package;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $_components = [];
            /** @var Component $component */
            foreach($this->components as $component)
            {
                $_components[] = $component->toArray($bytecode);
            }

            $_dependencies = [];
            /** @var Dependency $dependency */
            foreach($this->dependencies as $dependency)
            {
                $_dependencies[] = $dependency->toArray($bytecode);
            }

            $_resources = [];
            /** @var Resource $resource */
            foreach($this->resources as $resource)
            {
                $_resources[] = $resource->toArray($bytecode);
            }

            $_execution_units = [];
            foreach($this->execution_units as $unit)
            {
                $_execution_units[] = $unit->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('header') : 'header') => $this?->header?->toArray($bytecode),
                ($bytecode ? Functions::cbc('assembly') : 'assembly') => $this?->assembly?->toArray($bytecode),
                ($bytecode ? Functions::cbc('dependencies') : 'dependencies') => $_dependencies,
                ($bytecode ? Functions::cbc('main_execution_policy') : 'main_execution_policy') => $this?->main_execution_policy,
                ($bytecode ? Functions::cbc('installer') : 'installer') => $this?->installer?->toArray($bytecode),
                ($bytecode ? Functions::cbc('execution_units') : 'execution_units') => $_execution_units,
                ($bytecode ? Functions::cbc('resources') : 'resources') => $_resources,
                ($bytecode ? Functions::cbc('components') : 'components') => $_components
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Package
        {
            $object = new self();

            $object->header = Functions::array_bc($data, 'header');
            if($object->header !== null)
            {
                $object->header = Header::fromArray($object->header);
            }

            $object->assembly = Functions::array_bc($data, 'assembly');
            if($object->assembly !== null)
            {
                $object->assembly = Assembly::fromArray($object->assembly);
            }

            $object->main_execution_policy = Functions::array_bc($data, 'main_execution_policy');

            $object->installer = Functions::array_bc($data, 'installer');
            if($object->installer !== null)
            {
                $object->installer = Installer::fromArray($object->installer);
            }

            $_dependencies = Functions::array_bc($data, 'dependencies');
            if($_dependencies !== null)
            {
                foreach($_dependencies as $dependency)
                {
                    $object->dependencies[] = Dependency::fromArray($dependency);
                }
            }

            $_resources = Functions::array_bc($data, 'resources');
            if($_resources !== null)
            {
                foreach($_resources as $resource)
                {
                    $object->resources[] = Resource::fromArray($resource);
                }
            }

            $_components = Functions::array_bc($data, 'components');
            if($_components !== null)
            {
                foreach($_components as $component)
                {
                    $object->components[] = Component::fromArray($component);
                }
            }

            $_execution_units = Functions::array_bc($data, 'execution_units');
            if($_execution_units !== null)
            {
                foreach($_execution_units as $unit)
                {
                    $object->execution_units[] = ExecutionUnit::fromArray($unit);
                }
            }

            return $object;
        }
    }