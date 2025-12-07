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

    namespace ncc\Classes;

    use ncc\Interfaces\ReferenceInterface;
    use ncc\Runtime;

    /**
     * StreamWrapper for accessing imported package contents via the ncc:// protocol.
     *
     * This wrapper allows reading files from imported NCC packages using URLs in the format:
     * ncc://package.name/path/to/resource
     *
     * Example usage:
     * ```php
     * Runtime::import('path/to/package.ncc');
     * StreamWrapper::register();
     * $content = file_get_contents('ncc://net.nosial.configlib/ConfigLib/Configuration.php');
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

            [$packageName, $resourcePath] = $parsed;

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
            if ($this->reference === null)
            {
                return '';
            }

            // Lazy load the data only when needed
            if ($this->data === null)
            {
                $this->data = $this->packageReader->read($this->reference);
                // If it's an ExecutionUnit, it will return an object, convert to string
                if (!is_string($this->data))
                {
                    $this->data = '';
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
            return $this->position;
        }

        /**
         * Checks if the stream has reached EOF.
         *
         * @return bool True if at EOF, false otherwise
         */
        public function stream_eof(): bool
        {
            if ($this->reference === null)
            {
                return true;
            }

            // We need to load data to know the size
            if ($this->data === null)
            {
                $this->data = $this->packageReader->read($this->reference);
                if (!is_string($this->data))
                {
                    $this->data = '';
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
            if ($this->reference === null)
            {
                return false;
            }

            // Load data if not already loaded
            if ($this->data === null)
            {
                $this->data = $this->packageReader->read($this->reference);
                if (!is_string($this->data))
                {
                    $this->data = '';
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
            if ($this->reference === null)
            {
                return false;
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
                'size' => $this->reference->getSize(),
                'atime' => 0,
                'mtime' => 0,
                'ctime' => 0,
                'blksize' => self::READ_BUFFER_SIZE,
                'blocks' => ceil($this->reference->getSize() / 512),
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
            // Parse the URL
            $parsed = $this->parseUrl($path);
            if ($parsed === null)
            {
                return false;
            }

            [$packageName, $resourcePath] = $parsed;

            // Get the package reader from Runtime
            if (!Runtime::isImported($packageName))
            {
                return false;
            }

            $packages = Runtime::getImportedPackages();
            $packageReader = $packages[$packageName];

            // Find the resource reference
            $reference = $packageReader->find($resourcePath);
            if ($reference === null)
            {
                return false;
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
                'size' => $reference->getSize(),
                'atime' => 0,
                'mtime' => 0,
                'ctime' => 0,
                'blksize' => self::READ_BUFFER_SIZE,
                'blocks' => ceil($reference->getSize() / 512),
            ];
        }

        /**
         * Closes the stream and releases resources.
         *
         * @return void
         */
        public function stream_close(): void
        {
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
         * Open directory handle (not supported for package resources).
         *
         * @param string $path The directory path
         * @param int $options Flags
         * @return bool Always returns false (directory listing not supported)
         */
        public function dir_opendir(string $path, int $options): bool
        {
            // Directory listing not supported for package resources
            return false;
        }

        /**
         * Read entry from directory handle.
         *
         * @return string|false Always returns false (directory listing not supported)
         */
        public function dir_readdir(): string|false
        {
            // Directory listing not supported
            return false;
        }

        /**
         * Rewind directory handle.
         *
         * @return bool Always returns false (directory listing not supported)
         */
        public function dir_rewinddir(): bool
        {
            // Directory listing not supported
            return false;
        }

        /**
         * Close directory handle.
         *
         * @return bool Always returns true
         */
        public function dir_closedir(): bool
        {
            // Nothing to close
            return true;
        }

        /**
         * Parses an ncc:// URL and extracts package name and resource path.
         *
         * @param string $url The URL to parse (e.g., ncc://package.name/resource/path)
         * @return array{0: string, 1: string}|null Array of [packageName, resourcePath] or null on failure
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

            // Split into package name and resource path
            $parts = explode('/', $path, 2);
            if (count($parts) < 2)
            {
                return null;
            }

            [$packageName, $resourcePath] = $parts;

            if (empty($packageName) || empty($resourcePath))
            {
                return null;
            }

            return [$packageName, $resourcePath];
        }
    }
