<?php

    $autoload_path = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'autoload.php';
    if(!file_exists($autoload_path))
    {
        throw new Exception("Autoload file not found");
    }

    require $autoload_path;