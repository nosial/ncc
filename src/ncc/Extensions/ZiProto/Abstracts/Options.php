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

    namespace ncc\Extensions\ZiProto\Abstracts;

    /**
     * Class Options
     * @package ncc\ZiProto\Abstracts
     */
    final class Options
    {
        public const BIGINT_AS_STR = 0b001;
        public const BIGINT_AS_GMP = 0b010;
        public const BIGINT_AS_EXCEPTION = 0b100;
        public const FORCE_STR = 0b00000001;
        public const FORCE_BIN = 0b00000010;
        public const DETECT_STR_BIN = 0b00000100;
        public const FORCE_ARR = 0b00001000;
        public const FORCE_MAP = 0b00010000;
        public const DETECT_ARR_MAP = 0b00100000;
        public const FORCE_FLOAT32 = 0b01000000;
        public const FORCE_FLOAT64 = 0b10000000;
    }