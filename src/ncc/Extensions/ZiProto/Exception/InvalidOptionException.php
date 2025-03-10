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

    namespace ncc\Extensions\ZiProto\Exception;

    use function array_pop;
    use function count;
    use function implode;
    use InvalidArgumentException;
    use function sprintf;

    /**
     * Class InvalidOptionException
     * @package ncc\ZiProto\Exception
     */
    class InvalidOptionException extends InvalidArgumentException
    {
        /**
         * @param string $invalid_option
         * @param array $valid_options
         * @return InvalidOptionException
         */
        public static function outOfRange(string $invalid_option, array $valid_options) : self
        {
            $use = count($valid_options) > 2
                ? sprintf('one of %2$s or %1$s', array_pop($valid_options), implode(', ', $valid_options))
                : implode(' or ', $valid_options);
            return new self("Invalid option $invalid_option, use $use.");
        }
    }