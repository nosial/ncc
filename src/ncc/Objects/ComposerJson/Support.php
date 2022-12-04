<?php

    namespace ncc\Objects\ComposerJson;

    class Support
    {
        /**
         * Email address for support
         *
         * @var string|null
         */
        public $Email;

        /**
         * URL to the issue tracker
         *
         * @var string|null
         */
        public $Issues;

        /**
         * URL to the forum
         *
         * @var string|null
         */
        public $Forum;

        /**
         * URL to the Wiki
         *
         * @var string|null
         */
        public $Wiki;

        /**
         * The IRC channel for support, as irc://server/channel
         *
         * @var string|null
         */
        public $IRC;

        /**
         * URL to browse or download the sources
         *
         * @var string|null
         */
        public $Source;

        /**
         * URL to the documentation
         *
         * @var string|null
         */
        public $Docs;

        /**
         * URL to the RSS feed
         *
         * @var string|null
         */
        public $RSS;

        /**
         * URL to the chat channel
         *
         * @var string|null
         */
        public $Chat;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'email' => $this->Email,
                'issues' => $this->Issues,
                'forum' => $this->Forum,
                'wiki' => $this->Wiki,
                'irc' => $this->IRC,
                'source' => $this->Source,
                'docs' => $this->Docs,
                'rss' => $this->RSS,
                'chat' => $this->Chat
            ];
        }

        /**
         * Constructs object from array representation
         *
         * @param array $data
         * @return Support
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['email']))
                $object->Email = $data['email'];

            if(isset($data['issues']))
                $object->Issues = $data['issues'];

            if(isset($data['forum']))
                $object->Forum = $data['forum'];

            if(isset($data['wiki']))
                $object->Wiki = $data['wiki'];

            if(isset($data['irc']))
                $object->IRC = $data['irc'];

            if(isset($data['source']))
                $object->Source = $data['source'];

            if(isset($data['docs']))
                $object->Docs = $data['docs'];

            if(isset($data['rss']))
                $object->RSS = $data['rss'];

            if(isset($data['chat']))
                $object->Chat = $data['chat'];

            return $object;
        }
    }