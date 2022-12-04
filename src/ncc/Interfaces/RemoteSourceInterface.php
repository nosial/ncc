<?php

    namespace ncc\Interfaces;

    use ncc\Objects\RemotePackageInput;

    interface RemoteSourceInterface
    {
        /**
         * Fetches a package and all it's dependencies from the given remote source
         * and optionally converts and compiles it to a local package, returns the
         * fetched package as a path to the ncc package file
         *
         * @param RemotePackageInput $packageInput
         * @return string
         */
        public static function fetch(RemotePackageInput $packageInput): string;
    }