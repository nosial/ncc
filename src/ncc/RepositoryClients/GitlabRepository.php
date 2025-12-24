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
    use ncc\Enums\AuthenticationType;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\OperationException;
    use ncc\Objects\Authentication\AccessToken;
    use ncc\Objects\Authentication\UsernamePassword;
    use ncc\Objects\RemotePackage;
    use ncc\Objects\RepositoryConfiguration;

    class GitlabRepository extends AbstractRepository
    {
        public function __construct(RepositoryConfiguration $configuration, ?AbstractAuthentication $authentication=null)
        {
            parent::__construct($configuration, $authentication);
            if($configuration->getType() !== RepositoryType::GITLAB)
            {
                Logger::getLogger()->error(sprintf('Invalid repository type for GitlabRepository, expected %s, got %s', RepositoryType::GITLAB->value, $configuration->getType()->value));
                throw new InvalidArgumentException(sprintf('Invalid repository type for GitlabRepository, expected %s, got %s', RepositoryType::GITHUB->value, $configuration->getType()->value));
            }
            Logger::getLogger()->debug(sprintf('Initialized GitlabRepository for host %s with %s', $configuration->getHost(), $authentication !== null ? 'authentication' : 'no authentication'));
        }

        /**
         * @inheritDoc
         */
        public function getTags(string $group, string $project): array
        {
            $project = str_replace('.', '/', $project); // Gitlab doesn't like dots in project names (eg; "libs/config" becomes "libs%2Fconfig")
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s/repository/tags?order_by=updated&sort=desc', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project));
            $curl = curl_init($endpoint);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            if($this->getAuthentication() !== null)
            {
                $headers = self::injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            Logger::getLogger()->debug(sprintf('Fetching tags for %s/%s from %s', $group, $project, $endpoint));

            $results = [];
            foreach(self::processHttpResponse($curl, $group, $project) as $tag)
            {
                if(isset($tag['release']['tag_name']))
                {
                    $results[] = $tag['release']['tag_name'];
                }
            }

            Logger::getLogger()->info(sprintf('Found %d tags for %s/%s', count($results), $group, $project));
            return $results;
        }

        /**
         * @inheritDoc
         */
        public function getLatestTag(string $group, string $project): string
        {
            Logger::getLogger()->debug(sprintf('Getting latest tag for %s/%s', $group, $project));
            $results = self::getTags($group, $project);

            if(count($results) === 0)
            {
                Logger::getLogger()->warning(sprintf('No tags found for %s/%s', $group, $project));
                throw new OperationException(sprintf('No tags found for %s/%s', $group, $project));
            }

            Logger::getLogger()->info(sprintf('Latest tag for %s/%s is %s', $group, $project, $results[0]));
            return $results[0];
        }

        /**
         * @inheritDoc
         */
        public function getTagArchive(string $group, string $project, string $tag): ?RemotePackage
        {
            $project = str_replace('.', '/', $project); // Gitlab doesn't like dots in project names (eg; "libs/config" becomes "libs%2Fconfig")
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s/repository/archive.zip?sha=%s', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project), rawurlencode($tag));
            $curl = curl_init($endpoint);
            $headers = [
                'User-Agent: ncc'
            ];

            if ($this->getAuthentication() !== null)
            {
                $headers = $this->injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            Logger::getLogger()->debug(sprintf('Fetching tag archive for %s/%s/%s from %s', $group, $project, $tag, $endpoint));
            $response = curl_exec($curl);

            if ($response === false)
            {
                Logger::getLogger()->error(sprintf('Failed to get tag archive for %s/%s/%s: %s', $group, $project, $tag, curl_error($curl)));
                throw new NetworkException(sprintf('Failed to get tag archive for %s/%s/%s: %s', $group, $project, $tag, curl_error($curl)));
            }

            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            Logger::getLogger()->verbose(sprintf('Received HTTP %d response for tag archive %s/%s/%s', $http_code, $group, $project, $tag));

            if ($http_code !== 200)
            {
                Logger::getLogger()->error(sprintf('Server responded with HTTP code %s when getting tag archive for %s/%s/%s', $http_code, $group, $project, $tag));
                throw new NetworkException(sprintf('Server responded with HTTP code %s when getting tag archive for %s/%s/%s', $http_code, $group, $project, $tag));
            }

            Logger::getLogger()->info(sprintf('Found tag archive for %s/%s/%s', $group, $project, $tag));
            return new RemotePackage(curl_getinfo($curl, CURLINFO_EFFECTIVE_URL), RemotePackageType::SOURCE_ZIP, $group, $project);
        }

        /**
         * @inheritDoc
         */
        public function getReleases(string $group, string $project): array
        {
            $project = str_replace('.', '/', $project); // Gitlab doesn't like dots in project names (eg; "libs/config" becomes "libs%2Fconfig")
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s/releases?order_by=released_at&sort=desc', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project));
            $curl = curl_init($endpoint);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            if($this->getAuthentication() !== null)
            {
                $headers = $this->injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            Logger::getLogger()->debug(sprintf('Fetching releases for %s/%s from %s', $group, $project, $endpoint));
            $results = [];
            foreach($this->processHttpResponse($curl, $group, $project) as $item)
            {
                if(isset($item['tag_name']))
                {
                    $results[] = $item['tag_name'];
                }
            }

            Logger::getLogger()->info(sprintf('Found %d releases for %s/%s', count($results), $group, $project));
            return $results;
        }

        /**
         * @inheritDoc
         */
        public function getLatestRelease(string $group, string $project): string
        {
            Logger::getLogger()->debug(sprintf('Getting latest release for %s/%s', $group, $project));
            $results = self::getReleases($group, $project);

            if(count($results) === 0)
            {
                Logger::getLogger()->warning(sprintf('No releases found for %s/%s', $group, $project));
                throw new OperationException(sprintf('No releases found for %s/%s', $group, $project));
            }

            Logger::getLogger()->info(sprintf('Latest release for %s/%s is %s', $group, $project, $results[0]));
            return $results[0];
        }

        /**
         * @inheritDoc
         */
        public function getReleaseArchive(string $group, string $project, string $release): ?RemotePackage
        {
            $project = str_replace('.', '/', $project); // Gitlab doesn't like dots in project names (eg; "libs/config" becomes "libs%2Fconfig")
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s/releases/%s', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project), rawurlencode($release));
            $curl = curl_init($endpoint);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            if($this->getAuthentication() !== null)
            {
                $headers = $this->injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            Logger::getLogger()->debug(sprintf('Fetching release archive for %s/%s/%s from %s', $group, $project, $release, $endpoint));
            $response = self::processHttpResponse($curl, $group, $project);

            if(!isset($response['assets']['sources']))
            {
                Logger::getLogger()->error(sprintf('No source assets found for %s/%s/%s', $group, $project, $release));
                throw new NetworkException(sprintf('No source assets found for %s/%s/%s', $group, $project, $release));
            }

            Logger::getLogger()->verbose(sprintf('Found %d source assets for release %s in %s/%s', count($response['assets']['sources']), $release, $group, $project));
            foreach($response['assets']['sources'] as $asset)
            {
                if(!isset($asset['format'], $asset['url']))
                {
                    continue;
                }

                if($asset['format'] === 'zip')
                {
                    Logger::getLogger()->info(sprintf('Found zip archive for release %s in %s/%s', $release, $group, $project));
                    $results = new RemotePackage($asset['url'], RemotePackageType::SOURCE_ZIP, $group, $project);
                }
                elseif($asset['format'] === 'tar')
                {
                    Logger::getLogger()->info(sprintf('Found tar archive for release %s in %s/%s', $release, $group, $project));
                    $results = new RemotePackage($asset['url'], RemotePackageType::SOURCE_TAR, $group, $project);
                }
                else
                {
                    Logger::getLogger()->error(sprintf('Unknown source asset format "%s" for %s/%s/%s', $asset['format'], $group, $project, $release));
                    throw new NetworkException(sprintf('Unknown source asset format "%s" for %s/%s/%s', $asset['format'], $group, $project, $release));
                }

                return $results;
            }

            Logger::getLogger()->error(sprintf('No archive found for %s/%s/%s', $group, $project, $release));
            throw new NetworkException(sprintf('No archive found for %s/%s/%s', $group, $project, $release));
        }

        /**
         * @inheritDoc
         */
        public function getReleasePackage(string $group, string $project, string $release): ?RemotePackage
        {
            $project = str_replace('.', '/', $project);
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s/releases/%s', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project), rawurlencode($release));
            $curl = curl_init($endpoint);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            if($this->getAuthentication() !== null)
            {
                $headers = $this->injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            Logger::getLogger()->debug(sprintf('Fetching release package for %s/%s/%s from %s', $group, $project, $release, $endpoint));
            $response = $this->processHttpResponse($curl, $group, $project);
            $target_asset = null;

            Logger::getLogger()->verbose(sprintf('Found %d linked assets for release %s in %s/%s', count($response['assets']['links'] ?? []), $release, $group, $project));
            foreach($response['assets']['links'] as $asset)
            {
                if(preg_match('/\.ncc$/', $asset['name']) === 1)
                {
                    $target_asset = $asset;
                    Logger::getLogger()->debug(sprintf('Found .ncc asset: %s', $asset['name']));
                }
            }

            if ($target_asset)
            {
                $asset_url = $target_asset['direct_asset_url'] ?? $target_asset['url'] ?? null;
                if ($asset_url)
                {
                    Logger::getLogger()->info(sprintf('Found release package for %s/%s/%s', $group, $project, $release));
                    return new RemotePackage($asset_url, RemotePackageType::NCC, $group, $project);
                }
            }

            Logger::getLogger()->warning(sprintf('No suitable package found for release %s in %s/%s', $release, $group, $project));
            return null;
        }

        /**
         * @inheritDoc
         */
        public function getGit(string $group, string $project): ?RemotePackage
        {
            $project = str_replace('.', '/', $project);
            $endpoint = sprintf('%s://%s/api/v4/projects/%s%%2F%s', $this->getConfiguration()->isSslEnabled() ? 'https' : 'http', $this->getConfiguration()->getHost(), $group, rawurlencode($project));
            $curl = curl_init($endpoint);
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ncc'
            ];

            if($this->getAuthentication() !== null)
            {
                $headers = $this->injectAuthentication($curl, $headers);
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers
            ]);

            Logger::getLogger()->debug(sprintf('Fetching git url for %s/%s from %s', $group, $project, $endpoint));
            $response = $this->processHttpResponse($curl, $group, $project);
            if(isset($response['http_url_to_repo']))
            {
                Logger::getLogger()->info(sprintf('Found git url for %s/%s', $group, $project));
                return new RemotePackage($response['http_url_to_repo'], RemotePackageType::SOURCE_GIT, $group, $project);
            }

            Logger::getLogger()->warning(sprintf('No git url found for %s/%s', $group, $project));
            return null;
        }

        private function injectAuthentication(CurlHandle $curl, array $headers): array
        {
            Logger::getLogger()->debug(sprintf('Injecting authentication of type %s', $this->getAuthentication()->getType()->value));
            switch($this->getAuthentication()->getType())
            {
                case AuthenticationType::ACCESS_TOKEN->value:
                    if($this->getAuthentication() instanceof AccessToken)
                    {
                        $headers[] = 'Private-Token: ' . $this->getAuthentication()->getAccessToken();
                        Logger::getLogger()->verbose('Using access token authentication');
                        break;
                    }

                    Logger::getLogger()->error(sprintf('Invalid authentication type for Access Token, got %s instead', $this->getAuthentication()->getType()->value));
                    throw new OperationException(sprintf('Invalid authentication type for Access Token, got %s instead', $this->getAuthentication()->getType()->value));

                case AuthenticationType::USERNAME_PASSWORD->value:
                    if($this->getAuthentication() instanceof UsernamePassword)
                    {
                        curl_setopt($curl, CURLOPT_USERPWD, $this->getAuthentication()->getUsername() . ':' . $this->getAuthentication()->getPassword());
                        Logger::getLogger()->verbose(sprintf('Using username/password authentication for user %s', $this->getAuthentication()->getUsername()));
                        break;
                    }

                    Logger::getLogger()->error(sprintf('Invalid authentication type for Username/Password, got %s instead', $this->getAuthentication()->getType()->value));
                    throw new OperationException(sprintf('Invalid authentication type for Username/Password, got %s instead', $this->getAuthentication()->getType()->value));
            }

            return $headers;
        }

        private function processHttpResponse(CurlHandle $curl, string $group, string $project): array
        {
            $retry_count = 0;
            $response = false;

            while($retry_count < 3 && $response === false)
            {
                $response = curl_exec($curl);

                if($response === false)
                {
                    Logger::getLogger()->warning(sprintf('HTTP request failed for %s/%s: %s, retrying (%s/3)', $group, $project, curl_error($curl), $retry_count + 1));
                    $retry_count++;
                }
            }

            if($response === false)
            {
                Logger::getLogger()->error(sprintf('HTTP request failed for %s/%s after 3 retries: %s', $group, $project, curl_error($curl)));
                throw new NetworkException(sprintf('HTTP request failed for %s/%s: %s', $group, $project, curl_error($curl)));
            }

            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            Logger::getLogger()->verbose(sprintf('Received HTTP %d response for %s/%s', $http_code, $group, $project));
            switch ($http_code)
            {
                case 200:
                    Logger::getLogger()->debug(sprintf('Successfully received response for %s/%s', $group, $project));
                    break;

                case 401:
                    Logger::getLogger()->error(sprintf('Authentication failed for %s/%s, 401 Unauthorized', $group, $project));
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 401 Unauthorized, invalid/expired access token', $group, $project));

                case 403:
                    Logger::getLogger()->error(sprintf('Authentication failed for %s/%s, 403 Forbidden', $group, $project));
                    throw new OperationException(sprintf('Authentication failed for %s/%s, 403 Forbidden, insufficient scope', $group, $project));

                case 404:
                    Logger::getLogger()->error(sprintf('Resource not found for %s/%s, 404 Not Found', $group, $project));
                    throw new OperationException(sprintf('Resource not found for %s/%s, server returned 404 Not Found', $group, $project));

                default:
                    Logger::getLogger()->error(sprintf('Server responded with HTTP %s for %s/%s', $http_code, $group, $project));
                    throw new OperationException(sprintf('Server responded with HTTP code %s for %s/%s: %s', curl_getinfo($curl, CURLINFO_HTTP_CODE), $group, $project, $response));
            }

            try
            {
                Logger::getLogger()->debug(sprintf('Parsing JSON response for %s/%s', $group, $project));
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            }
            catch(JsonException $e)
            {
                Logger::getLogger()->error(sprintf('Failed to parse JSON response from %s/%s: %s', $group, $project, $e->getMessage()));
                throw new OperationException(sprintf('Failed to parse response from %s/%s: %s', $group, $project, $e->getMessage()), $e->getCode(), $e);
            }
        }
    }