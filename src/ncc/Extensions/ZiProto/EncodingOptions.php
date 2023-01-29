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
     * Class EncodingOptions
     * @package ZiProto
     */
    final class EncodingOptions
    {
        /**
         * @var mixed
         */
        private $strBinMode;

        /**
         * @var mixed
         */
        private $arrMapMode;

        /**
         * @var mixed
         */
        private $floatMode;

        /**
         * EncodingOptions constructor.
         */
        private function __construct() {}

        /**
         * @return EncodingOptions
         */
        public static function fromDefaults() : self
        {
            $self = new self();
            $self->strBinMode = Options::DETECT_STR_BIN;
            $self->arrMapMode = Options::DETECT_ARR_MAP;
            $self->floatMode = Options::FORCE_FLOAT64;

            return $self;
        }

        /**
         * @param int $bitmask
         * @return EncodingOptions
         */
        public static function fromBitmask(int $bitmask) : self
        {
            $self = new self();

            if (self::getSingleOption('str/bin', $bitmask, Options::FORCE_STR | Options::FORCE_BIN | Options::DETECT_STR_BIN))
            {
                $self->strBinMode = self::getSingleOption('str/bin', $bitmask,
                    Options::FORCE_STR |
                    Options::FORCE_BIN |
                    Options::DETECT_STR_BIN
                );
            }
            else
            {
                $self->strBinMode = Options::DETECT_STR_BIN;
            }

            if (self::getSingleOption('arr/map', $bitmask, Options::FORCE_ARR | Options::FORCE_MAP | Options::DETECT_ARR_MAP))
            {
                $self->arrMapMode = self::getSingleOption('arr/map', $bitmask,
                    Options::FORCE_ARR |
                    Options::FORCE_MAP |
                    Options::DETECT_ARR_MAP
                );
            }
            else
            {
                $self->arrMapMode = Options::DETECT_ARR_MAP;
            }

            if (self::getSingleOption('float', $bitmask, Options::FORCE_FLOAT32 | Options::FORCE_FLOAT64))
            {
                $self->floatMode = self::getSingleOption('float', $bitmask,
                    Options::FORCE_FLOAT32 |
                    Options::FORCE_FLOAT64
                );
            }
            else
            {
                $self->floatMode = Options::FORCE_FLOAT64;
            }

            return $self;
        }

        /**
         * @return bool
         */
        public function isDetectStrBinMode() : bool
        {
            return Options::DETECT_STR_BIN === $this->strBinMode;
        }

        /**
         * @return bool
         */
        public function isForceStrMode() : bool
        {
            return Options::FORCE_STR === $this->strBinMode;
        }

        /**
         * @return bool
         */
        public function isDetectArrMapMode() : bool
        {
            return Options::DETECT_ARR_MAP === $this->arrMapMode;
        }

        /**
         * @return bool
         */
        public function isForceArrMode() : bool
        {
            return Options::FORCE_ARR === $this->arrMapMode;
        }

        /**
         * @return bool
         */
        public function isForceFloat32Mode() : bool
        {
            return Options::FORCE_FLOAT32 === $this->floatMode;
        }

        /**
         * @param string $name
         * @param int $bitmask
         * @param int $validBitmask
         * @return int
         */
        private static function getSingleOption(string $name, int $bitmask, int $validBitmask) : int
        {
            $option = $bitmask & $validBitmask;

            if ($option === ($option & -$option))
            {
                return $option;
            }

            static $map = [
                Options::FORCE_STR => 'FORCE_STR',
                Options::FORCE_BIN => 'FORCE_BIN',
                Options::DETECT_STR_BIN => 'DETECT_STR_BIN',
                Options::FORCE_ARR => 'FORCE_ARR',
                Options::FORCE_MAP => 'FORCE_MAP',
                Options::DETECT_ARR_MAP => 'DETECT_ARR_MAP',
                Options::FORCE_FLOAT32 => 'FORCE_FLOAT32',
                Options::FORCE_FLOAT64 => 'FORCE_FLOAT64',
            ];

            $validOptions = [];

            for ($i = $validBitmask & -$validBitmask; $i <= $validBitmask; $i <<= 1)
            {
                $validOptions[] = __CLASS__.'::'.$map[$i];
            }

            throw InvalidOptionException::outOfRange($name, $validOptions);
        }
    }
