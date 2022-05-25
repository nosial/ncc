<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\Vault;

    use ncc\Abstracts\RemoteAuthenticationType;
    use ncc\Abstracts\RemoteSource;

    class Entry
    {
        /**
         * The unique alias of the source entry, can also be used for remote resource fetching for dependencies with the
         * following example schemes;
         *
         *  - alias@github.com/org/package
         *  - alias@git.example.org/org/package
         *  - alias@gitlab.com/org/package
         *
         * @var string
         */
        public $Alias;

        /**
         * The remote source of the entry, currently only supported sources are allowed.
         *
         * @var string|RemoteSource
         */
        public $Source;

        /**
         * The host of the remote source, eg; github.com or git.example.org, will be used for remote resource fetching
         * for dependencies with the following example schemes;
         *
         *  - github.com/org/package
         *  - git.example.org/org/package
         *  - gitlab.com/org/package
         *
         * @var string
         */
        public $SourceHost;

        /**
         * @var string|RemoteAuthenticationType
         */
        public $AuthenticationType;

        /**
         * Indicates if the authentication details are encrypted or not, if encrypted a passphrase is required
         * by the user
         *
         * @var bool
         */
        public $Encrypted;

        /**
         * The authentication details.
         *
         * If the remote authentication type is private access token, the first index (0) would be the key itself
         * If the remote authentication type is a username and password, first index would be Username and second
         * would be the password.
         *
         * @var array
         */
        public $Authentication;

        /**
         * Returns an array representation of the object
         *
         * @return array
         * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
         */
        public function toArray(): array
        {
            return [
                'alias' => $this->Alias,
                'source' => $this->Source,
                'source_host' => $this->SourceHost,
                'authentication_type' => $this->AuthenticationType,
                'encrypted' => $this->Encrypted,
                'authentication' => $this->Authentication
            ];
        }

        /**
         * Returns an array representation of the object
         *
         * @param array $data
         * @return Entry
         */
        public static function fromArray(array $data): Entry
        {
            $EntryObject = new Entry();

            if(isset($data['alias']))
            {
                $EntryObject->Alias = $data['alias'];
            }

            if(isset($data['source']))
            {
                $EntryObject->Source = $data['source'];
            }

            if(isset($data['source_host']))
            {
                $EntryObject->SourceHost = $data['source_host'];
            }

            if(isset($data['authentication_type']))
            {
                $EntryObject->AuthenticationType = $data['authentication_type'];
            }

            if(isset($data['encrypted']))
            {
                $EntryObject->Encrypted = $data['encrypted'];
            }

            if(isset($data['authentication']))
            {
                $EntryObject->Authentication = $data['authentication'];
            }

            return $EntryObject;
        }
    }