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

    namespace ncc\Objects\NccVersionInformation;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\ComponentVersionNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Utilities\IO;

    class Component
    {
        /**
         * The Vendor of the component
         *
         * @var string
         */
        public $Vendor;

        /**
         * The name of the package
         *
         * @var string
         */
        public $PackageName;

        /**
         * Attempts to resolve the component's build version
         *
         * @return string
         * @throws ComponentVersionNotFoundException
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function getVersion(): string
        {
            $third_party_path = NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR;
            $component_path = $third_party_path . $this->Vendor . DIRECTORY_SEPARATOR . $this->PackageName . DIRECTORY_SEPARATOR;

            if(!file_exists($component_path . 'VERSION'))
                throw new ComponentVersionNotFoundException('The file \'' . $component_path . 'VERSION' . '\' does not exist');

            return IO::fread($component_path . 'VERSION');
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'vendor' => $this->Vendor,
                'package_name' => $this->PackageName
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Component
         */
        public static function fromArray(array $data): Component
        {
            $ComponentObject = new Component();

            if(isset($data['vendor']))
                $ComponentObject->Vendor = $data['vendor'];

            if(isset($data['package_name']))
                $ComponentObject->PackageName = $data['package_name'];

            return $ComponentObject;
        }
    }