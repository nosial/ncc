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

namespace ncc\Utilities;

    use ncc\Enums\LogLevel;
    use ncc\Enums\RegexPatterns;
    use ncc\Enums\Scopes;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Validate
    {
        /**
         * Determines if the runtime meets the required extensions
         *
         * @return array
         */
        public static function requiredExtensions(): array
        {
            $requirements = [
                'zlib',
                'libxml',
                'curl',
                'ctype',
                'json',
                'mbstring',
                'posix',
                'ctype',
                'tokenizer'
            ];

            $results = [];

            foreach($requirements as $ext)
            {
                if(in_array(strtolower($ext), get_loaded_extensions()))
                {
                    $results[$ext] = true;
                }
                else
                {
                    $results[$ext] = false;
                }
            }

            return $results;
        }

        /**
         * Validates the version number
         *
         * @param string $input
         * @return bool
         */
        public static function version(string $input): bool
        {
            if(preg_match(RegexPatterns::SEMANTIC_VERSIONING_2, $input))
            {
                return true;
            }

            if(preg_match(RegexPatterns::COMPOSER_VERSION_FORMAT, $input))
            {
                return true;
            }

            if(preg_match(RegexPatterns::PYTHON_VERSION_FORMAT, $input))
            {
                return true;
            }

            return false;
        }

        /**
         * Validates the scope
         *
         * @param string $input
         * @param bool $resolve
         * @return bool
         * @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection
         */
        public static function scope(string $input, bool $resolve=true): bool
        {
            if($resolve)
            {
                $input = Resolver::resolveScope($input);
            }

            switch($input)
            {
                case Scopes::SYSTEM->value:
                case Scopes::USER->value:
                    return true;

                default:
                    return false;
            }
        }

        /**
         * Validates if the package name is valid
         *
         * @param $input
         * @return bool
         */
        public static function packageName($input): bool
        {
            if($input === null)
            {
                return false;
            }

            if(!preg_match(RegexPatterns::PACKAGE_NAME_FORMAT, $input))
            {
                return false;
            }

            return true;
        }

        /**
         * Validates the name of the project
         *
         * @param $input
         * @return bool
         */
        public static function projectName($input): bool
        {
            if($input === null)
            {
                return false;
            }

            if($input === '')
            {
                return false;
            }

            if(strlen($input) > 126)
            {
                return false;
            }

            return true;
        }

        /**
         * Determines if a Unix filepath is valid
         *
         * @param $input
         * @return bool
         */
        public static function unixFilepath($input): bool
        {
            if(preg_match(RegexPatterns::UNIX_PATH, $input))
            {
                return true;
            }

            return false;
        }

        /**
         * Validates if the constant name is valid
         *
         * @param $input
         * @return bool
         */
        public static function constantName($input): bool
        {
            if($input === null)
            {
                return false;
            }

            if(!preg_match(RegexPatterns::CONSTANT_NAME, $input))
            {
                return false;
            }

            return true;
        }

        /**
         * Validates the execution policy name
         *
         * @param string $input
         * @return bool
         */
        public static function executionPolicyName(string $input): bool
        {
            if(!preg_match(RegexPatterns::EXECUTION_POLICY_NAME, $input))
            {
                return false;
            }

            return true;
        }

        /**
         * Determines if the given log level is valid or not
         *
         * @param string $input
         * @return bool
         */
        public static function checkLogLevel(string $input): bool
        {
            if(!in_array(strtolower($input), LogLevel::ALL))
            {
                return false;
            }

            return true;
        }

        /**
         * Determines if given input exceeds the path length limit
         *
         * @param string $input
         * @return bool
         */
        public static function exceedsPathLength(string $input): bool
        {
            return strlen($input) > 4096;
        }

        /**
         * Determines if the input is considered "name friendly" and does not
         * contain any special characters, spaces or weird prefixes
         *
         * @param string $input
         * @return bool
         */
        public static function nameFriendly(string $input): bool
        {
            if($input === '')
            {
                return false;
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $input))
            {
                return false;
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $input))
            {
                return false;
            }

            return true;
        }

        /**
         * Validates if the given input is a valid path name
         *
         * @param string $input
         * @return bool
         */
        public static function pathName(string $input): bool
        {
            if($input === '')
            {
                return false;
            }

            if (!preg_match('/^[a-zA-Z0-9_\-\/]+$/', $input))
            {
                return false;
            }

            if (!preg_match('/^[a-zA-Z_\-\/][a-zA-Z0-9_\-\/]*$/', $input))
            {
                return false;
            }

            return true;
        }

        /***
         * Returns True if the given remote package input is valid
         *
         * @param string $input
         * @return bool
         */
        public static function remotePackageInput(string $input): bool
        {
            if(preg_match(RegexPatterns::REMOTE_PACKAGE, $input))
            {
                return true;
            }

            return false;
        }
    }