<?php

    // Check for NCC
    if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php'))
    {
        print('Could not find \'autoload.php\', this script is intended to be executed during the redistribution process');
        exit(1);
    }

    require(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php');

    // Start script
    function scanContents($dir, &$results = array())
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $key => $value)
        {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path))
            {
                $results[] = str_ireplace(__DIR__ . DIRECTORY_SEPARATOR, (string)null, $path);
            }
            else if ($value !== '.' && $value !== '..')
            {
                scanContents($path, $results);
            }
        }

        return $results;
    }

    ncc\Utilities\Console::out('Creating checksum.bin ...');

    $excluded_files = [
        'hash_check.php',
        'generate_build_files.php',
        'checksum.bin',
        'build_files'
    ];

    $hash_values = [];
    foreach(scanContents(__DIR__) as $file)
    {

        if(!in_array($file, $excluded_files, true))
        {
            $hash_values[$file] = hash_file('sha256', __DIR__ . DIRECTORY_SEPARATOR . $file);
        }
    }

    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'checksum.bin', \ncc\Extensions\ZiProto\ZiProto::encode($hash_values));
    ncc\Utilities\Console::out('Created checksum.bin');
    exit(0);