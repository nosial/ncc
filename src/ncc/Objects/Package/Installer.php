<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Utilities\Functions;

    class Installer
    {
        /**
         * An array of execution policies to execute pre install
         *
         * @var string[]|null
         */
        public $PreInstall;

        /**
         * An array of execution policies to execute post install
         *
         * @var string[]|null
         */
        public $PostInstall;

        /**
         * An array of execution policies to execute pre uninstall
         *
         * @var string[]|null
         */
        public $PreUninstall;

        /**
         * An array of execution policies to execute post uninstall
         *
         * @var string[]|null
         */
        public $PostUninstall;

        /**
         * An array of execution policies to execute pre update
         *
         * @var string[]|null
         */
        public $PreUpdate;

        /**
         * An array of execution policies to execute post update
         *
         * @var string[]|null
         */
        public $PostUpdate;

        /**
         * Returns an array representation of the object
         * Returns null if all properties are not set
         *
         * @param bool $bytecode
         * @return array|null
         */
        public function toArray(bool $bytecode=false): ?array
        {
            if(
                $this->PreInstall == null && $this->PostInstall == null &&
                $this->PreUninstall == null && $this->PostUninstall == null &&
                $this->PreUpdate == null && $this->PostUpdate == null
            )
            {
                return null;
            }

            return [
                ($bytecode ? Functions::cbc('pre_install') : 'pre_install') => ($this->PreInstall == null ? null : $this->PreInstall),
                ($bytecode ? Functions::cbc('post_install') : 'post_install') => ($this->PostInstall == null ? null : $this->PostInstall),
                ($bytecode ? Functions::cbc('pre_uninstall') : 'pre_uninstall') => ($this->PreUninstall == null ? null : $this->PreUninstall),
                ($bytecode? Functions::cbc('post_uninstall') : 'post_uninstall') => ($this->PostUninstall == null ? null : $this->PostUninstall),
                ($bytecode? Functions::cbc('pre_update') : 'pre_update') => ($this->PreUpdate == null ? null : $this->PreUpdate),
                ($bytecode? Functions::cbc('post_update') : 'post_update') => ($this->PostUpdate == null ? null : $this->PostUpdate)
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Installer
         * @noinspection DuplicatedCode
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