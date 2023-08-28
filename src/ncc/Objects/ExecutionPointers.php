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

    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    class ExecutionPointers implements BytecodeObjectInterface
    {
        /**
         * @var string
         */
        private $package;

        /**
         * @var string
         */
        private $version;

        /**
         * @var ExecutionPointer[]
         */
        private $pointers;

        /**
         * @param string|null $package
         * @param string|null $version
         */
        public function __construct(?string $package=null, ?string $version=null)
        {
            $this->package = $package;
            $this->version = $version;
            $this->pointers = [];
        }

        /**
         * Adds an Execution Unit as a pointer
         *
         * @param ExecutionUnit $unit
         * @param string $bin_file
         * @param bool $overwrite
         * @return bool
         * @throws PathNotFoundException
         */
        public function addUnit(ExecutionUnit $unit, string $bin_file, bool $overwrite=true): bool
        {
            if(Validate::exceedsPathLength($bin_file))
            {
                return false;
            }

            if(!file_exists($bin_file))
            {
                throw new PathNotFoundException($bin_file);
            }

            if($overwrite)
            {
                $this->deleteUnit($unit->getExecutionPolicy()->getName());
            }
            elseif($this->getUnit($unit->getExecutionPolicy()->getName()) !== null)
            {
                return false;
            }

            $this->pointers[] = new ExecutionPointer($unit, $bin_file);
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

            if($unit === null)
            {
                return false;
            }

            $new_pointers = [];
            foreach($this->pointers as $pointer)
            {
                if($pointer->getExecutionPolicy()->getName() !== $name)
                {
                    $new_pointers[] = $pointer;
                }
            }

            $this->pointers = $new_pointers;
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
            foreach($this->pointers as $pointer)
            {
                if($pointer->getExecutionPolicy()->getName() === $name)
                {
                    return $pointer;
                }
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
            return $this->pointers;
        }

        /**
         * Returns the version of the package that uses these execution policies.
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * Returns the name of the package that uses these execution policies
         *
         * @return string
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool  $bytecode=false): array
        {
            $pointers = [];
            foreach($this->pointers as $pointer)
            {
                $pointers[] = $pointer->toArray($bytecode);
            }
            return [
                ($bytecode ? Functions::cbc('package') : 'package')  => $this->package,
                ($bytecode ? Functions::cbc('version') : 'version')  => $this->version,
                ($bytecode ? Functions::cbc('pointers') : 'pointers') => $pointers
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->version = Functions::array_bc($data, 'version');
            $object->package = Functions::array_bc($data, 'package');
            $object->pointers = Functions::array_bc($data, 'pointers');

            if($object->pointers !== null)
            {
                $pointers = [];
                foreach($object->pointers as $pointer)
                {
                    $pointers[] = ExecutionPointer::fromArray($pointer);
                }
                $object->pointers = $pointers;
            }

            return $object;
        }
    }