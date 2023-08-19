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

    namespace ncc\Objects\PackageLock;

    use ncc\Objects\ProjectConfiguration\Dependency;
    use ncc\Utilities\Functions;

    class DependencyEntry
    {
        /**
         * The name of the package dependency
         *
         * @var string
         */
        public $PackageName;

        /**
         * The version of the package dependency
         *
         * @var string
         */
        public $Version;

        public function __construct(?Dependency $dependency=null)
        {
            if($dependency !== null)
            {
                $this->PackageName = $dependency->name;
                $this->Version = $dependency->version;
            }
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('package_name') : 'package_name') => $this->PackageName,
                ($bytecode ? Functions::cbc('version') : 'version') => $this->Version,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return DependencyEntry
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            $object->PackageName = Functions::array_bc($data, 'package_name');
            $object->Version = Functions::array_bc($data, 'version');

            return $object;
        }
    }