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

    namespace ncc\Abstracts;

    use Exception;
    use ncc\ArchiveExtractors\TarArchive;
    use ncc\ArchiveExtractors\ZipArchive;
    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\PathResolver;
    use ncc\Classes\ShutdownHandler;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
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
         * @return RemotePackage[] Returns an array of RemotePackage objects representing possible sources
         */
        public function getAll(string $group, string $project, ?string $version=null): array
        {
            $results = [];

            // Treat "latest" as null to use the latest release/tag
            if($version !== null && strtolower($version) === 'latest')
            {
                $version = null;
            }

            // Find possible release archives
            try
            {
                $releaseArchive = $this->getReleaseArchive($group, $project, $version ?? $this->getLatestRelease($group, $project));
                if($releaseArchive !== null)
                {
                    $results[] = $releaseArchive;
                }
            }
            catch(Exception $e)
            {
                Logger::getLogger()->warning(sprintf('Could not get release archive for %s/%s:%s - %s', $group, $project, $version ?? 'latest', $e->getMessage()), $e);
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
            catch(Exception $e)
            {
                // Don't log warnings for Packagist repositories about tags not being supported (it's expected behavior)
                if(!($this instanceof PackagistRepository && $e instanceof OperationException && str_contains($e->getMessage(), 'does not support tags')))
                {
                    Logger::getLogger()->warning(sprintf('Could not get tag archive for %s/%s:%s - %s', $group, $project, $version ?? 'latest', $e->getMessage()), $e);
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
            catch(Exception $e)
            {
                Logger::getLogger()->warning(sprintf('Could not get the git repo for %s/%s:%s - %s', $group, $project, $version ?? 'latest', $e->getMessage()), $e);
            }

            // Find possible release packages
            try
            {
                $releasePackage = $this->getReleasePackage($group, $project, $version ?? $this->getLatestRelease($group, $project));
                if($releasePackage !== null)
                {
                    $results[] = $releasePackage;
                }
            }
            catch(Exception $e)
            {
                Logger::getLogger()->warning(sprintf('Could not get release package for %s/%s:%s - %s', $group, $project, $version ?? 'latest', $e->getMessage()), $e);
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
         * Returns a RemotePackage object representing the Git URL of a project
         *
         * @param string $group The group or namespace the project belongs to
         * @param string $project The name of the project
         * @return RemotePackage|null Returns the source of the git repository
         * @throws OperationException Thrown if there was an error during the operation
         */
        public abstract function getGit(string $group, string $project): ?RemotePackage;

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
            if(!IO::isDir(PathResolver::getTmpLocation()))
            {
                IO::mkdir(PathResolver::getTmpLocation());
            }
            elseif(!IO::isWritable(PathResolver::getTmpLocation()))
            {
                throw new IOException(sprintf('No write permissions for the temporary path %s', PathResolver::getTmpLocation()));
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
            if(!IO::isDir($path))
            {
                IO::mkdir($path);
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

            curl_close($curl);
            return $filePath;
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