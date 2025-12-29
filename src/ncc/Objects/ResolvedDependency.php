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

    namespace ncc\Objects;

    use ncc\Classes\Logger;
    use ncc\Classes\PackageReader;
    use ncc\Runtime;

    class ResolvedDependency
    {
        private string $package;
        private PackageSource $updateSource;
        private ?PackageReader $reader;

        public function __construct(string $package, PackageSource $updateSource)
        {
            $this->package = $package;
            $this->updateSource = $updateSource;
            $version = $updateSource->getVersion() ?? 'latest';
            Logger::getLogger()->debug(sprintf('ResolvedDependency: Looking up package=%s, version=%s', $package, $version), 'ResolvedDependency');
            $entry = Runtime::getPackageEntry($package, $version);
            if($entry === null)
            {
                Logger::getLogger()->debug(sprintf('ResolvedDependency: Package entry not found for %s version %s', $package, $version), 'ResolvedDependency');
                $this->reader = null;
            }
            else
            {
                Logger::getLogger()->debug(sprintf('ResolvedDependency: Package entry found, path=%s', Runtime::getPackagePath($package, $version)), 'ResolvedDependency');
                $this->reader = new PackageReader(Runtime::getPackagePath($package, $version));
            }
        }

        public function getPackage(): string
        {
            return $this->package;
        }

        public function getPackageSource(): PackageSource
        {
            return $this->updateSource;
        }

        public function getPackageReader(): ?PackageReader
        {
            return $this->reader;
        }
     }