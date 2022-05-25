<?php

    namespace ncc\Abstracts;

    abstract class RemoteSource
    {
        /**
         * The original source is from GitHub (Enterprise not supported yet)
         */
        const GitHub = 'GITHUB';

        /**
         * The original source is from Gitlab or a Gitlab instance
         */
        const Gitlab = 'GITLAB';
    }