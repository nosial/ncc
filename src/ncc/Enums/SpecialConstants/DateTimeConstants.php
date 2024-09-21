<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    namespace ncc\Enums\SpecialConstants;

    enum DateTimeConstants : string
    {
        // Day Format

        /**
         * Day of the month, 2 digits with leading zeros
         */
        case d = '%d%'; // 01 through 31

        /**
         * A textual representation of a day, three letters
         */
        case D = '%D%'; // Mon through Sun

        /**
         * Day of the month without leading zeros
         */
        case j = '%j%'; // 1 through 31

        /**
         * A full textual representation of the day of the week
         */
        case l = '%l%'; // Sunday through Saturday

        /**
         * ISO 8601 numeric representation of the day of the week
         */
        case N = '%N%'; // 1 (Monday) to 7 (Sunday)

        /**
         * English ordinal suffix for the day of the month, 2 characters
         */
        case S = '%S%'; // st, nd, rd, th

        /**
         * Numeric representation of the day of the week
         */
        case w = '%w%'; // 0 (sunday) through 6 (Saturday)

        /**
         * The day of the year (starting from 0)
         */
        case z = '%z%'; // 0 through 365



        // Week Format

        /**
         * ISO 8601 week number of year, weeks starting on Monday
         */
        case W = '%W%'; // 42 (42nd week in year)



        // Month Format

        /**
         * A full textual representation of a month, such as January or March
         */
        case F = '%F%'; // January through December

        /**
         * Numeric representation of a month, with leading zeros
         */
        case m = '%m%'; // 01 through 12

        /**
         * A short textual representation of a month, three letters
         */
        case M = '%M%'; // Jan through Dec

        /**
         * Numeric representation of a month, without leading zeros
         */
        case n = '%n%'; // 1 through 12

        /**
         * Number of days in the given month
         */
        case t = '%t%'; // 28 through 31



        // Year format
        /**
         * Whether it's a leap year
         */
        case L = '%L%'; // 1 (leap year), 0 otherwise

        /**
         * ISO 8601 week-numbering year. This has the same value as Y,
         * except that if the ISO week number (W) belongs to the previous
         * or next year, that year is used instead.
         */
        case o = '%o%'; // Same as Y, except that it use week number to decide which year it falls onto

        /**
         * A full numeric representation of a year, at least 4 digits, with - for years BCE.
         */
        case Y = '%Y%'; // 1991, 2012, 2014, ...

        /**
         * A two digit representation of a year
         */
        case y = '%y%'; // 91, 12, 14, ...

        // Time Format
        /**
         * Lowercase Ante meridiem and Post meridiem
         */
        case a = '%a%'; // am or pm

        /**
         * Uppercase Ante meridiem and Post meridiem
         */
        case A = '%A%'; // AM or PM

        /**
         * Swatch Internet time
         */
        case B = '%B%'; // 000 through 999

        /**
         * 12-hour format of an hour without leading zeros
         */
        case g = '%g%'; // 1 through 12

        /**
         * 24-hour format of an hour without leading zeros
         */
        case G = '%G%'; // 0 through 23

        /**
         * 12-hour format of an hour with leading zeros
         */
        case h = '%h%'; // 01 through 12

        /**
         * 24-hour format of an hour with leading zeros
         */
        case H = '%H%'; // 01 through 23

        /**
         * Minutes with leading zeros
         */
        case i = '%i%'; // 01 through 59

        /**
         * Seconds with leading zeros
         */
        case s = '%s%'; // 00 through 59

        // DateTime format
        case c = '%c%'; // 2004-02-12T15:19:21
        case r = '%r%'; // Thu, 21 Dec 2000 16:01:07
        case u = '%u%'; // Unix Timestamp (seconds since Jan 1 1970 00:00:00)
    }