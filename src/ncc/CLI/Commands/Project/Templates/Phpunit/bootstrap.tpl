<?PHP
        require 'ncc';

        if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . '${DEFAULT_BUILD_OUTPUT}'))
        {
            throw new Exception('Build output not found: ' . __DIR__ . DIRECTORY_SEPARATOR . '${DEFAULT_BUILD_OUTPUT}');
        }

        import(__DIR__ . DIRECTORY_SEPARATOR . '${DEFAULT_BUILD_OUTPUT}');