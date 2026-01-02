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

    namespace ncc\Objects\Package;

    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\PackageSource;

    class DependencyReference implements SerializableInterface
    {
        private string $package;
        private string $version;
        private ?PackageSource $source;

        /**
         * Public constructor for the dependency reference
         *
         * @param string $package The package name of the dependency
         * @param string $version The version constraint of the dependency
         * @param PackageSource|string|null $source The source string of the dependency
         */
        public function __construct(string $package, string $version, PackageSource|string|null $source=null)
        {
            if(is_string($source))
            {
                $source = new PackageSource($source);
            }

            $this->package = $package;
            $this->version = $version;
            $this->source = $source;
        }

        /**
         * Returns the package name of the dependency
         *
         * @return string The package name
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * Returns the version constraint of the dependency
         *
         * @return string The version constraint
         */
        public function getVersion(): string
        {
            return $this->version;
        }


        /**
         * Returns the source of the dependency
         *
         * @return PackageSource|null The package source of the dependency
         */
        public function getSource(): ?PackageSource
        {
            return $this->source;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'package' => $this->package,
                'version' => $this->version,
                'source' => $this->source !== null ? (string)$this->source : null,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): DependencyReference
        {
            return new self($data['package'], $data['version'], $data['source'] ?? null);
        }

        public function __toString(): string
        {
            return $this->source !== null ? (string)$this->source : '';
        }
    }