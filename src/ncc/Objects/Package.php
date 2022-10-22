<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use ncc\Exceptions\InvalidPackageException;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\Header;
    use ncc\Objects\Package\Installer;
    use ncc\Objects\Package\MagicBytes;
    use ncc\Objects\Package\MainExecutionPolicy;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;

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
         * @var MainExecutionPolicy|null
         */
        public $MainExecutionPolicy;

        /**
         * The installer object that is used to install the package if the package is install-able
         *
         * @var Installer|null
         */
        public $Installer;

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

            return [
                ($bytecode ? Functions::cbc('header') : 'header') => $this->Header->toArray($bytecode),
                ($bytecode ? Functions::cbc('assembly') : 'assembly') => $this->Assembly->toArray($bytecode),
                ($bytecode ? Functions::cbc('dependencies') : 'dependencies') => $_dependencies,
                ($bytecode ? Functions::cbc('main_execution_policy') : 'main_execution_policy') => $this->MainExecutionPolicy->toArray($bytecode),
                ($bytecode ? Functions::cbc('installer') : 'installer') => $this->Installer->toArray($bytecode),
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
            if($object->MainExecutionPolicy !== null)
                $object->MainExecutionPolicy = MainExecutionPolicy::fromArray($object->MainExecutionPolicy);

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

            return $object;
        }
    }