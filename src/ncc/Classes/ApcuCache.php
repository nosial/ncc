<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    use APCUIterator;

    class ApcuCache
    {
        private const string KEY_PREFIX = 'ncc_';
        private static ?bool $available = null;

        /**
         * Checks if APCu is available and enabled for ncc's use.
         *
         * @return bool True if APCu is available and NCC_DISABLE_APCU is not set
         */
        public static function isAvailable(): bool
        {
            if (self::$available === null)
            {
                self::$available =
                    extension_loaded('apcu') &&
                    function_exists('apcu_fetch') &&
                    getenv('NCC_DISABLE_APCU') === false;
            }

            return self::$available;
        }

        /**
         * Stores a value in APCu with the ncc_ prefix.
         *
         * @param string $key The cache key (without prefix)
         * @param mixed $value The value to store
         * @param int $ttl Time to live in seconds (0 = permanent until eviction or cleared)
         * @return bool True on success, false on failure
         */
        public static function set(string $key, mixed $value, int $ttl = 0): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_store(self::KEY_PREFIX . $key, $value, $ttl);
        }

        /**
         * Retrieves a value from APCu.
         *
         * @param string $key The cache key (without prefix)
         * @return mixed The cached value or null if not found
         */
        public static function get(string $key): mixed
        {
            if (!self::isAvailable())
            {
                return null;
            }

            $success = false;
            $result = apcu_fetch(self::KEY_PREFIX . $key, $success);
            return $success ? $result : null;
        }

        /**
         * Checks if a key exists in APCu.
         *
         * @param string $key The cache key (without prefix)
         * @return bool True if the key exists
         */
        public static function exists(string $key): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_exists(self::KEY_PREFIX . $key);
        }

        /**
         * Deletes a single key from APCu.
         *
         * @param string $key The cache key (without prefix)
         * @return bool True on success, false if the key did not exist
         */
        public static function delete(string $key): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_delete(self::KEY_PREFIX . $key);
        }

        /**
         * Clears all ncc-prefixed entries from APCu.
         *
         * This is called when the package lock is updated to ensure stale
         * cached data is not served across package installs/uninstalls.
         *
         * Only entries prefixed with "ncc_" are removed, leaving other
         * applications' APCu data untouched.
         */
        public static function clear(): void
        {
            if (!self::isAvailable())
            {
                return;
            }

            $iterator = new APCUIterator('/^' . preg_quote(self::KEY_PREFIX, '/') . '/');
            apcu_delete($iterator);
        }
    }
