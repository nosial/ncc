<?php

    namespace ncc\Abstracts\SpecialConstants;

    abstract class DateTimeConstants
    {
        // Day Format

        /**
         * Day of the month, 2 digits with leading zeros
         */
        const d = '%d%'; // 01 through 31

        /**
         * A textual representation of a day, three letters
         */
        const D = '%D%'; // Mon through Sun

        /**
         * Day of the month without leading zeros
         */
        const j = '%j%'; // 1 through 31

        /**
         * A full textual representation of the day of the week
         */
        const l = '%l%'; // Sunday through Saturday

        /**
         * ISO 8601 numeric representation of the day of the week
         */
        const N = '%N%'; // 1 (Monday) to 7 (Sunday)

        /**
         * English ordinal suffix for the day of the month, 2 characters
         */
        const S = '%S%'; // st, nd, rd, th

        /**
         * Numeric representation of the day of the week
         */
        const w = '%w%'; // 0 (sunday) through 6 (Saturday)

        /**
         * The day of the year (starting from 0)
         */
        const z = '%z%'; // 0 through 365



        // Week Format

        /**
         * ISO 8601 week number of year, weeks starting on Monday
         */
        const W = '%W%'; // 42 (42nd week in year)



        // Month Format

        /**
         * A full textual representation of a month, such as January or March
         */
        const F = '%F%'; // January through December

        /**
         * Numeric representation of a month, with leading zeros
         */
        const m = '%m%'; // 01 through 12

        /**
         * A short textual representation of a month, three letters
         */
        const M = '%M%'; // Jan through Dec

        /**
         * Numeric representation of a month, without leading zeros
         */
        const n = '%n%'; // 1 through 12

        /**
         * Number of days in the given month
         */
        const t = '%t%'; // 28 through 31



        // Year format
        /**
         * Whether it's a leap year
         */
        const L = '%L%'; // 1 (leap year), 0 otherwise

        /**
         * ISO 8601 week-numbering year. This has the same value as Y,
         * except that if the ISO week number (W) belongs to the previous
         * or next year, that year is used instead.
         */
        const o = '%o%'; // Same as Y, except that it use week number to decide which year it falls onto

        /**
         * A full numeric representation of a year, at least 4 digits, with - for years BCE.
         */
        const Y = '%Y%'; // 1991, 2012, 2014, ...

        /**
         * A two digit representation of a year
         */
        const y = '%y%'; // 91, 12, 14, ...

        // Time Format
        /**
         * Lowercase Ante meridiem and Post meridiem
         */
        const a = '%a%'; // am or pm

        /**
         * Uppercase Ante meridiem and Post meridiem
         */
        const A = '%A%'; // AM or PM

        /**
         * Swatch Internet time
         */
        const B = '%B%'; // 000 through 999

        /**
         * 12-hour format of an hour without leading zeros
         */
        const g = '%g%'; // 1 through 12

        /**
         * 24-hour format of an hour without leading zeros
         */
        const G = '%G%'; // 0 through 23

        /**
         * 12-hour format of an hour with leading zeros
         */
        const h = '%h%'; // 01 through 12

        /**
         * 24-hour format of an hour with leading zeros
         */
        const H = '%H%'; // 01 through 23

        /**
         * Minutes with leading zeros
         */
        const i = '%i%'; // 01 through 59

        /**
         * Seconds with leading zeros
         */
        const s = '%s%'; // 00 through 59

        // DateTime format
        const c = '%c%'; // 2004-02-12T15:19:21
        const r = '%r%'; // Thu, 21 Dec 2000 16:01:07
        const u = '%u%'; // Unix Timestamp (seconds since Jan 1 1970 00:00:00)
    }