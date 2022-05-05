<?php

    namespace ncc\Objects;

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
         * @var string|null
         */
        public $Flags;

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
            return [
                'version' => $this->Version,
                'branch' => $this->Branch,
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
            
            if(isset($data['version']))
                $NccVersionInformationObject->Version = $data['version'];

            return $NccVersionInformationObject;
        }
    }