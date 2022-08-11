<?php

    /**
     * NCC Autoloader file v1.0
     *
     * This file attempts to autoload all the required files for NCC and
     * initialize NCC immediately, this file checks for initialization
     * before proceeding to improve performance.
     */

    if(defined('NCC_INIT') == false)
    {
        // polyfill-mbstring
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-mbstring' . DIRECTORY_SEPARATOR . 'bootstrap.php');

        // polyfill-ctype
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-ctype' . DIRECTORY_SEPARATOR . 'bootstrap.php');

        // Generated SPL file
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'autoload_spl.php');

        if(\ncc\ncc::initialize() == false)
        {
            trigger_error('NCC Failed to initialize', E_USER_WARNING);
        }
    }