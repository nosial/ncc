<?php

    namespace ncc\CLI;

    class Functions
    {
        /**
         * Simple print function with builtin EOL terminator
         *
         * @param string $out
         * @param bool $eol
         * @param int $padding
         * @param int $pad_type
         * @param string $pad_string
         * @return void
         */
        public static function print(string $out, bool $eol=true, int $padding=0, int $pad_type=0, string $pad_string=' ')
        {
            if($padding > 0)
            {
                $out = str_pad($out, $padding, $pad_string, $pad_type);
            }

            if($eol)
            {
                $out = $out . PHP_EOL;
            }

            print($out);
        }
    }