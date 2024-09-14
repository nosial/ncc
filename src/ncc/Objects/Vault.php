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

    use InvalidArgumentException;
    use ncc\Enums\Types\AuthenticationType;
    use ncc\Enums\Versions;
    use ncc\Interfaces\AuthenticationInterface;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\Vault\Entry;
    use ncc\Utilities\Functions;

    class Vault implements BytecodeObjectInterface
    {
        /**
         * The vault's current version for backwards compatibility
         *
         * @var string
         */
        public $version;

        /**
         * The vault's stored credential entries
         *
         * @var Entry[]
         */
        public $entries;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->version = Versions::CREDENTIALS_STORE_VERSION->value;
            $this->entries = [];
        }

        /**
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version;
        }

        /**
         * @param string $version
         */
        public function setVersion(string $version): void
        {
            $this->version = $version;
        }

        /**
         * Adds a new entry to the vault
         *
         * @param string $name
         * @param AuthenticationInterface $password
         * @param bool $encrypt
         * @return bool
         * @noinspection PhpUnused
         */
        public function addEntry(string $name, AuthenticationInterface $password, bool $encrypt=true): bool
        {
            // Check if the entry already exists
            foreach($this->entries as $entry)
            {
                if($entry->getName() === $name)
                {
                    return false;
                }
            }

            // Create the new entry
            $entry = new Entry();
            $entry->setName($name);
            $entry->setEncrypted($encrypt);
            $entry->setAuthentication($password);

            // Add the entry to the vault
            $this->entries[] = $entry;
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
            foreach($this->entries as $index => $entry)
            {
                if($entry->getName() === $name)
                {
                    // Remove the entry
                    unset($this->entries[$index]);
                    return true;
                }
            }

            // Entry isn't found
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
            return $this->entries;
        }

        /**
         * Returns an existing entry from the vault
         *
         * @param string $name
         * @return Entry
         */
        public function getEntry(string $name): Entry
        {
            foreach($this->entries as $entry)
            {
                if($entry->getName() === $name)
                {
                    return $entry;
                }
            }

            throw new InvalidArgumentException(sprintf('Entry "%s" does not exist in the vault', $name));
        }

        /**
         * Authenticates an entry in the vault
         *
         * @param string $name
         * @param string $password
         * @return bool
         */
        public function authenticate(string $name, string $password): bool
        {
            $entry = $this->getEntry($name);

            if(($entry->getPassword() === null) && $entry->isEncrypted() && !$entry->isCurrentlyDecrypted())
            {
                return $entry->unlock($password);
            }

            $input = [];
            switch($entry->getPassword()->getAuthenticationType())
            {
                case AuthenticationType::USERNAME_PASSWORD->value:
                    $input = ['password' => $password];
                    break;
                case AuthenticationType::ACCESS_TOKEN->value:
                    $input = ['token' => $password];
                    break;
            }

            return $entry->authenticate($input);
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $entries = [];

            foreach($this->entries as $entry)
            {
                $entries[] = $entry->toArray($bytecode);
            }

            return [
                ($bytecode ? Functions::cbc('version') : 'version') => $this->version,
                ($bytecode ? Functions::cbc('entries') : 'entries') => $entries,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Vault
        {
            $vault = new Vault();
            $vault->version = Functions::array_bc($data, 'version');
            $entries = Functions::array_bc($data, 'entries');
            $vault->entries = [];

            foreach($entries as $entry)
            {
                $vault->entries[] = Entry::fromArray($entry);
            }

            return $vault;
        }

    }