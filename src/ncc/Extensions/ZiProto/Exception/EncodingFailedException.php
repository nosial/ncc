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

    namespace ncc\Extensions\ZiProto\Exception;

    use function get_class;
    use function gettype;
    use function is_object;
    use RuntimeException;
    use function sprintf;
    use Throwable;

    /**
     * Class EncodingFailedException
     * @package ncc\ZiProto\Exception
     */
    class EncodingFailedException extends RuntimeException
    {
        /**
         * @var mixed
         */
        private $value;

        /**
         * EncodingFailedException constructor.
         * @param $value
         * @param string $message
         * @param Throwable|null $previous
         */
        public function __construct($value, string $message='', Throwable $previous=null)
        {
            parent::__construct($message, 0, $previous);
            $this->value = $value;
        }

        /**
         * @return mixed
         */
        public function getValue(): mixed
        {
            return $this->value;
        }

        /**
         * @param $value
         * @return EncodingFailedException
         */
        public static function unsupportedType($value) : self
        {
            $message = sprintf('Unsupported type: %s.',
                is_object($value) ? get_class($value) : gettype($value)
            );
            return new self($value, $message);
        }
    }