<?php

    namespace SimpleLibrary;

    class StringHelper
    {
        /**
         * Reverses a string
         *
         * @param string $input
         * @return string
         */
        public static function reverse(string $input): string
        {
            return strrev($input);
        }

        /**
         * Converts string to uppercase
         *
         * @param string $input
         * @return string
         */
        public static function uppercase(string $input): string
        {
            return strtoupper($input);
        }

        /**
         * Converts string to lowercase
         *
         * @param string $input
         * @return string
         */
        public static function lowercase(string $input): string
        {
            return strtolower($input);
        }

        /**
         * Checks if string contains substring
         *
         * @param string $haystack
         * @param string $needle
         * @return bool
         */
        public static function contains(string $haystack, string $needle): bool
        {
            return str_contains($haystack, $needle);
        }
    }
