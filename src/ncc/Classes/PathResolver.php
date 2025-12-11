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

    use RuntimeException;

    class PathResolver
    {
        /**
         * Returns the home path of the user directory
         *
         * @return string
         */
        public static function getUserHome(): string
        {
            $home = getenv('HOME');
            if($home === false || $home === '')
            {
                $home = getenv('HOMEPATH');
                if($home === false || $home === '')
                {
                    throw new RuntimeException("Could not resolve user home directory");
                }
                $drive = getenv('HOMEDRIVE');
                if($drive !== false && $drive !== '')
                {
                    $home = $drive . $home;
                }
            }

            return rtrim($home, DIRECTORY_SEPARATOR);
        }

        /**
         * Checks if running as root/system user
         *
         * @return bool
         */
        private static function isSystemUser(): bool
        {
            // Check if running as root on Unix-like systems
            if (function_exists('posix_geteuid') && posix_geteuid() === 0)
            {
                return true;
            }

            // Check if running with elevated privileges on Windows
            if (PHP_OS_FAMILY === 'Windows')
            {
                $identity = shell_exec('whoami /groups 2>nul | findstr /i "S-1-16-12288" 2>nul');
                if ($identity !== null && trim($identity) !== '')
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Returns the system-level package manager location
         * This always returns a valid path regardless of user privileges
         *
         * @return string
         */
        public static function getSystemPackageManagerLocation(): string
        {
            if (PHP_OS_FAMILY === 'Windows')
            {
                $systemDrive = getenv('SystemDrive');
                if ($systemDrive === false || $systemDrive === '')
                {
                    $systemDrive = getenv('HOMEDRIVE');
                    if ($systemDrive === false || $systemDrive === '')
                    {
                        $systemDrive = 'C:';
                    }
                }
                return $systemDrive . DIRECTORY_SEPARATOR . 'ncc';
            }

            return DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ncc';
        }

        /**
         * Returns the user-level package manager location
         * Returns null when running as root/system user
         *
         * @return string|null
         */
        public static function getUserPackageManagerLocation(): ?string
        {
            if (self::isSystemUser())
            {
                return null;
            }

            return self::getUserHome() . DIRECTORY_SEPARATOR . 'ncc';
        }

        /**
         * Returns all possible package locations in order of priority
         * User-level location is checked first (if applicable), then system-level
         *
         * @return array<string>
         */
        public static function getAllPackageLocations(): array
        {
            $locations = [];

            // Include user-level location first if not running as system user
            $userLocation = self::getUserPackageManagerLocation();
            if ($userLocation !== null)
            {
                $locations[] = $userLocation;
            }

            // Always include system-level location
            $locations[] = self::getSystemPackageManagerLocation();

            return $locations;
        }
    }