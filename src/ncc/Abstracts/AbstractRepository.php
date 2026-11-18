<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Abstracts;

    use ArrayObject;
    use CurlHandle;
    use Throwable;
    use ncc\ArchiveExtractors\TarArchive;
    use ncc\ArchiveExtractors\ZipArchive;
    use ncc\Libraries\fslib\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\PathResolver;
    use ncc\Classes\ShutdownHandler;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IOException;
    use ncc\Libraries\Process\ExecutableFinder;
    use ncc\Libraries\Process\Process;
    use ncc\Objects\RemotePackage;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\RepositoryClients\GiteaRepository;
    use ncc\RepositoryClients\GithubRepository;
    use ncc\RepositoryClients\GitlabRepository;
    use ncc\RepositoryClients\PackagistRepository;

    abstract class AbstractRepository
    {
        private RepositoryConfiguration $configuration;
        private ?AbstractAuthentication $authentication;

        /**
         * AbstractRepository constructor.
         *
         * @param RepositoryConfiguration $configuration The configuration for this repository
         * @param AbstractAuthentication|null $authentication The authentication method to use for this repository, if any
         */
        public function __construct(RepositoryConfiguration $configuration, ?AbstractAuthentication $authentication=null)
        {
            $this->configuration = $configuration;
            $this->authentication = $authentication;
        }

        /**
         * Returns the configuration of this repository
         *
         * @return RepositoryConfiguration Returns the repository configuration
         */
        public function getConfiguration(): RepositoryConfiguration
        {
            return $this->configuration;
        }

        /**
         * Returns the authentication method used for this repository, if any
         *
         * @return AbstractAuthentication|null Returns the authentication method or null if none is set
         */
        public function getAuthentication(): ?AbstractAuthentication
        {
            return $this->authentication;
        }

        /**
         * Attempts to resolve all the possible remote packages ncc can obtain to use to attempt to install/convert
         * the package
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @param string|null $version The specific version (release/tag) to get packages for, or null for latest
         * @param array|null $errors If provided, receives the unique error messages of the sources that could not be
         *                           resolved, so callers can explain why no (or fewer) sources were found
         * @return RemotePackage[] Returns an array of RemotePackage objects representing possible sources
         */
        public function getAll(string $group, string $project, ?string $version=null, ?array &$errors=null): array
        {
            $results = [];
            $errors = [];
            $recordError = static function(string $sourceName, Throwable $e) use ($group, $project, $version, &$errors): void
            {
                Logger::getLogger()?->verbose(sprintf('Could not get %s for %s/%s:%s - %s', $sourceName, $group, $project, $version ?? 'latest', $e->getMessage()));
                if(!in_array($e->getMessage(), $errors, true))
                {
                    $errors[] = $e->getMessage();
                }
            };

            // Treat "latest" as null to use the latest release/tag
            if($version !== null && strtolower($version) === 'latest')
            {
                $version = null;
            }

            // Resolve the release once, it's shared by the release archive and the release package
            $release = $version;
            if($release === null)
            {
                try
                {
                    $release = $this->getLatestRelease($group, $project);
                }
                catch(Throwable $e)
                {
                    $recordError('the latest release', $e);
                }
            }

            // Find possible release archives
            if($release !== null)
            {
                try
                {
                    $releaseArchive = $this->getReleaseArchive($group, $project, $release);
                    if($releaseArchive !== null)
                    {
                        $results[] = $releaseArchive;
                    }
                }
                catch(Throwable $e)
                {
                    $recordError('release archive', $e);
                }
            }

            // Find possible tag archives
            try
            {
                $tagArchive = $this->getTagArchive($group, $project, $version ?? $this->getLatestTag($group, $project));
                if($tagArchive !== null)
                {
                    $results[] = $tagArchive;
                }
            }
            catch(Throwable $e)
            {
                // Don't log warnings for Packagist repositories about tags not being supported (it's expected behavior)
                if(!($this instanceof PackagistRepository && $e instanceof OperationException && str_contains($e->getMessage(), 'does not support tags')))
                {
                    $recordError('tag archive', $e);
                }
            }

            // Find possible git source
            try
            {
                $gitSource = $this->getGit($group, $project);
                if($gitSource !== null)
                {
                    $results[] = $gitSource;
                }
            }
            catch(Throwable $e)
            {
                $recordError('git repository', $e);
            }

            // Find possible release packages
            if($release !== null)
            {
                try
                {
                    $releasePackage = $this->getReleasePackage($group, $project, $release);
                    if($releasePackage !== null)
                    {
                        $results[] = $releasePackage;
                    }
                }
                catch(Throwable $e)
                {
                    $recordError('release package', $e);
                }
            }

            return $results;
        }

        /**
         * Returns an array of available tags for a given project in the repository
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project to get tags for
         * @return string[] Returns an array of available tags for the given project
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getTags(string $group, string $project): array;

        /**
         * Returns the latest tag for a given project in the repository
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project to get the latest tag for
         * @return string Returns the latest tag for the given project
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getLatestTag(string $group, string $project): string;

        /**
         * Returns a RemotePackage object representing the archive for a specific tag of a project
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @param string $tag The tag for which to get the archive
         * @return RemotePackage|null Returns a RemotePackage object if found, or null if not found
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getTagArchive(string $group, string $project, string $tag): ?RemotePackage;

        /**
         * Returns an array of available releases for a given project in the repository
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project to get releases for
         * @return string[] Returns an array of available releases for the given project
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getReleases(string $group, string $project): array;

        /**
         * Returns the latest release for a given project in the repository
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project to get the latest release for
         * @return string Returns the latest release for the given project
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getLatestRelease(string $group, string $project): string;

        /**
         * Returns a RemotePackage object representing the archive for a specific release of a project
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @param string $release The release for which to get the archive
         * @return RemotePackage|null Returns a RemotePackage object if found, or null if not found
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getReleaseArchive(string $group, string $project, string $release): ?RemotePackage;

        /**
         * Returns a RemotePackage object representing the package for a specific release of a project
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @param string $release The release for which to get the package
         * @return RemotePackage|null Returns a RemotePackage object if found, or null if not found
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getReleasePackage(string $group, string $project, string $release): ?RemotePackage;

        /**
         * Returns the download URL for a specific release asset by name
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @param string $release The release for which to get the asset
         * @param string $assetName The name of the asset to find (e.g., "template.zip")
         * @return string|null Returns the download URL of the asset, or null if not found
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getReleaseAssetUrl(string $group, string $project, string $release, string $assetName): ?string;

        /**
         * Fetches the git URL for a project from the repository's API.
         * Subclasses should override this to retrieve the actual git URL via the API.
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @return RemotePackage|null Returns the source of the git repository, or null if not found
         * @throws OperationException Thrown if there was an error during the operation
         */
        protected abstract function fetchGit(string $group, string $project): ?RemotePackage;

        /**
         * Returns a RemotePackage object representing the Git URL of a project.
         * Falls back to a constructed URL using the repository host, group and project if the API
         * call fails or returns null. If the host begins with "api.", that prefix is stripped before
         * building the assumed URL.
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @return RemotePackage|null Returns the source of the git repository
         */
        public function getGit(string $group, string $project): ?RemotePackage
        {
            try
            {
                $result = $this->fetchGit($group, $project);
                if($result !== null)
                {
                    return $result;
                }
            }
            catch(Throwable $e)
            {
                Logger::getLogger()?->debug(sprintf('Failed to fetch git URL for %s/%s from API, falling back to assumed URL: %s', $group, $project, $e->getMessage()));
            }

            // Fallback: construct an assumed git URL from the host, group and project
            $host = $this->getConfiguration()->getHost();
            if(str_starts_with($host, 'api.'))
            {
                $host = substr($host, 4);
            }

            $scheme = $this->getConfiguration()->isSslEnabled() ? 'https' : 'http';
            $url = sprintf('%s://%s/%s/%s.git', $scheme, $host, $group, $project);
            Logger::getLogger()?->debug(sprintf('Using assumed git URL for %s/%s: %s', $group, $project, $url));

            return new RemotePackage($url, RemotePackageType::SOURCE_GIT, $group, $project);
        }

        /**
         * Downloads and extracts the given remote package to a temporary location
         *
         * @param RemotePackage $remotePackage The remote package to download
         * @param callable|null $progress Optional callback function to report progress
         * @return string The path to the downloaded and extracted package
         * @throws IOException Thrown if there was an error writing files
         * @throws OperationException Thrown if there was an error during the operation
         */
        public function download(RemotePackage $remotePackage, ?callable $progress=null): string
        {
            if(!IO::isDirectory(PathResolver::getTmpLocation()))
            {
                IO::createDirectory(PathResolver::getTmpLocation());
            }
            elseif(!IO::isWritable(PathResolver::getTmpLocation()))
            {
                throw new OperationException(sprintf('No write permissions for the temporary path %s', PathResolver::getTmpLocation()));
            }

            switch($remotePackage->getType())
            {
                case RemotePackageType::SOURCE_ZIP:
                    $downloadedPackage = $this->downloadFile($remotePackage->getDownloadUrl(), PathResolver::getTmpLocation(), $progress);
                    if($progress !== null)
                    {
                        $progress(1, 1, 'Extracting ZIP archive');
                    }
                    $outputPath = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . uniqid();
                    ZipArchive::extract($downloadedPackage, $outputPath);
                    ShutdownHandler::flagTemporary($downloadedPackage);
                    ShutdownHandler::flagTemporary($outputPath);
                    return $outputPath;

                case RemotePackageType::SOURCE_TAR:
                    $downloadedPackage = $this->downloadFile($remotePackage->getDownloadUrl(), PathResolver::getTmpLocation(), $progress);
                    if($progress !== null)
                    {
                        $progress(1, 1, 'Extracting TAR archive');
                    }
                    $outputPath = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . uniqid();
                    TarArchive::extract($downloadedPackage, $outputPath);
                    ShutdownHandler::flagTemporary($downloadedPackage);
                    ShutdownHandler::flagTemporary($outputPath);
                    return $outputPath;

                case RemotePackageType::SOURCE_GIT:
                    // USe symfony process to clone the repo properly
                    $outputPath = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . uniqid();
                    $this->gitClone($remotePackage->getDownloadUrl(), $outputPath, $progress);
                    ShutdownHandler::flagTemporary($outputPath);
                    return $outputPath;

                case RemotePackageType::NCC:
                    $downloadedPackage = $this->downloadFile($remotePackage->getDownloadUrl(), PathResolver::getTmpLocation(), $progress);
                    ShutdownHandler::flagTemporary($downloadedPackage);
                    return $downloadedPackage;
            }

            throw new OperationException(sprintf('Unsupported remote package type: %s', $remotePackage->getType()->value));
        }

        /**
         * Clones a git repository from a given URL to a specified path
         *
         * @param string $url The URL of the git repository to clone
         * @param string $path The path where the repository should be cloned
         * @param callable|null $progressCallback Optional callback function to report progress
         * @return void The path to the cloned repository
         * @throws OperationException Thrown if the git clone operation fails
         */
        private function gitClone(string $url, string $path, ?callable $progressCallback=null): void
        {
            $gitExecutable = (new ExecutableFinder())->find('git');

            if($gitExecutable === null)
            {
                throw new OperationException('Git executable not found in PATH');
            }

            if($progressCallback !== null)
            {
                // Initial progress for git clone (indeterminate)
                $progressCallback(0, 100, 'Cloning Git repository...');
            }
            
            $process = new Process([$gitExecutable, 'clone', $url, $path]);
            $process->setTimeout(300);
            $process->run();

            if(!$process->isSuccessful())
            {
                throw new OperationException(sprintf('Failed to clone git repository from %s: %s', $url, $process->getErrorOutput()));
            }

        }

        /**
         * Downloads a file from a given URL to a specified path
         *
         * @param string $url The URL of the file to download
         * @param string $path The path where the file should be saved
         * @param callable|null $progressCallback Optional callback function to report progress
         * @return string The path to the downloaded file
         * @throws IOException Thrown if there was an error writing the file
         * @throws OperationException Thrown if the download fails
         */
        private function downloadFile(string $url, string $path, ?callable $progressCallback=null): string
        {
            $filePath = basename(parse_url($url, PHP_URL_PATH));
            $curl = curl_init($url);

            if(empty($filePath))
            {
                $filePath = uniqid('download_', true) . '.bin';
            }

            if(str_ends_with($path, '/'))
            {
                $path = substr($path, 0, -1);
            }

            // Ensure the directory exists before attempting to write the file
            if(!IO::isDirectory($path))
            {
                IO::createDirectory($path);
            }

            $filePath = $path . DIRECTORY_SEPARATOR . $filePath;
            $fileHandle = fopen($filePath, 'wb');
            $end = false;

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_NOPROGRESS, false);
            curl_setopt($curl, CURLOPT_FILE, $fileHandle);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'User-Agent: ncc'
            ]);
            curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, static function ($resource, $totalBytes, $downloadedBytes)  use ($url, &$end, $progressCallback)
            {
                if($progressCallback !== null && $totalBytes > 0)
                {
                    // Format bytes for display
                    $downloadedMB = round($downloadedBytes / 1048576, 2);
                    $totalMB = round($totalBytes / 1048576, 2);
                    $message = sprintf('Downloading %s (%s MB / %s MB)', basename($url), $downloadedMB, $totalMB);
                    $progressCallback($downloadedBytes, $totalBytes, $message);
                }

                if($totalBytes == 0)
                {
                    return;
                }

                if($totalBytes === $downloadedBytes && $end)
                {
                    return;
                }

                if($totalBytes === $downloadedBytes)
                {
                    $end = true;
                }
            });

            unset($progress_bar);
            curl_exec($curl);
            fclose($fileHandle);

            if(curl_errno($curl))
            {
                throw new OperationException(sprintf('Failed to download file from %s: %s', $url, curl_error($curl)));
            }

            return $filePath;
        }

        /**
         * Captures the response headers of a cURL request, the returned object is filled with the headers of the final
         * response (lowercase names) once the request is executed
         *
         * @param CurlHandle $curl The cURL instance to capture the response headers of
         * @return ArrayObject The response headers, filled when the request is executed
         */
        protected static function captureResponseHeaders(CurlHandle $curl): ArrayObject
        {
            $headers = new ArrayObject();
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, static function($handle, string $header) use ($headers): int
            {
                // A new status line starts the headers of another response (eg; after a redirect or a retry)
                if(str_starts_with($header, 'HTTP/'))
                {
                    $headers->exchangeArray([]);
                }
                elseif(str_contains($header, ':'))
                {
                    [$name, $value] = explode(':', $header, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }

                return strlen($header);
            });

            return $headers;
        }

        /**
         * Determines if a response was rejected because the API rate limit was exceeded. HTTP 429 is always a rate
         * limit, GitHub answers 403 with "x-ratelimit-remaining: 0" or a "rate limit" message instead
         *
         * @param int $httpCode The HTTP status code of the response
         * @param ArrayObject|array $headers The response headers with lowercase names
         * @param string|false $body The response body
         * @return bool True if the API rate limit was exceeded
         */
        protected static function isRateLimited(int $httpCode, ArrayObject|array $headers, string|false $body): bool
        {
            if($httpCode === 429)
            {
                return true;
            }

            if($httpCode !== 403)
            {
                return false;
            }

            return ($headers['x-ratelimit-remaining'] ?? null) === '0'
                || ($headers['ratelimit-remaining'] ?? null) === '0'
                || isset($headers['retry-after'])
                || (is_string($body) && stripos($body, 'rate limit') !== false);
        }

        /**
         * Builds the error message for a request rejected by the API rate limit, including when the limit resets if
         * the server reports it
         *
         * @param string $group The group or namespace of the requested project
         * @param string $project The name of the requested project
         * @param ArrayObject|array $headers The response headers with lowercase names
         * @return string The error message
         */
        protected function getRateLimitMessage(string $group, string $project, ArrayObject|array $headers): string
        {
            $message = sprintf('API rate limit of %s exceeded for %s/%s', $this->getConfiguration()->getName(), $group, $project);

            $reset = $headers['x-ratelimit-reset'] ?? $headers['ratelimit-reset'] ?? null;
            if(isset($headers['retry-after']) && ctype_digit($headers['retry-after']))
            {
                $message .= sprintf(', retry in %d second(s)', (int)$headers['retry-after']);
            }
            elseif($reset !== null && ctype_digit($reset))
            {
                // Reset headers are a unix timestamp
                $message .= sprintf(', the limit resets at %s', date('H:i:s', (int)$reset));
            }

            if($this->getAuthentication() === null)
            {
                $message .= sprintf(', add an authentication entry with "ncc auth" and use it with --%s-auth=<entry> for a higher limit', $this->getConfiguration()->getName());
            }

            return $message;
        }

        /**
         * Constructs a repository client from a given repository configuration
         *
         * @param RepositoryConfiguration $configuration The repository configuration
         * @return AbstractRepository The constructed repository client
         */
        public static function fromConfiguration(RepositoryConfiguration $configuration, ?AbstractAuthentication $authentication=null): AbstractRepository
        {
            return match($configuration->getType())
            {
                RepositoryType::GITHUB => new GithubRepository($configuration, $authentication),
                RepositoryType::GITEA => new GiteaRepository($configuration, $authentication),
                RepositoryType::GITLAB => new GitlabRepository($configuration, $authentication),
                RepositoryType::PACKAGIST => new PackagistRepository($configuration, $authentication)
            };
        }
    }