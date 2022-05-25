<?php


    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Vault;

    class DefaultEntry
    {
        /**
         * The alias entry to use for default authentication
         *
         * @var string
         */
        public $Alias;

        /**
         * The source that the alias is for
         *
         * @var string
         */
        public $Source;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'alias' => $this->Alias,
                'source' => $this->Source
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return DefaultEntry
         */
        public static function fromArray(array $data): DefaultEntry
        {
            $DefaultEntryObject = new DefaultEntry();

            if(isset($data['alias']))
            {
                $DefaultEntryObject->Alias = $data['alias'];
            }

            if(isset($data['source']))
            {
                $DefaultEntryObject->Source = $data['source'];
            }

            return $DefaultEntryObject;
        }

    }