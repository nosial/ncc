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

    namespace ncc\Classes;

    use Exception;
    use ncc\Interfaces\ReferenceInterface;
    use ncc\Libraries\fslib\IO;
    use ncc\Runtime;

    /**
     * StreamWrapper for accessing imported package contents via the ncc:// protocol.
     *
     * This wrapper allows reading files from NCC packages using URLs in the format:
     * ncc://package.name/path/to/resource
     * ncc://package.name=1.0.0/path/to/resource (with specific version)
     *
     * Packages are automatically imported from the package manager if not already loaded.
     * Once imported with a version, the package is always accessible via its name:
     * ncc://package.name/path/to/resource (regardless of imported version)
     *
     * Special package-only imports (no resource path):
     * ncc://package.name - imports latest version, returns empty content
     * ncc://package.name=1.0.0 - imports specific version, returns empty content
     *
     * Example usage:
     * ```php
     * StreamWrapper::register();
     * 
     * // Auto-imports latest version from package manager
     * $content = file_get_contents('ncc://net.nosial.configlib/ConfigLib/Configuration.php');
     * 
     * // Auto-imports specific version
     * require 'ncc://com.example.package=1.0.0/Main.php';
     * 
     * // Package-only import (imports but returns empty content)
     * require 'ncc://com.example.helpers'; // imports latest
     * require 'ncc://com.example.helpers=2.1.5'; // imports v2.1.5
     * 
     * // After import, always accessible without version
     * $data = file_get_contents('ncc://com.example.package/data.txt');
     * ```
     *
     * The implementation is designed to have a small memory footprint by reading data
     * in chunks rather than loading entire files into memory.
     */
    class StreamWrapper
    {
        private const PROTOCOL = 'ncc';
        private const READ_BUFFER_SIZE = 8192; // 8KB chunks
        private ?PackageReader $packageReader = null;
        private ?ReferenceInterface $reference = null;
        /** @var int Current read position within the resource */
        private int $position = 0;
        /** @var string|null Cached data for the current resource */
        private ?string $data = null;
        /** @var resource|null Context passed to stream operations */
        public $context;
        /** @var array|null List of directory entries for directory operations */
        private ?array $dirEntries = null;
        /** @var int Current position in directory listing */
        private int $dirPosition = 0;
        /** @var array<string, string> Cache of extracted phar files (ncc path => filesystem path) */
        private static array $pharCache = [];
        /** @var resource|null File handle for proxied phar file */
        private $pharHandle = null;
        /** @var bool Whether this stream is proxying to a real phar file */
        private bool $isPharProxy = false;

        /**
         * Registers the ncc:// stream wrapper.
         *
         * @return bool True on success, false if already registered.
         */
        public static function register(): bool
        {
            if (in_array(self::PROTOCOL, stream_get_wrappers(), true))
            {
                return false;
            }

            return stream_wrapper_register(self::PROTOCOL, self::class);
        }

        /**
         * Unregisters the ncc:// stream wrapper.
         *
         * @return bool True on success, false if not registered.
         */
        public static function unregister(): bool
        {
            if (!in_array(self::PROTOCOL, stream_get_wrappers(), true))
            {
                return false;
            }

            return stream_wrapper_unregister(self::PROTOCOL);
        }

        /**
         * Opens a stream for reading from an NCC package.
         *
         * @param string $path The path in format: ncc://package.name/resource/path
         * @param string $mode The mode (only 'r' and 'rb' are supported)
         * @param int $options Stream options
         * @param string|null $opened_path The full path that was actually opened
         * @return bool True on success, false on failure
         */
        public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
        {
            // Only support read mode
            if (!in_array($mode, ['r', 'rb'], true))
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Mode '$mode' is not supported. Only 'r' and 'rb' are allowed.", E_USER_WARNING);
                }
                return false;
            }

            // Parse the URL
            $parsed = $this->parseUrl($path);
            if ($parsed === null)
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Invalid ncc:// URL format: $path", E_USER_WARNING);
                }
                return false;
            }

            [$packageName, $resourcePath, $isPackageOnly] = $parsed;

            // Get the package reader from Runtime
            if (!Runtime::isImported($packageName))
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Package not imported: $packageName", E_USER_WARNING);
                }
                return false;
            }

            $packages = Runtime::getImportedPackages();
            $this->packageReader = $packages[$packageName];

            // Handle package-only reference (e.g., ncc://package.name)
            if ($isPackageOnly)
            {
                // Return empty content for package-only references
                $this->reference = null;
                $this->position = 0;
                $this->data = ''; // Empty PHP file content
                $opened_path = $path;
                return true;
            }

            // Find the resource reference
            $this->reference = $this->packageReader->find($resourcePath);
            if ($this->reference === null)
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Resource not found: $resourcePath in package $packageName", E_USER_WARNING);
                }
                return false;
            }

            // Special handling for .phar files - extract to temp location
            if (str_ends_with(strtolower($resourcePath), '.phar'))
            {
                $tempPath = $this->extractPharToTemp($path, $resourcePath);
                if ($tempPath === null)
                {
                    if ($options & STREAM_REPORT_ERRORS)
                    {
                        trigger_error("Failed to extract phar file: $resourcePath", E_USER_WARNING);
                    }
                    return false;
                }
                // Open the temporary file for reading
                $this->pharHandle = fopen($tempPath, $mode);
                if ($this->pharHandle === false)
                {
                    if ($options & STREAM_REPORT_ERRORS)
                    {
                        trigger_error("Failed to open extracted phar file: $tempPath", E_USER_WARNING);
                    }
                    return false;
                }
                // Mark this as a phar proxy
                $this->isPharProxy = true;
                $this->reference = null;
                $this->position = 0;
                $opened_path = $tempPath;
                return true;
            }

            // Initialize position and clear data cache
            $this->position = 0;
            $this->data = null;

            $opened_path = $path;
            return true;
        }

        /**
         * Reads data from the stream.
         *
         * @param int $count Maximum number of bytes to read
         * @return string The data read, or empty string on EOF
         */
        public function stream_read(int $count): string
        {
            // If proxying to a phar file, read from the temp file handle
            if ($this->isPharProxy && $this->pharHandle !== null)
            {
                $data = fread($this->pharHandle, $count);
                return $data !== false ? $data : '';
            }

            // Lazy load the data only when needed
            if ($this->data === null)
            {
                // Package-only reference (no resource path)
                if ($this->reference === null)
                {
                    $this->data = '';
                }
                else
                {
                    $this->data = $this->packageReader->read($this->reference);
                    // If it's an ExecutionUnit, it will return an object, convert to string
                    if (!is_string($this->data))
                    {
                        $this->data = '';
                    }
                }
            }

            $dataLength = strlen($this->data);

            // Check if we're at EOF
            if ($this->position >= $dataLength)
            {
                return '';
            }

            // Calculate how much to read
            $remaining = $dataLength - $this->position;
            $toRead = min($count, $remaining);

            // Read the chunk
            $chunk = substr($this->data, $this->position, $toRead);
            $this->position += $toRead;

            return $chunk;
        }

        /**
         * Returns the current position in the stream.
         *
         * @return int The current position
         */
        public function stream_tell(): int
        {
            if ($this->isPharProxy && $this->pharHandle !== null)
            {
                $pos = ftell($this->pharHandle);
                return $pos !== false ? $pos : 0;
            }
            return $this->position;
        }

        /**
         * Checks if the stream has reached EOF.
         *
         * @return bool True if at EOF, false otherwise
         */
        public function stream_eof(): bool
        {
            if ($this->isPharProxy && $this->pharHandle !== null)
            {
                return feof($this->pharHandle);
            }

            // We need to load data to know the size
            if ($this->data === null)
            {
                // Package-only reference (no resource path)
                if ($this->reference === null)
                {
                    $this->data = '';
                }
                else
                {
                    $this->data = $this->packageReader->read($this->reference);
                    if (!is_string($this->data))
                    {
                        $this->data = '';
                    }
                }
            }

            return $this->position >= strlen($this->data);
        }

        /**
         * Seeks to a specific position in the stream.
         *
         * @param int $offset The offset
         * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
         * @return bool True on success, false on failure
         */
        public function stream_seek(int $offset, int $whence = SEEK_SET): bool
        {
            if ($this->isPharProxy && $this->pharHandle !== null)
            {
                return fseek($this->pharHandle, $offset, $whence) === 0;
            }

            // Load data if not already loaded
            if ($this->data === null)
            {
                // Package-only reference (no resource path)
                if ($this->reference === null)
                {
                    $this->data = '';
                }
                else
                {
                    $this->data = $this->packageReader->read($this->reference);
                    if (!is_string($this->data))
                    {
                        $this->data = '';
                    }
                }
            }

            $dataLength = strlen($this->data);

            // Calculate new position
            $newPosition = match($whence)
            {
                SEEK_SET => $offset,
                SEEK_CUR => $this->position + $offset,
                SEEK_END => $dataLength + $offset,
                default => false,
            };

            if ($newPosition === false || $newPosition < 0 || $newPosition > $dataLength)
            {
                return false;
            }

            $this->position = $newPosition;
            return true;
        }

        /**
         * Gets statistics about the stream.
         *
         * @return array|false Statistics array or false on failure
         */
        public function stream_stat(): array|false
        {
            if ($this->isPharProxy && $this->pharHandle !== null)
            {
                return fstat($this->pharHandle);
            }

            // Handle package-only reference (no resource path)
            $size = 0;
            if ($this->reference !== null)
            {
                // If the package is compressed, we need the decompressed size for PHP's require/include
                // Otherwise, the stored size is accurate and we can avoid decompression overhead
                if ($this->packageReader->getHeader()->isCompressed())
                {
                    if ($this->data === null)
                    {
                        // Load data to get accurate decompressed size
                        $this->data = $this->packageReader->read($this->reference);
                        if (!is_string($this->data))
                        {
                            $this->data = '';
                        }
                    }
                    $size = strlen($this->data);
                }
                else
                {
                    // Not compressed, stored size is accurate
                    $size = $this->reference->getSize();
                }
            }
            elseif ($this->data !== null)
            {
                $size = strlen($this->data);
            }

            // Return minimal stat array
            return [
                'dev' => 0,
                'ino' => 0,
                'mode' => 0100444, // Regular file, read-only
                'nlink' => 1,
                'uid' => 0,
                'gid' => 0,
                'rdev' => 0,
                'size' => $size,
                'atime' => 0,
                'mtime' => 0,
                'ctime' => 0,
                'blksize' => self::READ_BUFFER_SIZE,
                'blocks' => ceil($size / 512),
            ];
        }

        /**
         * Gets statistics about a URL path.
         *
         * @param string $path The path in format: ncc://package.name/resource/path
         * @param int $flags Stream stat flags
         * @return array|false Statistics array or false on failure
         */
        public function url_stat(string $path, int $flags): array|false
        {
            // Check if this is a cached phar file
            if (isset(self::$pharCache[$path]) && IO::exists(self::$pharCache[$path]))
            {
                return stat(self::$pharCache[$path]);
            }

            // Parse the URL
            $parsed = $this->parseUrl($path);
            if ($parsed === null)
            {
                return false;
            }

            [$packageName, $resourcePath, $isPackageOnly] = $parsed;

            // Get the package reader from Runtime
            if (!Runtime::isImported($packageName))
            {
                return false;
            }

            $packages = Runtime::getImportedPackages();
            $packageReader = $packages[$packageName];

            // Handle package-only reference
            $size = 0;
            if ($isPackageOnly)
            {
                $size = 0; // Empty content for package-only reference
            }
            else
            {
                // Find the resource reference
                $reference = $packageReader->find($resourcePath);
                if ($reference === null)
                {
                    return false;
                }
                // If compressed, report decompressed size for PHP's require/include to work correctly
                // Otherwise use stored size to avoid unnecessary decompression overhead
                if ($packageReader->getHeader()->isCompressed())
                {
                    $data = $packageReader->read($reference);
                    $size = is_string($data) ? strlen($data) : 0;
                }
                else
                {
                    $size = $reference->getSize();
                }
            }

            // Return minimal stat array
            return [
                'dev' => 0,
                'ino' => 0,
                'mode' => 0100444, // Regular file, read-only
                'nlink' => 1,
                'uid' => 0,
                'gid' => 0,
                'rdev' => 0,
                'size' => $size,
                'atime' => 0,
                'mtime' => 0,
                'ctime' => 0,
                'blksize' => self::READ_BUFFER_SIZE,
                'blocks' => ceil($size / 512),
            ];
        }

        /**
         * Closes the stream and releases resources.
         *
         * @return void
         */
        public function stream_close(): void
        {
            if ($this->pharHandle !== null)
            {
                fclose($this->pharHandle);
                $this->pharHandle = null;
            }
            $this->isPharProxy = false;
            $this->packageReader = null;
            $this->reference = null;
            $this->position = 0;
            $this->data = null;
        }

        /**
         * Sets options on the stream.
         *
         * @param int $option The option to set (STREAM_OPTION_BLOCKING, STREAM_OPTION_READ_TIMEOUT, etc.)
         * @param int $arg1 First argument
         * @param int $arg2 Second argument
         * @return bool True on success, false on failure
         */
        public function stream_set_option(int $option, int $arg1, int $arg2): bool
        {
            // For read-only streams, we don't support setting options
            // Return false to indicate the operation is not supported
            return false;
        }

        /**
         * Flushes the output.
         *
         * @return bool Always returns true for read-only streams
         */
        public function stream_flush(): bool
        {
            // Read-only streams don't need flushing
            return true;
        }

        /**
         * Advisory file locking.
         *
         * @param int $operation LOCK_SH, LOCK_EX, LOCK_UN, LOCK_NB
         * @return bool Always returns false (locking not supported for read-only package contents)
         */
        public function stream_lock(int $operation): bool
        {
            // Locking not supported for read-only package resources
            return false;
        }

        /**
         * Truncate stream.
         *
         * @param int $new_size The new size
         * @return bool Always returns false (truncation not supported for read-only streams)
         */
        public function stream_truncate(int $new_size): bool
        {
            // Truncation not supported for read-only package resources
            return false;
        }

        /**
         * Write to stream.
         *
         * @param string $data The data to write
         * @return int Always returns 0 (writing not supported for read-only streams)
         */
        public function stream_write(string $data): int
        {
            // Writing not supported for read-only package resources
            return 0;
        }

        /**
         * Retrieve the underlying resource.
         *
         * @param int $cast_as STREAM_CAST_FOR_SELECT or STREAM_CAST_AS_STREAM
         * @return resource|false Always returns false (no underlying resource for package streams)
         */
        public function stream_cast(int $cast_as): false
        {
            // No underlying resource to cast
            return false;
        }

        /**
         * Change stream metadata.
         *
         * @param string $path The file path
         * @param int $option One of the STREAM_META_* constants
         * @param mixed $value The value for the option
         * @return bool Always returns false (metadata changes not supported)
         */
        public function stream_metadata(string $path, int $option, mixed $value): bool
        {
            // Metadata changes not supported for read-only package resources
            return false;
        }

        /**
         * Create a directory.
         *
         * @param string $path Directory path
         * @param int $mode Permissions
         * @param int $options Flags
         * @return bool Always returns false (directory creation not supported)
         */
        public function mkdir(string $path, int $mode, int $options): bool
        {
            // Directory operations not supported for read-only packages
            return false;
        }

        /**
         * Remove a directory.
         *
         * @param string $path Directory path
         * @param int $options Flags
         * @return bool Always returns false (directory removal not supported)
         */
        public function rmdir(string $path, int $options): bool
        {
            // Directory operations not supported for read-only packages
            return false;
        }

        /**
         * Rename a file or directory.
         *
         * @param string $path_from The current path
         * @param string $path_to The new path
         * @return bool Always returns false (renaming not supported)
         */
        public function rename(string $path_from, string $path_to): bool
        {
            // Rename not supported for read-only packages
            return false;
        }

        /**
         * Delete a file.
         *
         * @param string $path The file path
         * @return bool Always returns false (file deletion not supported)
         */
        public function unlink(string $path): bool
        {
            // File deletion not supported for read-only packages
            return false;
        }

        /**
         * Open directory handle for listing contents within a package path.
         *
         * @param string $path The directory path in format: ncc://package.name/path/to/dir
         * @param int $options Flags
         * @return bool True on success, false on failure
         */
        public function dir_opendir(string $path, int $options): bool
        {
            // Parse the URL
            $parsed = $this->parseUrl($path);
            if ($parsed === null)
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Invalid ncc:// URL format: $path", E_USER_WARNING);
                }
                return false;
            }

            [$packageName, $resourcePath, $isPackageOnly] = $parsed;

            // Get the package reader from Runtime
            if (!Runtime::isImported($packageName))
            {
                if ($options & STREAM_REPORT_ERRORS)
                {
                    trigger_error("Package not imported: $packageName", E_USER_WARNING);
                }
                return false;
            }

            $packages = Runtime::getImportedPackages();
            $packageReader = $packages[$packageName];

            // Normalize the resource path (remove trailing slashes)
            $resourcePath = rtrim($resourcePath, '/');
            $searchPrefix = $resourcePath === '' ? '' : $resourcePath . '/';

            // Get all references and filter those that match the directory path
            $allReferences = $packageReader->getAllReferences();
            $entries = [];
            $seenDirs = [];

            foreach ($allReferences as $reference)
            {
                $refName = $reference->getName();

                // Check if this reference is under the requested path
                if ($resourcePath === '' || str_starts_with($refName, $searchPrefix))
                {
                    // Get the relative path from the search prefix
                    $relativePath = $resourcePath === '' ? $refName : substr($refName, strlen($searchPrefix));

                    // Check if this is a direct child or in a subdirectory
                    $slashPos = strpos($relativePath, '/');
                    if ($slashPos === false)
                    {
                        // Direct file in this directory
                        $entries[] = $relativePath;
                    }
                    else
                    {
                        // File in subdirectory - add the subdirectory name only once
                        $dirName = substr($relativePath, 0, $slashPos);
                        if (!isset($seenDirs[$dirName]))
                        {
                            $entries[] = $dirName . '/';
                            $seenDirs[$dirName] = true;
                        }
                    }
                }
            }

            // Store the entries and reset position
            $this->dirEntries = $entries;
            $this->dirPosition = 0;

            return true;
        }

        /**
         * Read entry from directory handle.
         *
         * @return string|false The next entry name, or false if no more entries
         */
        public function dir_readdir(): string|false
        {
            if ($this->dirEntries === null || $this->dirPosition >= count($this->dirEntries))
            {
                return false;
            }

            $entry = $this->dirEntries[$this->dirPosition];
            $this->dirPosition++;
            return $entry;
        }

        /**
         * Rewind directory handle.
         *
         * @return bool True on success, false on failure
         */
        public function dir_rewinddir(): bool
        {
            if ($this->dirEntries === null)
            {
                return false;
            }

            $this->dirPosition = 0;
            return true;
        }

        /**
         * Close directory handle.
         *
         * @return bool Always returns true
         */
        public function dir_closedir(): bool
        {
            $this->dirEntries = null;
            $this->dirPosition = 0;
            return true;
        }

        /**
         * Extracts a .phar file from an NCC package to a temporary location.
         * PHP's Phar extension requires real filesystem paths, so we extract
         * .phar files to temp storage and return the filesystem path.
         *
         * @param string $nccPath The full ncc:// URL (for caching)
         * @param string $resourcePath The resource path within the package
         * @return string|null The temporary filesystem path, or null on failure
         */
        private function extractPharToTemp(string $nccPath, string $resourcePath): ?string
        {
            // Check if already extracted
            if (isset(self::$pharCache[$nccPath]))
            {
                if (IO::exists(self::$pharCache[$nccPath]))
                {
                    return self::$pharCache[$nccPath];
                }
                // Cache entry exists but file doesn't, remove it
                unset(self::$pharCache[$nccPath]);
            }

            try
            {
                // Read the phar data from the package
                $pharData = $this->packageReader->read($this->reference);
                if (!is_string($pharData))
                {
                    return null;
                }

                // Create temp directory if it doesn't exist
                $tempDir = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . 'phars';
                if (!IO::isDirectory($tempDir))
                {
                    IO::createDirectory($tempDir, true, 0755);
                }

                // Generate unique temp file path
                $tempPath = $tempDir . DIRECTORY_SEPARATOR . basename($resourcePath) . '.' . uniqid();

                // Write the phar data to the temp file
                IO::writeFile($tempPath, $pharData);

                // Register for cleanup on shutdown
                ShutdownHandler::flagTemporary($tempPath);

                // Cache the mapping
                self::$pharCache[$nccPath] = $tempPath;

                return $tempPath;
            }
            catch (Exception $e)
            {
                trigger_error("Failed to extract phar: " . $e->getMessage(), E_USER_WARNING);
                return null;
            }
        }

        /**
         * Parses an ncc:// URL and extracts package name and resource path.
         * Handles version specifications (e.g., ncc://package.name=1.0.0/resource/path)
         * and automatically imports packages if not already loaded.
         *
         * Special handling: ncc://package.name and ncc://package.name=version are valid
         * for require/include statements, returning empty content after importing the package.
         *
         * @param string $url The URL to parse (e.g., ncc://package.name/resource/path or ncc://package.name=1.0.0/resource/path)
         * @return array{0: string, 1: string, 2: bool}|null Array of [packageName, resourcePath, isPackageOnly] or null on failure
         */
        private function parseUrl(string $url): ?array
        {
            // Remove the protocol prefix
            if (!str_starts_with($url, self::PROTOCOL . '://'))
            {
                return null;
            }

            $path = substr($url, strlen(self::PROTOCOL . '://'));
            if (empty($path))
            {
                return null;
            }

            // Split into package specification and resource path
            $parts = explode('/', $path, 2);
            $packageSpec = $parts[0];
            $resourcePath = $parts[1] ?? '';

            if (empty($packageSpec))
            {
                return null;
            }

            // Check if package specification includes version (e.g., package.name=1.0.0)
            $version = 'latest';
            if (str_contains($packageSpec, '='))
            {
                [$packageName, $version] = explode('=', $packageSpec, 2);
                if (empty($packageName) || empty($version))
                {
                    return null;
                }
            }
            else
            {
                $packageName = $packageSpec;
            }

            // Auto-import the package if not already imported
            if (!Runtime::isImported($packageName))
            {
                try
                {
                    Runtime::import($packageName, $version);
                }
                catch (Exception $e)
                {
                    // Import failed, return null to trigger error handling in caller
                    trigger_error("Failed to auto-import package '$packageName' version '$version': " . $e->getMessage(), E_USER_WARNING);
                    return null;
                }
            }

            // If resource path is empty, this is a package-only reference (valid for require/include)
            $isPackageOnly = empty($resourcePath);
            
            // Normalize the resource path to resolve relative segments (.. and .)
            if (!$isPackageOnly)
            {
                $resourcePath = $this->normalizePath($resourcePath);
            }

            return [$packageName, $resourcePath, $isPackageOnly];
        }

        /**
         * Normalizes a path by resolving relative segments (.. and .).
         *
         * @param string $path The path to normalize
         * @return string The normalized path
         */
        private function normalizePath(string $path): string
        {
            // Split the path into segments
            $segments = explode('/', $path);
            $normalized = [];

            foreach ($segments as $segment)
            {
                // Skip empty segments and current directory references
                if ($segment === '' || $segment === '.')
                {
                    continue;
                }

                // Handle parent directory references
                if ($segment === '..')
                {
                    // Remove the last segment if it exists
                    if (count($normalized) > 0)
                    {
                        array_pop($normalized);
                    }
                    continue;
                }

                // Add normal segments
                $normalized[] = $segment;
            }

            return implode('/', $normalized);
        }
    }
