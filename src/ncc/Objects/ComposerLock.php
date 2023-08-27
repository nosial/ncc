<?php
/*
 * Copyright (c) Nosial 2022-2023, all rights reserved.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
 *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
 *  of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

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
                if($package->name === $name)
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
            if($this->Packages !== null)
            {
                foreach($this->Packages as $package)
                {
                    $_packages[] = $package->toArray();
                }
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
        public static function fromArray(array $data): ComposerLock
        {
            $object = new self();

            $object->Readme = $data['_readme'];
            $object->ContentHash = $data['content-hash'];
            $object->Packages = [];
            if($data['packages'] !== null)
            {
                foreach($data['packages'] as $package)
                {
                    $object->Packages[] = ComposerJson::fromArray($package);
                }
            }
            $object->PackagesDev = $data['packages-dev'];
            $object->Aliases = $data['aliases'];
            $object->MinimumStability = $data['minimum-stability'];
            $object->StabilityFlags = $data['stability-flags'];
            $object->PreferStable = $data['prefer-stable'];
            $object->PreferLowest = $data['prefer-lowest'];
            $object->Platform = $data['platform'];
            $object->PlatformDev = $data['platform-dev'];
            $object->PluginApiVersion = $data['plugin-api-version'];

            return $object;
        }
    }