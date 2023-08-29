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

    use ncc\Interfaces\SerializableObjectInterface;

    class HttpResponse implements SerializableObjectInterface
    {
        /**
         * The HTTP status code.
         *
         * @var int
         */
        private $status_code;

        /**
         * The headers returned by the server.
         *
         * @var array
         */
        private $headers;

        /**
         * The body returned by the server.
         *
         * @var string
         */
        private $body;

        /**
         * HttpResponse constructor.
         */
        public function __construct()
        {
            $this->status_code = 0;
            $this->headers = [];
            $this->body = (string)null;
        }

        /**
         * @return int
         */
        public function getStatusCode(): int
        {
            return $this->status_code;
        }

        /**
         * @param int $status_code
         */
        public function setStatusCode(int $status_code): void
        {
            $this->status_code = $status_code;
        }

        /**
         * @return array
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * @param array $headers
         */
        public function setHeaders(array $headers): void
        {
            $this->headers = $headers;
        }

        /**
         * @return string
         */
        public function getBody(): string
        {
            return $this->body;
        }

        /**
         * @param string $body
         */
        public function setBody(string $body): void
        {
            $this->body = $body;
        }

        /**
         * Returns an array representation of the object.
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'status_code' => $this->status_code,
                'headers' => $this->headers,
                'body' => $this->body
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): HttpResponse
        {
            $object = new self();

            $object->status_code = $data['status_code'];
            $object->headers = $data['headers'];
            $object->body = $data['body'];

            return $object;
        }
    }