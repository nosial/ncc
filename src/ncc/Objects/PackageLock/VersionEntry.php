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

    use InvalidArgumentException;
    use ncc\Enums\FileDescriptor;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Utilities\Functions;
    use ncc\Utilities\PathFinder;

    class VersionEntry implements BytecodeObjectInterface
    {
        /**
         * The version of the package that's installed
         *
         * @var string
         */
        private $version;

        /**
         * An array of packages that this package depends on
         *
         * @var Dependency[]
         */
        private $dependencies;

        /**
         * @var ExecutionPolicy[]
         */
        public $execution_policies;

        /**
         * The main execution policy for this version entry if applicable
         *
         * @var string|null
         */
        public $main_execution_policy;

        /**
         * Public Constructor
         */
        public function __construct(string $version)
        {
            $this->version = $version;
            $this->dependencies = [];
            $this->execution_policies = [];
            $this->main_execution_policy = null;
        }

        /**
         * Returns the version of the package that's installed
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * Returns the main execution policy for this version entry if applicable
         *
         * @return string|null
         */
        public function getMainExecutionPolicy(): ?string
        {
            return $this->main_execution_policy;
        }

        /**
         * Sets the main execution policy for this version entry if applicable
         *
         * @param string|null $policy
         * @return void
         */
        public function setMainExecutionPolicy(?string $policy): void
        {
            $this->main_execution_policy = $policy;
        }

        /**
         * Returns an array of packages that this package depends on
         *
         * @return Dependency[]
         */
        public function getDependencies(): array
        {
            return $this->dependencies;
        }

        /**
         * Returns a dependency by name if it exists
         *
         * @param string $name
         * @return Dependency
         */
        public function getDependency(string $name): Dependency
        {
            foreach($this->dependencies as $dependency)
            {
                if($dependency->getName() === $name)
                {
                    return $dependency;
                }
            }

            throw new InvalidArgumentException(sprintf('Dependency "%s" does not exist in the version entry', $name));
        }

        /**
         * Returns True if the dependency exists, false otherwise
         *
         * @param string $name
         * @return bool
         */
        public function dependencyExists(string $name): bool
        {
            foreach($this->dependencies as $dependency)
            {
                if($dependency->getName() === $name)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Adds a dependency to the version entry
         *
         * @param Dependency $dependency
         * @return void
         */
        public function addDependency(Dependency $dependency): void
        {
            if($this->dependencyExists($dependency->getName()))
            {
                return;
            }

            $this->dependencies[] = $dependency;
        }

        /**
         * Returns an array of execution policies for this version entry
         *
         * @return ExecutionPolicy[]
         */
        public function getExecutionPolicies(): array
        {
            return $this->execution_policies;
        }

        /**
         * Returns the main execution policy for this version entry if it exists
         *
         * @param string $name
         * @return ExecutionPolicy|null
         */
        public function getExecutionPolicy(string $name): ?ExecutionPolicy
        {
            foreach($this->execution_policies as $executionPolicy)
            {
                if($executionPolicy->getName() === $name)
                {
                    return $executionPolicy;
                }
            }

            return null;
        }

        /**
         * Adds an execution policy to the version entry
         *
         * @param ExecutionPolicy $executionPolicy
         * @return void
         */
        public function addExecutionPolicy(ExecutionPolicy $executionPolicy): void
        {
            if($this->getExecutionPolicy($executionPolicy->getName()) !== null)
            {
                return;
            }

            $this->execution_policies[] = $executionPolicy;
        }

        /**
         * Returns the path where the package is installed
         *
         * @param string $package_name
         * @return string
         */
        public function getPath(string $package_name): string
        {
            return PathFinder::getPackagesPath() . DIRECTORY_SEPARATOR . sprintf('%s=%s', $package_name, $this->getVersion());
        }

        /**
         * Returns the path where the shadow package is located
         *
         * @param string $package_name
         * @return string
         */
        public function getShadowPackagePath(string $package_name): string
        {
            return $this->getPath($package_name) . DIRECTORY_SEPARATOR . FileDescriptor::SHADOW_PACKAGE;
        }

        /**
         * Returns True if the package is broken, false otherwise
         *
         * @return bool
         */
        public function isBroken(string $package_name): bool
        {
            if(!is_file($this->getPath($package_name) . DIRECTORY_SEPARATOR . FileDescriptor::SHADOW_PACKAGE))
            {
                return true;
            }

            if(!is_file($this->getPath($package_name) . DIRECTORY_SEPARATOR . FileDescriptor::ASSEMBLY))
            {
                return true;
            }

            if(!is_file($this->getPath($package_name) . DIRECTORY_SEPARATOR . FileDescriptor::METADATA))
            {
                return true;
            }

            return false;
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

            $execution_policies = [];
            foreach($this->execution_policies as $policy)
            {
                $execution_policies[] = $policy->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('version')  : 'version')  => $this->version,
                ($bytecode ? Functions::cbc('dependencies')  : 'dependencies')  => $dependencies,
                ($bytecode ? Functions::cbc('execution_policies')  : 'execution_policies')  => $execution_policies,
                ($bytecode ? Functions::cbc('main_execution_policy')  : 'main_execution_policy')  => $this->main_execution_policy,
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
            $version = Functions::array_bc($data, 'version');

            if($version === null)
            {
                throw new ConfigurationException('VersionEntry is missing version');
            }

            $object = new self($version);
            $object->main_execution_policy = Functions::array_bc($data, 'main_execution_policy');

            $dependencies = Functions::array_bc($data, 'dependencies');
            if($dependencies !== null)
            {
                foreach($dependencies as $_datum)
                {
                    $object->dependencies[] = Dependency::fromArray($_datum);
                }
            }

            $execution_policies = Functions::array_bc($data, 'execution_policies');
            if($execution_policies !== null)
            {
                foreach($execution_policies as $_datum)
                {
                    $object->execution_policies[] = ExecutionPolicy::fromArray($_datum);
                }
            }

            return $object;
        }
    }