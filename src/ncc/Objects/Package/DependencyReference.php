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

    use ncc\Interfaces\SerializableInterface;
    use ncc\Objects\PackageSource;
    use ncc\Objects\RepositoryConfiguration;

    class DependencyReference implements SerializableInterface
    {
        private string $package;
        private string $version;
        private bool $static;
        private ?PackageSource $source;
        private ?RepositoryConfiguration $repository;

        /**
         * Public constructor for the dependency reference
         *
         * @param PackageSource|string $source The source string of the dependency
         * @param bool $static True if the dependency is statically included in the build, False if dynamically linked
         */
        public function __construct(string $package, string $version, bool $static, PackageSource|string|null $source=null, ?RepositoryConfiguration $repository=null)
        {
            if(is_string($source))
            {
                $source = new PackageSource($source);
            }

            $this->package = $package;
            $this->version = $version;
            $this->static = $static;
            $this->source = $source;
            $this->repository = $repository;
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
         * Returns True if the dependency is statically included in the build, False if dynamically linked
         *
         * @return bool True if static, False otherwise.
         */
        public function isStatic(): bool
        {
            return $this->static;
        }

        /**
         * Returns the source of the dependency
         *
         * @return PackageSource The package source of the dependency
         */
        public function getSource(): PackageSource
        {
            return $this->source;
        }

        /**
         * Returns the repository configuration of the dependency, or null if none is set
         *
         * @return RepositoryConfiguration|null The repository configuration
         */
        public function getRepository(): ?RepositoryConfiguration
        {
            return $this->repository;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'package' => $this->package,
                'version' => $this->version,
                'static' => $this->static,
                'source' => (string)$this->source ?? null,
                'repository' => $this->repository?->toArray() ?? null
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): DependencyReference
        {
            $repository = null;
            if(isset($data['repository']) && is_array($data['repository']))
            {
                $repository = RepositoryConfiguration::fromArray($data['repository']);
            }

            return new self($data['package'], $data['version'], $data['static'] ?? false, $data['source'] ?? null, $repository);
        }

        public function __toString(): string
        {
            return (string)$this->source;
        }
    }