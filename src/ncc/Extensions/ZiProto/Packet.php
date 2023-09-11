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

    use JsonSerializable;
    use function array_values;
    use function chr;
    use function count;
    use function is_array;
    use function is_bool;
    use function is_float;
    use function is_int;
    use function is_string;
    use function pack;
    use function preg_match;
    use function strlen;
    use ncc\Extensions\ZiProto\Abstracts\Regex;
    use ncc\Extensions\ZiProto\Exception\InvalidOptionException;
    use ncc\Extensions\ZiProto\Exception\EncodingFailedException;
    use ncc\Extensions\ZiProto\TypeTransformer\Validator;

    /**
     * Class Packet
     * @package ZiProto
     */
    class Packet
    {
        /**
         * @var bool
         */
        private $bin_mode;

        /**
         * @var bool
         */
        private $force_string;

        /**
         * @var bool
         */
        private $detect_array_map;

        /**
         * @var bool
         */
        private $force_array;

        /**
         * @var bool
         */
        private $force_float32;

        /**
         * @var Validator[]
         */
        private $transformers;

        /**
         * Packet constructor.
         *
         * @param int|EncodingOptions|null $options
         * @see EncodingOptions
         * @throws InvalidOptionException
         */
        public function __construct(int|EncodingOptions|null $options=null)
        {
            if ($options === null)
            {
                $options = EncodingOptions::fromDefaults();
            }
            elseif (!$options instanceof EncodingOptions)
            {
                $options = EncodingOptions::fromBitmask($options);
            }

            $this->bin_mode = $options->isDetectStrBinMode();
            $this->force_string = $options->isForceStrMode();
            $this->detect_array_map = $options->isDetectArrMapMode();
            $this->force_array = $options->isForceArrMode();
            $this->force_float32 = $options->isForceFloat32Mode();
            $this->transformers = [];
        }

        /**
         * Registers a transformer.
         *
         * @param Validator $transformer
         * @return Packet
         */
        public function registerTransformer(Validator $transformer): self
        {
            $this->transformers[] = $transformer;
            return $this;
        }

        /**
         * @param $value
         * @return false|string
         */
        public function encode($value): false|string
        {
            if (is_int($value))
            {
                return $this->encodeInt($value);
            }

            if (is_string($value))
            {
                if ($this->force_string)
                {
                    return $this->encodeStr($value);
                }

                if ($this->bin_mode)
                {
                    return preg_match(Regex::UTF8_REGEX, $value)
                        ? $this->encodeStr($value)
                        : $this->encodeBin($value);
                }

                return $this->encodeBin($value);
            }

            if (is_array($value))
            {
                if ($this->detect_array_map)
                {
                    return array_values($value) === $value
                        ? $this->encodeArray($value)
                        : $this->encodeMap($value);
                }

                return $this->force_array ? $this->encodeArray($value) : $this->encodeMap($value);
            }

            if (null === $value)
            {
                return "\xc0";
            }

            if (is_bool($value))
            {
                return $value ? "\xc3" : "\xc2";
            }

            if (is_float($value))
            {
                return $this->encodeFloat($value);
            }

            if ($value instanceof Ext)
            {
                return $this->encodeExt($value->getType(), $value->getData());
            }

            if($value instanceof JsonSerializable)
            {
                return $this->encode($value->jsonSerialize());
            }

            if ($this->transformers)
            {
                foreach ($this->transformers as $transformer)
                {
                    if (null !== $encoded = $transformer->check($this, $value))
                    {
                        return $encoded;
                    }
                }
            }

            throw EncodingFailedException::unsupportedType($value);
        }

        /**
         * @return string
         */
        public function encodeNil(): string
        {
            return "\xc0";
        }

        /**
         * @param $bool
         * @return string
         */
        public function encodeBool($bool): string
        {
            return $bool ? "\xc3" : "\xc2";
        }

        /**
         * @param $int
         * @return string
         */
        public function encodeInt($int): string
        {
            if ($int >= 0)
            {
                if ($int <= 0x7f)
                {
                    return chr($int);
                }

                if ($int <= 0xff)
                {
                    return "\xcc". chr($int);
                }

                if ($int <= 0xffff)
                {
                    return "\xcd". chr($int >> 8). chr($int);
                }

                if ($int <= 0xffffffff)
                {
                    return pack('CN', 0xce, $int);
                }

                return pack('CJ', 0xcf, $int);
            }

            if ($int >= -0x20)
            {
                return chr(0xe0 | $int);
            }

            if ($int >= -0x80)
            {
                return "\xd0". chr($int);
            }

            if ($int >= -0x8000)
            {
                return "\xd1". chr($int >> 8). chr($int);
            }

            if ($int >= -0x80000000)
            {
                return pack('CN', 0xd2, $int);
            }

            return pack('CJ', 0xd3, $int);
        }

        /**
         * @param $float
         * @return string
         */
        public function encodeFloat($float): string
        {
            return $this->force_float32
                ? "\xca". pack('G', $float)
                : "\xcb". pack('E', $float);
        }

        /**
         * @param $str
         * @return string
         */
        public function encodeStr($str): string
        {
            $length = strlen($str);

            if ($length < 32)
            {
                return chr(0xa0 | $length).$str;
            }

            if ($length <= 0xff)
            {
                return "\xd9". chr($length).$str;
            }

            if ($length <= 0xffff)
            {
                return "\xda". chr($length >> 8). chr($length).$str;
            }

            return pack('CN', 0xdb, $length).$str;
        }

        /**
         * @param $str
         * @return string
         */
        public function encodeBin($str): string
        {
            $length = strlen($str);

            if ($length <= 0xff)
            {
                return "\xc4". chr($length).$str;
            }

            if ($length <= 0xffff)
            {
                return "\xc5". chr($length >> 8). chr($length).$str;
            }

            return pack('CN', 0xc6, $length).$str;
        }

        /**
         * @param $array
         * @return string
         */
        public function encodeArray($array): string
        {
            $data = $this->encodeArrayHeader(count($array));

            foreach ($array as $val)
            {
                $data .= $this->encode($val);
            }

            return $data;
        }

        /**
         * @param $size
         * @return string
         */
        public function encodeArrayHeader($size): string
        {
            if ($size <= 0xf)
            {
                return chr(0x90 | $size);
            }

            if ($size <= 0xffff)
            {
                return "\xdc". chr($size >> 8). chr($size);
            }

            return pack('CN', 0xdd, $size);
        }

        /**
         * @param $map
         * @return string
         */
        public function encodeMap($map): string
        {
            $data = $this->encodeMapHeader(count($map));

            if ($this->force_string)
            {
                foreach ($map as $key => $val)
                {
                    $data .= is_string($key) ? $this->encodeStr($key) : $this->encodeInt($key);
                    $data .= $this->encode($val);
                }

                return $data;
            }

            if ($this->bin_mode)
            {
                foreach ($map as $key => $val)
                {
                    $data .= is_string($key)
                        ? (preg_match(Regex::UTF8_REGEX, $key) ? $this->encodeStr($key) : $this->encodeBin($key))
                        : $this->encodeInt($key);
                    $data .= $this->encode($val);
                }

                return $data;
            }

            foreach ($map as $key => $val)
            {
                $data .= is_string($key) ? $this->encodeBin($key) : $this->encodeInt($key);
                $data .= $this->encode($val);
            }

            return $data;
        }

        /**
         * @param $size
         * @return string
         */
        public function encodeMapHeader($size): string
        {
            if ($size <= 0xf)
            {
                return chr(0x80 | $size);
            }

            if ($size <= 0xffff)
            {
                return "\xde". chr($size >> 8). chr($size);
            }

            return pack('CN', 0xdf, $size);
        }

        /**
         * @param $type
         * @param $data
         * @return string
         */
        public function encodeExt($type, $data): string
        {
            $length = strlen($data);

            switch ($length)
            {
                case 1: return "\xd4". chr($type).$data;
                case 2: return "\xd5". chr($type).$data;
                case 4: return "\xd6". chr($type).$data;
                case 8: return "\xd7". chr($type).$data;
                case 16: return "\xd8". chr($type).$data;
            }

            if ($length <= 0xff)
            {
                return "\xc7". chr($length). chr($type).$data;
            }

            if ($length <= 0xffff)
            {
                return pack('CnC', 0xc8, $length, $type).$data;
            }

            return pack('CNC', 0xc9, $length, $type).$data;
        }
    }
