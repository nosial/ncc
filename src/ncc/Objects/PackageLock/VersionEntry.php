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

    namespace ncc\Objects\PackageLock;

    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Utilities\Functions;

    class VersionEntry
    {
        /**
         * The version of the package that's installed
         *
         * @var string
         */
        public $Version;

        /**
         * The compiler extension used for the package
         *
         * @var Compiler
         */
        public $Compiler;

        /**
         * An array of packages that this package depends on
         *
         * @var DependencyEntry[]
         */
        public $Dependencies;

        /**
         * @var ExecutionUnit[]
         */
        public $ExecutionUnits;

        /**
         * The main execution policy for this version entry if applicable
         *
         * @var string|null
         */
        public $MainExecutionPolicy;

        /**
         * The path where the package is located
         *
         * @var string
         */
        public $Location;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Dependencies = [];
            $this->ExecutionUnits = [];
        }

        /**
         * Returns installation paths
         *
         * @return InstallationPaths
         */
        public function getInstallPaths(): InstallationPaths
        {
            return new InstallationPaths($this->Location);
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $dependencies = [];
            foreach($this->Dependencies as $dependency)
            {
                $dependencies[] = $dependency->toArray($bytecode);
            }

            $execution_units = [];
            foreach($this->ExecutionUnits as $executionUnit)
            {
                $execution_units[] = $executionUnit->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('version')  : 'version')  => $this->Version,
                ($bytecode ? Functions::cbc('compiler')  : 'compiler')  => $this->Compiler->toArray(),
                ($bytecode ? Functions::cbc('dependencies')  : 'dependencies')  => $dependencies,
                ($bytecode ? Functions::cbc('execution_units')  : 'execution_units')  => $execution_units,
                ($bytecode ? Functions::cbc('main_execution_policy')  : 'main_execution_policy')  => $this->MainExecutionPolicy,
                ($bytecode ? Functions::cbc('location')  : 'location')  => $this->Location,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return VersionEntry
         */
        public static function fromArray(array $data): self
        {
            $object = new self();
            $object->Version = Functions::array_bc($data, 'version');
            $object->Compiler = Compiler::fromArray(Functions::array_bc($data, 'compiler'));
            $object->MainExecutionPolicy = Functions::array_bc($data, 'main_execution_policy');
            $object->Location = Functions::array_bc($data, 'location');

            $dependencies = Functions::array_bc($data, 'dependencies');
            if($dependencies !== null)
            {
                foreach($dependencies as $_datum)
                {
                    $object->Dependencies[] = DependencyEntry::fromArray($_datum);
                }
            }

            $execution_units = Functions::array_bc($data, 'execution_units');
            if($execution_units !== null)
            {
                foreach($execution_units as $_datum)
                {
                    $object->ExecutionUnits[] = ExecutionUnit::fromArray($_datum);
                }
            }

            return $object;
        }
    }