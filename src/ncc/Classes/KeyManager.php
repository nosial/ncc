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

    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\Signing\SigningKey;
    use RuntimeException;

    class KeyManager
    {
        public const string ENV_SIGNING_KEY = 'NCC_SIGNING_KEY';
        public const string ENV_SIGNING_KEY_PASSPHRASE = 'NCC_SIGNING_KEY_PASSPHRASE';
        private const string KEY_EXTENSION = '.key';

        /**
         * Returns the directory signing keys are stored in, the user's data directory or the system data directory
         * when running as root
         *
         * @return string The keys directory
         */
        public static function getKeysDirectory(): string
        {
            try
            {
                $location = PathResolver::getUserLocation();
            }
            catch(RuntimeException)
            {
                $location = null;
            }

            return ($location ?? PathResolver::getSystemLocation()) . DIRECTORY_SEPARATOR . 'keys';
        }

        /**
         * Validates the name of a key
         *
         * @param string $name The name of the key
         * @throws OperationException If the name is not valid
         */
        public static function validateName(string $name): void
        {
            if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $name))
            {
                throw new OperationException(sprintf('Invalid key name "%s", names may only contain letters, digits, ".", "_" and "-" (up to 64 characters)', $name));
            }
        }

        /**
         * Returns the path of a key file
         *
         * @param string $name The name of the key
         * @return string The path of the key file
         * @throws OperationException If the name is not valid
         */
        public static function getKeyPath(string $name): string
        {
            self::validateName($name);
            return self::getKeysDirectory() . DIRECTORY_SEPARATOR . $name . self::KEY_EXTENSION;
        }

        /**
         * Returns whether a key with the given name exists
         *
         * @param string $name The name of the key
         * @return bool True if the key exists
         * @throws OperationException If the name is not valid
         */
        public static function exists(string $name): bool
        {
            return IO::isFile(self::getKeyPath($name));
        }

        /**
         * Lists the stored keys
         *
         * @return array<string, array{type: \ncc\Enums\SignerType, signer: string, public_key: string}|null> Key name => public key information, null for unreadable keys
         */
        public static function listKeys(): array
        {
            $directory = self::getKeysDirectory();
            if(!IO::isDirectory($directory))
            {
                return [];
            }

            $keys = [];
            foreach(glob($directory . DIRECTORY_SEPARATOR . '*' . self::KEY_EXTENSION) ?: [] as $path)
            {
                $name = basename($path, self::KEY_EXTENSION);
                try
                {
                    $keys[$name] = SigningKey::inspect(IO::readFile($path));
                }
                catch(OperationException|IOException $e)
                {
                    Logger::getLogger()?->warning(sprintf('Failed to read the signing key %s: %s', $path, $e->getMessage()));
                    $keys[$name] = null;
                }
            }

            ksort($keys);
            return $keys;
        }

        /**
         * Reads the encrypted form of a stored key
         *
         * @param string $name The name of the key
         * @return string The encrypted key
         * @throws OperationException If the key does not exist
         * @throws IOException If the key cannot be read
         */
        public static function read(string $name): string
        {
            if(!self::exists($name))
            {
                throw new OperationException(sprintf('The signing key "%s" does not exist, see "ncc key list"', $name));
            }

            return trim(IO::readFile(self::getKeyPath($name)));
        }

        /**
         * Stores the encrypted form of a key, the key file is only readable by the current user
         *
         * @param string $name The name of the key
         * @param string $exported The encrypted key
         * @param bool $overwrite Whether an existing key with the same name may be overwritten
         * @throws OperationException If the key already exists or is not valid
         * @throws IOException If the key cannot be written
         */
        public static function save(string $name, string $exported, bool $overwrite=false): void
        {
            SigningKey::inspect($exported);
            if(!$overwrite && self::exists($name))
            {
                throw new OperationException(sprintf('The signing key "%s" already exists, use --overwrite to replace it', $name));
            }

            $directory = self::getKeysDirectory();
            if(!IO::isDirectory($directory))
            {
                IO::createDirectory($directory, 0700, true);
            }
            IO::chmod($directory, 0700);

            $path = self::getKeyPath($name);
            $previousUmask = umask(0077);
            try
            {
                IO::writeFile($path, trim($exported) . PHP_EOL);
            }
            finally
            {
                umask($previousUmask);
            }
            IO::chmod($path, 0600);
        }

        /**
         * Deletes a stored key
         *
         * @param string $name The name of the key
         * @throws OperationException If the key does not exist
         * @throws IOException If the key cannot be deleted
         */
        public static function delete(string $name): void
        {
            if(!self::exists($name))
            {
                throw new OperationException(sprintf('The signing key "%s" does not exist', $name));
            }

            IO::delete(self::getKeyPath($name), false);
        }

        /**
         * Returns whether a signing key is provided through the NCC_SIGNING_KEY environment variable
         *
         * @return bool True if NCC_SIGNING_KEY is set and not empty
         */
        public static function hasEnvironmentKey(): bool
        {
            $value = getenv(self::ENV_SIGNING_KEY);
            return $value !== false && trim($value) !== '';
        }

        /**
         * Resolves and decrypts a signing key. A named key is read from the key files, without a name the key is
         * taken from the NCC_SIGNING_KEY environment variable. The passphrase is taken from
         * NCC_SIGNING_KEY_PASSPHRASE, or prompted for when running interactively.
         *
         * @param string|null $name The name of a stored key, or null to use NCC_SIGNING_KEY
         * @return SigningKey|null The decrypted key, null if no name is given and NCC_SIGNING_KEY is not set
         * @throws OperationException If the key cannot be found or decrypted
         * @throws IOException If the key cannot be read
         */
        public static function resolve(?string $name=null): ?SigningKey
        {
            if($name !== null)
            {
                $exported = self::read($name);
                $label = sprintf('signing key "%s"', $name);
            }
            elseif(self::hasEnvironmentKey())
            {
                $value = trim(getenv(self::ENV_SIGNING_KEY));
                if(!SigningKey::isExportedKey($value) && IO::isFile($value))
                {
                    $value = trim(IO::readFile($value));
                }

                $exported = $value;
                $label = 'signing key from ' . self::ENV_SIGNING_KEY;
            }
            else
            {
                return null;
            }

            $info = SigningKey::inspect($exported);
            $passphrase = self::getPassphrase(sprintf('Enter the passphrase of the %s (%s "%s"): ', $label, $info['type']->value, $info['signer']));
            return SigningKey::import($exported, $passphrase);
        }

        /**
         * Returns the passphrase of a signing key from NCC_SIGNING_KEY_PASSPHRASE or by prompting for it
         *
         * @param string $prompt The prompt to show
         * @return string The passphrase
         * @throws OperationException If no passphrase is available and the session is not interactive
         */
        public static function getPassphrase(string $prompt): string
        {
            $passphrase = getenv(self::ENV_SIGNING_KEY_PASSPHRASE);
            if($passphrase !== false && $passphrase !== '')
            {
                return $passphrase;
            }

            if(!Console::isInteractive())
            {
                throw new OperationException(sprintf('A passphrase is required to use the signing key, set %s when running non-interactively', self::ENV_SIGNING_KEY_PASSPHRASE));
            }

            return Console::getPassword($prompt, false);
        }

        /**
         * Prompts for a new passphrase twice and returns it
         *
         * @param string $prompt The prompt to show
         * @return string The passphrase
         * @throws OperationException If the passphrases do not match or the passphrase is empty
         */
        public static function promptNewPassphrase(string $prompt='Enter a passphrase for the signing key: '): string
        {
            $passphrase = getenv(self::ENV_SIGNING_KEY_PASSPHRASE);
            if($passphrase !== false && $passphrase !== '')
            {
                return $passphrase;
            }

            if(!Console::isInteractive())
            {
                throw new OperationException(sprintf('A passphrase is required, set %s when running non-interactively', self::ENV_SIGNING_KEY_PASSPHRASE));
            }

            $passphrase = Console::getPassword($prompt, false);
            if($passphrase === '')
            {
                throw new OperationException('The passphrase cannot be empty');
            }

            if(Console::getPassword('Confirm the passphrase: ', false) !== $passphrase)
            {
                throw new OperationException('The passphrases do not match');
            }

            return $passphrase;
        }
    }
