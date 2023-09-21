<?php

    // Check for NCC
    if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php'))
    {
        print('Could not find \'autoload.php\', this script is intended to be executed during the redistribution process');
        exit(1);
    }

    /** @noinspection PhpIncludeInspection */
    require(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php');

    // Start script
    function scanContents($dir)
    {
        $results = [];

        foreach (scandir($dir, SCANDIR_SORT_NONE) as $key => $value)
        {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);

            if (!is_dir($path))
            {
                $results[] = str_ireplace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
            }
            elseif ($value !== '.' && $value !== '..')
            {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $results = array_merge($results, scanContents($path));
            }
        }

        return $results;
    }


    $excluded_files = [
        'hash_check.php',
        'generate_build_files.php',
        'default_config.yaml',
        'installer',
        'checksum.bin',
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
        if(!in_array($path, $excluded_files, true))
        {
            $build_files_content[] = $path;
        }
    }

    $build_files = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'build_files', 'ab+');

    fwrite($build_files, implode("\n", $build_files_content));
    fclose($build_files);

    exit(0);