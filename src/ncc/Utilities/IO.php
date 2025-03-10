<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    namespace ncc\Utilities;

    use InvalidArgumentException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use RuntimeException;
    use SplFileInfo;
    use SplFileObject;

    class IO
    {
        /**
         * Attempts to write the specified file, with proper error handling
         *
         * @param string $uri
         * @param string $data
         * @param int $perms
         * @param string $mode
         * @return void
         * @throws IOException
         */
        public static function fwrite(string $uri, string $data, int $perms=0644, string $mode='w'): void
        {
            $fileInfo = new SplFileInfo($uri);

            if(!is_dir($fileInfo->getPath()) && !mkdir($concurrentDirectory = $fileInfo->getPath(), 0755, true) && !is_dir($concurrentDirectory))
            {
                throw new IOException(sprintf('Unable to create directory: (%s)', $fileInfo->getPath()));
            }

            Console::outDebug(sprintf('writing %s of data to %s', Functions::b2u(strlen($data)), $uri));

            $file = new SplFileObject($uri, $mode);

            if (!$file->flock(LOCK_EX | LOCK_NB))
            {
                throw new IOException(sprintf('Unable to obtain lock on file: (%s)', $uri));
            }

            if (!$file->fwrite($data))
            {
                throw new IOException(sprintf('Unable to write content to file: (%s)... to (%s)', substr($data,0,25), $uri));
            }

            if (!$file->flock(LOCK_UN))
            {
                throw new IOException(sprintf('Unable to remove lock on file: (%s)', $uri));
            }

            if (!@chmod($uri, $perms))
            {
                throw new IOException(sprintf('Unable to chmod: (%s) to (%s)', $uri, $perms));
            }
        }

        /**
         * Attempts to read the specified file
         *
         * @param string $uri
         * @param string $mode
         * @param int|null $length
         * @return string
         * @throws IOException
         * @throws PathNotFoundException
         */
        public static function fread(string $uri, string $mode='r', ?int $length=null): string
        {
            $fileInfo = new SplFileInfo($uri);

            if(!is_dir($fileInfo->getPath()))
            {
                throw new IOException(sprintf('Attempted to read data from a directory instead of a file: (%s)', $uri));
            }

            if(!file_exists($uri))
            {
                throw new PathNotFoundException($uri);
            }

            if(!is_readable($uri))
            {
                throw new IOException(sprintf('Unable to read file (permission issue?): (%s)', $uri));
            }

            $file = new SplFileObject($uri, $mode);
            if($length === null)
            {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $length = $file->getSize();

                if($length === false)
                {
                    throw new IOException(sprintf('Unable to get size of file: (%s)', $uri));
                }
            }

            if($length === 0)
            {
                return (string)null;
            }

            Console::outDebug(sprintf('reading %s', $uri));
            return $file->fread($length);
        }

        /**
         * Returns an array of all files in the specified path
         *
         * @param string $path
         * @param bool $recursive
         * @return array
         * @throws IOException
         */
        public static function scan(string $path, bool $recursive = true): array
        {
            if (!is_readable($path))
            {
                throw new IOException(sprintf('Directory does not exist or is not readable: %s', $path));
            }

            $files = [];
            $items = scandir($path, SCANDIR_SORT_NONE);

            if ($items === false)
            {
                throw new IOException(sprintf('Unable to list directory items: %s', $path));
            }

            foreach ($items as $file)
            {
                if ($file === '.' || $file === '..')
                {
                    continue;
                }

                $file_path = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($file_path) && $recursive)
                {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $files = array_merge($files, self::scan($file_path));
                    continue;
                }

                $files[] = $file_path;
            }

            return $files;
        }
    }