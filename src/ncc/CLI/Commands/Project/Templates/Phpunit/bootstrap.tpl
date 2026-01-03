<?PHP
        require 'ncc';

        if(!file_exists('${DEFAULT_BUILD_OUTPUT}'))
        {
            throw new Exception('Build output not found: ${DEFAULT_BUILD_OUTPUT}');
        }

        import('${DEFAULT_BUILD_OUTPUT}');