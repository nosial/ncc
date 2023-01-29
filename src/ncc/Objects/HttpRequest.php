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

    use ncc\Abstracts\HttpRequestType;

    class HttpRequest
    {
        /**
         * The HTTP request type.
         *
         * @var string|HttpRequestType
         */
        public $Type;

        /**
         * The URL to send the request to.
         *
         * @var string
         */
        public $Url;

        /**
         * The headers to send with the request.
         *
         * @var array
         */
        public $Headers;

        /**
         * The body to send with the request.
         *
         * @var string|null
         */
        public $Body;

        /**
         * The authentication username or password to send with the request.
         *
         * @var array|string
         */
        public $Authentication;

        /**
         * An array of curl options to set
         *
         * @var array
         */
        public $Options;

        public function __construct()
        {
            $this->Type = HttpRequestType::GET;
            $this->Body = null;
            $this->Headers = [
                'User-Agent: ncc/1.0'
            ];
            $this->Options = [];
        }

        /**
         * Returns an array representation of the object.
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'type' => $this->Type,
                'url' => $this->Url,
                'headers' => $this->Headers,
                'body' => $this->Body,
                'authentication' => $this->Authentication,
                'options' => $this->Options
            ];
        }

        /**
         * Returns the hash of the object.
         * (This is used for caching)
         *
         * @return string
         */
        public function requestHash(): string
        {
            return hash('sha1', json_encode($this->toArray()));
        }

        /**
         * Constructs a new HttpRequest object from an array representation.
         *
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $request = new self();
            $request->Type = $data['type'];
            $request->Url = $data['url'];
            $request->Headers = $data['headers'];
            $request->Body = $data['body'];
            $request->Authentication = $data['authentication'];
            $request->Options = $data['options'];
            return $request;
        }
    }