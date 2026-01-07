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

    use FilesystemIterator;
    use ncc\Exceptions\IOException;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    class IO
    {
        /**
         * Creates a directory at the specified path.
         *
         * @param string $path The path where the directory should be created.
         * @param bool $recursive Whether to create directories recursively.
         * @throws IOException If the directory cannot be created.
         */
        public static function mkdir(string $path, bool $recursive=true): void
        {
            Logger::getLogger()?->verbose(sprintf('Creating directory %s', $path));
            if(@mkdir($path, recursive: $recursive) === false && !is_dir($path))
            {
                throw new IOException(sprintf('Failed to create directory %s', $path));
            }
        }

        /**
         * Removes a file or directory at the specified path.
         *
         * @param string $path The path to the file or directory to remove.
         * @param bool $recursive Whether to remove directories recursively.
         * @throws IOException If the file or directory cannot be removed.
         */
        public static function rm(string $path, bool $recursive=true): void
        {
            Logger::getLogger()?->verbose(sprintf('Deleting %s', $path));

            if(is_dir($path))
            {
                if($recursive)
                {
                    $it = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach($files as $file)
                    {
                        if($file->isDir())
                        {
                            if(!@rmdir($file->getRealPath()))
                            {
                                throw new IOException(sprintf('Failed to remove directory %s', $file->getRealPath()));
                            }
                        }
                        else
                        {
                            if(!@unlink($file->getRealPath()))
                            {
                                throw new IOException(sprintf('Failed to remove file %s', $file->getRealPath()));
                            }
                        }
                    }

                    if(!@rmdir($path))
                    {
                        throw new IOException(sprintf('Failed to remove directory %s', $path));
                    }
                }
                else
                {
                    if(!@rmdir($path))
                    {
                        throw new IOException(sprintf('Failed to remove directory %s', $path));
                    }
                }
            }
            else
            {
                if(!@unlink($path))
                {
                    throw new IOException(sprintf('Failed to remove file %s', $path));
                }
            }
        }

        /**
         * Updates the access and modification time of the file at the specified path.
         *
         * @param string $path The path to the file to touch.
         * @throws IOException If the file cannot be touched.
         */
        public static function touch(string $path): void
        {
            Logger::getLogger()?->verbose(sprintf('Touching file %s', $path));
            if(@touch($path) === false)
            {
                throw new IOException(sprintf('Failed to touch file %s', $path));
            }
        }

        /**
         * Writes content to a file at the specified path.
         *
         * @param string $path The path to the file to write.
         * @param string $content The content to write to the file.
         * @throws IOException If the file cannot be written.
         */
        public static function writeFile(string $path, string $content): void
        {
            Logger::getLogger()?->verbose(sprintf('Writing file %s', $path));
            
            // Ensure parent directory exists
            $directory = dirname($path);
            if(!is_dir($directory))
            {
                self::mkdir($directory, true);
            }
            
            if(@file_put_contents($path, $content) === false)
            {
                throw new IOException(sprintf('Failed to write file %s', $path));
            }
        }

        /**
         * Reads content from a file at the specified path.
         *
         * @param string $path The path to the file to read.
         * @return string The content of the file.
         * @throws IOException If the file cannot be read.
         */
        public static function readFile(string $path): string
        {
            Logger::getLogger()?->verbose(sprintf('Reading file %s', $path));
            $content = @file_get_contents($path);
            if($content === false)
            {
                throw new IOException(sprintf('Failed to read file %s', $path));
            }
            return $content;
        }

        /**
         * Copies a file from source to destination.
         *
         * @param string $source The source file path.
         * @param string $destination The destination file path.
         * @throws IOException If the file cannot be copied.
         */
        public static function copy(string $source, string $destination): void
        {
            Logger::getLogger()?->verbose(sprintf('Copying file from %s to %s', $source, $destination));
            if(!@copy($source, $destination))
            {
                throw new IOException(sprintf('Failed to copy file from %s to %s', $source, $destination));
            }
        }

        /**
         * Renames or moves a file or directory.
         *
         * @param string $oldName The current file or directory path.
         * @param string $newName The new file or directory path.
         * @throws IOException If the file or directory cannot be renamed.
         */
        public static function rename(string $oldName, string $newName): void
        {
            Logger::getLogger()?->verbose(sprintf('Renaming %s to %s', $oldName, $newName));
            if(!@rename($oldName, $newName))
            {
                throw new IOException(sprintf('Failed to rename %s to %s', $oldName, $newName));
            }
        }

        /**
         * Changes file mode (permissions).
         *
         * @param string $path The path to the file or directory.
         * @param int $mode The new permissions mode.
         * @throws IOException If the permissions cannot be changed.
         */
        public static function chmod(string $path, int $mode): void
        {
            Logger::getLogger()?->verbose(sprintf('Changing permissions of %s to %o', $path, $mode));
            if(!@chmod($path, $mode))
            {
                throw new IOException(sprintf('Failed to change permissions of %s', $path));
            }
        }

        /**
         * Checks if a file or directory exists.
         *
         * @param string $path The path to check.
         * @return bool True if the file or directory exists, false otherwise.
         */
        public static function exists(string $path): bool
        {
            Logger::getLogger()?->verbose(sprintf('Checking if %s exists', $path));
            return file_exists($path);
        }

        /**
         * Checks if a path is a directory.
         *
         * @param string $path The path to check.
         * @return bool True if the path is a directory, false otherwise.
         */
        public static function isDir(string $path): bool
        {
            Logger::getLogger()?->verbose(sprintf('Checking if %s is a directory', $path));
            return is_dir($path);
        }

        /**
         * Checks if a path is a file.
         *
         * @param string $path The path to check.
         * @return bool True if the path is a file, false otherwise.
         */
        public static function isFile(string $path): bool
        {
            Logger::getLogger()?->verbose(sprintf('Checking if %s is a file', $path));
            return is_file($path);
        }

        /**
         * Checks if a file or directory is readable.
         *
         * @param string $path The path to check.
         * @return bool True if the file or directory is readable, false otherwise.
         */
        public static function isReadable(string $path): bool
        {
            Logger::getLogger()?->verbose(sprintf('Checking if %s is readable', $path));
            return is_readable($path);
        }

        /**
         * Checks if a file or directory is writable.
         *
         * @param string $path The path to check.
         * @return bool True if the file or directory is writable, false otherwise.
         */
        public static function isWritable(string $path): bool
        {
            Logger::getLogger()?->verbose(sprintf('Checking if %s is writable', $path));
            return is_writable($path);
        }

        /**
         * Gets the size of a file.
         *
         * @param string $path The path to the file.
         * @return int The size of the file in bytes.
         * @throws IOException If the file size cannot be determined.
         */
        public static function filesize(string $path): int
        {
            Logger::getLogger()?->verbose(sprintf('Getting size of file %s', $path));
            $size = @filesize($path);
            if($size === false)
            {
                throw new IOException(sprintf('Failed to get size of file %s', $path));
            }
            return $size;
        }

        /**
         * Creates a symbolic link.
         *
         * @param string $target The target of the link.
         * @param string $link The link path.
         * @throws IOException If the symbolic link cannot be created.
         */
        public static function symlink(string $target, string $link): void
        {
            Logger::getLogger()?->verbose(sprintf('Creating symbolic link from %s to %s', $link, $target));
            if(!@symlink($target, $link))
            {
                throw new IOException(sprintf('Failed to create symbolic link from %s to %s', $link, $target));
            }
        }

        /**
         * Creates a hard link.
         *
         * @param string $target The target of the link.
         * @param string $link The link path.
         * @throws IOException If the hard link cannot be created.
         */
        public static function link(string $target, string $link): void
        {
            Logger::getLogger()?->verbose(sprintf('Creating hard link from %s to %s', $link, $target));
            if(!@link($target, $link))
            {
                throw new IOException(sprintf('Failed to create hard link from %s to %s', $link, $target));
            }
        }
    }