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
                Logger::getLogger()->error(sprintf('Invalid repository type for PackagistRepository, expected %s, got %s', RepositoryType::PACKAGIST->value, $configuration->getType()->value));
                throw new InvalidArgumentException(sprintf('Invalid repository type for PackagistRepository, expected %s, got %s', RepositoryType::PACKAGIST->value, $configuration->getType()->value));
            }
            Logger::getLogger()->debug(sprintf('Initialized PackagistRepository for host %s', $configuration->getHost()));
        }

        /**
         * @inheritDoc
         */
        public function getTags(string $group, string $project): array
        {
            Logger::getLogger()->warning('Packagist does not support tags');
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getLatestTag(string $group, string $project): string
        {
            Logger::getLogger()->warning('Packagist does not support tags');
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getTagArchive(string $group, string $project, string $tag): ?RemotePackage
        {
            Logger::getLogger()->warning('Packagist does not support tags');
            throw new OperationException('Packagist does not support tags.');
        }

        /**
         * @inheritDoc
         */
        public function getReleases(string $group, string $project): array
        {
            Logger::getLogger()->debug(sprintf('Fetching releases for %s/%s from Packagist', $group, $project));
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($this->getConfiguration()->isSslEnabled() ? 'https' : 'http'), $this->getConfiguration()->getHost(), rawurlencode($group), rawurlencode($project));

            Logger::getLogger()->verbose(sprintf('Fetching package data from %s', $endpoint));
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
                Logger::getLogger()->error(sprintf('Invalid response from %s/%s, missing "package.versions" key', $group, $project));
                throw new NetworkException(sprintf('Invalid response from %s/%s, missing "package.versions" key', $group, $project));
            }

            $versions = array_keys($response['package']['versions']);
            Logger::getLogger()->info(sprintf('Found %d versions for %s/%s', count($versions), $group, $project));
            return $versions;
        }

        /**
         * @inheritDoc
         */
        public function getLatestRelease(string $group, string $project): string
        {
            Logger::getLogger()->debug(sprintf('Getting latest release for %s/%s', $group, $project));
            $versions = $this->getReleases($group, $project);

            // Filter out pre-release versions such as alpha, beta, rc, dev
            $initial_count = count($versions);
            $versions = array_filter($versions, static function($version)
            {
                return !preg_match('/-alpha|-beta|-rc|dev/i', $version);
            });
            Logger::getLogger()->verbose(sprintf('Filtered %d pre-release versions from %s/%s', $initial_count - count($versions), $group, $project));

            // Sort versions in descending order using Semver::rsort
            $versions = Semver::rsort($versions);

            if (!isset($versions[0]))
            {
                Logger::getLogger()->warning(sprintf('Failed to resolve latest version for %s/%s', $group, $project));
                throw new OperationException(sprintf('Failed to resolve latest version for %s/%s', $group, $project));
            }

            Logger::getLogger()->info(sprintf('Latest release for %s/%s is %s', $group, $project, $versions[0]));
            return $versions[0]; // The first version in the sorted array is the latest
        }

        /**
         * @inheritDoc
         */
        public function getReleaseArchive(string $group, string $project, string $release): ?RemotePackage
        {
            Logger::getLogger()->debug(sprintf('Getting release archive for %s/%s version %s', $group, $project, $release));
            $version = $this->resolveVersion($group, $project, $release);
            Logger::getLogger()->info(sprintf('Resolved version %s to %s for %s/%s', $release, $version, $group, $project));
            $endpoint = sprintf('%s://%s/packages/%s/%s.json', ($this->getConfiguration()->isSslEnabled() ? 'https' : 'http'), $this->getConfiguration()->getHost(), rawurlencode($group), rawurlencode($project));

            Logger::getLogger()->verbose(sprintf('Fetching archive for %s/%s version %s from %s', $group, $project, $version, $endpoint));

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
                Logger::getLogger()->error(sprintf('Invalid response from %s/%s, version %s does not exist', $group, $project, $version));
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not exist', $group, $project, $version));
            }

            if(!isset($response['package']['versions'][$version]['dist']['url']))
            {
                Logger::getLogger()->error(sprintf('Invalid response from %s/%s, version %s does not have a dist URL', $group, $project, $version));
                throw new NetworkException(sprintf('Invalid response from %s/%s, version %s does not have a dist URL', $group, $project, $version));
            }

            Logger::getLogger()->info(sprintf('Found archive URL for %s/%s version %s', $group, $project, $version));
            return new RemotePackage($response['package']['versions'][$version]['dist']['url'], RemotePackageType::SOURCE_ZIP, $group, $project, $version);
        }

        /**
         * @inheritDoc
         */
        public function getReleasePackage(string $group, string $project, string $release): ?RemotePackage
        {
            Logger::getLogger()->warning('Packagist does not provide .ncc packages');
            return null;
        }

        /**
         * @inheritDoc
         */
        public function getGit(string $group, string $project): ?RemotePackage
        {
            Logger::getLogger()->warning('Packagist does not provide direct git access');
            return null;
        }

        private function resolveVersion(string $group, string $project, string $version): string
        {
            Logger::getLogger()->debug(sprintf('Resolving version %s for %s/%s', $version, $group, $project));
            $versions = $this->getReleases($group, $project);
            usort($versions, static function($a, $b)
            {
                return Comparator::lessThanOrEqualTo($a, $b) ? 1 : -1;
            });

            Logger::getLogger()->verbose(sprintf('Checking %d available versions for %s/%s', count($versions), $group, $project));
            foreach($versions as $working_version)
            {
                // Avoid using dev versions if the requested version is not a dev version
                if(false === stripos($version, "-dev") && false !== stripos($working_version, "-dev"))
                {
                    Logger::getLogger()->debug(sprintf('Skipping dev version %s for %s/%s', $working_version, $group, $project));
                    continue;
                }

                if(Semver::satisfies($working_version, $version))
                {
                    Logger::getLogger()->info(sprintf('Resolved version %s to %s for %s/%s', $version, $working_version, $group, $project));
                    return $working_version;
                }
            }

            Logger::getLogger()->error(sprintf('Version %s for %s/%s does not exist', $version, $group, $project));
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
                Logger::getLogger()->error(sprintf('HTTP request failed for %s/%s after 3 retries: %s', $vendor, $project, curl_error($curl)));
                throw new NetworkException(sprintf('HTTP request failed for %s/%s: %s', $vendor, $project, curl_error($curl)));
            }

            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            Logger::getLogger()->verbose(sprintf('Received HTTP %d response for %s/%s', $http_code, $vendor, $project));
            switch ($http_code)
            {
                case 200:
                    Logger::getLogger()->debug(sprintf('Successfully received response for %s/%s', $vendor, $project));
                    break;

                case 401:
                    Logger::getLogger()->error(sprintf('Authentication failed for %s/%s, 401 Unauthorized', $vendor, $project));
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 401 Unauthorized, invalid/expired access token', $vendor, $project));

                case 403:
                    Logger::getLogger()->error(sprintf('Authentication failed for %s/%s, 403 Forbidden', $vendor, $project));
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 403 Forbidden, insufficient scope', $vendor, $project));

                case 404:
                    Logger::getLogger()->error(sprintf('Resource not found for %s/%s, 404 Not Found', $vendor, $project));
                    throw new OperationException(sprintf('Resource not found for %s/%s, server returned 404 Not Found', $vendor, $project));

                default:
                    Logger::getLogger()->error(sprintf('Server responded with HTTP %s for %s/%s', $http_code, $vendor, $project));
                    throw new OperationException(sprintf('Server responded with HTTP code %s for %s/%s: %s', curl_getinfo($curl, CURLINFO_HTTP_CODE), $vendor, $project, $response));
            }

            try
            {
                Logger::getLogger()->debug(sprintf('Parsing JSON response for %s/%s', $vendor, $project));
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                Logger::getLogger()->error(sprintf('Failed to parse JSON response from %s/%s: %s', $vendor, $project, $e->getMessage()));
                throw new OperationException(sprintf('Failed to parse response from %s/%s: %s', $vendor, $project, $e->getMessage()), $e->getCode(), $e);
            }
        }
    }