<?php

    namespace ncc\Abstracts\SpecialConstants;

    abstract class BuildConstants
    {
        /**
         * The Unix Timestamp for when the package was compiled
         */
        const CompileTimestamp = '%COMPILE_TIMESTAMP%';

        /**
         * The version of NCC that was used to compile the package
         */
        const NccBuildVersion = '%NCC_BUILD_VERSION%';

        /**
         * NCC Build Flags exploded into spaces
         */
        const NccBuildFlags = '%NCC_BUILD_FLAGS%';

        /**
         * NCC Build Branch
         */
        const NccBuildBranch = '%NCC_BUILD_BRANCH%';
    }