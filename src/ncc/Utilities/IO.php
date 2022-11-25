<?php

    namespace ncc\Utilities;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
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

            if(!is_dir($fileInfo->getPath()))
            {
                throw new IOException(sprintf('Attempted to write data to a directory instead of a file: (%s)', $uri));
            }

            Console::outDebug(sprintf('writing %s of data to %s', Functions::b2u(strlen($data)), $uri));
            $file = new SplFileObject($uri, $mode);

            if (!$file->flock(LOCK_EX | LOCK_NB))
            {
                throw new IOException(sprintf('Unable to obtain lock on file: (%s)', $uri));
            }
            elseif (!$file->fwrite($data))
            {
                throw new IOException(sprintf('Unable to write content to file: (%s)... to (%s)', substr($data,0,25), $uri));
            }
            elseif (!$file->flock(LOCK_UN))
            {
                throw new IOException(sprintf('Unable to remove lock on file: (%s)', $uri));
            }
            elseif (!@chmod($uri, $perms))
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
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
                throw new FileNotFoundException(sprintf('Cannot find file %s', $uri));
            }

            if(!is_readable($uri))
            {
                throw new AccessDeniedException(sprintf('Insufficient permissions to read %s', $uri));
            }

            $file = new SplFileObject($uri, $mode);
            if($length == null)
            {
                $length = $file->getSize();
            }

            if($length == 0)
            {
                return (string)null;
            }

            Console::outDebug(sprintf('reading %s', $uri));
            return $file->fread($length);
        }
    }