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

    use ncc\CLI\Logger;

    class Utilities
    {
        /**
         * Parses the package source string into its components: organization, package name, version, and repository.
         * Returns null if the input string is invalid.
         *
         * @param string $sourceString The source string to parse eg; "organization/name=version@repository", "organization/name@repository", "organization/name=version", or "organization/name".
         * @return array|null An associative array with keys 'organization', 'package_name', 'version', and 'repository', or null if invalid.
         */
        public static function parsePackageSource(string $sourceString): ?array
        {
            if (trim($sourceString) === '')
            {
                return null;
            }

            // Capture organization and package name separately, and optionally version and repository
            $pattern = '/^(?P<organization>[a-z](?:[a-z0-9._-]*[a-z0-9])?)\/(?P<package_name>[a-z](?:[a-z0-9._-]*[a-z0-9])?)(?:=(?P<version>[^\s@=]*))?(?:@(?P<repository>[a-z](?:[a-z0-9._-]*[a-z0-9])?))?$/ix';

            if (!preg_match($pattern, $sourceString, $matches))
            {
                return null;
            }

            return [
                'organization' => $matches['organization'],
                'package_name' => $matches['package_name'],
                'version' => (!empty($matches['version'])) ? $matches['version'] : null,
                'repository' => (!empty($matches['repository'])) ? $matches['repository'] : null
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

        /**
         * A method to return the absolute path to the project's yml file path. If the given path points ot a project.yml
         * or project.yaml path, the same value will be returned. If the given path is a directory, it will return the
         * project.yml or project.yaml file found in that directory. In any other case if no project.yml or project.yaml
         * file is found, null is returned.
         *
         * @param string $path The path to the project.yml file or directory where the file is located in
         * @return string|null The absolute path to the project configuration file
         */
       public static function getProjectConfiguration(string $path): ?string
       {
           if (!IO::exists($path))
           {
               Logger::getLogger()->debug(sprintf('File %s does not exist', $path));
               return null;
           }

           // If $path is a directory, look for project.yml or project.yaml inside it
           if (IO::isDir($path))
           {
               $ymlPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'project.yml';
               Logger::getLogger()->debug(sprintf('Checking path %s', $ymlPath));
               if (IO::isFile($ymlPath))
               {
                   return realpath($ymlPath);
               }

               $yamlPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'project.yaml';
               Logger::getLogger()->debug(sprintf('Checking path %s', $yamlPath));
               if (IO::isFile($yamlPath))
               {
                   return realpath($yamlPath);
               }

               return null;
           }

           // If $path is a file and is named 'project.yml' or 'project.yaml'
           $filename = basename($path);
           Logger::getLogger()->debug(sprintf('Checking path %s', $filename));
           if (IO::isFile($path) && ($filename === 'project.yml' || $filename === 'project.yaml'))
           {
               return realpath($path);
           }

           return null;
       }

        /**
         * Replaces all occurrences of search strings with their corresponding replacement values.
         * This method implements string replacement without using str_replace.
         *
         * @param string $input The input string to perform replacements on
         * @param array $replace An associative array where keys are strings to find and values are their replacements
         * @return string The resulting string after all replacements have been applied
         */
        public static function replaceString(string $input, array $replace): string
        {
            if (empty($replace))
            {
                return $input;
            }

            $result = $input;

            foreach ($replace as $search => $replacement)
            {
                if (!is_string($search) || !is_string($replacement))
                {
                    continue;
                }

                if ($search === '')
                {
                    continue;
                }

                $searchLen = strlen($search);
                $resultLen = strlen($result);
                $newResult = '';
                $i = 0;

                while ($i < $resultLen)
                {
                    // Check if we found the search string at current position
                    $found = true;
                    for ($j = 0; $j < $searchLen; $j++)
                    {
                        if ($i + $j >= $resultLen || $result[$i + $j] !== $search[$j])
                        {
                            $found = false;
                            break;
                        }
                    }

                    if ($found)
                    {
                        // Append the replacement string
                        $newResult .= $replacement;
                        // Skip past the search string
                        $i += $searchLen;
                    }
                    else
                    {
                        // Append the current character
                        $newResult .= $result[$i];
                        $i++;
                    }
                }

                $result = $newResult;
            }

            return $result;
        }

    }