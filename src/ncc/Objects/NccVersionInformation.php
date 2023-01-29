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

    use ncc\Objects\NccVersionInformation\Component;

    class NccVersionInformation
    {
        /**
         * The current version of the build
         *
         * @var string|null
         */
        public $Version;

        /**
         * The branch of the version
         *
         * @var string|null
         */
        public $Branch;

        /**
         * Flags for the current build
         *
         * @var array|null
         */
        public $Flags;

        /**
         * An array of components that ncc uses and comes pre-built with
         *
         * @var Component[]
         */
        public $Components;

        /**
         * The remote source for where NCC can check for available updates and how to
         * install these updates
         *
         * @var string|null
         */
        public $UpdateSource;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $components = [];

            foreach($this->Components as $component)
            {
                $components[] = $component->toArray();
            }

            return [
                'version' => $this->Version,
                'branch' => $this->Branch,
                'components' =>$components,
                'flags' => $this->Flags
            ];
        }

        /**
         * Constructs an object from an array representation 
         *
         * @param array $data
         * @return NccVersionInformation
         */
        public static function fromArray(array $data): NccVersionInformation
        {
            $NccVersionInformationObject = new NccVersionInformation();

            if(isset($data['flags']))
                $NccVersionInformationObject->Flags = $data['flags'];

            if(isset($data['branch']))
                $NccVersionInformationObject->Branch = $data['branch'];

            if(isset($data['components']))
            {
                foreach($data['components'] as $datum)
                {
                    $NccVersionInformationObject->Components[] = Component::fromArray($datum);
                }
            }

            if(isset($data['version']))
                $NccVersionInformationObject->Version = $data['version'];

            return $NccVersionInformationObject;
        }
    }