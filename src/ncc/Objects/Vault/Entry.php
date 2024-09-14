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

    namespace ncc\Objects\Vault;

    use Exception;
    use ncc\Defuse\Crypto\Crypto;
    use ncc\Defuse\Crypto\Exception\EnvironmentIsBrokenException;
    use ncc\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
    use ncc\Enums\Types\AuthenticationType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Interfaces\AuthenticationInterface;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\Vault\Password\AccessToken;
    use ncc\Objects\Vault\Password\UsernamePassword;
    use ncc\Utilities\Functions;
    use RuntimeException;

    class Entry implements BytecodeObjectInterface
    {
        /**
         * The entry's unique identifier
         *
         * @var string
         */
        private $name;

        /**
         * Whether the entry's password is encrypted
         *
         * @var bool
         */
        private $encrypted;

        /**
         * The entry's password
         *
         * @var AuthenticationInterface|string|null
         */
        private $password;

        /**
         * Whether the entry's password is currently decrypted in memory
         * (Not serialized)
         *
         * @var bool
         */
        private $currently_decrypted;

        /**
         * Returns an array representation of the object
         *
         */
        public function __construct()
        {
            $this->encrypted = true;
            $this->currently_decrypted = true;
        }

        /**
         * Test Authenticates the entry
         *
         * For UsernamePassword the $input parameter expects an array with the keys 'username' and 'password'
         * For AccessToken the $input parameter expects an array with the key 'token'
         *
         * @param array $input
         * @return bool
         * @noinspection PhpUnused
         */
        public function authenticate(array $input): bool
        {
            if(!$this->currently_decrypted)
            {
                return false;
            }

            if($this->password === null)
            {
                return false;
            }

            switch($this->password->getAuthenticationType())
            {
                case AuthenticationType::USERNAME_PASSWORD->value:
                    if(!($this->password instanceof UsernamePassword))
                    {
                        return false;
                    }

                    $username = $input['username'] ?? null;
                    $password = $input['password'] ?? null;

                    if($username === null && $password === null)
                    {
                        return false;
                    }

                    if($username === null)
                    {
                        return $password === $this->password->getPassword();
                    }

                    if($password === null)
                    {
                        return $username === $this->password->getUsername();
                    }

                    return $username === $this->password->getUsername() && $password === $this->password->getPassword();

                case AuthenticationType::ACCESS_TOKEN->value:
                    if(!($this->password instanceof AccessToken))
                    {
                        return false;
                    }

                    $token = $input['token'] ?? null;

                    if($token === null)
                    {
                        return false;
                    }

                    return $token === $this->password->getAccessToken();

                default:
                    return false;

            }
        }

        /**
         * @param AuthenticationInterface $password
         * @return void
         */
        public function setAuthentication(AuthenticationInterface $password): void
        {
            $this->password = $password;
        }

        /**
         * @return bool
         * @noinspection PhpUnused
         */
        public function isCurrentlyDecrypted(): bool
        {
            return $this->currently_decrypted;
        }

        /**
         * Locks the entry by encrypting the password
         *
         * @return bool
         */
        public function lock(): bool
        {
            if($this->password === null)
            {
                return false;
            }

            if($this->encrypted)
            {
                return false;
            }

            if(!$this->currently_decrypted)
            {
                return false;
            }

            if(!($this->password instanceof AuthenticationInterface))
            {
                return false;
            }

            $this->password = $this->encrypt();
            return true;
        }

        /**
         * Unlocks the entry by decrypting the password
         *
         * @param string $password
         * @return bool
         * @throws Exception
         */
        public function unlock(string $password): bool
        {
            if($this->password === null)
            {
                sleep(random_int(2, 3));
                return false;
            }

            if(!$this->encrypted)
            {
                sleep(random_int(2, 3));
                return false;
            }

            if($this->currently_decrypted)
            {
                sleep(random_int(2, 3));
                return false;
            }

            if(!is_string($this->password))
            {
                sleep(random_int(2, 3));
                return false;
            }

            try
            {
                $password = Crypto::decryptWithPassword($this->password, $password, true);
            }
            catch (EnvironmentIsBrokenException $e)
            {
                throw new RuntimeException(sprintf('Cannot decrypt password: %s', $e->getMessage()), $e->getCode(), $e);
            }
            catch (WrongKeyOrModifiedCiphertextException $e)
            {
                unset($e);
                sleep(random_int(2, 3));
                return false;
            }

            $this->password = ZiProto::decode($password);
            $this->currently_decrypted = true;

            return true;
        }

        /**
         * Returns the password object as an encrypted binary string
         *
         * @return string|null
         */
        private function encrypt(): ?string
        {
            if(!$this->currently_decrypted)
            {
                return false;
            }

            if($this->password === null)
            {
                return false;
            }

            if(!($this->password instanceof AuthenticationInterface))
            {
                return null;
            }

            $data = ZiProto::encode($this->password->toArray(true));

            try
            {
                return Crypto::encryptWithPassword($data, (string)$this->password, true);
            }
            catch(EnvironmentIsBrokenException $e)
            {
                throw new RuntimeException(sprintf('Cannot encrypt password: %s', $e->getMessage()), $e->getCode(), $e);
            }
        }

        /**
         * @return bool
         */
        public function isEncrypted(): bool
        {
            return $this->encrypted;
        }

        /**
         * Returns false if the entry needs to be decrypted first
         *
         * @param bool $encrypted
         * @return bool
         */
        public function setEncrypted(bool $encrypted): bool
        {
            if(!$this->currently_decrypted)
            {
                return false;
            }

            $this->encrypted = $encrypted;
            return true;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return AuthenticationInterface
         */
        public function getPassword(): AuthenticationInterface
        {
            if(!$this->currently_decrypted)
            {
                throw new RuntimeException(sprintf('Cannot get password for entry "%s" because it is currently encrypted', $this->name));
            }

            return $this->password;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            if($this->password !== null)
            {
                if($this->encrypted && $this->currently_decrypted)
                {
                    $password = $this->encrypt();
                }
                elseif($this->encrypted)
                {
                    $password = $this->password;
                }
                else
                {
                    $password = $this->password->toArray(true);
                }
            }
            else
            {
                $password = $this->password;
            }

            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->name,
                ($bytecode ? Functions::cbc('encrypted') : 'encrypted') => $this->encrypted,
                ($bytecode ? Functions::cbc('password') : 'password') => $password,
            ];
        }

        /**
         * Constructs an object from an array representation
         *
         * @param array $data
         * @return Entry
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): self
        {
            $self = new self();

            $self->name = Functions::array_bc($data, 'name');
            $self->encrypted = Functions::array_bc($data, 'encrypted');
            $password = Functions::array_bc($data, 'password');

            if($password !== null)
            {
                if($self->encrypted)
                {
                    $self->password = $password;
                    $self->currently_decrypted = false;
                }
                elseif(is_array($password))
                {
                    $self->password = match (Functions::array_bc($password, 'authentication_type'))
                    {
                        AuthenticationType::USERNAME_PASSWORD->value => UsernamePassword::fromArray($password),
                        AuthenticationType::ACCESS_TOKEN->value => AccessToken::fromArray($password)
                    };
                }
            }

            return $self;
        }
    }