<?php

    namespace ncc\Utilities;

    class RuntimeCache
    {
        /**
         * An array of cache entries
         *
         * @var array
         */
        private static $cache = [];

        /**
         * Sets a value, returns the value
         *
         * @param $key
         * @param $value
         * @return mixed
         */
        public static function set($key, $value)
        {
            self::$cache[$key] = $value;
            return $value;
        }

        /**
         * Gets an existing value, null if it doesn't exist
         *
         * @param $key
         * @return mixed|null
         */
        public static function get($key)
        {
            if(isset(self::$cache[$key]))
                return self::$cache[$key];

            return null;
        }
    }