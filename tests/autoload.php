<?php

    $BuildDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'build';
    $AutoloadPath = $BuildDirectory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';

    if(!file_exists($BuildDirectory) || !is_dir($BuildDirectory))
        throw new RuntimeException('Build directory does not exist, to run tests you must build the project.');

    if(!file($AutoloadPath) || is_file($AutoloadPath))
        throw new RuntimeException('Autoload file does not exist in \'' . $BuildDirectory .'\', to run tests you must build the project.');

    require($AutoloadPath);