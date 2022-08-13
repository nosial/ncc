<?php

    namespace ncc\Managers;

    class ConfigurationManager
    {
        /**
         * Public Constructor
         *
         * @param string $package
         */
        public function __construct(string $package)
        {
            $this->Package = $package;
        }
    }