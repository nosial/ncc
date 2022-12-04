<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Utilities\Functions;

    class Installer
    {
        /**
         * An array of execution policies to execute pre-installation
         *
         * @var string[]|null
         */
        public $PreInstall;

        /**
         * An array of execution policies to execute post-installation
         *
         * @var string[]|null
         */
        public $PostInstall;

        /**
         * An array of execution policies to execute pre-uninstallation
         *
         * @var string[]|null
         */
        public $PreUninstall;

        /**
         * An array of execution policies to execute post-uninstallation
         *
         * @var string[]|null
         */
        public $PostUninstall;

        /**
         * An array of execution policies to execute pre-update
         *
         * @var string[]|null
         */
        public $PreUpdate;

        /**
         * An array of execution policies to execute post-update
         *
         * @var string[]|null
         */
        public $PostUpdate;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->PreInstall !== null && count($this->PreInstall) > 0)
                $results[($bytecode ? Functions::cbc('pre_install') : 'pre_install')] = $this->PreInstall;

            if($this->PostInstall !== null && count($this->PostInstall) > 0)
                $results[($bytecode ? Functions::cbc('post_install') : 'post_install')] = $this->PostInstall;

            if($this->PreUninstall !== null && count($this->PreUninstall) > 0)
                $results[($bytecode ? Functions::cbc('pre_uninstall') : 'pre_uninstall')] = $this->PreUninstall;

            if($this->PostUninstall !== null && count($this->PostUninstall) > 0)
                $results[($bytecode ? Functions::cbc('post_uninstall') : 'post_uninstall')] = $this->PostUninstall;

            if($this->PreUpdate !== null && count($this->PreUpdate) > 0)
                $results[($bytecode ? Functions::cbc('pre_update') : 'pre_update')] = $this->PreUpdate;

            if($this->PostUpdate !== null && count($this->PostUpdate) > 0)
                $results[($bytecode ? Functions::cbc('post_update') : 'post_update')] = $this->PostUpdate;

            return $results;
        }

        /**
         * @param array $data
         * @return Installer
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->PreInstall = Functions::array_bc($data, 'pre_install');
            $object->PostInstall = Functions::array_bc($data, 'post_install');
            $object->PreUninstall = Functions::array_bc($data, 'pre_uninstall');
            $object->PostUninstall = Functions::array_bc($data, 'post_uninstall');
            $object->PreUpdate = Functions::array_bc($data, 'pre_update');
            $object->PostUpdate = Functions::array_bc($data, 'post_update');

            return $object;
        }
    }