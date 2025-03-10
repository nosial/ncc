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

    namespace ncc\Extensions\ZiProto;

    use Exception;
    use InvalidArgumentException;

    /**
     * ZiProto Class
     *
     * Class ZiProto
     * @package ZiProto
     */
    class ZiProto
    {
        /**
         * Encodes the given value to a msgpack string.
         *
         * @param mixed $value
         * @param int|null $options
         * @see EncodingOptions
         * @return string
         */
        public static function encode(mixed $value, ?int $options=null) : string
        {
            try
            {
                return (new Packet($options))->encode($value);
            }
            catch(Exception $e)
            {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }
        }

        /**
         * Decodes the given msgpack string.
         *
         * @param string $data
         * @param int|null $options
         * @see DecodingOptions
         * @return array|bool|int|mixed|Ext|resource|string|null
         */
        public static function decode(string $data, ?int $options=null): mixed
        {
            try
            {
                return (new BufferStream($data, $options))->decode();
            }
            catch(Exception $e)
            {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }