<?php
    namespace ncc\ncc\ZiProto\TypeTransformer;

    use ncc\ZiProto\Packet;

    /**
     * Interface Validator
     * @package ncc\ncc\ZiProto\TypeTransformer
     */
    interface Validator
    {
        /**
         * @param Packet $packer
         * @param $value
         * @return string
         */
        public function check(Packet $packer, $value) :string;
    }