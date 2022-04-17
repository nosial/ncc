<?php

    namespace ncc\ZiProto\Type;

    /**
     * Class Binary
     * @package ncc\ZiProto\Type
     */
    final class Binary
    {
        /**
         * @var string
         */
        public $data;

        /**
         * Binary constructor.
         * @param string $data
         */
        public function __construct(string $data)
        {
            $this->data = $data;
        }
    }