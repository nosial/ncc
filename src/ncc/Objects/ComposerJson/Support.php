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