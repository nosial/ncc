<?php

    namespace ncc\Abstracts;

    abstract class DependencySourceType
    {
        /**
         * The dependency pointer does not point to a package
         */
        const None = 'none';

        /**
         * Indicates if the dependency is statically linked and the
         * reference points to the compiled package of the dependency
         */
        const StaticLinking = 'static';

        /**
         * Indicates if the pointer reference points to a remote source
         * to fetch the dependency from
         */
        const RemoteSource = 'remote';

        /**
         * Indicates if the pointer reference points to a relative file with the
         * filename format "{package_name}=={version}.ncc" to fetch the dependency from
         */
        const Local = 'local';
    }