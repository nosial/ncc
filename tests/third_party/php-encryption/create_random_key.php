<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    print('Generating new random key' . PHP_EOL);
    $key = \ncc\Defuse\Crypto\Key::createNewRandomKey();
    $ascii_key = $key->saveToAsciiSafeString();

    print('Key: ' . $ascii_key . PHP_EOL);