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

    namespace ncc\Classes;

    use Exception;
    use finfo;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Utilities\Console;
    use PharData;
    use RuntimeException;
    use ValueError;
    use ZipArchive;

    class ArchiveExtractor
    {
        /**
         * Extracts a given archive to a directory path
         *
         * @param string $archive_path
         * @param string $directory_path
         * @return void
         * @throws IOException
         * @throws NotSupportedException
         */
        public static function extract(string $archive_path, string $directory_path): void
        {
            if(!is_file($archive_path))
            {
                throw new IOException(sprintf('Archive file "%s" does not exist', $archive_path));
            }

            $file_info = new finfo(FILEINFO_MIME);

            $mime = strtolower($file_info->file($archive_path));
            if(($pos = strpos($mime, ';')) !== false)
            {
                $mime = substr($mime, 0, $pos);
            }

            switch($mime)
            {
                case 'application/zip':
                    Console::outDebug(sprintf('Extracting zip archive "%s" to "%s"', $archive_path, $directory_path));
                    self::extractZip($archive_path, $directory_path);
                    break;

                case 'application/x-tar':
                case 'application/x-gzip':
                    Console::outDebug(sprintf('Extracting tar archive "%s" to "%s"', $archive_path, $directory_path));
                    $phar = new PharData($archive_path);
                    $phar->extractTo($directory_path);
                    break;

                default:
                    throw new NotSupportedException(sprintf('Unable to extract archive "%s" of type "%s"', $archive_path, $file_info->file($archive_path)));
            }
        }

        /**
         * Extracts a zip archive to a directory
         *
         * @param string $archive_path
         * @param string $directory_path
         * @return void
         * @throws IOException
         */
        private static function extractZip(string $archive_path, string $directory_path): void
        {
            $zip_archive = new ZipArchive();
            Console::outDebug(sprintf('Opening zip archive "%s"', $archive_path));

            if(!$zip_archive->open($archive_path))
            {
                throw new IOException(sprintf('Unable to open zip archive "%s"', $archive_path));
            }

            if(!is_dir($directory_path) && !mkdir($directory_path, 0777, true) && !is_dir($directory_path))
            {
                throw new IOException(sprintf('Unable to create directory "%s"', $directory_path));
            }

            for($i=0; $i < $zip_archive->numFiles; $i++)
            {
                $entry_name = $zip_archive->statIndex($i)['name'];

                if(str_ends_with($entry_name, '/'))
                {
                    $concurrent_directory = $directory_path . DIRECTORY_SEPARATOR . $entry_name;
                    Console::outDebug(sprintf('Creating directory "%s"', $concurrent_directory));
                    if(!is_dir($concurrent_directory) && !mkdir($concurrent_directory, 0777, true) && !is_dir($concurrent_directory))
                    {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrent_directory));
                    }

                    continue;
                }

                try
                {
                    Console::outDebug(sprintf('Extracting file "%s" from archive "%s"', $entry_name, $archive_path));
                    $zip_archive->extractTo($directory_path, $entry_name);
                }
                catch(Exception $e)
                {
                    throw new IOException(sprintf('Unable to extract file "%s" from archive "%s"', $entry_name, $archive_path), $e);
                }
            }

            try
            {
                $zip_archive->close();
            }
            catch(ValueError $e)
            {
                Console::outWarning('An error occurred while closing the zip archive, ' . $e->getMessage());
            }
        }
    }