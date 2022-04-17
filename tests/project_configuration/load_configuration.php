<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    $object = \ncc\Objects\ProjectConfiguration::fromFile(__DIR__ . DIRECTORY_SEPARATOR . 'project.json');

    var_dump($object);