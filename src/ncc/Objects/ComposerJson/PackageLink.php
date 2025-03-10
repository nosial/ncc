<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Objects\ComposerJson;

    use ncc\Interfaces\SerializableObjectInterface;
    use ncc\ThirdParty\composer\semver\VersionParser;

    class PackageLink implements SerializableObjectInterface
    {
        /**
         * The name of the package that is required
         *
         * @var string|null
         */
        private $package_name;

        /**
         * The version of the package that is required
         *
         * @var string|null
         */
        private $version;

        /**
         * @param string|null $package_name
         * @param string|null $version
         */
        public function __construct(?string $package_name=null, ?string $version=null)
        {
            $this->package_name = $package_name;
            $this->version = $version;
        }

        /**
         * @return string|null
         */
        public function getPackageName(): ?string
        {
            return $this->package_name;
        }

        /**
         * @return string|null
         */
        public function getVersion(bool $normalized=false): ?string
        {
            if($normalized)
            {
               return (new VersionParser())->parseConstraints($this->version)->getPrettyString();
            }

            return $this->version;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'package_name' => $this->package_name,
                'version' => $this->version
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): PackageLink
        {
            $object = new self();

            if(isset($data['package_name']))
            {
                $object->package_name = $data['package_name'];
            }

            if(isset($data['version']))
            {
                $object->version = $data['version'];
            }

            return $object;
        }
    }