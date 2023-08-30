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

    namespace ncc\Classes\GitlabExtension;

    use ncc\Enums\Versions;
    use ncc\Classes\HttpClient;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\GitException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\RepositorySourceInterface;
    use ncc\Objects\DefinedRemoteSource;
    use ncc\Objects\HttpRequest;
    use ncc\Objects\RemotePackageInput;
    use ncc\Objects\RepositoryQueryResults;
    use ncc\Objects\Vault\Entry;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\Utilities\Functions;

    class GitlabService implements RepositorySourceInterface
    {
        /**
         * Attempts to return the gitRepositoryUrl of a release, cannot specify a version.
         * This needs to be done using git
         *
         * @param RemotePackageInput $packageInput
         * @param DefinedRemoteSource $definedRemoteSource
         * @param Entry|null $entry
         * @return RepositoryQueryResults
         * @throws AuthenticationException
         * @throws GitException
         * @throws IOException
         * @throws NetworkException
         */
        public static function getGitRepository(RemotePackageInput $packageInput, DefinedRemoteSource $definedRemoteSource, ?Entry $entry=null): RepositoryQueryResults
        {
            $httpRequest = new HttpRequest();
            $protocol = ($definedRemoteSource->isSsl() ? "https" : "http");
            $owner_f = str_ireplace(array("/", "."), "%2F", $packageInput->getVendor());
            $project_f = str_ireplace(array("/", "."), "%2F", $packageInput->getPackage());
            $httpRequest->setUrl($protocol . '://' . $definedRemoteSource->getHost() . "/api/v4/projects/$owner_f%2F$project_f");
            $httpRequest = Functions::prepareGitServiceRequest($httpRequest, $entry);

            $response = HttpClient::request($httpRequest, true);

            if($response->getStatusCode() !== 200)
            {
                throw new GitException(sprintf('Failed to fetch releases for the given repository. Status code: %s', $response->getStatusCode()));
            }

            $response_decoded = Functions::loadJson($response->getBody(), Functions::FORCE_ARRAY);

            $query = new RepositoryQueryResults();
            $query->getFiles()->GitSshUrl = ($response_decoded['ssh_url_to_repo'] ?? null);
            $query->getFiles()->GitHttpUrl = ($response_decoded['http_url_to_repo'] ?? null);
            $query->setVersion(Functions::convertToSemVer($response_decoded['default_branch']));
            $query->setReleaseDescription($response_decoded['description'] ?? null);
            $query->setReleaseName($response_decoded['name'] ?? null);

            return $query;
        }

        /**
         * Returns the download URL of the requested version of the package.
         *
         * @param RemotePackageInput $package_input
         * @param DefinedRemoteSource $defined_remote_source
         * @param Entry|null $entry
         * @return RepositoryQueryResults
         * @throws AuthenticationException
         * @throws GitException
         * @throws NetworkException
         * @throws IOException
         */
        public static function getRelease(RemotePackageInput $package_input, DefinedRemoteSource $defined_remote_source, ?Entry $entry = null): RepositoryQueryResults
        {
            $releases = self::getReleases($package_input->getVendor(), $package_input->getPackage(), $defined_remote_source, $entry);

            if(count($releases) === 0)
            {
                throw new GitException(sprintf('No releases found for the repository %s/%s (selected version: %s)', $package_input->getVendor(), $package_input->getPackage(), $package_input->getVersion()));
            }

            // Query the latest package only
            if($package_input->getVersion() === Versions::LATEST)
            {
                $latest_version = null;
                foreach($releases as $release)
                {
                    if($latest_version === null)
                    {
                        $latest_version = $release->Version;
                        continue;
                    }

                    if(VersionComparator::compareVersion($release->Version, $latest_version) === 1)
                    {
                        $latest_version = $release->Version;
                    }
                }

                return $releases[$latest_version];
            }

            // Query a specific version
            if(!isset($releases[$package_input->getVersion()]))
            {
                // Find the closest thing to the requested version
                $selected_version = null;
                foreach($releases as $version => $url)
                {
                    if($selected_version === null)
                    {
                        $selected_version = $version;
                        continue;
                    }

                    if(VersionComparator::compareVersion($version, $package_input->getVersion()) === 1)
                    {
                        $selected_version = $version;
                    }
                }

                if($selected_version === null)
                {
                    throw new GitException(sprintf('Could not find a release for %s/%s with the version %s', $package_input->getVendor(), $package_input->getPackage(), $package_input->getVersion()));
                }
            }
            else
            {
                $selected_version = $package_input->getVersion();
            }

            if(!isset($releases[$selected_version]))
            {
                throw new GitException(sprintf('Could not find a release for %s/%s with the version %s', $package_input->getVendor(), $package_input->getPackage(), $package_input->getVersion()));
            }

            return $releases[$selected_version];
        }

        /**
         * @param RemotePackageInput $packageInput
         * @param DefinedRemoteSource $definedRemoteSource
         * @param Entry|null $entry
         * @return RepositoryQueryResults
         * @throws NotSupportedException
         */
        public static function getNccPackage(RemotePackageInput $packageInput, DefinedRemoteSource $definedRemoteSource, ?Entry $entry = null): RepositoryQueryResults
        {
            throw new NotSupportedException(sprintf('The given repository source "%s" does not support ncc packages.', $definedRemoteSource->getHost()));
        }

        /**
         * Returns an array of all the tags for the given owner and repository name.
         *
         * @param string $owner
         * @param string $repository
         * @param DefinedRemoteSource $definedRemoteSource
         * @param Entry|null $entry
         * @return array
         * @throws AuthenticationException
         * @throws GitException
         * @throws IOException
         * @throws NetworkException
         */
        private static function getReleases(string $owner, string $repository, DefinedRemoteSource $definedRemoteSource, ?Entry $entry): array
        {
            $httpRequest = new HttpRequest();
            $protocol = ($definedRemoteSource->isSsl() ? "https" : "http");
            $owner_f = str_ireplace("/", "%2F", $owner);
            $owner_f = str_ireplace(".", "%2F", $owner_f);
            $repository_f = str_ireplace("/", "%2F", $repository);
            $repository_f = str_ireplace(".", "%2F", $repository_f);

            $httpRequest->setUrl($protocol . '://' . $definedRemoteSource->getHost() . "/api/v4/projects/$owner_f%2F$repository_f/releases");
            $httpRequest = Functions::prepareGitServiceRequest($httpRequest, $entry);

            $response = HttpClient::request($httpRequest, true);

            if($response->getStatusCode() !== 200)
            {
                throw new GitException(sprintf('Failed to fetch releases for repository %s/%s. Status code: %s', $owner, $repository, $response->getStatusCode()));
            }

            $response_decoded = Functions::loadJson($response->getBody(), Functions::FORCE_ARRAY);

            if(count($response_decoded) === 0)
            {
                return [];
            }

            $return = [];
            foreach($response_decoded as $release)
            {
                $query_results = new RepositoryQueryResults();
                $query_results->setReleaseName($release['name'] ?? null);
                $query_results->setReleaseDescription($release['description'] ?? null);
                $query_results->setVersion(Functions::convertToSemVer($release['tag_name']));

                if(isset($release['assets']['sources']) && count($release['assets']['sources']) > 0)
                {
                    foreach($release['assets']['sources'] as $source)
                    {
                        if($source['format'] === 'zip')
                        {
                            $query_results->getFiles()->ZipballUrl = $source['url'];
                            break;
                        }

                        if($source['format'] === 'tar.gz')
                        {
                            $query_results->getFiles()->ZipballUrl = $source['url'];
                            break;
                        }

                        if($source['format'] === 'ncc')
                        {
                            $query_results->getFiles()->PackageUrl = $source['url'];
                            break;
                        }
                    }
                }

                $return[$query_results->getVersion()] = $query_results;
            }

            return $return;
        }
    }