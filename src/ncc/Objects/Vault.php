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

    use ncc\Enums\AuthenticationType;
    use ncc\Enums\Versions;
    use ncc\Exceptions\RuntimeException;
    use ncc\Interfaces\PasswordInterface;
    use ncc\Objects\Vault\Entry;
    use ncc\Utilities\Functions;

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
         * Public Constructor
         */
        public function __construct()
        {
            $this->Version = Versions::CREDENTIALS_STORE_VERSION;
            $this->Entries = [];
        }

        /**
         * Adds a new entry to the vault
         *
         * @param string $name
         * @param PasswordInterface $password
         * @param bool $encrypt
         * @return bool
         * @noinspection PhpUnused
         */
        public function addEntry(string $name, PasswordInterface $password, bool $encrypt=true): bool
        {
            // Check if the entry already exists
            foreach($this->Entries as $entry)
            {
                if($entry->getName() === $name)
                    return false;
            }

            // Create the new entry
            $entry = new Entry();
            $entry->setName($name);
            $entry->setEncrypted($encrypt);
            $entry->setAuthentication($password);

            // Add the entry to the vault
            $this->Entries[] = $entry;
            return true;
        }

        /**
         * Deletes an entry from the vault
         *
         * @param string $name
         * @return bool
         * @noinspection PhpUnused
         */
        public function deleteEntry(string $name): bool
        {
            // Find the entry
            foreach($this->Entries as $index => $entry)
            {
                if($entry->getName() === $name)
                {
                    // Remove the entry
                    unset($this->Entries[$index]);
                    return true;
                }
            }

            // Entry not found
            return false;
        }

        /**
         * Returns all the entries in the vault
         *
         * @return array|Entry[]
         * @noinspection PhpUnused
         */
        public function getEntries(): array
        {
            return $this->Entries;
        }

        /**
         * Returns an existing entry from the vault
         *
         * @param string $name
         * @return Entry|null
         */
        public function getEntry(string $name): ?Entry
        {
            foreach($this->Entries as $entry)
            {
                if($entry->getName() === $name)
                    return $entry;
            }

            return null;
        }

        /**
         * Authenticates an entry in the vault
         *
         * @param string $name
         * @param string $password
         * @return bool
         * @throws RuntimeException
         * @noinspection PhpUnused
         */
        public function authenticate(string $name, string $password): bool
        {
            $entry = $this->getEntry($name);
            if($entry === null)
                return false;

            if($entry->getPassword() === null)
            {
                if($entry->isEncrypted() && !$entry->isCurrentlyDecrypted())
                {
                    return $entry->unlock($password);
                }
            }

            $input = [];
            switch($entry->getPassword()->getAuthenticationType())
            {
                case AuthenticationType::USERNAME_PASSWORD:
                    $input = ['password' => $password];
                    break;
                case AuthenticationType::ACCESS_TOKEN:
                    $input = ['token' => $password];
                    break;
            }

            return $entry->authenticate($input);
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $entries = [];
            foreach($this->Entries as $entry)
            {
                $entries[] = $entry->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('version') : 'version') => $this->Version,
                ($bytecode ? Functions::cbc('entries') : 'entries') => $entries,
            ];
        }

        /**
         * Constructs a new object from an array
         *
         * @param array $array
         * @return Vault
         */
        public static function fromArray(array $array): Vault
        {
            $vault = new Vault();
            $vault->Version = Functions::array_bc($array, 'version');
            $entries = Functions::array_bc($array, 'entries');
            $vault->Entries = [];

            foreach($entries as $entry)
                $vault->Entries[] = Entry::fromArray($entry);

            return $vault;
        }

    }