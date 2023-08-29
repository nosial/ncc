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
        private $readme;

        /**
         * @var string
         */
        private $content_hash;

        /**
         * @var ComposerJson[]|null
         */
        private $packages;

        /**
         * @var array|null
         */
        private $packages_dev;

        /**
         * @var array|null
         */
        private $aliases;

        /**
         * @var string|null
         */
        private $minimum_stability;

        /**
         * @var array|null
         */
        private $stability_flags;

        /**
         * @var bool
         */
        private $prefer_stable;

        /**
         * @var bool
         */
        private $prefer_lowest;

        /**
         * @var array|null
         */
        private $platform;

        /**
         * @var array|null
         */
        private $platform_dev;

        /**
         * @var string|null
         */
        private $plugin_api_version;

        /**
         * Returns an existing package from the lock file
         *
         * @param string $name
         * @return ComposerJson|null
         */
        public function getPackage(string $name): ?ComposerJson
        {
            foreach($this->packages as $package)
            {
                if($package->getName() === $name)
                {
                    return $package;
                }
            }

            return null;
        }

        /**
         * @return string
         */
        public function getReadme(): string
        {
            return $this->readme;
        }

        /**
         * @param string $readme
         */
        public function setReadme(string $readme): void
        {
            $this->readme = $readme;
        }

        /**
         * @return string
         */
        public function getContentHash(): string
        {
            return $this->content_hash;
        }

        /**
         * @param string $content_hash
         */
        public function setContentHash(string $content_hash): void
        {
            $this->content_hash = $content_hash;
        }

        /**
         * @return ComposerJson[]|null
         */
        public function getPackages(): ?array
        {
            return $this->packages;
        }

        /**
         * @param ComposerJson[]|null $packages
         */
        public function setPackages(?array $packages): void
        {
            $this->packages = $packages;
        }

        /**
         * @return array|null
         */
        public function getPackagesDev(): ?array
        {
            return $this->packages_dev;
        }

        /**
         * @param array|null $packages_dev
         */
        public function setPackagesDev(?array $packages_dev): void
        {
            $this->packages_dev = $packages_dev;
        }

        /**
         * @return array|null
         */
        public function getAliases(): ?array
        {
            return $this->aliases;
        }

        /**
         * @param array|null $aliases
         */
        public function setAliases(?array $aliases): void
        {
            $this->aliases = $aliases;
        }

        /**
         * @return string|null
         */
        public function getMinimumStability(): ?string
        {
            return $this->minimum_stability;
        }

        /**
         * @param string|null $minimum_stability
         */
        public function setMinimumStability(?string $minimum_stability): void
        {
            $this->minimum_stability = $minimum_stability;
        }

        /**
         * @return array|null
         */
        public function getStabilityFlags(): ?array
        {
            return $this->stability_flags;
        }

        /**
         * @param array|null $stability_flags
         */
        public function setStabilityFlags(?array $stability_flags): void
        {
            $this->stability_flags = $stability_flags;
        }

        /**
         * @return bool
         */
        public function isPreferStable(): bool
        {
            return $this->prefer_stable;
        }

        /**
         * @param bool $prefer_stable
         */
        public function setPreferStable(bool $prefer_stable): void
        {
            $this->prefer_stable = $prefer_stable;
        }

        /**
         * @return bool
         */
        public function isPreferLowest(): bool
        {
            return $this->prefer_lowest;
        }

        /**
         * @param bool $prefer_lowest
         */
        public function setPreferLowest(bool $prefer_lowest): void
        {
            $this->prefer_lowest = $prefer_lowest;
        }

        /**
         * @return array|null
         */
        public function getPlatform(): ?array
        {
            return $this->platform;
        }

        /**
         * @param array|null $platform
         */
        public function setPlatform(?array $platform): void
        {
            $this->platform = $platform;
        }

        /**
         * @return array|null
         */
        public function getPlatformDev(): ?array
        {
            return $this->platform_dev;
        }

        /**
         * @param array|null $platform_dev
         */
        public function setPlatformDev(?array $platform_dev): void
        {
            $this->platform_dev = $platform_dev;
        }

        /**
         * @return string|null
         */
        public function getPluginApiVersion(): ?string
        {
            return $this->plugin_api_version;
        }

        /**
         * @param string|null $plugin_api_version
         */
        public function setPluginApiVersion(?string $plugin_api_version): void
        {
            $this->plugin_api_version = $plugin_api_version;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $_packages = [];
            if($this->packages !== null)
            {
                foreach($this->packages as $package)
                {
                    $_packages[] = $package->toArray();
                }
            }
            return [
                '_readme' => $this->readme,
                'content-hash' => $this->content_hash,
                'packages' => $_packages,
                'packages-dev' => $this->packages_dev,
                'aliases' => $this->aliases,
                'minimum-stability' => $this->minimum_stability,
                'stability-flags' => $this->stability_flags,
                'prefer-stable' => $this->prefer_stable,
                'prefer-lowest' => $this->prefer_lowest,
                'platform' => $this->platform,
                'platform-dev' => $this->platform_dev,
                'plugin-api-version' => $this->plugin_api_version,
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

            $object->readme = $data['_readme'];
            $object->content_hash = $data['content-hash'];
            $object->packages = [];
            if($data['packages'] !== null)
            {
                foreach($data['packages'] as $package)
                {
                    $object->packages[] = ComposerJson::fromArray($package);
                }
            }
            $object->packages_dev = $data['packages-dev'];
            $object->aliases = $data['aliases'];
            $object->minimum_stability = $data['minimum-stability'];
            $object->stability_flags = $data['stability-flags'];
            $object->prefer_stable = $data['prefer-stable'];
            $object->prefer_lowest = $data['prefer-lowest'];
            $object->platform = $data['platform'];
            $object->platform_dev = $data['platform-dev'];
            $object->plugin_api_version = $data['plugin-api-version'];

            return $object;
        }
    }