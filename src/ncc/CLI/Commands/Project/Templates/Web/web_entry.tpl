<?php
    require 'ncc';
    import('${PACKAGE_NAME}');

    (new \DynamicalWeb\DynamicalWeb('${PACKAGE_NAME}'))->handleRequest();