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
    use ncc\Exceptions\InvalidPackageException;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageParsingException;
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
        public $magic_bytes;

        /**
         * The true header of the package
         *
         * @var Header
         */
        public $header;

        /**
         * The assembly object of the package
         *
         * @var Assembly
         */
        public $assembly;

        /**
         * An array of dependencies that the package depends on
         *
         * @var Dependency[]
         */
        public $dependencies;

        /**
         * The Main Execution Policy object for the package if the package is an executable package.
         *
         * @var string|null
         */
        public $main_execution_policy;

        /**
         * The installer object that is used to install the package if the package is install-able
         *
         * @var Installer|null
         */
        public $installer;

        /**
         * An array of execution units defined in the package
         *
         * @var ExecutionUnit[]
         */
        public $execution_units;

        /**
         * An array of resources that the package depends on
         *
         * @var Resource[]
         */
        public $resources;

        /**
         * An array of components for the package
         *
         * @var Component[]
         */
        public $components;

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
                if($dep->name === $dependency->name)
                {
                    $this->removeDependency($dep->name);
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
                if($dep->name === $name)
                {
                    unset($this->dependencies[$key]);
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
         * @throws InvalidPackageException
         * @throws InvalidProjectConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            // Validate the MagicBytes constructor
            if($this->magic_bytes === null)
            {
                if($throw_exception)
                {
                    throw new InvalidPackageException('The MagicBytes property is required and cannot be null');
                }

                return false;
            }

            // Validate the assembly object
            if($this->assembly === null)
            {
                if($throw_exception)
                {
                    throw new InvalidPackageException('The Assembly property is required and cannot be null');
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
                if($unit->execution_policy->name === $name)
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
         * @throws PackageParsingException
         * @throws PathNotFoundException
         */
        public static function load(string $path): Package
        {
            if(!file_exists($path) || !is_file($path) || !is_readable($path))
            {
                throw new PathNotFoundException($path);
            }

            $handle = fopen($path, "rb");
            $header = fread($handle, 256); // Read the first 256 bytes of the file
            fclose($handle);

            if(stripos($header, 'NCC_PACKAGE') === 0)
            {
                throw new PackageParsingException('The package \'' . $path . '\' does not appear to be a valid NCC Package (Missing Header)');
            }

            // Extract the package structure version
            $package_structure_version = strtoupper(substr($header, 11, 3));

            if(!in_array($package_structure_version, PackageStructureVersions::ALL))
            {
                throw new PackageParsingException('The package \'' . $path . '\' has a package structure version of ' . $package_structure_version . ' which is not supported by this version NCC');
            }

            // Extract the package encoding type and package type
            $encoding_header = strtoupper(substr($header, 14, 5));
            $encoding_type = substr($encoding_header, 0, 3);
            $package_type = substr($encoding_header, 3, 2);

            $magic_bytes = new MagicBytes();
            $magic_bytes->PackageStructureVersion = $package_structure_version;

            // Determine the encoding type
            switch($encoding_type)
            {
                case '300':
                    $magic_bytes->Encoder = EncoderType::ZI_PROTO;
                    $magic_bytes->IsCompressed = false;
                    $magic_bytes->IsEncrypted = false;
                    break;

                case '301':
                    $magic_bytes->Encoder = EncoderType::ZI_PROTO;
                    $magic_bytes->IsCompressed = true;
                    $magic_bytes->IsEncrypted = false;
                    break;

                case '310':
                    $magic_bytes->Encoder = EncoderType::ZI_PROTO;
                    $magic_bytes->IsCompressed = false;
                    $magic_bytes->IsEncrypted = true;
                    break;

                case '311':
                    $magic_bytes->Encoder = EncoderType::ZI_PROTO;
                    $magic_bytes->IsCompressed = true;
                    $magic_bytes->IsEncrypted = true;
                    break;

                default:
                    throw new PackageParsingException('Cannot determine the encoding type for the package \'' . $path . '\' (Got ' . $encoding_type . ')');
            }

            // Determine the package type
            switch($package_type)
            {
                case '40':
                    $magic_bytes->IsInstallable = true;
                    $magic_bytes->IsExecutable = false;
                    break;

                case '41':
                    $magic_bytes->IsInstallable = false;
                    $magic_bytes->IsExecutable = true;
                    break;

                case '42':
                    $magic_bytes->IsInstallable = true;
                    $magic_bytes->IsExecutable = true;
                    break;

                default:
                    throw new PackageParsingException('Cannot determine the package type for the package \'' . $path . '\' (Got ' . $package_type . ')');
            }

            // Assuming all is good, load the entire fire into memory and parse its contents
            try
            {
                $package = self::fromArray(ZiProto::decode(substr(IO::fread($path), strlen($magic_bytes->toString()))));
            }
            catch(Exception $e)
            {
                throw new PackageParsingException('Cannot decode the contents of the package \'' . $path . '\', invalid encoding or the package is corrupted, ' . $e->getMessage(), $e);
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