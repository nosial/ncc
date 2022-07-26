<?php

    namespace ncc\Utilities;

    use ncc\Abstracts\ConsoleColors;

    class Console
    {
        /**
         * Inline Progress bar, created by dealnews.com.
         *
         * // TODO: Add non-inline option
         * @copyright Copyright (c) 2010, dealnews.com, Inc. All rights reserved.
         * @param int $value
         * @param int $total
         * @param int $size
         * @param array $flags
         * @return void
         */
        public static function inlineProgressBar(int $value, int $total, int $size=38, array $flags=[])
        {
            static $start_time;

            // if we go over our bound, just ignore it
            if($value > $total) return;

            if(empty($start_time)) $start_time=time();
            $now = time();

            $perc=(double)($value/$total);

            $bar=floor($perc*$size);

            $status_bar="\r[ ";
            $status_bar.=str_repeat("=", $bar);
            if($bar<$size){
                $status_bar.=">";
                $status_bar.=str_repeat(" ", $size-$bar);
            } else {
                $status_bar.="=";
            }

            $disp=number_format($perc*100, 0);

            $status_bar.=" ] $disp%  $value/$total";

            $rate = ($now-$start_time)/$value;
            $left = $total - $value;
            $eta = round($rate * $left, 2);

            $elapsed = $now - $start_time;

            $status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

            echo "$status_bar  ";

            flush();

            // when done, send a newline
            if($value == $total) {
                echo "\n";
            }
        }

        /**
         * Simple output function
         *
         * @param string $message
         * @param bool $newline
         * @return void
         */
        public static function out(string $message, bool $newline=true)
        {
            if($newline)
            {
                print($message . PHP_EOL);
                return;
            }

            print($message);
        }

        /**
         * Formats the text to have a different color and returns the formatted value
         *
         * @param string $input The input of the text value
         * @param string $color_code The color code of the escaped character (\e[91m)
         * @param bool $persist If true, the formatting will terminate in the default color
         * @return string
         */
        public static function formatColor(string $input, string $color_code, bool $persist=true): string
        {
            if($persist)
            {
                return $color_code . $input . ConsoleColors::Default;
            }

            return $color_code . $input;
        }

        /**
         * Prints out a warning output
         *
         * @param string $message
         * @param bool $newline
         * @return void
         */
        public static function outWarning(string $message, bool $newline=true)
        {
            self::out(self::formatColor(ConsoleColors::Yellow, 'Warning: ') . $message, $newline);
        }
    }