<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    class InstallationPaths
    {
        /**
         * The path of where the package will be installed at
         *
         * @var string
         */
        private $InstallationPath;

        /**
         * @param string $installation_path
         */
        public function __construct(string $installation_path)
        {
            $this->InstallationPath = $installation_path;
        }

        /**
         * Returns the data path where NCC's metadata & runtime information is stored
         *
         * @return string
         */
        public function getDataPath(): string
        {
            return $this->InstallationPath . DIRECTORY_SEPARATOR . 'ncc';
        }

        /**
         * Returns the source path for where the package resides
         *
         * @return string
         */
        public function getSourcePath(): string
        {
            return $this->InstallationPath . DIRECTORY_SEPARATOR . 'src';
        }

        /**
         * Returns the path for where executables are located
         *
         * @return string
         */
        public function getBinPath(): string
        {
            return $this->InstallationPath . DIRECTORY_SEPARATOR . 'bin';
        }

        /**
         * @return string
         */
        public function getInstallationPath(): string
        {
            return $this->InstallationPath;
        }
    }