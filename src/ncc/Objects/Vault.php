<?php

    namespace ncc\Objects;

    use ncc\Objects\Vault\Entry;

    class Vault
    {
        /**
         * The vault's current version for backwards compatibility
         *
         * @var string
         */
        public $Version;

        /**
         * @var Entry[]
         */
        public $Entries;
    }