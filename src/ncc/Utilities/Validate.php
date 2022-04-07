<?php

    namespace ncc\Utilities;

    use ncc\Abstracts\RegexPatterns;

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
    }