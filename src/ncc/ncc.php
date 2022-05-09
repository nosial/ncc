<?php

    namespace ncc;
    
    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class ncc
    {
    
        /**
         * The cache'd version of the version information object.
         *
         * @var \ncc\Objects\NccVersionInformation|null
         */
        private static $VersionInformation;

        /**
         * NCC Public Constructor
         */
        public function __construct()
        {
            
        }

        /**
         * Returns the version information object about the current build of NCC
         *
         * @param boolean $reload Indicates if the cached version is to be ignored and the version file to be reloaded and validated
         * @return \ncc\Objects\NccVersionInformation
         */
        public static function getVersionInformation(bool $reload=False): \ncc\Objects\NccVersionInformation
        {
            if(self::$VersionInformation !== null && $reload == False)
                return self::$VersionInformation;

            if(file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'version.json') == false)
            {
                throw new \ncc\Exceptions\RuntimeException('The file \'version.json\' was not found in \'' . __DIR__ . '\'');
            }

            try
            {
                self::$VersionInformation = \ncc\Objects\NccVersionInformation::fromArray(\ncc\Utilities\Functions::loadJsonFile(__DIR__ . DIRECTORY_SEPARATOR . 'version.json', \ncc\Utilities\Functions::FORCE_ARRAY));
            }
            catch(\ncc\Exceptions\MalformedJsonException $e)
            {
                throw new \ncc\Exceptions\RuntimeException('Unable to parse JSON contents of \'version.json\' in \'' . __DIR__ . '\'', $e);
            }

            if(self::$VersionInformation->Version == null)
            {
                throw new \ncc\Exceptions\RuntimeException('The version number is not specified in the version information file');
            }

            if(self::$VersionInformation->Branch == null)
            {
                throw new \ncc\Exceptions\RuntimeException('The version branch is not specified in the version information file');
            }

            return self::$VersionInformation;
        }

        /**
         * Initializes the NCC environment
         *
         * @return bool
         */
        public static function initialize(): bool
        {
            if(defined('NCC_INIT'))
                return false;
            
            // Set debugging/troubleshooting constants
            define('NCC_EXEC_LOCATION', __DIR__); // The directory of where ncc.php is located
            define('NCC_EXEC_IWD', getcwd()); // The initial working directory when NCC was first invoked

            // Set version information about the current build
            $VersionInformation = self::getVersionInformation(true);
            define('NCC_VERSION_NUMBER', $VersionInformation->Version);
            define('NCC_VERSION_BRANCH', $VersionInformation->Branch);
            define('NCC_VERSION_UPDATE_SOURCE', $VersionInformation->UpdateSource);
            define('NCC_VERSION_FLAGS', $VersionInformation->Flags);

            return true;
        }

        /**
         * Returns the constants set by NCC
         *
         * @return array
         */
        public static function getConstants(): array
        {
            if(defined('NCC_INIT') == false)
            {
                throw new \ncc\Exceptions\RuntimeException('NCC Must be initialized before executing ' . get_called_class() . '::getDefinitions()');
            }

            return [
                // Init
                'NCC_INIT' => constant('NCC_INIT'),

                // Debugging/Troubleshooting constants
                'NCC_EXEC_LOCATION' => constant('NCC_INIT'),
                'NCC_EXEC_IWD' => constant('NCC_EXEC_IWD'),

                // Version Information
                'NCC_VERSION_NUMBER' => constant('NCC_VERSION_NUMBER'),
                'NCC_VERSION_BRANCH' => constant('NCC_VERSION_BRANCH'),
                'NCC_VERSION_UPDATE_SOURCE' => constant('NCC_VERSION_UPDATE_SOURCE'),
                'NCC_VERSION_FLAGS' => constant('NCC_VERSION_FLAGS'),
            ];
        }
    }
    
