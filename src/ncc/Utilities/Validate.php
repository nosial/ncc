<?php

    namespace ncc\Utilities;

    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\RegexPatterns;
    use ncc\Abstracts\Scopes;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
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
            if(preg_match(RegexPatterns::SemanticVersioning2, $input))
                return true;

            if(preg_match(RegexPatterns::ComposerVersionFormat, $input))
                return true;

            if(preg_match(RegexPatterns::PythonVersionFormat, $input))
                return true;

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
                case Scopes::System:
                case Scopes::User:
                    return true;

                default:
                    return false;
            }
        }

        /**
         * Determines if the user has access to the given scope permission
         *
         * @param string|null $input
         * @return bool
         */
        public static function scopePermission(?string $input=null): bool
        {
            $input = Resolver::resolveScope($input);

            if($input == Scopes::System && posix_getuid() !== 0)
                return false;

            return true;
        }

        /**
         * Validates if the package name is valid
         *
         * @param $input
         * @return bool
         */
        public static function packageName($input): bool
        {
            if($input == null)
            {
                return false;
            }

            if(!preg_match(RegexPatterns::PackageNameFormat, $input))
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
            if($input == null)
            {
                return false;
            }

            if(strlen($input) == 0)
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
            if(preg_match(RegexPatterns::UnixPath, $input))
            {
                return true;
            }

            return false;
        }

        /**
         * Determines if a Windows filepath is valid
         *
         * @param $input
         * @return bool
         */
        public static function windowsFilepath($input): bool
        {
            if(preg_match(RegexPatterns::WindowsPath, $input))
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
            if($input == null)
            {
                return false;
            }

            if(!preg_match(RegexPatterns::ConstantName, $input))
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
            if($input == null)
            {
                return false;
            }

            if(!preg_match(RegexPatterns::ExecutionPolicyName, $input))
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
            if(!in_array(strtolower($input), LogLevel::All))
                return false;

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
            if(strlen($input) > 4096)
                return true;

            return false;
        }
    }