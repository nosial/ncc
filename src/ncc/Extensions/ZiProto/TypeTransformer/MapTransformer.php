<?php
    namespace ncc\ZiProto\TypeTransformer;

    use ncc\ZiProto\Packet;
    use ncc\ZiProto\Type\Map;

    /**
     * Class MapTransformer
     * @package ncc\ncc\ZiProto\TypeTransformer
     */
    abstract class MapTransformer
    {
        /**
         * @param Packet $packer
         * @param $value
         * @return string|null
         */
        public function encode(Packet $packer, $value): ?string
        {
            if ($value instanceof Map)
            {
                return $packer->encodeMap($value->map);
            }

            return null;
        }
    }