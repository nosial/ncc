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

    use JsonException;
    use ncc\Enums\HttpRequestType;
    use ncc\Interfaces\SerializableObjectInterface;
    use RuntimeException;

    class HttpRequest implements SerializableObjectInterface
    {
        /**
         * The HTTP request type.
         *
         * @var string|HttpRequestType
         */
        private $type;

        /**
         * The URL to send the request to.
         *
         * @var string
         */
        private $url;

        /**
         * The headers to send with the request.
         *
         * @var array
         */
        private $headers;

        /**
         * The body to send with the request.
         *
         * @var string|null
         */
        private $body;

        /**
         * The authentication username or password to send with the request.
         *
         * @var array|string
         */
        private $authentication;

        /**
         * An array of curl options to set
         *
         * @var array
         */
        private $options;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->type = HttpRequestType::GET;
            $this->options = [];
            $this->headers = [
                'User-Agent: ncc/1.0'
            ];
        }

        /**
         * @return HttpRequestType|string
         */
        public function getType(): HttpRequestType|string
        {
            return $this->type;
        }

        /**
         * @param HttpRequestType|string $type
         */
        public function setType(HttpRequestType|string $type): void
        {
            $this->type = $type;
        }

        /**
         * @return string
         */
        public function getUrl(): string
        {
            return $this->url;
        }

        /**
         * @param string $url
         */
        public function setUrl(string $url): void
        {
            $this->url = $url;
        }

        /**
         * @return array|string[]
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * @param array|string[] $headers
         */
        public function setHeaders(array $headers): void
        {
            $this->headers = $headers;
        }

        /**
         * @param string $header
         * @return void
         */
        public function addHeader(string $header): void
        {
            $this->headers[] = $header;
        }

        /**
         * @return string|null
         */
        public function getBody(): ?string
        {
            return $this->body;
        }

        /**
         * @param string|null $body
         */
        public function setBody(?string $body): void
        {
            $this->body = $body;
        }

        /**
         * @return array|string
         */
        public function getAuthentication(): array|string
        {
            return $this->authentication;
        }

        /**
         * @param array|string $authentication
         */
        public function setAuthentication(array|string $authentication): void
        {
            $this->authentication = $authentication;
        }

        /**
         * @return array
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * @param array $options
         */
        public function setOptions(array $options): void
        {
            $this->options = $options;
        }

        /**
         * Returns the hash of the object.
         * (This is used for caching)
         *
         * @return string
         */
        public function requestHash(): string
        {
            try
            {
                return hash('sha1', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
            }
            catch(JsonException $e)
            {
                throw new RuntimeException(sprintf('Failed to hash request: %s', $e->getMessage()), $e->getCode(), $e);
            }
        }

        /**
         * Returns an array representation of the object.
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'type' => $this->type,
                'url' => $this->url,
                'headers' => $this->headers,
                'body' => $this->body,
                'authentication' => $this->authentication,
                'options' => $this->options
            ];
        }

        /**
         * Constructs a new HttpRequest object from an array representation.
         *
         * @param array $data
         * @return HttpRequest
         */
        public static function fromArray(array $data): HttpRequest
        {
            $request = new self();

            $request->type = $data['type'];
            $request->url = $data['url'];
            $request->headers = $data['headers'];
            $request->body = $data['body'];
            $request->authentication = $data['authentication'];
            $request->options = $data['options'];

            return $request;
        }
    }