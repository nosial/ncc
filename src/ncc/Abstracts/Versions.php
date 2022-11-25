<?php

    namespace ncc\Abstracts;

    abstract class Versions
    {
        /**
         * The current version of the credentials store file format
         */
        const CredentialsStoreVersion = '1.0.0';

        /**
         * The current version of the package structure file format
         */
        const PackageStructureVersion = '1.0';

        /**
         * The current version of the package lock structure file format
         */
        const PackageLockVersion = '1.0.0';
    }