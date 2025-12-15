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

    use FilesystemIterator;
    use ncc\CLI\Logger;
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
            Logger::getLogger()->verbose(sprintf('Creating directory %s', $path));
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
        public static function rm(string $path, bool $recursive): void
        {
            Logger::getLogger()->verbose(sprintf('Deleting %s', $path));

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
            Logger::getLogger()->verbose(sprintf('Touching file %s', $path));
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
            Logger::getLogger()->verbose(sprintf('Writing file %s', $path));
            if(!@file_put_contents($path, $content))
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
            Logger::getLogger()->verbose(sprintf('Reading file %s', $path));
            $content = @file_get_contents($path);
            if($content === false)
            {
                throw new IOException(sprintf('Failed to read file %s', $path));
            }
            return $content;
        }

        public static function santizeName(string $name): string
        {
            // Remove any invalid characters from the name
            return preg_replace('/[<>:"\/|?*\x00-\x1F]/', '_', $name);
        }
    }