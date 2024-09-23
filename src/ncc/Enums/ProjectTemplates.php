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

    namespace ncc\Enums;

    enum ProjectTemplates : string
    {
        /**
         * A template that is used to create a PHP library project
         */
        case PHP_LIBRARY = 'phplib';

        /**
         * A template that is used to create a PHP CLI application project
         */
        case PHP_CLI = 'phpcli';

        /**
         * A template for generating a Makefile for the PHP project
         */
        case PHP_MAKE = 'phpmake';

        /**
         * A template used for creating PHP Unit testing bootstrap
         */
        case PHP_UNIT = 'phpunit';

        /**
         * Template that combines PHP_LIBRARY, PHP_MAKE and PHP_UNIT in one
         */
        case PHP_LIBRARY_FULL = 'phplib_full';

        /**
         * Template that combines PHP_LIBRARY, PHP_MAKE, PHP_UNIT and PHP_CLI in one
         */
        case PHP_CLI_FULL = 'phpcli_full';

        /**
         * Template that applies a GitHub workflow CI that builds the project, tests the project and creates
         * automatic builds for releases
         */
        case PHP_GITHUB_CI = 'phpci_github';

        /**
         * Suggests the closest matching `ProjectTemplates` instance based on the given input string.
         *
         * @param string $input The input string to compare against.
         * @return ProjectTemplates|null The closest matching `ProjectTemplates` instance, or null if no close match is found.
         */
        public static function suggest(string $input): ?ProjectTemplates
        {
            $closest = null;
            $shortest_distance = -1;

            foreach (self::cases() as $case)
            {
                $distance = levenshtein($input, $case->value);

                if ($distance === 0)
                {
                    return $case;
                }

                if ($shortest_distance === -1 || $distance < $shortest_distance)
                {
                    $closest = $case;
                    $shortest_distance = $distance;
                }
            }

            return $closest ?: null;
        }
    }