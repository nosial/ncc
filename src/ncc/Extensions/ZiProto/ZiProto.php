<?php

    namespace ncc\ZiProto;

    use ncc\ZiProto\Exception\DecodingFailedException;
    use ncc\ZiProto\Exception\EncodingFailedException;
    use ncc\ZiProto\Exception\InvalidOptionException;

    /**
     * ZiProto Class
     *
     * Class ZiProto
     * @package ZiProto
     */
    class ZiProto
    {
        /**
         * @param mixed $value
         * @param EncodingOptions|int|null $options
         *
         * @throws InvalidOptionException
         * @throws EncodingFailedException
         *
         * @return string
         */
        public static function encode($value, $options = null) : string
        {
            return (new Packet($options))->encode($value);
        }

        /**
         * @param string $data
         * @param DecodingOptions|int|null $options
         *
         * @throws InvalidOptionException
         * @throws DecodingFailedException
         *
         * @return mixed
         */
        public static function decode(string $data, $options = null)
        {
            return (new BufferStream($data, $options))->decode();
        }
    }