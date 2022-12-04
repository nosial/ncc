<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    class ComposerLock
    {
        /**
         * @var string
         */
        public $Readme;

        /**
         * @var string
         */
        public $ContentHash;

        /**
         * @var ComposerJson[]|null
         */
        public $Packages;

        /**
         * @var array|null
         */
        public $PackagesDev;

        /**
         * @var array|null
         */
        public $Aliases;

        /**
         * @var string|null
         */
        public $MinimumStability;

        /**
         * @var array|null
         */
        public $StabilityFlags;

        /**
         * @var bool
         */
        public $PreferStable;

        /**
         * @var bool
         */
        public $PreferLowest;

        /**
         * @var array|null
         */
        public $Platform;

        /**
         * @var array|null
         */
        public $PlatformDev;

        /**
         * @var string|null
         */
        public $PluginApiVersion;

        /**
         * Returns an existing package from the lock file
         *
         * @param string $name
         * @return ComposerJson|null
         */
        public function getPackage(string $name): ?ComposerJson
        {
            foreach($this->Packages as $package)
            {
                if($package->Name == $name)
                {
                    return $package;
                }
            }

            return null;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $_packages = [];
            if($this->Packages != null)
            {
                foreach($this->Packages as $package)
                    $_packages[] = $package->toArray();
            }
            return [
                '_readme' => $this->Readme,
                'content-hash' => $this->ContentHash,
                'packages' => $_packages,
                'packages-dev' => $this->PackagesDev,
                'aliases' => $this->Aliases,
                'minimum-stability' => $this->MinimumStability,
                'stability-flags' => $this->StabilityFlags,
                'prefer-stable' => $this->PreferStable,
                'prefer-lowest' => $this->PreferLowest,
                'platform' => $this->Platform,
                'platform-dev' => $this->PlatformDev,
                'plugin-api-version' => $this->PluginApiVersion,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $obj = new self();
            $obj->Readme = $data['_readme'];
            $obj->ContentHash = $data['content-hash'];
            $obj->Packages = [];
            if($data['packages'] != null)
            {
                foreach($data['packages'] as $package)
                    $obj->Packages[] = ComposerJson::fromArray($package);
            }
            $obj->PackagesDev = $data['packages-dev'];
            $obj->Aliases = $data['aliases'];
            $obj->MinimumStability = $data['minimum-stability'];
            $obj->StabilityFlags = $data['stability-flags'];
            $obj->PreferStable = $data['prefer-stable'];
            $obj->PreferLowest = $data['prefer-lowest'];
            $obj->Platform = $data['platform'];
            $obj->PlatformDev = $data['platform-dev'];
            $obj->PluginApiVersion = $data['plugin-api-version'];
            return $obj;
        }
    }