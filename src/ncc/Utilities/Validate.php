<?php

    namespace ncc\Utilities;

    use ncc\Abstracts\RegexPatterns;
    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Validate
    {
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
    }