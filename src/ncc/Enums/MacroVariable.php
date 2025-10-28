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
        // Generic Macros
        case DATE = '${DATE}';
        case TIME = '${TIME}';
        case DATETIME = '${DATETIME}';
        case YEAR = '${YEAR}';
        case MONTH = '${MONTH}';
        case DAY = '${DAY}';
        case HOUR = '${HOUR}';
        case MINUTE = '${MINUTE}';
        case SECOND = '${SECOND}';

        // Runtime Macros
        case PROJECT_PATH = '${PROJECT_PATH}';
        case CURRENT_WORKING_DIRECTORY = '${CWD}';

        // Project Macros
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

        /**
         * Handles built-in macro variables and returns their values.
         *
         * @param string $input The macro variable to resolve.
         * @return string|null The resolved value or null if not a built-in macro.
         */
        private static function builtinHandler(string $input): ?string
        {
            return match(self::tryFrom($input))
            {
                self::DATE => date('Y-m-d'),
                self::TIME => date('H:i:s'),
                self::DATETIME => date('Y-m-d H:i:s'),
                self::YEAR => date('Y'),
                self::MONTH => date('m'),
                self::DAY => date('d'),
                self::HOUR => date('H'),
                self::MINUTE => date('i'),
                self::SECOND => date('s'),
                self::CURRENT_WORKING_DIRECTORY => getcwd(),
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
            $pattern = '/\$\{([A-Z_.]+)}/';
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
