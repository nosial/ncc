<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use Exception;
    use ncc\Abstracts\EncoderType;
    use ncc\Abstracts\PackageStructureVersions;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidPackageException;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageParsingException;
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

    class Package
    {
        /**
         * The parsed magic bytes of the package into an object representation
         *
         * @var MagicBytes
         */
        public $MagicBytes;

        /**
         * The true header of the package
         *
         * @var Header
         */
        public $Header;

        /**
         * The assembly object of the package
         *
         * @var Assembly
         */
        public $Assembly;

        /**
         * An array of dependencies that the package depends on
         *
         * @var Dependency[]
         */
        public $Dependencies;

        /**
         * The Main Execution Policy object for the package if the package is an executable package.
         *
         * @var string|null
         */
        public $MainExecutionPolicy;

        /**
         * The installer object that is used to install the package if the package is install-able
         *
         * @var Installer|null
         */
        public $Installer;

        /**
         * An array of execution units defined in the package
         *
         * @var ExecutionUnit[]
         */
        public $ExecutionUnits;

        /**
         * An array of resources that the package depends on
         *
         * @var Resource[]
         */
        public $Resources;

        /**
         * An array of components for the package
         *
         * @var Component[]
         */
        public $Components;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->MagicBytes = new MagicBytes();
            $this->Header = new Header();
            $this->Assembly = new Assembly();
            $this->ExecutionUnits = [];
            $this->Components = [];
            $this->Dependencies = [];
            $this->Resources = [];
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
            if($this->MagicBytes == null)
            {
                if($throw_exception)
                    throw new InvalidPackageException('The MagicBytes property is required and cannot be null');

                return false;
            }

            // Validate the assembly object
            if($this->Assembly == null)
            {
                if($throw_exception)
                    throw new InvalidPackageException('The Assembly property is required and cannot be null');

                return false;
            }

            if(!$this->Assembly->validate($throw_exception))
                return false;

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
            foreach($this->ExecutionUnits as $unit)
            {
                if($unit->ExecutionPolicy->Name == $name)
                    return $unit;
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
            $package_contents = $this->MagicBytes->toString() . ZiProto::encode($this->toArray(true));
            IO::fwrite($output_path, $package_contents, 0777);
        }

        /**
         * Attempts to parse the specified package path and returns the object representation
         * of the package, including with the MagicBytes representation that is in the
         * file headers.
         *
         * @param string $path
         * @return Package
         * @throws FileNotFoundException
         * @throws PackageParsingException
         */
        public static function load(string $path): Package
        {
            if(!file_exists($path) || !is_file($path) || !is_readable($path))
            {
                throw new FileNotFoundException('The file ' . $path . ' does not exist or is not readable');
            }

            $handle = fopen($path, "rb");
            $header = fread($handle, 256); // Read the first 256 bytes of the file
            fclose($handle);

            if(!strtoupper(substr($header, 0, 11)) == 'NCC_PACKAGE')
                throw new PackageParsingException('The package \'' . $path . '\' does not appear to be a valid NCC Package (Missing Header)');

            // Extract the package structure version
            $package_structure_version = strtoupper(substr($header, 11, 3));

            if(!in_array($package_structure_version, PackageStructureVersions::ALL))
                throw new PackageParsingException('The package \'' . $path . '\' has a package structure version of ' . $package_structure_version . ' which is not supported by this version NCC');

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
                    $magic_bytes->Encoder = EncoderType::ZiProto;
                    $magic_bytes->IsCompressed = false;
                    $magic_bytes->IsEncrypted = false;
                    break;

                case '301':
                    $magic_bytes->Encoder = EncoderType::ZiProto;
                    $magic_bytes->IsCompressed = true;
                    $magic_bytes->IsEncrypted = false;
                    break;

                case '310':
                    $magic_bytes->Encoder = EncoderType::ZiProto;
                    $magic_bytes->IsCompressed = false;
                    $magic_bytes->IsEncrypted = true;
                    break;

                case '311':
                    $magic_bytes->Encoder = EncoderType::ZiProto;
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

            // TODO: Implement encryption and compression parsing

            // Assuming all is good, load the entire fire into memory and parse its contents
            try
            {
                $package = Package::fromArray(ZiProto::decode(substr(IO::fread($path), strlen($magic_bytes->toString()))));
            }
            catch(Exception $e)
            {
                throw new PackageParsingException('Cannot decode the contents of the package \'' . $path . '\', invalid encoding or the package is corrupted, ' . $e->getMessage(), $e);
            }

            $package->MagicBytes = $magic_bytes;
            return $package;
        }

        /**
         * Constructs an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $_components = [];
            /** @var Component $component */
            foreach($this->Components as $component)
                $_components[] = $component->toArray($bytecode);

            $_dependencies = [];
            /** @var Dependency $dependency */
            foreach($this->Dependencies as $dependency)
                $_dependencies[] = $dependency->toArray($bytecode);

            $_resources = [];
            /** @var Resource $resource */
            foreach($this->Resources as $resource)
                $_resources[] = $resource->toArray($bytecode);

            $_execution_units = [];
            foreach($this->ExecutionUnits as $unit)
                $_execution_units[] = $unit->toArray($bytecode);

            return [
                ($bytecode ? Functions::cbc('header') : 'header') => $this?->Header?->toArray($bytecode),
                ($bytecode ? Functions::cbc('assembly') : 'assembly') => $this?->Assembly?->toArray($bytecode),
                ($bytecode ? Functions::cbc('dependencies') : 'dependencies') => $_dependencies,
                ($bytecode ? Functions::cbc('main_execution_policy') : 'main_execution_policy') => $this?->MainExecutionPolicy,
                ($bytecode ? Functions::cbc('installer') : 'installer') => $this?->Installer?->toArray($bytecode),
                ($bytecode ? Functions::cbc('execution_units') : 'execution_units') => $_execution_units,
                ($bytecode ? Functions::cbc('resources') : 'resources') => $_resources,
                ($bytecode ? Functions::cbc('components') : 'components') => $_components
            ];
        }

        /**
         * @param array $data
         * @return Package
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->Header = Functions::array_bc($data, 'header');
            if($object->Header !== null)
                $object->Header = Header::fromArray($object->Header);

            $object->Assembly = Functions::array_bc($data, 'assembly');
            if($object->Assembly !== null)
                $object->Assembly = Assembly::fromArray($object->Assembly);

            $object->MainExecutionPolicy = Functions::array_bc($data, 'main_execution_policy');

            $object->Installer = Functions::array_bc($data, 'installer');
            if($object->Installer !== null)
                $object->Installer = Installer::fromArray($object->Installer);

            $_dependencies = Functions::array_bc($data, 'dependencies');
            if($_dependencies !== null)
            {
                foreach($_dependencies as $dependency)
                {
                    $object->Dependencies[] = Resource::fromArray($dependency);
                }
            }

            $_resources = Functions::array_bc($data, 'resources');
            if($_resources !== null)
            {
                foreach($_resources as $resource)
                {
                    $object->Resources[] = Resource::fromArray($resource);
                }
            }

            $_components = Functions::array_bc($data, 'components');
            if($_components !== null)
            {
                foreach($_components as $component)
                {
                    $object->Components[] = Component::fromArray($component);
                }
            }

            $_execution_units = Functions::array_bc($data, 'execution_units');
            if($_execution_units !== null)
            {
                foreach($_execution_units as $unit)
                {
                    $object->ExecutionUnits[] = ExecutionUnit::fromArray($unit);
                }
            }

            return $object;
        }
    }