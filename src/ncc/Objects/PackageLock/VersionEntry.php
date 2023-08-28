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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Utilities\Functions;

    class VersionEntry implements BytecodeObjectInterface
    {
        /**
         * The version of the package that's installed
         *
         * @var string
         */
        private $version;

        /**
         * The compiler extension used for the package
         *
         * @var Compiler
         */
        private $compiler;

        /**
         * An array of packages that this package depends on
         *
         * @var DependencyEntry[]
         */
        private $dependencies;

        /**
         * @var ExecutionUnit[]
         */
        public $execution_units;

        /**
         * The main execution policy for this version entry if applicable
         *
         * @var string|null
         */
        public $main_execution_policy;

        /**
         * The path where the package is located
         *
         * @var string
         */
        public $location;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->dependencies = [];
            $this->execution_units = [];
        }

        /**
         * Returns installation paths
         *
         * @return InstallationPaths
         */
        public function getInstallPaths(): InstallationPaths
        {
            return new InstallationPaths($this->location);
        }

        /**
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * @param string $version
         */
        public function setVersion(string $version): void
        {
            $this->version = $version;
        }

        /**
         * @return Compiler
         */
        public function getCompiler(): Compiler
        {
            return $this->compiler;
        }

        /**
         * @param Compiler $compiler
         */
        public function setCompiler(Compiler $compiler): void
        {
            $this->compiler = $compiler;
        }

        /**
         * @return array|DependencyEntry[]
         */
        public function getDependencies(): array
        {
            return $this->dependencies;
        }

        /**
         * @param array|DependencyEntry[] $dependencies
         */
        public function setDependencies(array $dependencies): void
        {
            $this->dependencies = $dependencies;
        }

        /**
         * @param DependencyEntry $dependency
         * @return void
         */
        public function addDependency(DependencyEntry $dependency): void
        {
            $this->dependencies[] = $dependency;
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
         * @return string
         */
        public function getLocation(): string
        {
            return $this->location;
        }

        /**
         * @param string $location
         */
        public function setLocation(string $location): void
        {
            $this->location = $location;
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
            foreach($this->dependencies as $dependency)
            {
                $dependencies[] = $dependency->toArray($bytecode);
            }

            $execution_units = [];
            foreach($this->execution_units as $executionUnit)
            {
                $execution_units[] = $executionUnit->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('version')  : 'version')  => $this->version,
                ($bytecode ? Functions::cbc('compiler')  : 'compiler')  => $this->compiler->toArray(),
                ($bytecode ? Functions::cbc('dependencies')  : 'dependencies')  => $dependencies,
                ($bytecode ? Functions::cbc('execution_units')  : 'execution_units')  => $execution_units,
                ($bytecode ? Functions::cbc('main_execution_policy')  : 'main_execution_policy')  => $this->main_execution_policy,
                ($bytecode ? Functions::cbc('location')  : 'location')  => $this->location,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return VersionEntry
         */
        public static function fromArray(array $data): VersionEntry
        {
            $object = new self();
            $object->version = Functions::array_bc($data, 'version');
            $object->compiler = Compiler::fromArray(Functions::array_bc($data, 'compiler'));
            $object->main_execution_policy = Functions::array_bc($data, 'main_execution_policy');
            $object->location = Functions::array_bc($data, 'location');

            $dependencies = Functions::array_bc($data, 'dependencies');
            if($dependencies !== null)
            {
                foreach($dependencies as $_datum)
                {
                    $object->dependencies[] = DependencyEntry::fromArray($_datum);
                }
            }

            $execution_units = Functions::array_bc($data, 'execution_units');
            if($execution_units !== null)
            {
                foreach($execution_units as $_datum)
                {
                    $object->execution_units[] = ExecutionUnit::fromArray($_datum);
                }
            }

            return $object;
        }
    }