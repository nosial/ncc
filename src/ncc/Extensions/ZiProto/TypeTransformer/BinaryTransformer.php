<?php

    namespace ncc\ncc\ZiProto\TypeTransformer;

    use ncc\ZiProto\Packet;
    use ncc\ZiProto\Type\Binary;

    /**
     * Class BinaryTransformer
     * @package ncc\ncc\ZiProto\TypeTransformer
     */
    abstract class BinaryTransformer
    {
        /**
         * @param Packet $packer
         * @param $value
         * @return string
         */
        public function pack(Packet $packer, $value): ?string
        {
            if ($value instanceof Binary)
            {
                return $packer->encodeBin($value->data);
            }

            return null;
        }
    }