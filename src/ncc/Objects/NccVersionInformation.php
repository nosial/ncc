<?php

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