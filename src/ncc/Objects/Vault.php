<?php

    namespace ncc\Objects;

    use ncc\Objects\Vault\DefaultEntry;
    use ncc\Objects\Vault\Entry;

    class Vault
    {
        /**
         * The vault's current version for backwards compatibility
         *
         * @var string
         */
        public $Version;

        /**
         * The vault's stored credential entries
         *
         * @var Entry[]
         */
        public $Entries;

        /**
         *
         *
         * @var DefaultEntry[]
         */
        public $DefaultEntries;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Entries = [];
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $Entries = [];

            foreach($this->Entries as $entry)
            {
                $Entries[] = $entry->toArray();
            }

            return [
                'version' => $this->Version,
                'entries' => $Entries
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return Vault
         */
        public static function fromArray(array $data): Vault
        {
            $VaultObject = new Vault();

            if(isset($data['version']))
                $VaultObject->Version = $data['version'];

            if(isset($data['entries']))
            {
                foreach($data['entries'] as $entry)
                {
                    $VaultObject->Entries[] = Entry::fromArray($entry);
                }
            }

            return $VaultObject;
        }
    }