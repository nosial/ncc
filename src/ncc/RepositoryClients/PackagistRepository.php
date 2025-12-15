<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\RepositoryClients;

    use CurlHandle;
    use InvalidArgumentException;
    use JsonException;
    use ncc\Abstracts\AbstractAuthentication;
    use ncc\Abstracts\AbstractRepository;
    use ncc\CLI\Logger;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\semver\Comparator;
    use ncc\Libraries\semver\Semver;
    use ncc\Objects\RemotePackage;
    use ncc\Objects\RepositoryConfiguration;

    class PackagistRepository extends AbstractRepository
    {
        public function __construct(RepositoryConfiguration $configuration, ?AbstractAuthentication $authentication=null)
        {
            parent::__construct($configuration, $authentication);
            if($configuration->getType() !== RepositoryType::PACKAGIST)
            {
                throw new InvalidArgumentException(sprintf('Invalid repository type for PackagistRepository, expected %s, got %s', RepositoryType::PACKAGIST->value, $configuration->getType()->value));
            }
        }

        /**
         * @inheritDoc
         */
        public function getTags(string $group, string $project): array
        {
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getLatestTag(string $group, string $project): string
        {
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getTagArchive(string $group, string $project, string $tag): ?RemotePackage
        {
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getReleases(string $group, string $project): array
        {
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($this->getConfiguration()->isSslEnabled() ? 'https' : 'http'), $this->getConfiguration()->getHost(), rawurlencode($group), rawurlencode($project));

            $curl = curl_init($endpoint);
            $headers = [
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            $response = $this->processHttpResponse($curl, $group, $project);
            curl_close($curl);

            if(!isset($response['package']['versions']))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, missing "package.versions" key', $group, $project));
            }

            return array_keys($response['package']['versions']);
        }

        /**
         * @inheritDoc
         */
        public function getLatestRelease(string $group, string $project): string
        {
            $versions = $this->getReleases($group, $project);

            // Filter out pre-release versions such as alpha, beta, rc, dev
            $versions = array_filter($versions, static function($version)
            {
                return !preg_match('/-alpha|-beta|-rc|dev/i', $version);
            });

            // Sort versions in descending order using Semver::rsort
            $versions = Semver::rsort($versions);

            if (!isset($versions[0]))
            {
                throw new OperationException(sprintf('Failed to resolve latest version for %s/%s', $group, $project));
            }

            return $versions[0]; // The first version in the sorted array is the latest
        }

        /**
         * @inheritDoc
         */
        public function getReleaseArchive(string $group, string $project, string $release): ?RemotePackage
        {
            $version = $this->resolveVersion($group, $project, $release);
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($this->getConfiguration()->isSslEnabled() ? 'https' : 'http'), $this->getConfiguration()->getHost(), rawurlencode($group), rawurlencode($project));

            Logger::getLogger()->verbose(sprintf('Fetching archive %s/%s version %s from %s', $group, $project, $version, $endpoint));

            $curl = curl_init($endpoint);
            $headers = [
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            $response = $this->processHttpResponse($curl, $group, $project);
            curl_close($curl);

            if(!isset($response['package']['versions'][$version]))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not exist', $group, $project, $version));
            }

            if(!isset($response['package']['versions'][$version]['dist']['url']))
            {
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not have a dist URL', $group, $project, $version));
            }

            return new RemotePackage($response['package']['versions'][$version]['dist']['url'], RemotePackageType::SOURCE_ZIP, $group, $project);
        }

        /**
         * @inheritDoc
         */
        public function getReleasePackage(string $group, string $project, string $release): ?RemotePackage
        {
            return null;
        }

        /**
         * @inheritDoc
         */
        public function getGit(string $group, string $project): ?RemotePackage
        {
            return null;
        }

        private function resolveVersion(string $group, string $project, string $version): string
        {
            $versions = $this->getReleases($group, $project);
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

            throw new InvalidArgumentException(sprintf('Version %s for %s/%s does not exist', $version, $group, $project));
        }


        private function processHttpResponse(CurlHandle $curl, string $vendor, string $project): array
        {
            $retry_count = 0;
            $response = false;

            while($retry_count < 3 && $response === false)
            {
                $response = curl_exec($curl);

                if($response === false)
                {
                    Logger::getLogger()->warning(sprintf('HTTP request failed for %s/%s: %s, retrying (%s/3)', $vendor, $project, curl_error($curl), $retry_count + 1));
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
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 401 Unauthorized, invalid/expired access token', $vendor, $project));

                case 403:
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 403 Forbidden, insufficient scope', $vendor, $project));

                case 404:
                    throw new OperationException(sprintf('Resource not found for %s/%s, server returned 404 Not Found', $vendor, $project));

                default:
                    throw new OperationException(sprintf('Server responded with HTTP code %s for %s/%s: %s', curl_getinfo($curl, CURLINFO_HTTP_CODE), $vendor, $project, $response));
            }

            try
            {
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                throw new OperationException(sprintf('Failed to parse response from %s/%s: %s', $vendor, $project, $e->getMessage()), $e);
            }
        }
    }