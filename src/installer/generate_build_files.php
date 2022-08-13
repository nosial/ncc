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
        $files = scandir($dir);

        foreach ($files as $key => $value)
        {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path))
            {
                $results[] = str_ireplace(__DIR__ . DIRECTORY_SEPARATOR, (string)null, $path);
            }
            else if ($value != '.' && $value != '..')
            {
                $results[] = str_ireplace(__DIR__ . DIRECTORY_SEPARATOR, (string)null, $path);
                scanContents($path, $results);
            }
        }

        return $results;
    }

    $excluded_files = [
        'hash_check.php',
        'generate_build_files.php',
        'installer',
        'checksum.bin'.
        'build_files',
        'ncc.sh',
        'extension'
    ];

    ncc\Utilities\Console::out('Creating build_files ...');

    if(file_exists('build_files'))
    {
        unlink('build_files');
    }

    $build_files_content = [];
    foreach(scanContents(__DIR__) as $path)
    {
        if(!in_array($path, $excluded_files))
        {
            $build_files_content[] = $path;
        }
    }
    $build_files = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'build_files', 'a+');
    fwrite($build_files, implode("\n", $build_files_content));
    fclose($build_files);
    ncc\Utilities\Console::out('Created build_files');
    exit(0);