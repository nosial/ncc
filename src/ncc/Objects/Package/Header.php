<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\Objects\Package;

    use InvalidArgumentException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\PackageSource;
    use Random\RandomException;
    use RuntimeException;

    class Header implements SerializableInterface
    {
        private array $flags;
        private bool $compressed;
        private string $buildNumber;
        private ?string $entryPoint;
        /** @var string|string[]|null  */
        private string|array|null $preInstall;
        /** @var string|string[]|null  */
        private string|array|null $postInstall;
        private ?array $definedConstants;
        /**
         * @var DependencyReference[]|null
         */
        private ?array $dependencyReferences;

        /**
         * Public constructor for the package header
         *
         * @param array $data The data to initialize the header with
         */
        public function __construct(array $data=[])
        {
            $this->flags = $data['flags'] ?? [];

            try
            {
                $this->buildNumber = $data['build_number'] ?? random_bytes(8);
            }
            catch (RandomException $e)
            {
                throw new RuntimeException('No build number for the header was provided and the fallback to generate a random build number has failed', $e->getCode(), $e);
            }

            $this->entryPoint = $data['entry_point'] ?? null;
            $this->preInstall = $data['pre_install'] ?? null;
            $this->compressed = $data['compressed'] ?? false;
            $this->postInstall = $data['post_install'] ?? null;
            $this->definedConstants = $data['defined_constants'] ?? null;
            if(isset($data['dependencies']) && is_array($data['dependencies']) && !empty($data['dependencies']))
            {
                $this->dependencyReferences = array_map(function($item){
                    return $item !== null ? DependencyReference::fromArray($item) : null; 
                }, $data['dependencies']);
                // Filter out null values
                $this->dependencyReferences = array_filter($this->dependencyReferences);
            }
            else
            {
                $this->dependencyReferences = [];
            }
        }

        /**
         * Returns the flags of the package
         *
         * @return string[] An array of single-item array flags associated with the package
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Returns True if the given flag exists, False otherwise.
         *
         * @param string $flag The flag to chekc
         * @return bool True if the flag exists, False otherwise
         */
        public function flagExists(string $flag): bool
        {
            return in_array($flag, $this->flags, true);
        }

        /**
         * Adds a flag to the header
         *
         * @param string $flag The name of the flag to add
         */
        public function addFlag(string $flag): void
        {
            if(!$this->flagExists($flag))
            {
                $this->flags[] = $flag;
            }
        }

        /**
         * Removes an existing flag from the header
         *
         * @param string $flag The flag to remove from the header
         */
        public function removeFlag(string $flag): void
        {
            $this->flags = array_filter($this->flags, fn($f) => $f !== $flag);
        }

        /**
         * Sets the flags property to the header
         *
         * @param string[] $flags The array of flags to set as a property
         */
        public function setFlags(array $flags): void
        {
            $this->flags = $flags;
        }

        /**
         * Clears the flags from the header
         *
         * @return void
         */
        public function clearFlags(): void
        {
            $this->flags = [];
        }

        /**
         * Returns True if the package is compressed, False otherwise
         *
         * @return bool True if the package is compressed, False otherwise
         */
        public function isCompressed(): bool
        {
            return $this->compressed;
        }

        public function setCompressed(bool $compressed): void
        {
            $this->compressed = $compressed;
        }

        /**
         * Returns the build number of the package
         *
         * @return string The build number of the package
         */
        public function getBuildNumber(): string
        {
            return $this->buildNumber;
        }

        /**
         * Sets the build number of the package
         *
         * @param string $buildNumber The build number of the package
         * @return void
         */
        public function setBuildNumber(string $buildNumber): void
        {
            if(strlen($buildNumber) > 8)
            {
                throw new InvalidArgumentException('The build number cannot exceed 8 bytes');
            }

            $this->buildNumber = $buildNumber;
        }

        /**
         * Gets the main entry point of the package
         *
         * @return string|null The main entry point of the package, as the execution unit's name, null if there is no entry point
         */
        public function getEntryPoint(): ?string
        {
            return $this->entryPoint;
        }

        /**
         * Sets the entry point to the package, accepts the name of an execution unit
         *
         * @param ?string $entryPoint The name of the execution unit to set as the entry point of the package, null if there is no entry point
         * @return void
         */
        public function setEntryPoint(?string $entryPoint): void
        {
            $this->entryPoint = $entryPoint;
        }

        /**
         * Returns the pre-install execution units
         *
         * @return string|array|null The names of the execution units to execute pre-intsalling the package
         */
        public function getPreInstall(): string|array|null
        {
            return $this->preInstall;
        }

        public function setPreInstall(string|array|null $preInstall): void
        {
            $this->preInstall = $preInstall;
        }

        public function getPostInstall(): string|array|null
        {
            return $this->postInstall;
        }

        public function setPostInstall(string|array|null $postInstall): void
        {
            $this->postInstall = $postInstall;
        }

        public function getDefinedConstants(): array
        {
            return $this->definedConstants;
        }

        public function setDefinedConstants(array $constants): void
        {
            $this->definedConstants = $constants;
        }

        public function getDependencyReferences(): array
        {
            return $this->dependencyReferences;
        }

        public function setDependencyReferences(array $data): void
        {
            foreach($data as $item)
            {
                if(!($item instanceof DependencyReference))
                {
                    throw new InvalidArgumentException('One or more items in the array is not a valid DependencyReference object');
                }
            }

            $this->dependencyReferences = $data;
        }

        public function addDependencyReference(PackageSource|string $source, bool $static): void
        {
            if($this->dependencyReferenceExists($source))
            {
                throw new InvalidArgumentException('The dependency reference already exists in the header');
            }

            $this->dependencyReferences[] = new DependencyReference($source, $static);
        }

        public function dependencyReferenceExists(PackageSource|string $source): bool
        {
            foreach($this->dependencyReferences as $reference)
            {
                if($reference->getSource() === $source)
                {
                    return true;
                }
            }

            return false;
        }

        public function toArray(): array
        {
            return [
                'flags' => $this->flags,
                'compressed' => $this->compressed,
                'build_number' => $this->buildNumber,
                'entry_point' => $this->entryPoint,
                'pre_install' => $this->preInstall,
                'post_install' => $this->postInstall,
                'dependencies' => $this->dependencyReferences ? array_map(function($item){ return $item->toArray(); }, $this->dependencyReferences) : null,
            ];
        }

        public static function fromArray(array $data): Header
        {
            return new Header($data);
        }
    }