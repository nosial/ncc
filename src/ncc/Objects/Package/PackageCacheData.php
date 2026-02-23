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

    use ncc\Objects\Project\Assembly;

    /**
     * Serializable cache data object for a parsed NCC package.
     *
     * Stores all metadata extracted by PackageReader so that subsequent loads can
     * skip full package parsing. Cache validity is verified via a SHA-1 hash of the
     * original package file, which is more reliable than mtime/size checks.
     *
     * The object is persisted with PHP's native serialize()/unserialize(), which
     * handles private/protected properties on nested objects (Header, Assembly,
     * *Reference) without requiring toArray()/fromArray() round-trips.
     */
    class PackageCacheData
    {
        /** Cache format version – bump when the structure changes to force re-generation. */
        public const CACHE_VERSION = 2;

        private int $version;
        /** SHA-1 hex digest of the package file at cache-creation time. */
        private string $fileSha1;
        private int $startOffset;
        private int $endOffset;
        private string $packageVersion;
        private Header $header;
        private Assembly $assembly;
        /** @var array<string, ExecutionUnitReference> */
        private array $executionUnitReferences;
        /** @var array<string, ComponentReference> */
        private array $componentReferences;
        /** @var array<string, ResourceReference> */
        private array $resourceReferences;

        public function __construct(
            string   $fileSha1,
            int      $startOffset,
            int      $endOffset,
            string   $packageVersion,
            Header   $header,
            Assembly $assembly,
            array    $executionUnitReferences,
            array    $componentReferences,
            array    $resourceReferences
        )
        {
            $this->version                  = self::CACHE_VERSION;
            $this->fileSha1                 = $fileSha1;
            $this->startOffset              = $startOffset;
            $this->endOffset                = $endOffset;
            $this->packageVersion           = $packageVersion;
            $this->header                   = $header;
            $this->assembly                 = $assembly;
            $this->executionUnitReferences  = $executionUnitReferences;
            $this->componentReferences      = $componentReferences;
            $this->resourceReferences       = $resourceReferences;
        }

        public function getVersion(): int
        {
            return $this->version;
        }

        public function getFileSha1(): string
        {
            return $this->fileSha1;
        }

        public function setFileSha1(string $fileSha1): void
        {
            $this->fileSha1 = $fileSha1;
        }

        public function getStartOffset(): int
        {
            return $this->startOffset;
        }

        public function setStartOffset(int $startOffset): void
        {
            $this->startOffset = $startOffset;
        }

        public function getEndOffset(): int
        {
            return $this->endOffset;
        }

        public function setEndOffset(int $endOffset): void
        {
            $this->endOffset = $endOffset;
        }

        public function getPackageVersion(): string
        {
            return $this->packageVersion;
        }

        public function setPackageVersion(string $packageVersion): void
        {
            $this->packageVersion = $packageVersion;
        }

        public function getHeader(): Header
        {
            return $this->header;
        }

        public function setHeader(Header $header): void
        {
            $this->header = $header;
        }

        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        public function setAssembly(Assembly $assembly): void
        {
            $this->assembly = $assembly;
        }

        /**
         * @return array<string, ExecutionUnitReference>
         */
        public function getExecutionUnitReferences(): array
        {
            return $this->executionUnitReferences;
        }

        /**
         * @param array<string, ExecutionUnitReference> $executionUnitReferences
         */
        public function setExecutionUnitReferences(array $executionUnitReferences): void
        {
            $this->executionUnitReferences = $executionUnitReferences;
        }

        /**
         * @return array<string, ComponentReference>
         */
        public function getComponentReferences(): array
        {
            return $this->componentReferences;
        }

        /**
         * @param array<string, ComponentReference> $componentReferences
         */
        public function setComponentReferences(array $componentReferences): void
        {
            $this->componentReferences = $componentReferences;
        }

        /**
         * @return array<string, ResourceReference>
         */
        public function getResourceReferences(): array
        {
            return $this->resourceReferences;
        }

        /**
         * @param array<string, ResourceReference> $resourceReferences
         */
        public function setResourceReferences(array $resourceReferences): void
        {
            $this->resourceReferences = $resourceReferences;
        }
    }
