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