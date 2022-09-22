<?php

    /**
     * NCC Autoloader file v1.0
     *
     * This file attempts to autoload all the required files for NCC and
     * initialize NCC immediately, this file checks for initialization
     * before proceeding to improve performance.
     */

    if(!defined('NCC_INIT'))
    {
        $third_party_path = __DIR__ . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR;
        $target_files = [
            __DIR__ . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'defuse' . DIRECTORY_SEPARATOR . 'php-encryption' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-ctype' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-ctype' . DIRECTORY_SEPARATOR . 'bootstrap.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-mbstring' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'polyfill-mbstring' . DIRECTORY_SEPARATOR . 'bootstrap.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'Process' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'Uid' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'Filesystem' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'Symfony' . DIRECTORY_SEPARATOR . 'Yaml' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'theseer' . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
            $third_party_path . 'theseer' . DIRECTORY_SEPARATOR . 'DirectoryScanner' . DIRECTORY_SEPARATOR . 'autoload_spl.php',
        ];

        foreach($target_files as $file)
        {
            if(!file_exists($file))
            {
                trigger_error('Cannot find file ' . $file, E_USER_WARNING);
                continue;
            }

            require_once($file);
        }

        if(\ncc\ncc::initialize() == false)
        {
            trigger_error('NCC Failed to initialize', E_USER_WARNING);
        }
    }