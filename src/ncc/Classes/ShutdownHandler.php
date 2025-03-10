<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\RuntimeCache;

    class ShutdownHandler
    {
        /**
         * @var bool
         */
        private static $registered = false;

        /**
         * @var array
         */
        private static $cleanup_paths = [];

        /**
         * Registers the shutdown handler
         *
         * @return void
         */
        public static function register(): void
        {
            if(self::$registered)
            {
                return;
            }

            self::$registered = true;
            register_shutdown_function([self::class, 'handle']);
        }

        /**
         * The shutdown handler for ncc
         *
         * @return void
         */
        public static function shutdown(): void
        {
            if(count(self::$cleanup_paths) > 0)
            {
                $filesystem = new Filesystem();

                foreach(self::$cleanup_paths as $path)
                {
                    try
                    {
                        //$filesystem->remove($path);
                    }
                    catch(Exception $e)
                    {
                        // ignore
                    }
                }
            }

            try
            {
                Functions::finalizePermissions();
            }
            catch (Exception $e)
            {
                Console::outWarning('An error occurred while shutting down ncc, ' . $e->getMessage());
            }
        }

        /**
         * Declares a path to be cleaned up on shutdown
         *
         * @param string $path
         * @return void
         */
        public static function declareTemporaryPath(string $path): void
        {
            if(!in_array($path, self::$cleanup_paths, true))
            {
                self::$cleanup_paths[] = $path;
            }
        }
    }