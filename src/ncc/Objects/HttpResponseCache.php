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

    class HttpResponseCache
    {
        /**
         * The cache of response
         *
         * @var HttpResponse
         */
        private $http_response;

        /**
         * The Unix Timestamp of when the cache becomes invalid
         *
         * @var int
         */
        private $ttl;

        /**
         * Creates a new HttpResponseCache
         *
         * @param HttpResponse $http_response
         * @param int $ttl
         */
        public function __construct(HttpResponse $http_response, int $ttl)
        {
            $this->http_response = $http_response;
            $this->ttl = $ttl;
        }

        /**
         * Returns the cached response
         *
         * @return HttpResponse
         */
        public function getHttpResponse(): HttpResponse
        {
            return $this->http_response;
        }

        /**
         * Returns the Unix Timestamp of when the cache becomes invalid
         *
         * @return int
         */
        public function getTtl(): int
        {
            return $this->ttl;
        }
    }