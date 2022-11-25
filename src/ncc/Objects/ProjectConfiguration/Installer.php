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
            return [
                ($bytecode ? Functions::cbc('pre_install') : 'pre_install') => $this->PreInstall,
                ($bytecode? Functions::cbc('post_install') : 'post_install') => $this->PostInstall,
                ($bytecode? Functions::cbc('pre_uninstall') : 'pre_uninstall') => $this->PostUninstall,
                ($bytecode? Functions::cbc('post_uninstall') : 'post_uninstall') => $this->PostUninstall,
                ($bytecode? Functions::cbc('pre_update') : 'pre_update') => $this->PreUpdate,
                ($bytecode? Functions::cbc('post_update') : 'post_update') => $this->PostUpdate
            ];
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