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

    namespace ncc\Objects\PackageLock;

    use InvalidArgumentException;

    class LockEntry
    {
        private string $package;
        private string $version;
        private array $dependencies;

        public function __construct(array $data)
        {
            $this->package = $data['package'] ?? throw new InvalidArgumentException('Lock entry must have a package name');
            $this->version = $data['version'] ?? throw new InvalidArgumentException('Lock entry must have a version');
            $this->dependencies = $data['dependencies'] ?? [];
        }

        public function getPackage(): string
        {
            return $this->package;
        }

        public function getVersion(): string
        {
            return $this->version;
        }

        public function getDependencies(): array
        {
            return $this->dependencies;
        }
    }