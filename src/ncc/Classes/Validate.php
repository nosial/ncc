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

    namespace ncc\Classes;

    class Validate
    {
        /**
         * Validates a given package name such as "com.example.package" with a similar naming convention to Java's
         * package names where a Group ID and Artifact ID is used.
         *
         * @param string $packageName The package name to validate.
         * @return bool True if the package name is valid, false otherwise.
         */
        public static function packageName(string $packageName): bool
        {
            // Regex to match package names like "com.example.package"
            return preg_match('/^[a-zA-Z]+(\.[a-zA-Z][a-zA-Z0-9]*)+$/', $packageName) === 1;
        }

        /**
         * Validates if the given string is a valid semantic version.
         *
         * @param string $version The version string to validate.
         * @return bool True if the version is valid, false otherwise.
         */
        public static function version(string $version): bool
        {
            // Simple regex to validate semantic versioning (e.g., 1.0.0, 2.1.3-beta, 0.0.1-alpha+001)
            return preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*)?(\+[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*)?$/', $version) === 1;
        }

        /**
         * Validates if the given string is a well-formed URL.
         *
         * @param string $url The URL to validate.
         * @return bool True if the URL is valid, false otherwise.
         */
        public static function url(string $url): bool
        {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }
    }