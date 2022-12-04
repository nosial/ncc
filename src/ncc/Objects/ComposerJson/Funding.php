<?php

    namespace ncc\Objects\ComposerJson;

    class Funding
    {
        /**
         * The type of funding, or the platform through which
         * funding can be provided. eg, patreon, opencollective,
         * tidelift or GitHub
         *
         * @var string|null
         */
        public $Type;

        /**
         * URL to a website with details, and a way to fund
         * the package
         *
         * @var string|null
         */
        public $URL;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'type' => $this->Type,
                'url' => $this->URL
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['type']))
                $object->Type = $data['type'];

            if(isset($data['url']))
                $object->URL = $data['url'];

            return $object;
        }
    }