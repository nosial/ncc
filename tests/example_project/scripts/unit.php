<?php
    echo 'Hello World!' . PHP_EOL;
    echo 'What is your name? ';
    $name = trim(fgets(STDIN));
    echo "Hello $name" . PHP_EOL;
