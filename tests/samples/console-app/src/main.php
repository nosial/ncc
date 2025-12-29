<?php

    use ConsoleApp\Application;

    $app = new Application();
    $arguments = array_slice($argv ?? [], 1);
    exit($app->run($arguments));
