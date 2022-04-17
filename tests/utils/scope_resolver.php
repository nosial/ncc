<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    print('Detected Scope: ' . \ncc\Utilities\Resolver::resolveScope() . PHP_EOL);