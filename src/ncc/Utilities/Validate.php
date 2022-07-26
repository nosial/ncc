<?php

    namespace ncc\Utilities;

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

    }