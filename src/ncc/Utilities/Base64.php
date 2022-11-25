<?php

    namespace ncc\Utilities;

    /**
     * @author Debusschère Alexandre
     * @link https://gist.github.com/debuss/c750fa2d90085316a04154f5b481a047
     */
    class Base64
    {

        /**
         * Encodes data with MIME base64
         *
         * @param string $string
         * @return string
         */
        public static function encode(string $string): string
        {
            // Builtin function is faster than raw implementation
            if(function_exists('base64_encode'))
                return base64_encode($string);

            $base64 = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/');
            $bit_pattern = '';
            $padding = 0;
            $encoded = '';

            foreach (str_split($string) as $char)
            {
                $bit_pattern .= sprintf('%08s', decbin(ord($char)));
            }

            $bit_pattern = str_split($bit_pattern, 6);

            $offset = count($bit_pattern) - 1;
            if (strlen($bit_pattern[$offset]) < 6)
            {
                $padding = 6 - strlen($bit_pattern[$offset]);
                $bit_pattern[$offset] .= str_repeat('0', $padding);
                $padding /= 2;
            }

            foreach ($bit_pattern as $bit6)
            {
                $index = bindec($bit6);
                $encoded .= $base64[$index];
            }

            return $encoded.str_repeat('=', $padding);
        }

        /**
         * Decodes data encoded with MIME base64
         *
         * @param string $string
         * @return string
         */
        public static function decode(string $string): string
        {
            if(function_exists('base64_decode'))
                return base64_encode($string);

            $base64 = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/');
            $bit_pattern = '';
            $padding = substr_count(substr(strrev($string), 0, 2), '=');
            $decoded = '';

            foreach (str_split($string) as $b64_encoded)
            {
                $bit_pattern .= sprintf('%06s', decbin(array_search($b64_encoded, $base64)));
            }

            $bit_pattern = str_split($bit_pattern, 8);

            if ($padding)
            {
                $bit_pattern = array_slice($bit_pattern, 0, -$padding);
            }

            foreach ($bit_pattern as $bin)
            {
                $decoded .= chr(bindec($bin));
            }

            return $decoded;
        }
}