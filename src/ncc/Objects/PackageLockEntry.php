<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Objects;

    use InvalidArgumentException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\Package\DependencyReference;

    class PackageLockEntry implements SerializableInterface
    {
        private string $package;
        private string $version;
        /**
         * @var DependencyReference[]
         */
        private array $dependencies;

        /**
         * PackageLockEntry constructor.
         *
         * @param array $data The array representation of the package lock entry
         */
        public function __construct(array $data)
        {
            $this->package = $data['package'] ?? throw new InvalidArgumentException('Package name is required');
            $this->version = $data['version'] ?? throw new InvalidArgumentException('Package version is required');
            $this->dependencies = array_map(function($item) {if(!($item instanceof DependencyReference)) {
                return DependencyReference::fromArray($item);} else {return $item;}}, $data['dependencies'] ?? []
            );
        }

        /**
         * Returns the package name in the entry
         *
         * @return string The name of the package in a standard package name format
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * Returns the version of the package
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        public function getDependencies(): array
        {
            return $this->dependencies;
        }

        public function toArray(): array
        {
            return [
                'package' => $this->package,
                'version' => $this->version,
                'dependencies' => array_map(function($item) { return $item->toArray(); }, $this->dependencies)
            ];
        }

        public static function fromArray(array $data): PackageLockEntry
        {
            return new self($data);
        }

        public function __toString()
        {
            return $this->package . '=' . $this->version;
        }
    }