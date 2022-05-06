<?php

    namespace ncc\Abstracts;

    abstract class NccBuildFlags
    {
        /**
         * Indicates if the build is currently unstable and some features may not work correctly
         * and can cause errors
         */
        const Unstable = 'UNSTABLE';
    }