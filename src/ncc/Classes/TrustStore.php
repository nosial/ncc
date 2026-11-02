<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Classes;

    use Exception;
    use ncc\Enums\SignerType;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\Signing\TrustedKey;

    class TrustStore
    {
        private string $dataDirectoryPath;
        private string $trustStorePath;
        /** @var TrustedKey[] */
        private array $entries;

        /**
         * Public Constructor
         *
         * @param string $dataDirectoryPath The ncc data directory the trust store is located in
         */
        public function __construct(string $dataDirectoryPath)
        {
            Logger::getLogger()?->debug(sprintf('Initializing TrustStore for: %s', $dataDirectoryPath));

            $this->dataDirectoryPath = $dataDirectoryPath;
            $this->trustStorePath = $this->dataDirectoryPath . DIRECTORY_SEPARATOR . 'trusted_keys.json';
            $this->entries = [];

            if(IO::isFile($this->trustStorePath))
            {
                try
                {
                    $data = json_decode(IO::readFile($this->trustStorePath), true);
                }
                catch(IOException $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to read the trust store %s: %s', $this->trustStorePath, $e->getMessage()));
                    $data = null;
                }

                if(is_array($data))
                {
                    foreach($data as $entry)
                    {
                        try
                        {
                            $this->entries[] = TrustedKey::fromArray($entry);
                        }
                        catch(Exception $e)
                        {
                            Logger::getLogger()?->warning(sprintf('Skipping invalid trusted key in %s: %s', $this->trustStorePath, $e->getMessage()));
                        }
                    }

                    Logger::getLogger()?->verbose(sprintf('Loaded %d trusted keys', count($this->entries)));
                }
            }
        }

        /**
         * Returns the data directory path
         *
         * @return string
         */
        public function getDataDirectoryPath(): string
        {
            return $this->dataDirectoryPath;
        }

        /**
         * Returns the path of the trust store file
         *
         * @return string
         */
        public function getTrustStorePath(): string
        {
            return $this->trustStorePath;
        }

        /**
         * Returns all the keys of the trust store, including revoked keys
         *
         * @return TrustedKey[]
         */
        public function getEntries(): array
        {
            return $this->entries;
        }

        /**
         * Returns the keys with the given key ID, a key can be trusted for more than one identity
         *
         * @param string $keyId The key ID
         * @return TrustedKey[] The matching keys
         */
        public function findByKeyId(string $keyId): array
        {
            $keyId = self::normalizeKeyId($keyId);
            return array_values(array_filter($this->entries, fn(TrustedKey $entry) => $entry->getKeyId() === $keyId));
        }

        /**
         * Trusts a key, if the key is already trusted for the same identity the existing entry is replaced (which
         * also un-revokes it)
         *
         * @param TrustedKey $trustedKey The key to trust
         * @return void
         */
        public function add(TrustedKey $trustedKey): void
        {
            $this->entries = array_values(array_filter($this->entries, fn(TrustedKey $entry) =>
                !($entry->getKeyId() === $trustedKey->getKeyId() && $entry->isFor($trustedKey->getSignerType(), $trustedKey->getSigner()))
            ));

            $this->entries[] = $trustedKey;
        }

        /**
         * Removes a key from the trust store, optionally only for the given identity
         *
         * @param string $keyId The key ID of the key to remove
         * @param SignerType|null $signerType Only remove the key for this identity type
         * @param string|null $signer Only remove the key for this signer
         * @return int The number of removed entries
         */
        public function remove(string $keyId, ?SignerType $signerType=null, ?string $signer=null): int
        {
            $keyId = self::normalizeKeyId($keyId);
            $count = count($this->entries);
            $this->entries = array_values(array_filter($this->entries, function(TrustedKey $entry) use ($keyId, $signerType, $signer)
            {
                if($entry->getKeyId() !== $keyId)
                {
                    return true;
                }

                if($signerType !== null && $signer !== null)
                {
                    return !$entry->isFor($signerType, $signer);
                }

                return false;
            }));

            return $count - count($this->entries);
        }

        /**
         * Revokes a key, packages signed by a revoked key are refused and the key is never prompted for again
         *
         * @param string $keyId The key ID of the key to revoke
         * @return int The number of revoked entries
         */
        public function revoke(string $keyId): int
        {
            $count = 0;
            foreach($this->findByKeyId($keyId) as $entry)
            {
                $entry->setRevoked(true);
                $count++;
            }

            return $count;
        }

        /**
         * Normalizes a key ID given by the user (a fingerprint with spaces or any case)
         *
         * @param string $keyId The key ID or fingerprint
         * @return string The normalized key ID
         */
        public static function normalizeKeyId(string $keyId): string
        {
            return strtolower(str_replace([' ', ':'], '', trim($keyId)));
        }

        /**
         * Saves the trust store
         *
         * @return bool True if the trust store was saved, false if the data directory is not writable
         */
        public function save(): bool
        {
            if(!IO::isDirectory($this->dataDirectoryPath))
            {
                try
                {
                    IO::createDirectory($this->dataDirectoryPath);
                }
                catch(IOException)
                {
                    return false;
                }
            }

            if(!IO::isWritable($this->dataDirectoryPath) || (IO::exists($this->trustStorePath) && !IO::isWritable($this->trustStorePath)))
            {
                Logger::getLogger()?->warning(sprintf('Cannot save the trust store, %s is not writable', $this->trustStorePath));
                return false;
            }

            try
            {
                IO::writeFile($this->trustStorePath, json_encode(array_map(fn(TrustedKey $entry) => $entry->toArray(), $this->entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                IO::chmod($this->trustStorePath, 0644);
            }
            catch(IOException $e)
            {
                Logger::getLogger()?->warning(sprintf('Failed to save the trust store %s: %s', $this->trustStorePath, $e->getMessage()));
                return false;
            }

            return true;
        }
    }
