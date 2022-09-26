<?php

    namespace ncc\Abstracts;

    abstract class ComponentFileExtensions
    {
        /**
         * The file extensions that the PHP compiler extension will accept as components.
         *
         * @var array
         */
        const Php = ['*.php', '*.php3', '*.php4', '*.php5', '*.phtml'];
    }