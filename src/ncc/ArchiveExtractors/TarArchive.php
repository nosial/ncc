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

    namespace ncc\ArchiveExtractors;

    use ncc\Libraries\fslib\IO;
    use ncc\Exceptions\OperationException;
    use ncc\Interfaces\ArchiveInterface;
    use ncc\Libraries\fslib\IOException;

    class TarArchive implements ArchiveInterface
    {
        private const int TAR_BLOCK_SIZE = 512;
        private const int TAR_HEADER_SIZE = 512;

        /**
         * @inheritDoc
         */
        public static function extract(string $archivePath, string $destinationPath): void
        {
            if (!IO::exists($archivePath))
            {
                throw new OperationException(sprintf('Archive file not found: %s', $archivePath));
            }

            if (!IO::isReadable($archivePath))
            {
                throw new OperationException(sprintf('Archive file is not readable: %s', $archivePath));
            }

            // Create destination directory if it doesn't exist
            if (!IO::isDirectory($destinationPath))
            {
                IO::createDirectory($destinationPath);
            }

            // Detect compression type and open the file accordingly
            $handle = self::openArchive($archivePath);

            try
            {
                self::extractTar($handle, $destinationPath);
            }
            finally
            {
                if (is_resource($handle))
                {
                    fclose($handle);
                }
            }
        }

        /**
         * Opens the tar archive, handling compression if needed
         *
         * @param string $archivePath
         * @return resource
         * @throws OperationException
         */
        private static function openArchive(string $archivePath)
        {
            // Check if the file is gzip compressed
            if (self::isGzipCompressed($archivePath))
            {
                $handle = @gzopen($archivePath, 'rb');
                if (!$handle)
                {
                    throw new OperationException(sprintf('Failed to open gzipped tar archive: %s', $archivePath));
                }
                return $handle;
            }

            // Check if the file is bzip2 compressed
            if (self::isBzip2Compressed($archivePath))
            {
                if (!function_exists('bzopen'))
                {
                    throw new OperationException('bzip2 extension is not available');
                }
                $handle = @bzopen($archivePath, 'r');
                if (!$handle)
                {
                    throw new OperationException(sprintf('Failed to open bzip2 tar archive: %s', $archivePath));
                }
                return $handle;
            }

            // Regular uncompressed tar
            $handle = @fopen($archivePath, 'rb');
            if (!$handle)
            {
                throw new OperationException(sprintf('Failed to open tar archive: %s', $archivePath));
            }
            return $handle;
        }

        /**
         * Extracts the tar archive
         *
         * @param resource $handle A file handle to the opened tar archive
         * @param string $destinationPath The path to extract the archive contents to
         * @throws OperationException Thrown on extraction errors
         * @throws IOException Thrown on IO errors
         */
        private static function extractTar($handle, string $destinationPath): void
        {
            while (!feof($handle))
            {
                // Read tar header
                $header = self::readBytes($handle, self::TAR_HEADER_SIZE);
                if (strlen($header) < self::TAR_HEADER_SIZE)
                {
                    break; // End of archive
                }

                // Check for end of archive (null blocks)
                if (trim($header) === '')
                {
                    break;
                }

                // Parse header
                $fileInfo = self::parseTarHeader($header);
                if (!$fileInfo)
                {
                    continue; // Invalid header, skip
                }

                // Build full path
                $fullPath = rtrim($destinationPath, '/') . '/' . ltrim($fileInfo['name'], '/');

                // Handle different file types
                if ($fileInfo['type'] === '5' || $fileInfo['type'] === 'dir')
                {
                    // Directory
                    if (!IO::isDirectory($fullPath))
                    {
                        IO::createDirectory($fullPath);
                    }
                }
                elseif ($fileInfo['type'] === '0' || $fileInfo['type'] === '' || $fileInfo['type'] === 'file')
                {
                    // Regular file
                    $dirPath = dirname($fullPath);
                    if (!IO::isDirectory($dirPath))
                    {
                        IO::createDirectory($dirPath);
                    }

                    // Extract file content
                    $fileHandle = @fopen($fullPath, 'wb');
                    if (!$fileHandle)
                    {
                        throw new OperationException(sprintf('Failed to create file: %s', $fullPath));
                    }

                    $remaining = $fileInfo['size'];
                    while ($remaining > 0)
                    {
                        $toRead = min($remaining, 8192);
                        $data = self::readBytes($handle, $toRead);
                        if ($data === false || strlen($data) === 0)
                        {
                            fclose($fileHandle);
                            throw new OperationException(sprintf('Failed to read file data for: %s', $fullPath));
                        }
                        fwrite($fileHandle, $data);
                        $remaining -= strlen($data);
                    }

                    fclose($fileHandle);

                    // Set file permissions if specified
                    if ($fileInfo['mode'])
                    {
                        try
                        {
                            IO::chmod($fullPath, $fileInfo['mode']);
                        }
                        catch(IOException)
                        {
                            // Ignore permission errors, continue extraction
                        }
                    }

                    // Skip padding to next block boundary
                    $padding = (self::TAR_BLOCK_SIZE - ($fileInfo['size'] % self::TAR_BLOCK_SIZE)) % self::TAR_BLOCK_SIZE;
                    if ($padding > 0)
                    {
                        self::readBytes($handle, $padding);
                    }
                }
                else
                {
                    // Skip unsupported file types (symlinks, etc.)
                    if ($fileInfo['size'] > 0)
                    {
                        $toSkip = $fileInfo['size'] + ((self::TAR_BLOCK_SIZE - ($fileInfo['size'] % self::TAR_BLOCK_SIZE)) % self::TAR_BLOCK_SIZE);
                        self::readBytes($handle, $toSkip);
                    }
                }
            }
        }

        /**
         * Parses a tar header block
         *
         * @param string $header The 512-byte tar header block
         * @return array|null Returns an associative array of file info or null on failure
         */
        private static function parseTarHeader(string $header): ?array
        {
            if (strlen($header) < 512)
            {
                return null;
            }

            // Extract fields from header
            $name = trim(substr($header, 0, 100), "\0");
            $mode = octdec(trim(substr($header, 100, 8), "\0 "));
            $uid = octdec(trim(substr($header, 108, 8), "\0 "));
            $gid = octdec(trim(substr($header, 116, 8), "\0 "));
            $size = octdec(trim(substr($header, 124, 12), "\0 "));
            $mtime = octdec(trim(substr($header, 136, 12), "\0 "));
            $checksum = octdec(trim(substr($header, 148, 8), "\0 "));
            $type = substr($header, 156, 1);
            $linkname = trim(substr($header, 157, 100), "\0");

            // Handle UStar format (extended name)
            $prefix = trim(substr($header, 345, 155), "\0");
            if ($prefix !== '')
            {
                $name = $prefix . '/' . $name;
            }

            // Validate checksum
            $checksumTest = 0;
            for ($i = 0; $i < 512; $i++)
            {
                $checksumTest += ord($header[$i]);
            }
            // Subtract the checksum field and add spaces
            for ($i = 148; $i < 156; $i++)
            {
                $checksumTest -= ord($header[$i]);
                $checksumTest += ord(' ');
            }

            if ($checksumTest !== $checksum && $checksum !== 0)
            {
                return null; // Invalid checksum
            }

            if (empty($name))
            {
                return null;
            }

            return [
                'name' => $name,
                'mode' => $mode,
                'uid' => $uid,
                'gid' => $gid,
                'size' => $size,
                'mtime' => $mtime,
                'type' => $type,
                'linkname' => $linkname,
            ];
        }

        /**
         * Reads bytes from the handle (works with regular and compressed streams)
         *
         * @param resource $handle The file handle
         * @param int $length Number of bytes to read
         * @return string The read bytes
         */
        private static function readBytes($handle, int $length): string
        {
            if ($length <= 0)
            {
                return '';
            }

            $data = '';
            $remaining = $length;

            while ($remaining > 0 && !feof($handle))
            {
                // Detect stream type
                $meta = stream_get_meta_data($handle);
                $wrapper = $meta['wrapper_type'] ?? '';

                if ($wrapper === 'ZLIB' || str_starts_with($meta['uri'] ?? '', 'compress.zlib://'))
                {
                    // Gzip stream
                    $chunk = gzread($handle, $remaining);
                }
                elseif ($wrapper === 'BZ2' || str_starts_with($meta['uri'] ?? '', 'compress.bzip2://'))
                {
                    // Bzip2 stream
                    $chunk = bzread($handle, $remaining);
                }
                else
                {
                    // Regular file stream
                    $chunk = fread($handle, $remaining);
                }

                if ($chunk === false || $chunk === '')
                {
                    break;
                }

                $data .= $chunk;
                $remaining -= strlen($chunk);
            }

            return $data;
        }

        /**
         * Checks if the file is gzip compressed
         *
         * @param string $filePath The file path
         * @return bool True if gzip compressed, false otherwise
         */
        private static function isGzipCompressed(string $filePath): bool
        {
            $handle = fopen($filePath, 'rb');
            if (!$handle)
            {
                return false;
            }

            $magic = fread($handle, 2);
            fclose($handle);

            return $magic === "\x1f\x8b"; // Gzip magic number
        }

        /**
         * Checks if the file is bzip2 compressed
         *
         * @param string $filePath The file path
         * @return bool True if bzip2 compressed, false otherwise
         */
        private static function isBzip2Compressed(string $filePath): bool
        {
            $handle = fopen($filePath, 'rb');
            if (!$handle)
            {
                return false;
            }

            $magic = fread($handle, 3);
            fclose($handle);

            return $magic === "BZh"; // Bzip2 magic number
        }
    }