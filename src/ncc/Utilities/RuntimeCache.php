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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Utilities;

    use Exception;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;

    class RuntimeCache
    {
        /**
         * An array of cache entries
         *
         * @var array
         */
        private static $cache = [];

        /**
         * An array of files to delete when the cache is cleared
         *
         * @var string[]
         */
        private static $temporary_files = [];

        /**
         * Sets a value, returns the value
         *
         * @param $key
         * @param $value
         * @return mixed
         */
        public static function set($key, $value): mixed
        {
            self::$cache[$key] = $value;
            return $value;
        }

        /**
         * Gets an existing value, null if it doesn't exist
         *
         * @param $key
         * @return mixed|null
         */
        public static function get($key): mixed
        {
            if(isset(self::$cache[$key]))
                return self::$cache[$key];

            return null;
        }

        /**
         * Sets a file as temporary, it will be deleted when the cache is cleared
         *
         * @param string $path
         * @return void
         */
        public static function setFileAsTemporary(string $path): void
        {
            Console::outDebug($path);
            if(!in_array($path, self::$temporary_files))
                self::$temporary_files[] = $path;
        }

        /**
         * Removes a file from the temporary files list
         *
         * @param string $path
         * @return void
         * @noinspection PhpUnused
         */
        public static function removeFileAsTemporary(string $path): void
        {
            Console::outDebug($path);
            if(in_array($path, self::$temporary_files))
                unset(self::$temporary_files[array_search($path, self::$temporary_files)]);
        }

        /**
         * @param bool $clear_memory
         * @param bool $clear_files
         * @return void
         */
        public static function clearCache(bool $clear_memory=true, bool $clear_files=true): void
        {
            Console::outDebug('clearing cache');

            if($clear_memory)
            {
                Console::outDebug(sprintf('clearing memory cache (%d entries)', count(self::$cache)));
                self::$cache = [];
            }

            if($clear_files)
            {
                Console::outDebug('clearing temporary files');
                $filesystem = new Filesystem();
                foreach(self::$temporary_files as $file)
                {
                    try
                    {
                        $filesystem->remove($file);
                        Console::outDebug(sprintf('deleted temporary file \'%s\'', $file));
                    }
                    catch (Exception $e)
                    {
                        Console::outDebug(sprintf('failed to delete temporary file \'%s\', %s', $file, $e->getMessage()));
                        unset($e);
                    }
                }
            }
        }
    }