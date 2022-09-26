<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    class NccUpdateInformation
    {
        /**
         * The version number 
         *
         * @var string|null
         */
        public $Version;

        /**
         * The URL source for where the update can be obtained
         *
         * @var string|null
         */
        public $DownloadSource;

        /**
         * Indicates if authentication is required or not to download from the source
         *
         * @var bool
         */
        public $AuthenticationRequired;

        /**
         * The username to use for the authentication if provided by the server
         *
         * @var string|null
         */
        public $AuthenticationUsername;

        /**
         * The password to use for the authentication if provided by the server
         *
         * @var string
         */
        public $AuthenticationPassword;

        /**
         * Flags for the build
         *
         * @var string|null
         */
        public $Flags;

        /**
         * A description explaining what the update consists of
         *
         * @var string|null
         */
        public $UpdateDescription;

        /**
         * An array of changes that has been made for the update
         *
         * @var array|null
         */
        public $ChangeLog;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->AuthenticationRequired = false;
            $this->Flags = [];
            $this->ChangeLog = [];
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'version' => $this->Version,
                'download_source' => $this->DownloadSource,
                'authentication_required' => $this->AuthenticationRequired,
                'authentication_username' => $this->AuthenticationUsername,
                'authentication_password' => $this->AuthenticationPassword,
                'flags' => $this->Flags,
                'update_description' => $this->UpdateDescription,
                'changelog' => $this->ChangeLog
            ];
        }

        /**
         * Constructs an object from an array representation 
         *
         * @param array $data
         * @return NccUpdateInformation
         */
        public static function fromArray(array $data): NccUpdateInformation
        {
            $NccUpdateInformationObject = new NccUpdateInformation();

            if(isset($data['version']))
                $NccUpdateInformationObject->Version = $data['version'];

            if(isset($data['download_source']))
                $NccUpdateInformationObject->DownloadSource = $data['download_source'];

            if(isset($data['authentication_required']))
                $NccUpdateInformationObject->AuthenticationRequired = $data['authentication_required'];

            if(isset($data['authentication_username']))
                $NccUpdateInformationObject->AuthenticationUsername = $data['authentication_username'];

            if(isset($data['authentication_password']))
                $NccUpdateInformationObject->AuthenticationPassword = $data['authentication_password'];

            if(isset($data['flags']))
                $NccUpdateInformationObject->Flags = $data['flags'];

            if(isset($data['update_description']))
                $NccUpdateInformationObject->UpdateDescription = $data['update_description'];

            if(isset($data['changelog']))
                $NccUpdateInformationObject->ChangeLog = $data['changelog'];

            return $NccUpdateInformationObject;
        }
    }