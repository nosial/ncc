<?php

    namespace ncc\Abstracts;

    abstract class RemoteSource
    {
        /**
         * The remote source is from composer
         */
        const Composer = 'composer';

        /**
         * The remote source is from a git repository
         */
        const Git = 'git';
    }