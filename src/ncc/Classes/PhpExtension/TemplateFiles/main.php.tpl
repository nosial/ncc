<?php

    if (PHP_SAPI !== 'cli')
    {
        print('%ASSEMBLY.PACKAGE% must be run from the command line.' . PHP_EOL);
        exit(1);
    }

    if(!isset($argv))
    {
        trigger_error('No $argv found, maybe you are using php-cgi?', E_USER_ERROR);
    }

    require('ncc');
    import('%ASSEMBLY.PACKAGE%', 'latest');

    \%ASSEMBLY.NAME%\Program::main($argv);