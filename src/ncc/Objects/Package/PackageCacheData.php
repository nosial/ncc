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
     * skip full package parsing. Cache validity is verified via a cheap stat-based
     * fingerprint (inode, size, mtime, ctime) of the package file, falling back to a
     * SHA-1 hash of the file when the fingerprint does not match.
     *
     * The object is persisted with PHP's native serialize()/unserialize(), which
     * handles private/protected properties on nested objects (Header, Assembly,
     * *Reference) without requiring toArray()/fromArray() round-trips.
     */
    class PackageCacheData
    {
        public const int CACHE_VERSION = 4;

        private int $version;
        private string $fileSha1;
        private ?string $fileFingerprint;
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
        private ?string $optimizedPath = null;
        private int $bodyEndOffset;
        /** @var PackageSignature[] */
        private array $signatures;
        private ?string $signatureError;
        private bool $signatureUnsupported;

        /**
         * Constructor method for initializing the object with file metadata, package details, and component references.
         *
         * @param string $fileSha1 The SHA-1 hash of the file.
         * @param string|null $fileFingerprint An optional unique fingerprint for the file.
         * @param int $startOffset The starting byte offset of the file.
         * @param int $endOffset The ending byte offset of the file.
         * @param string $packageVersion The version of the package.
         * @param Header $header The header metadata of the package.
         * @param Assembly $assembly The assembly associated with the file.
         * @param array $executionUnitReferences An array of execution unit references.
         * @param array $componentReferences An array of component references.
         * @param array $resourceReferences An array of resource references.
         * @param string|null $optimizedPath The optional optimized path for the file.
         * @param int|null $bodyEndOffset The end offset of the package body, defaults to the end offset.
         * @param PackageSignature[] $signatures The signatures of the package.
         * @param string|null $signatureError The error encountered while parsing the signature trailer, if any.
         * @param bool $signatureUnsupported Whether the signature trailer format is unsupported.
         *
         * @return void
         */
        public function __construct(string $fileSha1, ?string $fileFingerprint, int $startOffset, int  $endOffset, string $packageVersion, Header $header,
            Assembly $assembly, array $executionUnitReferences, array $componentReferences, array $resourceReferences, ?string $optimizedPath = null,
            ?int $bodyEndOffset = null, array $signatures = [], ?string $signatureError = null, bool $signatureUnsupported = false
        )
        {
            $this->version = self::CACHE_VERSION;
            $this->fileSha1 = $fileSha1;
            $this->fileFingerprint = $fileFingerprint;
            $this->startOffset = $startOffset;
            $this->endOffset = $endOffset;
            $this->packageVersion = $packageVersion;
            $this->header = $header;
            $this->assembly = $assembly;
            $this->executionUnitReferences = $executionUnitReferences;
            $this->componentReferences = $componentReferences;
            $this->resourceReferences = $resourceReferences;
            $this->optimizedPath = $optimizedPath;
            $this->bodyEndOffset = $bodyEndOffset ?? $endOffset;
            $this->signatures = $signatures;
            $this->signatureError = $signatureError;
            $this->signatureUnsupported = $signatureUnsupported;
        }

        /**
         * Retrieves the end offset of the package body (the data covered by signatures).
         *
         * @return int The end offset of the package body.
         */
        public function getBodyEndOffset(): int
        {
            return $this->bodyEndOffset;
        }

        /**
         * Retrieves the signatures of the package.
         *
         * @return PackageSignature[] The signatures of the package.
         */
        public function getSignatures(): array
        {
            return $this->signatures;
        }

        /**
         * Retrieves the error encountered while parsing the signature trailer.
         *
         * @return string|null The error message, or null if there was none.
         */
        public function getSignatureError(): ?string
        {
            return $this->signatureError;
        }

        /**
         * Returns whether the signature trailer format is unsupported.
         *
         * @return bool True if the signature trailer format is unsupported.
         */
        public function isSignatureUnsupported(): bool
        {
            return $this->signatureUnsupported;
        }

        /**
         * Retrieves the optimized path associated with the file, if available.
         *
         * @return string|null The optimized path, or null if not set.
         */
        public function getOptimizedPath(): ?string
        {
            return $this->optimizedPath;
        }

        /**
         * Sets the optimized path for the file.
         *
         * @param string|null $optimizedPath The optional optimized path to be set for the file.
         *
         * @return void
         */
        public function setOptimizedPath(?string $optimizedPath): void
        {
            $this->optimizedPath = $optimizedPath;
        }

        /**
         * Retrieves the current version of the object.
         *
         * @return int The version of the object.
         */
        public function getVersion(): int
        {
            return $this->version;
        }

        /**
         * Retrieves the SHA-1 hash of the file.
         *
         * @return string The SHA-1 hash of the file.
         */
        public function getFileSha1(): string
        {
            return $this->fileSha1;
        }

        /**
         * Sets the SHA-1 hash of the file.
         *
         * @param string $fileSha1 The SHA-1 hash of the file.
         * @return void
         */
        public function setFileSha1(string $fileSha1): void
        {
            $this->fileSha1 = $fileSha1;
        }

        /**
         * Retrieves the fingerprint of the file.
         *
         * @return string|null The fingerprint of the file, or null if not set.
         */
        public function getFileFingerprint(): ?string
        {
            return $this->fileFingerprint;
        }

        /**
         * Sets the fingerprint of the file.
         *
         * @param string|null $fileFingerprint The fingerprint of the file or null if not specified.
         * @return void
         */
        public function setFileFingerprint(?string $fileFingerprint): void
        {
            $this->fileFingerprint = $fileFingerprint;
        }

        /**
         * Computes a unique fingerprint for a file based on its metadata.
         *
         * @param string $filePath The path to the file for which the fingerprint is computed.
         * @return string|null The computed fingerprint as a string, or null if the file metadata could not be retrieved.
         */
        public static function computeFingerprint(string $filePath): ?string
        {
            clearstatcache(true, $filePath);
            $stat = @stat($filePath);
            if($stat === false)
            {
                return null;
            }

            return sprintf('%d:%d:%d:%d', $stat['ino'], $stat['size'], $stat['mtime'], $stat['ctime']);
        }

        /**
         * Retrieves the start offset value.
         *
         * @return int The start offset.
         */
        public function getStartOffset(): int
        {
            return $this->startOffset;
        }

        /**
         * Sets the start offset value.
         *
         * @param int $startOffset The start offset to be set.
         * @return void
         */
        public function setStartOffset(int $startOffset): void
        {
            $this->startOffset = $startOffset;
        }

        /**
         * Retrieves the end offset value.
         *
         * @return int The end offset value.
         */
        public function getEndOffset(): int
        {
            return $this->endOffset;
        }

        /**
         * Sets the end offset value.
         *
         * @param int $endOffset The end offset to be set.
         * @return void
         */
        public function setEndOffset(int $endOffset): void
        {
            $this->endOffset = $endOffset;
        }

        /**
         * Retrieves the version of the package.
         *
         * @return string The version of the package.
         */
        public function getPackageVersion(): string
        {
            return $this->packageVersion;
        }

        /**
         * Sets the version of the package.
         *
         * @param string $packageVersion The version of the package.
         * @return void
         */
        public function setPackageVersion(string $packageVersion): void
        {
            $this->packageVersion = $packageVersion;
        }

        /**
         * Retrieves the header object.
         *
         * @return Header The header object.
         */
        public function getHeader(): Header
        {
            return $this->header;
        }

        /**
         * Sets the header object.
         *
         * @param Header $header The header to be set.
         * @return void
         */
        public function setHeader(Header $header): void
        {
            $this->header = $header;
        }

        /**
         * Gets the assembly instance.
         *
         * @return Assembly The assembly instance.
         */
        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        /**
         * Sets the assembly instance.
         *
         * @param Assembly $assembly The assembly instance to set.
         * @return void
         */
        public function setAssembly(Assembly $assembly): void
        {
            $this->assembly = $assembly;
        }

        /**
         * Retrieves the references to the execution units.
         *
         * @return array An array containing the execution unit references.
         */
        public function getExecutionUnitReferences(): array
        {
            return $this->executionUnitReferences;
        }

        /**
         * Sets the execution unit references.
         *
         * @param array $executionUnitReferences An array of execution unit references.
         * @return void
         */
        public function setExecutionUnitReferences(array $executionUnitReferences): void
        {
            $this->executionUnitReferences = $executionUnitReferences;
        }

        /**
         * Retrieves the component references.
         *
         * @return array An array containing the component references.
         */
        public function getComponentReferences(): array
        {
            return $this->componentReferences;
        }

        /**
         * Sets the references for the components.
         *
         * @param array $componentReferences An array containing the component references.
         * @return void
         */
        public function setComponentReferences(array $componentReferences): void
        {
            $this->componentReferences = $componentReferences;
        }

        /**
         * Retrieves the resource references.
         *
         * @return array The array of resource references.
         */
        public function getResourceReferences(): array
        {
            return $this->resourceReferences;
        }

        /**
         * Sets the resource references.
         *
         * @param array $resourceReferences The array of resource references to be set.
         * @return void
         */
        public function setResourceReferences(array $resourceReferences): void
        {
            $this->resourceReferences = $resourceReferences;
        }
    }
