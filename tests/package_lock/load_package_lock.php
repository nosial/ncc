<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    \ncc\ncc::initialize();
    $package_lock_manager = new \ncc\Managers\PackageLockManager();
    $package_lock_manager->load();

    var_dump($package_lock_manager->getPackageLock());
