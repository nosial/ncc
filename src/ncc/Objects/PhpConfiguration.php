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

    class PhpConfiguration
    {
        /**
         * The Configuration name
         *
         * @var string|null
         */
        private $Name;

        /**
         * The default value that's globally set
         *
         * @var string
         */
        public $GlobalValue;

        /**
         * The local value that has been modified by the program
         *
         * @var string
         */
        public $LocalValue;

        /**
         * The access level for modifying this configuration value
         *
         * @var string
         */
        public $Access;

        /**
         * Sets a value to this Php Configuration
         *
         * @param string $value
         * @return bool
         * @noinspection PhpUnused
         */
        public function setValue(string $value): bool
        {
            if($this->Name == null)
            {
                return false;
            }

            ini_set($this->Name, $value);
            return true;
        }

        /**
         * Returns the current value set for this configuration
         *
         * @return string
         * @noinspection PhpUnused
         */
        public function getValue(): string
        {
            return $this->LocalValue;
        }

        /**
         * Resets the configuration value to its default state
         *
         * @return bool
         * @noinspection PhpUnused
         */
        public function resetValue(): bool
        {
            if($this->Name == null)
            {
                return false;
            }
            
            ini_restore($this->Name);
            return true;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
         */
        public function toArray(): array
        {
            return [
                'global_value' => $this->GlobalValue,
                'local_value' => $this->LocalValue,
                'access' => $this->Access
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @param string|null $name
         * @return PhpConfiguration
         */
        public static function fromArray(array $data, ?string $name=null): PhpConfiguration
        {
            $PhpConfiguratoinObject = new PhpConfiguration();

            if($name !== null)
            {
                $PhpConfiguratoinObject->Name = $name;
            }

            if(isset($data['global_value']))
            {
                $PhpConfiguratoinObject->GlobalValue = $data['global_value'];
            }

            if(isset($data['local_value']))
            {
                $PhpConfiguratoinObject->LocalValue = $data['local_value'];
            }

            if(isset($data['access']))
            {
                $PhpConfiguratoinObject->Access = $data['access'];
            }

            return $PhpConfiguratoinObject;
        }

        /**
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->Name;
        }
    }