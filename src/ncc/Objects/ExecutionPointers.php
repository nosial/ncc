<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use ncc\Exceptions\FileNotFoundException;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Utilities\Validate;

    class ExecutionPointers
    {
        /**
         * @var string
         */
        private $Package;

        /**
         * @var string
         */
        private $Version;

        /**
         * @var ExecutionPointer[]
         */
        private $Pointers;

        /**
         * @param string|null $package
         * @param string|null $version
         */
        public function __construct(?string $package=null, ?string $version=null)
        {
            $this->Package = $package;
            $this->Version = $version;
            $this->Pointers = [];
        }

        /**
         * Adds an Execution Unit as a pointer
         *
         * @param ExecutionUnit $unit
         * @param bool $overwrite
         * @return bool
         */
        public function addUnit(ExecutionUnit $unit, string $bin_file, bool $overwrite=true): bool
        {
            if(Validate::exceedsPathLength($bin_file))
                return false;

            if(!file_exists($bin_file))
                throw new FileNotFoundException('The file ' . $unit->Data . ' does not exist, cannot add unit \'' . $unit->ExecutionPolicy->Name . '\'');

            if($overwrite)
            {
                $this->deleteUnit($unit->ExecutionPolicy->Name);
            }
            elseif($this->getUnit($unit->ExecutionPolicy->Name) !== null)
            {
                return false;
            }

            $this->Pointers[] = new ExecutionPointer($unit, $bin_file);
            return true;
        }

        /**
         * Deletes an existing unit from execution pointers
         *
         * @param string $name
         * @return bool
         */
        public function deleteUnit(string $name): bool
        {
            $unit = $this->getUnit($name);
            if($unit == null)
                return false;

            $new_pointers = [];
            foreach($this->Pointers as $pointer)
            {
                if($pointer->ExecutionPolicy->Name !== $name)
                    $new_pointers[] = $pointer;
            }

            $this->Pointers = $new_pointers;
            return true;
        }

        /**
         * Returns an existing unit from the pointers
         *
         * @param string $name
         * @return ExecutionPointer|null
         */
        public function getUnit(string $name): ?ExecutionPointer
        {
            foreach($this->Pointers as $pointer)
            {
                if($pointer->ExecutionPolicy->Name == $name)
                    return $pointer;
            }

            return null;
        }

        /**
         * Returns an array of execution pointers that are currently configured
         *
         * @return array|ExecutionPointer[]
         */
        public function getPointers(): array
        {
            return $this->Pointers;
        }

        /**
         * Returns the version of the package that uses these execution policies.
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->Version;
        }

        /**
         * Returns the name of the package that uses these execution policies
         *
         * @return string
         */
        public function getPackage(): string
        {
            return $this->Package;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool  $bytecode=false): array
        {
            $pointers = [];
            foreach($this->Pointers as $pointer)
            {
                $pointers[] = $pointer->toArray($bytecode);
            }
            return $pointers;
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return ExecutionPointers
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            foreach($data as $datum)
            {
                $object->Pointers[] = ExecutionPointer::fromArray($datum);
            }

            return $object;
        }
    }