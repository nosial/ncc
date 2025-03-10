<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Objects\ComposerJson;

    use ncc\Interfaces\SerializableObjectInterface;

    class Support implements SerializableObjectInterface
    {
        /**
         * Email address for support
         *
         * @var string|null
         */
        private $email;

        /**
         * URL to the issue tracker
         *
         * @var string|null
         */
        private $issues;

        /**
         * URL to the forum
         *
         * @var string|null
         */
        private $forum;

        /**
         * URL to the Wiki
         *
         * @var string|null
         */
        private $wiki;

        /**
         * The IRC channel for support, as irc://server/channel
         *
         * @var string|null
         */
        private $irc;

        /**
         * URL to browse or download the sources
         *
         * @var string|null
         */
        private $source;

        /**
         * URL to the documentation
         *
         * @var string|null
         */
        private $docs;

        /**
         * URL to the RSS feed
         *
         * @var string|null
         */
        private $rss;

        /**
         * URL to the chat channel
         *
         * @var string|null
         */
        private $chat;

        /**
         * @return string|null
         */
        public function getEmail(): ?string
        {
            return $this->email;
        }

        /**
         * @return string|null
         */
        public function getIssues(): ?string
        {
            return $this->issues;
        }

        /**
         * @return string|null
         */
        public function getForum(): ?string
        {
            return $this->forum;
        }

        /**
         * @return string|null
         */
        public function getWiki(): ?string
        {
            return $this->wiki;
        }

        /**
         * @return string|null
         */
        public function getIrc(): ?string
        {
            return $this->irc;
        }

        /**
         * @return string|null
         */
        public function getSource(): ?string
        {
            return $this->source;
        }

        /**
         * @return string|null
         */
        public function getDocs(): ?string
        {
            return $this->docs;
        }

        /**
         * @return string|null
         */
        public function getRss(): ?string
        {
            return $this->rss;
        }

        /**
         * @return string|null
         */
        public function getChat(): ?string
        {
            return $this->chat;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'email' => $this->email,
                'issues' => $this->issues,
                'forum' => $this->forum,
                'wiki' => $this->wiki,
                'irc' => $this->irc,
                'source' => $this->source,
                'docs' => $this->docs,
                'rss' => $this->rss,
                'chat' => $this->chat
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Support
        {
            $object = new self();

            if(isset($data['email']))
            {
                $object->email = $data['email'];
            }

            if(isset($data['issues']))
            {
                $object->issues = $data['issues'];
            }

            if(isset($data['forum']))
            {
                $object->forum = $data['forum'];
            }

            if(isset($data['wiki']))
            {
                $object->wiki = $data['wiki'];
            }

            if(isset($data['irc']))
            {
                $object->irc = $data['irc'];
            }

            if(isset($data['source']))
            {
                $object->source = $data['source'];
            }

            if(isset($data['docs']))
            {
                $object->docs = $data['docs'];
            }

            if(isset($data['rss']))
            {
                $object->rss = $data['rss'];
            }

            if(isset($data['chat']))
            {
                $object->chat = $data['chat'];
            }

            return $object;
        }
    }