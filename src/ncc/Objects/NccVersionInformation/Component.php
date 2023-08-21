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

    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Utilities\IO;
    use RuntimeException;

    class Component
    {
        /**
         * The Vendor of the component
         *
         * @var string
         */
        public $vendor;

        /**
         * The name of the package
         *
         * @var string
         */
        public $package_name;

        /**
         * Attempts to resolve the component's build version
         *
         * @return string
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getVersion(): string
        {
            $third_party_path = NCC_EXEC_LOCATION . DIRECTORY_SEPARATOR . 'ThirdParty' . DIRECTORY_SEPARATOR;
            $component_path = $third_party_path . $this->vendor . DIRECTORY_SEPARATOR . $this->package_name . DIRECTORY_SEPARATOR;

            if(!file_exists($component_path . 'VERSION'))
            {
                throw new RuntimeException(sprintf('Component %s/%s does not have a VERSION stub file', $this->vendor, $this->package_name));
            }

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
                'vendor' => $this->vendor,
                'package_name' => $this->package_name
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return Component
         */
        public static function fromArray(array $data): Component
        {
            $object = new self();

            if(isset($data['vendor']))
            {
                $object->vendor = $data['vendor'];
            }

            if(isset($data['package_name']))
            {
                $object->package_name = $data['package_name'];
            }

            return $object;
        }
    }