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

    namespace ncc\Objects\Package;

    use ncc\Enums\EncoderType;
    use ncc\Enums\Versions;
    use ncc\Interfaces\SerializableObjectInterface;

    class MagicBytes implements SerializableObjectInterface
    {
        /**
         * The version of the package structure standard that is used
         *
         * @var string
         */
        private $package_structure_version;

        /**
         * The type of encoder that was used to encode the package structure
         *
         * @var string|EncoderType
         */
        private $encoder;

        /**
         * Indicates whether the package structure is compressed
         *
         * @var bool
         */
        private $compressed;

        /**
         * Indicates whether the package structure is encrypted
         *
         * @var bool
         */
        private $encrypted;

        /**
         * Indicates whether the package is installable
         *
         * @var bool
         */
        private $installable;

        /**
         * Indicates whether the package is a executable
         *
         * @var bool
         */
        private $executable;

        /**
         * Basic Public Constructor with default values
         */
        public function __construct()
        {
            $this->package_structure_version = Versions::PACKAGE_STRUCTURE_VERSION;
            $this->encoder = EncoderType::ZI_PROTO;
            $this->compressed = false;
            $this->encrypted = false;
            $this->installable = false;
            $this->executable = false;
        }

        /**
         * @return string
         */
        public function getPackageStructureVersion(): string
        {
            return $this->package_structure_version;
        }

        /**
         * @param string $package_structure_version
         */
        public function setPackageStructureVersion(string $package_structure_version): void
        {
            $this->package_structure_version = $package_structure_version;
        }

        /**
         * @return EncoderType|string
         */
        public function getEncoder(): EncoderType|string
        {
            return $this->encoder;
        }

        /**
         * @param EncoderType|string $encoder
         */
        public function setEncoder(EncoderType|string $encoder): void
        {
            $this->encoder = $encoder;
        }

        /**
         * @return bool
         */
        public function isCompressed(): bool
        {
            return $this->compressed;
        }

        /**
         * @param bool $compressed
         */
        public function setCompressed(bool $compressed): void
        {
            $this->compressed = $compressed;
        }

        /**
         * @return bool
         */
        public function isEncrypted(): bool
        {
            return $this->encrypted;
        }

        /**
         * @param bool $encrypted
         */
        public function setEncrypted(bool $encrypted): void
        {
            $this->encrypted = $encrypted;
        }

        /**
         * @return bool
         */
        public function isInstallable(): bool
        {
            return $this->installable;
        }

        /**
         * @param bool $installable
         */
        public function setInstallable(bool $installable): void
        {
            $this->installable = $installable;
        }

        /**
         * @return bool
         */
        public function isExecutable(): bool
        {
            return $this->executable;
        }

        /**
         * @param bool $executable
         */
        public function setExecutable(bool $executable): void
        {
            $this->executable = $executable;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'package_structure_version' => $this->package_structure_version,
                'encoder' => $this->encoder,
                'is_compressed' => $this->compressed,
                'is_encrypted'  => $this->encrypted,
                'is_installable' => $this->installable,
                'is_executable' => $this->executable
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['is_executable']))
            {
                $object->executable = (bool)$data['is_executable'];
            }

            return $object;
        }

        /**
         * Builds and returns the string representation of the magic bytes
         *
         * @return string
         */
        public function toString(): string
        {
            // NCC_PACKAGE1.0
            $magic_bytes = 'NCC_PACKAGE' . $this->package_structure_version;

            // NCC_PACKAGE1.03
            $magic_bytes .= $this->encoder;

            if($this->encrypted)
            {
                // NCC_PACKAGE1.031
                $magic_bytes .= '1';
            }
            else
            {
                // NCC_PACKAGE1.030
                $magic_bytes .= '0';
            }

            if($this->compressed)
            {
                // NCC_PACKAGE1.0301
                $magic_bytes .= '1';
            }
            else
            {
                // NCC_PACKAGE1.0300
                $magic_bytes .= '0';
            }

            if($this->executable && $this->installable)
            {
                // NCC_PACKAGE1.030142
                $magic_bytes .= '42';
            }
            elseif($this->executable)
            {
                // NCC_PACKAGE1.030141
                $magic_bytes .= '41';
            }
            elseif($this->installable)
            {
                // NCC_PACKAGE1.030140
                $magic_bytes .= '40';
            }
            else
            {
                // If no type is specified, default to installable only
                // NCC_PACKAGE1.030140
                $magic_bytes .= '40';
            }

            return $magic_bytes;
        }
    }