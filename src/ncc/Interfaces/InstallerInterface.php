<?php

    namespace ncc\Interfaces;

    use Exception;
    use ncc\Exceptions\ComponentChecksumException;
    use ncc\Exceptions\ComponentDecodeException;
    use ncc\Exceptions\UnsupportedComponentTypeException;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package;
    use ncc\Objects\Package\Component;

    interface InstallerInterface
    {
        /**
         * Public Constructor
         *
         * @param Package $package
         */
        public function __construct(Package $package);

        /**
         * Processes the component and optionally returns a string of the final component
         *
         * @param Component $component
         * @return string|null
         * @throws ComponentChecksumException
         * @throws ComponentDecodeException
         * @throws UnsupportedComponentTypeException
         */
        public function processComponent(Package\Component $component): ?string;

        /**
         * Processes the resource and optionally returns a string of the final resource
         *
         * @param Package\Resource $resource
         * @return string|null
         * @throws
         */
        public function processResource(Package\Resource $resource): ?string;

        /**
         * Method called before the installation stage begins
         *
         * @param InstallationPaths $installationPaths
         * @throws Exception
         * @return void
         */
        public function preInstall(InstallationPaths $installationPaths): void;

        /**
         * Method called after the installation stage is completed and all the files have been installed
         *
         * @param InstallationPaths $installationPaths
         * @throws Exception
         * @return void
         */
        public function postInstall(InstallationPaths $installationPaths): void;
    }