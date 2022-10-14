<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    $Scanner = new \ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner();
    $Basedir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

    /** @var SplFileInfo $item */
    foreach($Scanner($Basedir . DIRECTORY_SEPARATOR . 'src', true) as $item)
    {
        var_dump($item->getPath());
        var_dump($item);
    }