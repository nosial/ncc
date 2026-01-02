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

    use ncc\Exceptions\IOException;

    class ShutdownHandler
    {
        private static array $temporaryFiles = [];

        protected static function onShutdown(): void
        {
            foreach(self::$temporaryFiles as $file)
            {
                if(IO::exists($file))
                {
                    try
                    {
                        IO::rm($file, true);
                    }
                    catch (IOException $e)
                    {
                        Logger::getLogger()->warning(sprintf('Failed to delete temporary file %s on shutdown: %s', $file, $e->getMessage()), $e);
                    }
                }
            }
        }

        public static function flagTemporary(string $filePath): void
        {
            self::$temporaryFiles[] = $filePath;
        }

        public static function register(): void
        {
            register_shutdown_function([self::class, 'onShutdown']);
        }
    }