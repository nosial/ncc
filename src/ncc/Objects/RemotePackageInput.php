<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use ncc\Abstracts\RegexPatterns;

    class RemotePackageInput
    {
        /**
         * @var string|null
         */
        public $Vendor;

        /**
         * @var string|null
         */
        public $Package;

        /**
         * @var string|null
         */
        public $Version;

        /**
         * @var string|null
         */
        public $Branch;

        /**
         * @var string|null
         */
        public $Source;

        /**
         * Public Constructor & String Parser
         *
         * @param string|null $input
         */
        public function __construct(?string $input=null)
        {
            if($input !== null && preg_match(RegexPatterns::RemotePackage, $input, $matches))
            {
                $this->Vendor = $matches['vendor'];
                $this->Package = $matches['package'];
                $this->Version = $matches['version'];
                $this->Branch = $matches['branch'];
                $this->Source = $matches['source'];

                if(strlen($this->Vendor) == 0)
                    $this->Vendor = null;
                if(strlen($this->Package) == 0)
                    $this->Package = null;
                if(strlen($this->Version) == 0)
                    $this->Version = null;
                if(strlen($this->Branch) == 0)
                    $this->Branch = null;
                if(strlen($this->Source) == 0)
                    $this->Source = null;
            }
        }

        /**
         * Returns a string representation of the input
         *
         * @return string
         */
        public function toString(): string
        {
            if($this->Vendor == null || $this->Package == null)
            {
                return '';
            }

            $results = $this->Vendor . '/' . $this->Package;

            if($this->Version !== null)
                $results .= '=' . $this->Version;
            if($this->Branch !== null)
                $results .= ':' . $this->Branch;
            if($this->Source !== null)
                $results .= '@' . $this->Source;

            return $results;
        }

        /**
         * Returns a standard package name string representation
         *
         * @param bool $version
         * @return string
         */
        public function toStandard(bool $version=true): string
        {
            if($version)
                return str_replace('-', '_', sprintf('com.%s.%s=%s', $this->Vendor, $this->Package, $this->Version));
            return str_replace('-', '_', sprintf('com.%s.%s', $this->Vendor, $this->Package));
        }
    }