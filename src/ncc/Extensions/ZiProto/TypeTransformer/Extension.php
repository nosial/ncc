<?php

    namespace ncc\ncc\ZiProto\TypeTransformer;

    use ncc\ZiProto\BufferStream;

    /**
     * Interface Extension
     * @package ncc\ncc\ZiProto\TypeTransformer
     */
    interface Extension
    {
        /**
         * @return int
         */
        public function getType() : int;

        /**
         * @param BufferStream $stream
         * @param int $extLength
         * @return mixed
         */
        public function decode(BufferStream $stream, int $extLength);
    }