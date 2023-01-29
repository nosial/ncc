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

    use ncc\Exceptions\FileNotFoundException;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Utilities\Functions;
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
         * @param string $bin_file
         * @param bool $overwrite
         * @return bool
         * @throws FileNotFoundException
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
            /** @var ExecutionPointer $pointer */
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
            return [
                ($bytecode ? Functions::cbc('package') : 'package')  => $this->Package,
                ($bytecode ? Functions::cbc('version') : 'version')  => $this->Version,
                ($bytecode ? Functions::cbc('pointers') : 'pointers') => $pointers
            ];
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

            $object->Version = Functions::array_bc($data, 'version');
            $object->Package = Functions::array_bc($data, 'package');
            $object->Pointers = Functions::array_bc($data, 'pointers');

            if($object->Pointers !== null)
            {
                $pointers = [];
                foreach($object->Pointers as $pointer)
                {
                    $pointers[] = ExecutionPointer::fromArray($pointer);
                }
                $object->Pointers = $pointers;
            }

            return $object;
        }
    }