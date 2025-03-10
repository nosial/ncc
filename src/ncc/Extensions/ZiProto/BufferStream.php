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

    use function gmp_init;
    use function ord;
    use function sprintf;
    use function substr;
    use function unpack;
    use ncc\Extensions\ZiProto\Exception\InsufficientDataException;
    use ncc\Extensions\ZiProto\Exception\IntegerOverflowException;
    use ncc\Extensions\ZiProto\Exception\DecodingFailedException;
    use ncc\Extensions\ZiProto\Exception\InvalidOptionException;
    use ncc\Extensions\ZiProto\TypeTransformer\Extension;

    /**
     * Class BufferStream
     * @package ZiProto
     */
    class BufferStream
    {
        /**
         * @var string
         */
        private $buffer;

        /**
         * @var int
         */
        private $offset = 0;

        /**
         * @var bool
         */
        private $big_int_as_str;

        /**
         * @var bool
         */
        private $big_int_as_gmp;

        /**
         * @var Extension[]|null
         */
        private $transformers;

        /**
         * @param string $buffer
         * @param int|DecodingOptions|null $options
         * @throws InvalidOptionException
         */
        public function __construct(string $buffer = '', DecodingOptions|int|null $options=null)
        {
            if (null === $options)
            {
                /**
                 * @var DecodingOptions $options
                 * @noinspection PhpRedundantVariableDocTypeInspection
                 */
                $options = DecodingOptions::fromDefaults();
            }
            elseif (!$options instanceof EncodingOptions)
            {
                $options = DecodingOptions::fromBitmask($options);
            }

            $this->big_int_as_str = $options->isBigIntAsStrMode();
            $this->big_int_as_gmp = $options->isBigIntAsGmpMode();
            $this->buffer = $buffer;
        }

        /**
         * @param Extension $transformer
         * @return BufferStream
         */
        public function registerTransformer(Extension $transformer): self
        {
            $this->transformers[$transformer->getType()] = $transformer;
            return $this;
        }

        /**
         * @param string $data
         * @return BufferStream
         */
        public function append(string $data) : self
        {
            $this->buffer .= $data;
            return $this;
        }

        /**
         * @param string $buffer
         * @return BufferStream
         */
        public function reset(string $buffer='') : self
        {
            $this->buffer = $buffer;
            $this->offset = 0;
            return $this;
        }

        /**
         * Clone Method
         */
        public function __clone()
        {
            $this->buffer = '';
            $this->offset = 0;
        }

        /**
         * @return array
         */
        public function trydecode() : array
        {
            $data = [];
            $offset = $this->offset;

            try
            {
                do
                {
                    $data[] = $this->decode();
                    $offset = $this->offset;
                } while (isset($this->buffer[$this->offset]));
            }
            catch (InsufficientDataException $e)
            {
                $this->offset = $offset;
            }

            if ($this->offset)
            {
                $this->buffer = isset($this->buffer[$this->offset]) ? substr($this->buffer, $this->offset) : '';
                $this->offset = 0;
            }

            return $data;
        }

        /**
         * @return array|bool|int|mixed|resource|string|Ext|null
         */
        public function decode()
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            // fixint
            if ($c <= 0x7f)
            {
                return $c;
            }

            // fixstr
            if ($c >= 0xa0 && $c <= 0xbf)
            {
                return ($c & 0x1f) ? $this->decodeStrData($c & 0x1f) : '';
            }

            // fixarray
            if ($c >= 0x90 && $c <= 0x9f)
            {
                return ($c & 0xf) ? $this->decodeArrayData($c & 0xf) : [];
            }

            // fixmap
            if ($c >= 0x80 && $c <= 0x8f)
            {
                return ($c & 0xf) ? $this->decodeMapData($c & 0xf) : [];
            }

            // negfixint
            if ($c >= 0xe0)
            {
                return $c - 0x100;
            }

            return match ($c)
            {
                0xc0 => null,
                0xc2 => false,
                0xc3 => true,

                // bin
                0xd9, 0xc4 => $this->decodeStrData($this->decodeUint8()),
                0xda, 0xc5 => $this->decodeStrData($this->decodeUint16()),
                0xdb, 0xc6 => $this->decodeStrData($this->decodeUint32()),

                // float
                0xca => $this->decodeFloat32(),
                0xcb => $this->decodeFloat64(),

                // uint
                0xcc => $this->decodeUint8(),
                0xcd => $this->decodeUint16(),
                0xce => $this->decodeUint32(),
                0xcf => $this->decodeUint64(),

                // int
                0xd0 => $this->decodeInt8(),
                0xd1 => $this->decodeInt16(),
                0xd2 => $this->decodeInt32(),
                0xd3 => $this->decodeInt64(),

                // str

                // array
                0xdc => $this->decodeArrayData($this->decodeUint16()),
                0xdd => $this->decodeArrayData($this->decodeUint32()),

                // map
                0xde => $this->decodeMapData($this->decodeUint16()),
                0xdf => $this->decodeMapData($this->decodeUint32()),

                // ext
                0xd4 => $this->decodeExtData(1),
                0xd5 => $this->decodeExtData(2),
                0xd6 => $this->decodeExtData(4),
                0xd7 => $this->decodeExtData(8),
                0xd8 => $this->decodeExtData(16),
                0xc7 => $this->decodeExtData($this->decodeUint8()),
                0xc8 => $this->decodeExtData($this->decodeUint16()),
                0xc9 => $this->decodeExtData($this->decodeUint32()),

                default => throw DecodingFailedException::unknownCode($c), // Default case
            };
        }

        /**
         * @return void
         */
        public function decodeNil(): void
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            if ("\xc0" === $this->buffer[$this->offset])
            {
                ++$this->offset;
                return;
            }

            throw DecodingFailedException::unexpectedCode(ord($this->buffer[$this->offset++]), 'nil');
        }

        /**
         * @return bool
         */
        public function decodeBool(): bool
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if (0xc2 === $c)
            {
                return false;
            }

            if (0xc3 === $c)
            {
                return true;
            }

            throw DecodingFailedException::unexpectedCode($c, 'bool');
        }

        /**
         * @return int|resource|string
         */
        public function decodeInt()
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            // fixint
            if ($c <= 0x7f)
            {
                return $c;
            }

            // negfixint
            if ($c >= 0xe0)
            {
                return $c - 0x100;
            }

            switch ($c)
            {
                // uint
                case 0xcc: return $this->decodeUint8();
                case 0xcd: return $this->decodeUint16();
                case 0xce: return $this->decodeUint32();
                case 0xcf: return $this->decodeUint64();

                // int
                case 0xd0: return $this->decodeInt8();
                case 0xd1: return $this->decodeInt16();
                case 0xd2: return $this->decodeInt32();
                case 0xd3: return $this->decodeInt64();
            }

            throw DecodingFailedException::unexpectedCode($c, 'int');
        }

        /**
         * @return mixed
         */
        public function decodeFloat(): mixed
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if (0xcb === $c)
            {
                return $this->decodeFloat64();
            }

            if (0xca === $c)
            {
                return $this->decodeFloat32();
            }

            throw DecodingFailedException::unexpectedCode($c, 'float');
        }

        /**
         * @return string
         */
        public function decodeStr(): string
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if ($c >= 0xa0 && $c <= 0xbf)
            {
                return ($c & 0x1f) ? $this->decodeStrData($c & 0x1f) : '';
            }

            if (0xd9 === $c)
            {
                return $this->decodeStrData($this->decodeUint8());
            }

            if (0xda === $c)
            {
                return $this->decodeStrData($this->decodeUint16());
            }

            if (0xdb === $c)
            {
                return $this->decodeStrData($this->decodeUint32());
            }

            throw DecodingFailedException::unexpectedCode($c, 'str');
        }

        /**
         * @return string
         */
        public function decodeBin(): string
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if (0xc4 === $c)
            {
                return $this->decodeStrData($this->decodeUint8());
            }

            if (0xc5 === $c)
            {
                return $this->decodeStrData($this->decodeUint16());
            }

            if (0xc6 === $c)
            {
                return $this->decodeStrData($this->decodeUint32());
            }

            throw DecodingFailedException::unexpectedCode($c, 'bin');
        }

        /**
         * @return array
         */
        public function decodeArray(): array
        {
            $size = $this->decodeArrayHeader();
            $array = [];

            while ($size--)
            {
                $array[] = $this->decode();
            }

            return $array;
        }

        /**
         * @return int
         */
        public function decodeArrayHeader(): int
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if ($c >= 0x90 && $c <= 0x9f)
            {
                return $c & 0xf;
            }

            if (0xdc === $c)
            {
                return $this->decodeUint16();
            }

            if (0xdd === $c)
            {
                return $this->decodeUint32();
            }

            throw DecodingFailedException::unexpectedCode($c, 'array header');
        }

        /**
         * @return array
         */
        public function decodeMap(): array
        {
            $size = $this->decodeMapHeader();
            $map = [];

            while ($size--)
            {
                $map[$this->decode()] = $this->decode();
            }

            return $map;
        }

        /**
         * @return int
         */
        public function decodeMapHeader(): int
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            if ($c >= 0x80 && $c <= 0x8f)
            {
                return $c & 0xf;
            }

            if (0xde === $c)
            {
                return $this->decodeUint16();
            }

            if (0xdf === $c)
            {
                return $this->decodeUint32();
            }

            throw DecodingFailedException::unexpectedCode($c, 'map header');
        }

        /**
         * @return mixed|Ext
         */
        public function decodeExt(): mixed
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $c = ord($this->buffer[$this->offset]);
            ++$this->offset;

            switch ($c)
            {
                case 0xd4: return $this->decodeExtData(1);
                case 0xd5: return $this->decodeExtData(2);
                case 0xd6: return $this->decodeExtData(4);
                case 0xd7: return $this->decodeExtData(8);
                case 0xd8: return $this->decodeExtData(16);
                case 0xc7: return $this->decodeExtData($this->decodeUint8());
                case 0xc8: return $this->decodeExtData($this->decodeUint16());
                case 0xc9: return $this->decodeExtData($this->decodeUint32());
            }

            throw DecodingFailedException::unexpectedCode($c, 'ext header');
        }

        /**
         * @return int
         */
        private function decodeUint8(): int
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            return ord($this->buffer[$this->offset++]);
        }

        /**
         * @return int
         */
        private function decodeUint16(): int
        {
            if (!isset($this->buffer[$this->offset + 1]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 2);
            }

            $hi = ord($this->buffer[$this->offset]);
            $lo = ord($this->buffer[++$this->offset]);
            ++$this->offset;

            return $hi << 8 | $lo;
        }

        /**
         * @return mixed
         */
        private function decodeUint32(): mixed
        {
            if (!isset($this->buffer[$this->offset + 3]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 4);
            }

            $num = unpack('N', $this->buffer, $this->offset)[1];
            $this->offset += 4;

            return $num;
        }

        /**
         * @return resource|string
         */
        private function decodeUint64()
        {
            if (!isset($this->buffer[$this->offset + 7]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 8);
            }

            $num = unpack('J', $this->buffer, $this->offset)[1];
            $this->offset += 8;

            return $num < 0 ? $this->handleIntOverflow($num) : $num;
        }

        /**
         * @return int
         */
        private function decodeInt8(): int
        {
            if (!isset($this->buffer[$this->offset]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 1);
            }

            $num = ord($this->buffer[$this->offset]);
            ++$this->offset;

            return $num > 0x7f ? $num - 0x100 : $num;
        }

        /**
         * @return int
         */
        private function decodeInt16(): int
        {
            if (!isset($this->buffer[$this->offset + 1]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 2);
            }

            $hi = ord($this->buffer[$this->offset]);
            $lo = ord($this->buffer[++$this->offset]);
            ++$this->offset;

            return $hi > 0x7f ? $hi << 8 | $lo - 0x10000 : $hi << 8 | $lo;
        }

        /**
         * @return int
         */
        private function decodeInt32(): int
        {
            if (!isset($this->buffer[$this->offset + 3]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 4);
            }

            $num = unpack('N', $this->buffer, $this->offset)[1];
            $this->offset += 4;

            return $num > 0x7fffffff ? $num - 0x100000000 : $num;
        }

        /**
         * @return mixed
         */
        private function decodeInt64(): mixed
        {
            if (!isset($this->buffer[$this->offset + 7]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 8);
            }

            $num = unpack('J', $this->buffer, $this->offset)[1];
            $this->offset += 8;

            return $num;
        }

        /**
         * @return mixed
         */
        private function decodeFloat32(): mixed
        {
            if (!isset($this->buffer[$this->offset + 3]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 4);
            }

            $num = unpack('G', $this->buffer, $this->offset)[1];
            $this->offset += 4;

            return $num;
        }

        /**
         * @return mixed
         */
        private function decodeFloat64(): mixed
        {
            if (!isset($this->buffer[$this->offset + 7]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, 8);
            }

            $num = unpack('E', $this->buffer, $this->offset)[1];
            $this->offset += 8;

            return $num;
        }

        /**
         * @param $length
         * @return string
         */
        private function decodeStrData($length): string
        {
            if (!isset($this->buffer[$this->offset + $length - 1]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, $length);
            }

            $str = substr($this->buffer, $this->offset, $length);
            $this->offset += $length;

            return $str;
        }

        /**
         * @param $size
         * @return array
         */
        private function decodeArrayData($size): array
        {
            $array = [];

            while ($size--)
            {
                $array[] = $this->decode();
            }

            return $array;
        }

        /**
         * @param $size
         * @return array
         */
        private function decodeMapData($size): array
        {
            $map = [];

            while ($size--)
            {
                $map[$this->decode()] = $this->decode();
            }

            return $map;
        }

        /**
         * @param $length
         * @return mixed|Ext
         */
        private function decodeExtData($length): mixed
        {
            if (!isset($this->buffer[$this->offset + $length - 1]))
            {
                throw InsufficientDataException::unexpectedLength($this->buffer, $this->offset, $length);
            }

            // int8
            $num = ord($this->buffer[$this->offset]);
            ++$this->offset;
            $type = $num > 0x7f ? $num - 0x100 : $num;

            if (isset($this->transformers[$type]))
            {
                return $this->transformers[$type]->decode($this, $length);
            }

            $data = substr($this->buffer, $this->offset, $length);
            $this->offset += $length;

            return new Ext($type, $data);
        }

        /**
         * @param $value
         * @return resource|string
         */
        private function handleIntOverflow($value)
        {
            if ($this->big_int_as_str)
            {
                return sprintf('%u', $value);
            }

            if ($this->big_int_as_gmp)
            {
                return gmp_init(sprintf('%u', $value));
            }

            throw new IntegerOverflowException($value);
        }
    }