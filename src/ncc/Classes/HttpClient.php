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

namespace ncc\Classes;

    use CurlHandle;
    use ncc\Enums\HttpRequestType;
    use ncc\Enums\LogLevel;
    use ncc\CLI\Main;
    use ncc\Exceptions\NetworkException;
    use ncc\Objects\HttpRequest;
    use ncc\Objects\HttpResponse;
    use ncc\Objects\HttpResponseCache;
    use ncc\Utilities\Console;
    use ncc\Utilities\RuntimeCache;

    class HttpClient
    {
        /**
         * Prepares the curl request
         *
         * @param HttpRequest $request
         * @return CurlHandle
         */
        private static function prepareCurl(HttpRequest $request): CurlHandle
        {
            $curl = curl_init($request->geturl());
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getHeaders());
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getType());
            curl_setopt($curl, CURLOPT_NOPROGRESS, false);

            curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, static function($curl, $downloadSize, $downloaded)
            {
                if($downloadSize > 0 && ($downloaded !== $downloadSize) && Main::getLogLevel() !== null)
                {
                    switch(Main::getLogLevel())
                    {
                        case LogLevel::VERBOSE:
                        case LogLevel::DEBUG:
                        case LogLevel::SILENT:
                            Console::outVerbose(sprintf(' <= %s of %s bytes downloaded', $downloaded, $downloadSize));
                            break;

                        default:
                            Console::inlineProgressBar($downloaded, $downloadSize + 1);
                            break;
                    }
                }
            });

            switch($request->getType())
            {
                case HttpRequestType::GET:
                    curl_setopt($curl, CURLOPT_HTTPGET, true);
                    break;

                case HttpRequestType::POST:
                    curl_setopt($curl, CURLOPT_POST, true);
                    if($request->getBody() !== null)
                    {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getBody());
                    }
                    break;

                case HttpRequestType::PUT:
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    if($request->getBody() !== null)
                    {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getBody());
                    }
                    break;

                case HttpRequestType::DELETE:
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            }

            if (is_array($request->getAuthentication()))
            {
                curl_setopt($curl, CURLOPT_USERPWD, $request->getAuthentication()[0] . ':' . $request->getAuthentication()[1]);
            }
            else if (is_string($request->getAuthentication()))
            {
                curl_setopt($curl, CURLOPT_USERPWD, $request->getAuthentication());
            }

            foreach ($request->getOptions() as $option => $value)
            {
                curl_setopt($curl, $option, $value);
            }

            return $curl;
        }

        /**
         * Creates a new HTTP request and returns the response.
         *
         * @param HttpRequest $httpRequest
         * @param bool $cache
         * @return HttpResponse
         * @throws NetworkException
         */
        public static function request(HttpRequest $httpRequest, bool $cache=false): HttpResponse
        {
            if($cache)
            {
                /** @var HttpResponseCache $cache */
                $cache_value = RuntimeCache::get(sprintf('http_cache_%s', $httpRequest->requestHash()));
                if($cache_value !== null && $cache_value->getTtl() > time())
                {
                    Console::outDebug(sprintf('using cached response for %s', $httpRequest->requestHash()));
                    return $cache_value->getHttpResponse();
                }
            }

            $curl = self::prepareCurl($httpRequest);

            Console::outDebug(sprintf(' => %s request %s', $httpRequest->getType(), $httpRequest->getUrl()));

            if(count($httpRequest->getHeaders()) > 0)
            {
                Console::outDebug(sprintf(' => headers: %s', implode(', ', $httpRequest->getHeaders())));
            }

            if($httpRequest->getBody() !== null)
            {
                Console::outDebug(sprintf(' => body: %s', $httpRequest->getBody()));
            }

            $response = curl_exec($curl);

            if ($response === false)
            {
                $error = curl_error($curl);
                curl_close($curl);
                throw new NetworkException($error);
            }

            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $httpResponse = new HttpResponse();
            $httpResponse->setStatusCode(curl_getinfo($curl, CURLINFO_HTTP_CODE));
            $httpResponse->setHeaders(self::parseHeaders($headers));
            $httpResponse->setBody($body);

            Console::outDebug(sprintf(' <= %s response', $httpResponse->getStatusCode()));
            Console::outDebug(sprintf(' <= headers: %s', (implode(', ', $httpResponse->getHeaders()))));
            Console::outDebug(sprintf(' <= body: %s', $httpResponse->getBody()));

            curl_close($curl);

            if($cache)
            {
                $httpCacheObject = new HttpResponseCache($httpResponse, time() + 60);
                RuntimeCache::set(sprintf('http_cache_%s', $httpRequest->requestHash()), $httpCacheObject);

                Console::outDebug(sprintf('cached response for %s', $httpRequest->requestHash()));
            }

            return $httpResponse;
        }

        /**
         * Downloads a file from the given url and saves it to the given path.
         *
         * @param HttpRequest $httpRequest
         * @param string $path
         * @return void
         * @throws NetworkException
         */
        public static function download(HttpRequest $httpRequest, string $path): void
        {
            $curl = self::prepareCurl($httpRequest);

            $fp = fopen($path, 'wb');
            curl_setopt($curl, CURLOPT_FILE, $fp);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            $response = curl_exec($curl);

            if ($response === false)
            {
                $error = curl_error($curl);
                curl_close($curl);
                throw new NetworkException($error);
            }

            curl_close($curl);
            fclose($fp);
        }

        /**
         * Takes the return headers of a cURL request and parses them into an array.
         *
         * @param string $input
         * @return array
         */
        private static function parseHeaders(string $input): array
        {
            $headers = array();
            $lines = explode("\n", $input);
            $headers['HTTP'] = array_shift($lines);

            foreach ($lines as $line)
            {
                $header = explode(':', $line, 2);
                if (count($header) === 2)
                {
                    $headers[trim($header[0])] = trim($header[1]);
                }
            }

            return $headers;
        }
    }