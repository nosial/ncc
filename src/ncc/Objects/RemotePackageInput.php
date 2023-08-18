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

    use ncc\Enums\RegexPatterns;

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
            if($input !== null && preg_match(RegexPatterns::REMOTE_PACKAGE, $input, $matches))
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