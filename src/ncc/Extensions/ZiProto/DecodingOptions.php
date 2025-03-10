<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Extensions\ZiProto;

    use ncc\Extensions\ZiProto\Exception\InvalidOptionException;
    use ncc\Extensions\ZiProto\Abstracts\Options;

    /**
     * Class DecodingOptions
     * @package ZiProto
     */
    final class DecodingOptions
    {

        /**
         * @var int
         */
        private $big_int_mode;

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
            $self->big_int_mode = Options::BIGINT_AS_STR;

            return $self;
        }

        /**
         * @param int $bitmask
         * @return DecodingOptions
         */
        public static function fromBitmask(int $bitmask) : self
        {
            $self = new self();

            $self->big_int_mode = self::getSingleOption($bitmask,
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
            return Options::BIGINT_AS_STR === $this->big_int_mode;
        }

        /**
         * @return bool
         */
        public function isBigIntAsGmpMode() : bool
        {
            return Options::BIGINT_AS_GMP === $this->big_int_mode;
        }

        /**
         * @param int $bitmask
         * @param int $valid_bitmask
         * @return int
         */
        private static function getSingleOption(int $bitmask, int $valid_bitmask) : int
        {
            $option = $bitmask & $valid_bitmask;
            if ($option === ($option & -$option))
            {
                return $option;
            }

            static $map = [
                Options::BIGINT_AS_STR => 'BIGINT_AS_STR',
                Options::BIGINT_AS_GMP => 'BIGINT_AS_GMP',
                Options::BIGINT_AS_EXCEPTION => 'BIGINT_AS_EXCEPTION',
            ];

            $valid_options = [];

            /** @noinspection SuspiciousLoopInspection */
            for ($i = $valid_bitmask & -$valid_bitmask; $i <= $valid_bitmask; $i <<= 1)
            {
                $valid_options[] = __CLASS__.'::'.$map[$i];
            }

            throw InvalidOptionException::outOfRange('bigint', $valid_options);
        }
    }
