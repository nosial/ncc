<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    use RuntimeException;

    enum MacroVariable : string
    {
        // Datetime Macros
        case d = '${d}'; // Day of the month, 2 digits with leading zeros  01 through 31
        case D = '${D}'; // A textual representation of a day, three letters Mon through Sun
        case j = '${j}'; // Day of the month without leading zeros 1 through 31
        case l = '${l}'; // A full textual representation of the day of the week Sunday through Saturday
        case N = '${N}'; // ISO 8601 numeric representation of the day of the week 1 (Monday) to 7 (Sunday)
        case S = '${S}'; // English ordinal suffix for the day of the month, 2 characters st, nd, rd, th
        case w = '${w}'; // Numeric representation of the day of the week 0 (sunday) through 6 (Saturday)
        case z = '${z}'; // The day of the year (starting from 0) 0 through 365
        // Week Format
        case W = '${W}'; // ISO 8601 week number of year, weeks starting on Monday 42 (42nd week in year)
        // Month Format
        case F = '${F}'; // A full textual representation of a month, such as January or March January through December
        case m = '${m}'; // Numeric representation of a month, with leading zeros 01 through 12
        case M = '${M}'; // A short textual representation of a month, three letters Jan through Dec
        case n = '${n}'; // Numeric representation of a month, without leading zeros 1 through 12
        case t = '${t}'; // Number of days in the given month 28 through 31
        // Year format
        case L = '${L}'; // Whether it's a leap year 1 (leap year), 0 otherwise
        /**
         * ISO 8601 week-numbering year. This has the same value as Y,
         * except that if the ISO week number (W) belongs to the previous
         * or next year, that year is used instead.
         */
        case o = '${o}'; // Same as Y, except that it use week number to decide which year it falls onto
        case Y = '${Y}'; // A full numeric representation of a year, at least 4 digits, with - for years BCE. 1991, 2012, 2014, ...
        case y = '${y}'; // A two digit representation of a year 91, 12, 14, ...
        // Time Format
        case a = '${a}'; // Lowercase Ante meridiem and Post meridiem am or pm
        case A = '${A}'; // Uppercase Ante meridiem and Post meridiem AM or PM
        case B = '${B}'; // Swatch Internet time 000 through 999
        case g = '${g}'; // 12-hour format of an hour without leading zeros 1 through 12
        case G = '${G}'; // 24-hour format of an hour without leading zeros 0 through 23
        case h = '${h}'; // 12-hour format of an hour with leading zeros 01 through 12
        case H = '${H}'; // 24-hour format of an hour with leading zeros 01 through 23
        case i = '${i}'; // Minutes with leading zeros 01 through 59
        case s = '${s}'; // Seconds with leading zeros 00 through 59
        case c = '${c}'; // 2004-02-12T15:19:21
        case r = '${r}'; // Thu, 21 Dec 2000 16:01:07
        case u = '${u}'; // Unix Timestamp (seconds since Jan 1 1970 00:00:00)

        // Runtime Macros
        case CURRENT_WORKING_DIRECTORY = '${CWD}';
        case PROCESS_ID = '${PID}';
        case USER_ID = '${UID}';
        case GLOBAL_ID = '${GID}';
        case USER_HOME_PATH = '${HOME}';

        // Project Macros
        case PROJECT_PATH = '${PROJECT_PATH}';
        case DEFAULT_BUILD_CONFIGURATION = '${DEFAULT_BUILD_CONFIGURATION}';

        // Assembly Macros
        case ASSEMBLY_NAME = '${ASSEMBLY.NAME}';
        case ASSEMBLY_PACKAGE = '${ASSEMBLY.PACKAGE}';
        case ASSEMBLY_VERSION = '${ASSEMBLY.VERSION}';
        case ASSEMBLY_URL = '${ASSEMBLY.URL}';
        case ASSEMBLY_LICENSE = '${ASSEMBLY.LICENSE}';
        case ASSEMBLY_DESCRIPTION = '${ASSEMBLY.DESCRIPTION}';
        case ASSEMBLY_AUTHOR = '${ASSEMBLY.AUTHOR}';
        case ASSEMBLY_ORGANIZATION = '${ASSEMBLY.ORGANIZATION}';
        case ASSEMBLY_PRODUCT = '${ASSEMBLY.PRODUCT}';
        case ASSEMBLY_COPYRIGHT = '${ASSEMBLY.COPYRIGHT}';
        case ASSEMBLY_TRADEMARK = '${ASSEMBLY.TRADEMARK}';

        // Compiler Runtime Macros
        case COMPILE_TIMESTAMP = '${COMPILE_TIMESTAMP}';
        case NCC_BUILD_VERSION = '${NCC_BUILD_VERSION}';
        case BUILD_OUTPUT_PATH = '${BUILD_OUTPUT_PATH}';

        /**
         * Handles built-in macro variables and returns their values.
         *
         * @param string $input The macro variable to resolve.
         * @return string|null The resolved value or null if not a built-in macro.
         */
        private static function builtinHandler(string $input): ?string
        {
            $currentTime = time(); // Tiny optimization

            return match(self::tryFrom($input))
            {
                // DateTime Macros
                self::d => date('d', $currentTime),
                self::D => date('D', $currentTime),
                self::j => date('j', $currentTime),
                self::l => date('l', $currentTime),
                self::N => date('N', $currentTime),
                self::S => date('S', $currentTime),
                self::w => date('w', $currentTime),
                self::z => date('z', $currentTime),
                self::W => date('W', $currentTime),
                self::F => date('F', $currentTime),
                self::m => date('m', $currentTime),
                self::M => date('M', $currentTime),
                self::n => date('n', $currentTime),
                self::t => date('t', $currentTime),
                self::L => date('L', $currentTime),
                self::o => date('o', $currentTime),
                self::Y => date('Y', $currentTime),
                self::y => date('y', $currentTime),
                self::a => date('a', $currentTime),
                self::A => date('A', $currentTime),
                self::B => date('B', $currentTime),
                self::g => date('g', $currentTime),
                self::G => date('G', $currentTime),
                self::h => date('h', $currentTime),
                self::H => date('H', $currentTime),
                self::i => date('i', $currentTime),
                self::s => date('s', $currentTime),
                self::c => date('c', $currentTime),
                self::r => date('r', $currentTime),
                self::u => $currentTime,

                // Runtime Macros
                self::CURRENT_WORKING_DIRECTORY => getcwd(),

                // No matches
                default => null
            };
        }

        /**
         * Translates macros in a given string.
         *
         * @param string $input The input string containing macro variables.
         * @param bool $strict Whether to enable strict mode for unresolved macros.
         * @param callable|null $handle Optional callback to resolve macro values.
         * @param int $depth Internal parameter to track recursion depth.
         * @return string The string with translated macro variables.
         * @throws RuntimeException If strict mode is enabled and unresolved macros are found, or if max recursion depth is reached.
         */
        public static function fromInput(string $input, bool $strict=false, ?callable $handle=null, int $depth=0): string
        {
            // Prevent infinite recursion
            if ($depth > 100)
            {
                throw new RuntimeException("Maximum recursion depth reached while translating macros in: {$input}");
            }

            // Pattern to match macro variables like ${PROJECT_PATH} or ${ASSEMBLY.NAME}
            $pattern = '/\$\{([A-Za-z_.]+)}/';
            $unresolvedMacros = [];

            // Perform replacement recursively
            $result = preg_replace_callback($pattern, function($matches) use ($handle, &$unresolvedMacros)
            {
                $macroName = $matches[1]; // The macro name without ${ }
                $fullMacro = $matches[0]; // The full match including ${ }

                // Always check built-in macros first
                $builtinValue = self::builtinHandler($fullMacro);
                if ($builtinValue !== null)
                {
                    return $builtinValue;
                }

                // If a callback handler is provided, use it to resolve the macro
                if ($handle !== null) 
                {
                    $value = $handle($fullMacro);

                    // If handler returns null or the same macro, it couldn't be resolved
                    if ($value === null || $value === $fullMacro)
                    {
                        $unresolvedMacros[] = $fullMacro;
                        return $fullMacro; // Keep the original macro
                    }

                    return $value;
                }

                // No handler provided, mark as unresolved
                $unresolvedMacros[] = $fullMacro;
                return $fullMacro;
            }, $input);

            // Check if the result contains macros and the result changed (recursive processing)
            // This handles cases where macro replacement introduces new macros
            if ($result !== $input && preg_match($pattern, $result))
            {
                // Recursively translate, passing the depth parameter
                $result = self::fromInput($result, $strict, $handle, $depth + 1);
            }

            // If strict mode is enabled and there are unresolved macros, throw an exception
            // Only check this at the top level (depth 0) to collect all unresolved macros
            if ($strict && $depth === 0 && preg_match($pattern, $result))
            {
                throw new RuntimeException("Unable to translate one or more macro variables in: {$input}");
            }

            return $result;
        }

        /**
         * Translates macros in all string values of an array recursively.
         *
         * @param array $input The input array with potential macro variables.
         * @param bool $strict Whether to enable strict mode for unresolved macros.
         * @param callable|null $handle Optional callback to resolve macro values.
         * @return array The array with translated macro variables.
         * @throws RuntimeException If strict mode is enabled and unresolved macros are found.
         */
        public static function fromArray(array $input, bool $strict=false, ?callable $handle=null): array
        {
            $output = [];
            foreach($input as $key => $value)
            {
                if(is_string($value))
                {
                    $output[$key] = self::fromInput($value, $strict, $handle);
                }
                elseif(is_array($value))
                {
                    $output[$key] = self::fromArray($value, $strict, $handle);
                }
                else
                {
                    $output[$key] = $value;
                }
            }

            return $output;
        }
    }
