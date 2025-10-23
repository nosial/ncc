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

    class DependencyReference implements SerializableInterface
    {
        private PackageSource $source;
        private bool $static;

        /**
         * Public constructor for the dependency reference
         *
         * @param PackageSource|string $source The source string of the dependency
         * @param bool $static True if the dependency is statically included in the build, False if dynamically linked
         */
        public function __construct(PackageSource|string $source, bool $static)
        {
            if(is_string($source))
            {
                $source = new PackageSource($source);
            }

            $this->source = $source;
            $this->static = $static;
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
         * Returns True if the dependency is statically included in the build, False if dynamically linked
         *
         * @return bool True if static, False otherwise.
         */
        public function isStatic(): bool
        {
            return $this->static;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'source' => (string)$this->source,
                'static' => $this->static
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): DependencyReference
        {
            return new self($data['source'] ?? '', $data['static'] ?? false);
        }

        public function __toString(): string
        {
            return (string)$this->source;
        }
    }