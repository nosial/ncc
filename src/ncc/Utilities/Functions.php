<?php

    namespace ncc\Utilities;

    class Functions
    {
        /**
         * Calculates a byte-code representation of the input using CRC32
         *
         * @param string $input
         * @return int
         */
        public static function cbc(string $input): int
        {
            return hexdec(hash('crc32', $input));
        }

        /**
         * Returns the specified of a value of an array using plaintext, if none is found it will
         * attempt to use the cbc method to find the selected input, if all fails then null will be returned.
         *
         * @param array $data
         * @param string $select
         * @return mixed|null
         */
        public static function array_bc(array $data, string $select)
        {
            if(isset($data[$select]))
                return $data[$select];

            if(isset($data[self::cbc($select)]))
                return $data[self::cbc($select)];

            return null;
        }
    }