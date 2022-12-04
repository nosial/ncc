<?php

    namespace ncc\Objects\ComposerJson;

    class PackageLink
    {
        /**
         * The name of the package that is required
         *
         * @var string|null
         */
        public $PackageName;

        /**
         * The version of the package that is required
         *
         * @var string|null
         */
        public $Version;

        /**
         * @param string|null $packageName
         * @param string|null $version
         */
        public function __construct(?string $packageName=null, ?string $version=null)
        {
            $this->PackageName = $packageName;
            $this->Version = $version;
        }

        /**
         * Returns an array representation of object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'package_name' => $this->PackageName,
                'version' => $this->Version
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return PackageLink
         */
        public static function fromArray(array $data): PackageLink
        {
            $object = new self();

            if(isset($data['package_name']))
                $object->PackageName = $data['package_name'];

            if(isset($data['version']))
                $object->Version = $data['version'];

            return $object;
        }
    }