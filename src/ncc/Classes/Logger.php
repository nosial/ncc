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

    class Logger
    {
        private static bool $loggingDisabled = false;
        private static ?\ncc\Libraries\LogLib2\Logger $logger = null;

        /**
         * Returns the shared logger instance for the CLI interface
         *
         * @return \ncc\Libraries\LogLib2\Logger|null The CLI interface logging instance, or null if logging is disabled
         */
        public static function getLogger(): ?\ncc\Libraries\LogLib2\Logger
        {
            // Since this is a frequently called method, we cache the logging disabled state
            if(self::$loggingDisabled)
            {
                return null;
            }

            if(defined('NCC_DISABLE_LOGGING') || getenv('NCC_DISABLE_LOGGING') === '1')
            {
                self::$loggingDisabled = true;
                return null;
            }

            if(self::$logger === null)
            {
                self::$logger = new \ncc\Libraries\LogLib2\Logger('ncc');
            }

            return self::$logger;
        }
    }