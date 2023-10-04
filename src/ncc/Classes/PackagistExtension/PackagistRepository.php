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

    namespace ncc\Classes\PackagistExtension;

    use CurlHandle;
    use InvalidArgumentException;
    use JsonException;
    use ncc\Enums\Types\AuthenticationType;
    use ncc\Enums\Types\HttpRequestType;
    use ncc\Enums\Types\RepositoryResultType;
    use ncc\Enums\Versions;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\RepositoryInterface;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Objects\RepositoryResult;
    use ncc\ThirdParty\composer\Semver\Comparator;
    use ncc\ThirdParty\composer\Semver\Semver;
    use ncc\Utilities\Console;
    use ncc\Utilities\RuntimeCache;
    use RuntimeException;

    class PackagistRepository implements RepositoryInterface
    {
        /**
         * @inheritDoc
         */
        public static function fetchSourceArchive(RepositoryConfiguration $repository, string $vendor, string $project, string $version = Versions::LATEST, ?AuthenticationType $authentication = null): RepositoryResult
        {
            if($version === Versions::LATEST)
            {
                $version = self::getLatestVersion($repository, $vendor, $project);
            }

            $version = self::resolveVersion($repository, $vendor, $project, $version);
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($repository->isSsl() ? 'https' : 'http'), $repository->getHost(), rawurlencode($vendor), rawurlencode($project));

            Console::outDebug(sprintf('Fetching archive %s/%s version %s from %s', $vendor, $project, $version, $endpoint));

            if(RuntimeCache::exists($endpoint))
            {
                $response = RuntimeCache::get($endpoint);
            }
            else
            {
                $curl = curl_init($endpoint);
                $headers = [
                    'Accept: application/json',
                    'User-Agent: ncc'
                ];

                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => HttpRequestType::GET,
                    CURLOPT_HTTPHEADER => $headers
                ]);

                $response = self::processHttpResponse($curl, $vendor, $project);
                RuntimeCache::set($endpoint, $response);
                unset($curl);
            }

            if(!isset($response['package']['versions'][$version]))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not exist', $vendor, $project, $version));
            }

            if(!isset($response['package']['versions'][$version]['dist']['url']))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not have a dist URL', $vendor, $project, $version));
            }

            return new RepositoryResult($response['package']['versions'][$version]['dist']['url'], RepositoryResultType::SOURCE, $version);
        }

        /**
         * @inheritDoc
         * @throws NotSupportedException
         */
        public static function fetchPackage(RepositoryConfiguration $repository, string $vendor, string $project, string $version = Versions::LATEST, ?AuthenticationType $authentication = null): RepositoryResult
        {
            throw new NotSupportedException('Fetching ncc packages from Packagist is not supported');
        }


        /**
         * Returns an array of all versions for the specified vendor and project
         *
         * @param RepositoryConfiguration $repository
         * @param string $vendor
         * @param string $project
         * @return array
         * @throws AuthenticationException
         * @throws NetworkException
         */
        private static function getVersions(RepositoryConfiguration $repository, string $vendor, string $project): array
        {
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($repository->isSsl() ? 'https' : 'http'), $repository->getHost(), rawurlencode($vendor), rawurlencode($project));

            if(RuntimeCache::exists($endpoint))
            {
                $response = RuntimeCache::get($endpoint);
            }
            else
            {
                $curl = curl_init($endpoint);
                $headers = [
                    'Accept: application/json',
                    'User-Agent: ncc'
                ];

                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => HttpRequestType::GET,
                    CURLOPT_HTTPHEADER => $headers
                ]);

                $response = self::processHttpResponse($curl, $vendor, $project);
                RuntimeCache::set($endpoint, $response);
                unset($curl);
            }

            if(!isset($response['package']['versions']))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, missing "package.versions" key', $vendor, $project));
            }

            return array_keys($response['package']['versions']);
        }

        /**
         * Resolves the latest version for the specified vendor and project
         * 
         * @param RepositoryConfiguration $repository
         * @param string $vendor
         * @param string $project
         * @return string
         * @throws AuthenticationException
         * @throws NetworkException
         */
        public static function getLatestVersion(RepositoryConfiguration $repository, string $vendor, string $project): string
        {
            $versions = self::getVersions($repository, $vendor, $project);

            /** @noinspection KeysFragmentationWithArrayFunctionsInspection */
            $versions = array_filter($versions, static function($version)
            {
                return !preg_match('/-alpha|-beta|-rc|dev/i', $version);
            });

            usort($versions, static function($a, $b)
            {
                return Comparator::lessThanOrEqualTo($a, $b) ? 1 : -1;
            });

            if($versions[0] === null)
            {
                throw new NetworkException(sprintf('Failed to resolve latest version for %s/%s', $vendor, $project));
            }

            return $versions[0];
        }

        /**
         * Resolves the requested version from a constraint for the specified vendor and project
         *
         * @param RepositoryConfiguration $repository
         * @param string $vendor
         * @param string $project
         * @param string $version
         * @return string
         * @throws AuthenticationException
         * @throws NetworkException
         */
        public static function resolveVersion(RepositoryConfiguration $repository, string $vendor, string $project, string $version): string
        {
            $versions = self::getVersions($repository, $vendor, $project);

            usort($versions, static function($a, $b)
            {
                return Comparator::lessThanOrEqualTo($a, $b) ? 1 : -1;
            });

            foreach($versions as $working_version)
            {
                // Avoid using dev versions if the requested version is not a dev version
                if(false === stripos($version, "-dev") && false !== stripos($working_version, "-dev"))
                {
                    continue;
                }

                if(Semver::satisfies($working_version, $version))
                {
                    return $working_version;
                }
            }

            throw new InvalidArgumentException(sprintf('Version %s for %s/%s does not exist', $version, $vendor, $project));
        }

        /**
         * Processes the HTTP response from the server and returns the decoded JSON response
         *
         * @param CurlHandle $curl
         * @param string $vendor
         * @param string $project
         * @return array
         * @throws AuthenticationException
         * @throws NetworkException
         */
        private static function processHttpResponse(CurlHandle $curl, string $vendor, string $project): array
        {
            $retry_count = 0;
            $response = false;

            while($retry_count < 3 && $response === false)
            {
                $response = curl_exec($curl);

                if($response === false)
                {
                    Console::outWarning(sprintf('HTTP request failed for %s/%s: %s, retrying (%s/3)', $vendor, $project, curl_error($curl), $retry_count + 1));
                    $retry_count++;
                }
            }

            if($response === false)
            {
                throw new NetworkException(sprintf('HTTP request failed for %s/%s: %s', $vendor, $project, curl_error($curl)));
            }

            switch (curl_getinfo($curl, CURLINFO_HTTP_CODE))
            {
                case 200:
                    break;

                case 401:
                    throw new AuthenticationException(sprintf('Authentication failed for %s/%s, 401 Unauthorized, invalid/expired access token', $vendor, $project));

                case 403:
                    throw new AuthenticationException(sprintf('Authentication failed for %s/%s, 403 Forbidden, insufficient scope', $vendor, $project));

                case 404:
                    throw new NetworkException(sprintf('Resource not found for %s/%s, server returned 404 Not Found', $vendor, $project));

                default:
                    throw new NetworkException(sprintf('Server responded with HTTP code %s for %s/%s: %s', curl_getinfo($curl, CURLINFO_HTTP_CODE), $vendor, $project, $response));
            }

            try
            {
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                throw new RuntimeException(sprintf('Failed to parse response from %s/%s: %s', $vendor, $project, $e->getMessage()), $e);
            }
        }
    }