<?php

    if (PHP_SAPI !== 'cli')
    {
        print('%ASSEMBLY.PACKAGE% must be run from the command line.' . PHP_EOL);
        exit(1);
    }

    if(!isset($argv))
    {
        if(isset($_SERVER['argv']))
        {
            $argv = $_SERVER['argv'];
        }
        else
        {
            print('%ASSEMBLY.PACKAGE% failed to run, no $argv found.' . PHP_EOL);
            exit(1);
        }
    }

    require('ncc');
    \ncc\Classes\Runtime::import('%ASSEMBLY.PACKAGE%', 'latest');
	exit(\%ASSEMBLY.NAME%\Program::main($argv));