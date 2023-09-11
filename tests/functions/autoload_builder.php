<?php

    require 'ncc';

    $files = \ncc\Utilities\Functions::scanDirectory(
        '/home/netkas/PhpstormProjects/ncc/src/ncc',
        \ncc\Enums\ComponentFileExtensions::PHP
    );

    $autoload_generator = new \ncc\Classes\PhpExtension\AutoloadGenerator();
    $autoload = $autoload_generator->generateAutoloaderArray($files);

    var_dump($files);
    var_dump($autoload);