<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Package;

    use ncc\Abstracts\EncoderType;
    use ncc\Abstracts\Versions;

    class MagicBytes
    {
        /**
         * The version of the package structure standard that is used
         *
         * @var string
         */
        public $PackageStructureVersion;

        /**
         * The type of encoder that was used to encode the package structure
         *
         * @var string|EncoderType
         */
        public $Encoder;

        /**
         * Indicates whether the package structure is compressed
         *
         * @var bool
         */
        public $IsCompressed;

        /**
         * Indicates whether the package structure is encrypted
         *
         * @var bool
         */
        public $IsEncrypted;

        /**
         * Indicates whether the package is installable
         *
         * @var bool
         */
        public $IsInstallable;

        /**
         * Indicates whether the package is a executable
         *
         * @var bool
         */
        public $IsExecutable;

        /**
         * Basic Public Constructor with default values
         */
        public function __construct()
        {
            $this->PackageStructureVersion = Versions::PackageStructureVersion;
            $this->Encoder = EncoderType::ZiProto;
            $this->IsCompressed = false;
            $this->IsEncrypted = false;
            $this->IsInstallable = false;
            $this->IsExecutable = false;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'package_structure_version' => $this->PackageStructureVersion,
                'encoder' => $this->Encoder,
                'is_compressed' => $this->IsCompressed,
                'is_encrypted'  => $this->IsEncrypted,
                'is_installable' => $this->IsInstallable,
                'is_executable' => $this->IsExecutable
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return MagicBytes
         */
        public static function fromArray(array $data): self
        {
            $Object = new self();

            if(isset($data['is_executable']))
                $Object->IsExecutable = (bool)$data['is_executable'];

            return $Object;
        }

        /**
         * Builds and returns the string representation of the magic bytes
         *
         * @return string
         */
        public function toString(): string
        {
            // NCC_PACKAGE1.0
            $magic_bytes = 'NCC_PACKAGE' . $this->PackageStructureVersion;

            // NCC_PACKAGE1.03
            $magic_bytes .= $this->Encoder;

            if($this->IsEncrypted)
            {
                // NCC_PACKAGE1.031
                $magic_bytes .= '1';
            }
            else
            {
                // NCC_PACKAGE1.030
                $magic_bytes .= '0';
            }

            if($this->IsCompressed)
            {
                // NCC_PACKAGE1.0301
                $magic_bytes .= '1';
            }
            else
            {
                // NCC_PACKAGE1.0300
                $magic_bytes .= '0';
            }

            if($this->IsExecutable && $this->IsInstallable)
            {
                // NCC_PACKAGE1.030142
                $magic_bytes .= '42';
            }
            elseif($this->IsExecutable)
            {
                // NCC_PACKAGE1.030141
                $magic_bytes .= '41';
            }
            elseif($this->IsInstallable)
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