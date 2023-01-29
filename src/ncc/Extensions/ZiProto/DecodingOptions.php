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

namespace ncc\ZiProto;

    use ncc\ZiProto\Exception\InvalidOptionException;
    use ncc\ZiProto\Abstracts\Options;

    /**
     * Class DecodingOptions
     * @package ZiProto
     */
    final class DecodingOptions
    {

        /**
         * @var
         */
        private $bigIntMode;

        /**
         * DecodingOptions constructor.
         */
        private function __construct() {}

        /**
         * @return DecodingOptions
         */
        public static function fromDefaults() : self
        {
            $self = new self();
            $self->bigIntMode = Options::BIGINT_AS_STR;

            return $self;
        }

        /**
         * @param int $bitmask
         * @return DecodingOptions
         */
        public static function fromBitmask(int $bitmask) : self
        {
            $self = new self();

            $self->bigIntMode = self::getSingleOption($bitmask,
                Options::BIGINT_AS_STR |
                Options::BIGINT_AS_GMP |
                Options::BIGINT_AS_EXCEPTION
            ) ?: Options::BIGINT_AS_STR;

            return $self;
        }

        /**
         * @return bool
         */
        public function isBigIntAsStrMode() : bool
        {
            return Options::BIGINT_AS_STR === $this->bigIntMode;
        }

        /**
         * @return bool
         */
        public function isBigIntAsGmpMode() : bool
        {
            return Options::BIGINT_AS_GMP === $this->bigIntMode;
        }

        /**
         * @param int $bitmask
         * @param int $validBitmask
         * @return int
         */
        private static function getSingleOption(int $bitmask, int $validBitmask) : int
        {
            $option = $bitmask & $validBitmask;
            if ($option === ($option & -$option))
            {
                return $option;
            }

            static $map = [
                Options::BIGINT_AS_STR => 'BIGINT_AS_STR',
                Options::BIGINT_AS_GMP => 'BIGINT_AS_GMP',
                Options::BIGINT_AS_EXCEPTION => 'BIGINT_AS_EXCEPTION',
            ];

            $validOptions = [];
            for ($i = $validBitmask & -$validBitmask; $i <= $validBitmask; $i <<= 1)
            {
                $validOptions[] = __CLASS__.'::'.$map[$i];
            }

            throw InvalidOptionException::outOfRange('bigint', $validOptions);
        }
    }
