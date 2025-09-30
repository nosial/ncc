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

    class Utilities
    {
        /**
         * Parses the package source string into its components: organization, package name, version, and repository.
         * Returns null if the input string is invalid.
         *
         * @param string $sourceString The source string to parse eg; "organization/name=version@repository".
         * @return array|null An associative array with keys 'organization', 'package_name', 'version', and 'repository', or null if invalid.
         */
        public static function parsePackageSource(string $sourceString): ?array
        {
            if (trim($sourceString) === '')
            {
                return null;
            }

            // Capture organization and package name separately, but also keep the full package
            $pattern = '/^(?P<organization>[a-z](?:[a-z0-9._-]*[a-z0-9])?)\/(?P<package_name>[a-z](?:[a-z0-9._-]*[a-z0-9])?)(?:=(?P<version>[^\s@=]*))?@(?P<repository>[a-z](?:[a-z0-9._-]*[a-z0-9])?)$/ix';

            if (!preg_match($pattern, $sourceString, $matches))
            {
                return null;
            }

            return [
                'organization' => $matches['organization'],
                'package_name' => $matches['package_name'],
                'version' => (!empty($matches['version'])) ? $matches['version'] : 'latest',
                'repository' => $matches['repository']
            ];
        }

        /**
         * Cleans an array by removing null values and empty nested arrays.
         * Recursively processes nested arrays to ensure all levels are cleaned.
         *
         * @param array $array The array to clean.
         * @return array The cleaned array with null values and empty arrays removed.
         */
        public static function cleanArray(array $array): array
        {
            $cleaned = [];

            foreach ($array as $key => $value)
            {
                if ($value === null)
                {
                    // Skip null values entirely
                    continue;
                }

                if (is_array($value))
                {
                    // Recursively clean nested arrays
                    $cleanedValue = self::cleanArray($value);

                    // Only include the key if the cleaned array is not empty
                    if (!empty($cleanedValue))
                    {
                        $cleaned[$key] = $cleanedValue;
                    }
                    // If cleaned array is empty, skip this key (don't add empty arrays)
                }
                else
                {
                    // Keep non-null, non-array values
                    $cleaned[$key] = $value;
                }
            }

            return $cleaned;
        }
    }