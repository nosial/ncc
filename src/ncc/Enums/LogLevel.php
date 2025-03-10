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

    namespace ncc\Enums;

    use ncc\Utilities\Validate;

    enum LogLevel : string
    {
        case SILENT = 'silent';

        case VERBOSE = 'verbose';

        case DEBUG = 'debug';

        case INFO = 'info';

        case WARNING = 'warn';

        case ERROR = 'error';

        case FATAL = 'fatal';

        /**
         * Checks if the current log level permits logging at the specified level.
         *
         * @param LogLevel|null $current_level The log level to be checked. If null, the method returns false.
         * @return bool Returns true if logging is permitted at the specified level, otherwise false.
         */
        public function checkLogLevel(?LogLevel $current_level): bool
        {
            if ($current_level === null)
            {
                return false;
            }

            return match ($current_level)
            {
                LogLevel::DEBUG => in_array($this, [LogLevel::DEBUG, LogLevel::VERBOSE, LogLevel::INFO, LogLevel::WARNING, LogLevel::FATAL, LogLevel::ERROR], true),
                LogLevel::VERBOSE => in_array($this, [LogLevel::VERBOSE, LogLevel::INFO, LogLevel::WARNING, LogLevel::FATAL, LogLevel::ERROR], true),
                LogLevel::INFO => in_array($this, [LogLevel::INFO, LogLevel::WARNING, LogLevel::FATAL, LogLevel::ERROR], true),
                LogLevel::WARNING => in_array($this, [LogLevel::WARNING, LogLevel::FATAL, LogLevel::ERROR], true),
                LogLevel::ERROR => in_array($this, [LogLevel::FATAL, LogLevel::ERROR], true),
                LogLevel::FATAL => $this === LogLevel::FATAL,
                default => false,
            };
        }

        /**
         * Converts the given string input to a LogLevel.
         * If the input is invalid or not found, it defaults to LogLevel::INFO.
         *
         * @param string $input The input string to be converted to a LogLevel.
         * @return LogLevel Returns the corresponding LogLevel for the input string or LogLevel::INFO if not found.
         */
        public static function fromOrDefault(string $input): LogLevel
        {
            $value = self::tryFrom($input);

            if($value === null)
            {
                return self::INFO;
            }

            return $value;
        }
    }