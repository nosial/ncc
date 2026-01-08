<?php

    if (PHP_SAPI !== 'cli')
    {
        print('${PACKAGE_NAME} must be running from the command line.');
        exit(1);
    }

    print('Hello from ${PROJECT_NAME}!');
    exit(0);