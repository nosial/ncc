<?php

    $SourceDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ncc';

    if(file_exists($SourceDirectory . DIRECTORY_SEPARATOR . 'autoload.php') == false)
        throw new RuntimeException('The autoload file was not found in \'' . $SourceDirectory . '\'');

    require($SourceDirectory . DIRECTORY_SEPARATOR . 'autoload.php');