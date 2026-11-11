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

    use ncc\Libraries\LogLib2\Logger;
    use ncc\Libraries\LogLib2\Objects\ExceptionDetails;
    use Throwable;

    class ProgressLogger extends Logger
    {
        /**
         * @inheritDoc
         */
        public function warning(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $backtraceLevel = self::beginEvent();
            try
            {
                parent::warning($message, $e);
            }
            finally
            {
                self::endEvent($backtraceLevel);
            }
        }

        /**
         * @inheritDoc
         */
        public function error(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $backtraceLevel = self::beginEvent();
            try
            {
                parent::error($message, $e);
            }
            finally
            {
                self::endEvent($backtraceLevel);
            }
        }

        /**
         * @inheritDoc
         */
        public function critical(string $message, null|ExceptionDetails|Throwable $e=null): void
        {
            $backtraceLevel = self::beginEvent();
            try
            {
                parent::critical($message, $e);
            }
            finally
            {
                self::endEvent($backtraceLevel);
            }
        }

        /**
         * Removes the progress bar before a log event is written and raises the backtrace level by one to skip the
         * frame added by this class, so the event still reports the original caller.
         *
         * @return int The backtrace level to restore with endEvent()
         */
        private static function beginEvent(): int
        {
            $backtraceLevel = Logger::getBacktraceLevel();
            Logger::setBacktraceLevel($backtraceLevel + 1);
            Console::suspendProgress();
            return $backtraceLevel;
        }

        /**
         * Restores the backtrace level and draws the progress bar again after a log event was written.
         *
         * @param int $backtraceLevel The backtrace level returned by beginEvent()
         * @return void
         */
        private static function endEvent(int $backtraceLevel): void
        {
            Logger::setBacktraceLevel($backtraceLevel);
            Console::resumeProgress();
        }
    }
