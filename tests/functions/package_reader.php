<?php

    require 'ncc';
    $package_path = '/home/netkas/PhpstormProjects/ncc/tests/projects/lib/build/release/com.example.testlib.ncc';

    $package_manager = new \ncc\Managers\PackageManager();
    $package_manager->installPackage($package_path);
