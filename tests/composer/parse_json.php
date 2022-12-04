<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    $example = \ncc\Utilities\IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'json_example.json');
    var_dump(\ncc\Objects\ComposerJson::fromArray(json_decode($example, true)));