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

    namespace ncc\CLI;

    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Exceptions\IOException;

    class ShutdownHandler
    {
        private static bool $registered = false;
        private static array $temporaryFiles = [];

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
            register_shutdown_function([self::class, 'shutdown']);
        }

        /**
         * The shutdown handler for ncc
         *
         * @return void
         */
        public static function shutdown(): void
        {
            // Cleanup temporary files from the system.
            if(count(self::$temporaryFiles) > 0)
            {
                Logger::getLogger()?->verbose(sprintf("Cleaning up %d temporary file(s)", count(self::$temporaryFiles)));
                foreach(self::$temporaryFiles as $temporaryFile)
                {
                    $temporaryFile = realpath($temporaryFile);

                    if(!IO::exists($temporaryFile))
                    {
                        Logger::getLogger()?->debug(sprintf("Temporary file '%s' does not exist, skipping", $temporaryFile));
                        continue;
                    }

                    Logger::getLogger()?->debug(sprintf("Deleting temporary file '%s'", $temporaryFile));

                    try
                    {
                        IO::rm($temporaryFile, false);
                    }
                    catch(IOException $e)
                    {
                        Logger::getLogger()?->warning(sprintf("Cannot delete temporary file '%s' due to insufficient permissions", $temporaryFile));
                    }
                }
            }
        }

        /**
         * Declares a path to be cleaned up on shutdown
         *
         * @param string $path
         * @return void
         */
        public static function addTemporary(string $path): void
        {
            if(!in_array($path, self::$temporaryFiles, true))
            {
                self::$temporaryFiles[] = $path;
            }
        }
    }