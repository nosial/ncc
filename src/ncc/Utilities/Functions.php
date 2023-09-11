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

    namespace ncc\Utilities;

    use Exception;
    use FilesystemIterator;
    use JsonException;
    use ncc\Enums\AuthenticationType;
    use ncc\Enums\DefinedRemoteSourceType;
    use ncc\Enums\HttpRequestType;
    use ncc\Enums\Runners;
    use ncc\Enums\Scopes;
    use ncc\Classes\BashExtension\BashRunner;
    use ncc\Classes\GithubExtension\GithubService;
    use ncc\Classes\GitlabExtension\GitlabService;
    use ncc\Classes\HttpClient;
    use ncc\Classes\LuaExtension\LuaRunner;
    use ncc\Classes\PerlExtension\PerlRunner;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Classes\PythonExtension\Python2Runner;
    use ncc\Classes\PythonExtension\Python3Runner;
    use ncc\Classes\PythonExtension\PythonRunner;
    use ncc\Exceptions\ArchiveException;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NetworkException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Managers\ConfigurationManager;
    use ncc\Managers\CredentialManager;
    use ncc\Managers\PackageLockManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\ComposerJson;
    use ncc\Objects\DefinedRemoteSource;
    use ncc\Objects\HttpRequest;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Objects\RemotePackageInput;
    use ncc\Objects\RepositoryQueryResults;
    use ncc\Objects\RepositoryQueryResults\Files;
    use ncc\Objects\Vault\Entry;
    use ncc\ThirdParty\jelix\Version\Parser;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use RuntimeException;
    use Throwable;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Functions
    {
        /**
         * Forces the output to be an array
         */
        public const FORCE_ARRAY = 0b0001;

        /**
         * Forces the output to be pretty
         */
        public const PRETTY = 0b0010;

        /**
         * Escapes unicode characters
         */
        public const ESCAPE_UNICODE = 0b0100;

        /**
         * Calculates a byte-code representation of the input using CRC32
         *
         * @param string $input
         * @return string
         */
        public static function cbc(string $input): string
        {
            return RuntimeCache::get("cbc_$input") ?? RuntimeCache::set("cbc_$input", hash('crc32', $input, true));
        }

        /**
         * Returns the specified of a value of an array using plaintext, if none is found it will
         * attempt to use the cbc method to find the selected input, if all fails then null will be returned.
         *
         * @param array $data
         * @param string $select
         * @return mixed|null
         */
        public static function array_bc(array $data, string $select): mixed
        {
            return $data[$select] ?? $data[self::cbc($select)] ?? null;
        }

        /**
         * Loads a json file
         *
         * @param string $path
         * @param int $flags
         * @return array
         * @throws IOException
         * @throws PathNotFoundException
         */
        public static function loadJsonFile(string $path, int $flags=0): array
        {
            if(!file_exists($path))
            {
                throw new PathNotFoundException($path);
            }

            return self::loadJson(IO::fread($path), $flags);
        }

        /**
         * Parses a json string
         *
         * @param string $json
         * @param int $flags
         * @return array
         * @throws IOException
         */
        public static function loadJson(string $json, int $flags=0): array
        {
            try
            {
                return json_decode($json, ($flags & self::FORCE_ARRAY), 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            }
            catch(Throwable $e)
            {
                throw new IOException($e->getMessage(), $e);
            }
        }

        /**
         * Returns the JSON representation of a value. Accepts flag Json::PRETTY.
         *
         * @param mixed $value
         * @param int $flags
         * @return string
         * @throws IOException
         */
        public static function encodeJson(mixed $value, int $flags=0): string
        {
            $flags = (($flags & self::ESCAPE_UNICODE) ? 0 : JSON_UNESCAPED_UNICODE)
                | JSON_UNESCAPED_SLASHES
                | (($flags & self::PRETTY) ? JSON_PRETTY_PRINT : 0)
                | (defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0); // since PHP 5.6.6 & PECL JSON-C 1.3.7

            try
            {
                return json_encode($value, JSON_THROW_ON_ERROR | $flags);
            }
            catch (JsonException $e)
            {
                throw new IOException($e->getMessage(), $e);
            }
        }

        /**
         * Writes a json file to disk
         *
         * @param mixed $value
         * @param string $path
         * @param int $flags
         * @return void
         * @throws IOException
         */
        public static function encodeJsonFile(mixed $value, string $path, int $flags=0): void
        {
            file_put_contents($path, self::encodeJson($value, $flags));
        }

        /**
         * Returns the current working directory
         *
         * @param CliHelpSection[] $input
         * @return int
         */
        public static function detectParametersPadding(array $input): int
        {
            $current_count = 0;

            foreach($input as $optionsSection)
            {
                if(count($optionsSection->getParameters()) > 0)
                {
                    foreach($optionsSection->getParameters() as $parameter)
                    {
                        if($current_count < strlen($parameter))
                        {
                            $current_count = strlen($parameter);
                        }
                    }
                }
            }

            return $current_count;
        }

        /**
         * Returns the banner for the CLI menu (Really fancy stuff!)
         *
         * @param string $version
         * @param string $copyright
         * @param bool $basic_ascii
         * @return string
         * @throws IOException
         * @throws PathNotFoundException
         */
        public static function getBanner(string $version, string $copyright, bool $basic_ascii=false): string
        {
            if($basic_ascii)
            {
                $banner = IO::fread(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'banner_basic');
            }
            else
            {
                $banner = IO::fread(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'banner_extended');
            }

            $banner_version = str_pad($version, 21);
            $banner_copyright = str_pad($copyright, 30);

            return str_ireplace(array('%A', '%B'), array($banner_version, $banner_copyright), $banner);
        }

        /**
         * Removes the basename from a path, eg; extracts "foo/bar.txt" from "/root/foo/bar.txt" if "/root" is the
         * current working directory, optionally accepts a different basename using the $basename parameter.
         *
         * @param string $path
         * @param string|null $base_name
         * @return string
         */
        public static function removeBasename(string $path, ?string $base_name=null): string
        {
            if($base_name === null)
            {
                $base_name = getcwd();
            }

            // Append the trailing slash if it's not already there
            // "/etc/foo" becomes "/etc/foo/"
            if(!str_ends_with($base_name, DIRECTORY_SEPARATOR))
            {
                $base_name .= DIRECTORY_SEPARATOR;
            }

            // If the path is "/etc/foo/text.txt" and the basename is "/etc" then the returned path will be "foo/test.txt"
            return str_replace($base_name, (string)null, $path);
        }

        /**
         * Corrects the directory separator for the given path
         *
         * @param $path
         * @return string
         */
        public static function correctDirectorySeparator($path): string
        {
            return str_ireplace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        }

        /**
         * @param string $path
         * @param ExecutionPolicy $policy
         * @return ExecutionUnit
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public static function compileRunner(string $path, ExecutionPolicy $policy): ExecutionUnit
        {
            return match (strtolower($policy->getRunner()))
            {
                Runners::BASH => BashRunner::processUnit($path, $policy),
                Runners::PHP => PhpRunner::processUnit($path, $policy),
                Runners::PERL => PerlRunner::processUnit($path, $policy),
                Runners::PYTHON => PythonRunner::processUnit($path, $policy),
                Runners::PYTHON_2 => Python2Runner::processUnit($path, $policy),
                Runners::PYTHON_3 => Python3Runner::processUnit($path, $policy),
                Runners::LUA => LuaRunner::processUnit($path, $policy),
                default => throw new NotSupportedException('The runner \'' . $policy->getRunner() . '\' is not supported'),
            };
        }

        /**
         * Returns an array representation of the exception
         *
         * @param Exception $e
         * @return array
         */
        public static function exceptionToArray(Throwable $e): array
        {
            $exception = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => null,
                'trace_string' => $e->getTraceAsString(),
            ];

            if($e->getPrevious() !== null)
            {
                $exception['previous'] = self::exceptionToArray($e->getPrevious());
            }

            return $exception;
        }

        /**
         * Takes the input bytes and converts it to a readable unit representation
         *
         * @param int $bytes
         * @param int $decimals
         * @return string
         */
        public static function b2u(int $bytes, int $decimals=2): string
        {
            $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
            $factor = floor((strlen($bytes) - 1) / 3);
            return sprintf("%.{$decimals}f", $bytes / (1024 ** $factor)) . @$size[$factor];
        }

        /**
         * Initializes NCC files
         *
         * @return void
         * @throws AuthenticationException
         */
        public static function initializeFiles(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Cannot initialize NCC files, insufficient permissions');
            }

            Console::outVerbose('Initializing NCC files');

            $filesystem = new Filesystem();
            if(!$filesystem->exists(PathFinder::getDataPath(Scopes::SYSTEM)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getDataPath(Scopes::SYSTEM)));
                $filesystem->mkdir(PathFinder::getDataPath(Scopes::SYSTEM), 0755);
            }

            if(!$filesystem->exists(PathFinder::getCachePath(Scopes::SYSTEM)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getCachePath(Scopes::SYSTEM)));
                $filesystem->mkdir(PathFinder::getCachePath(Scopes::SYSTEM));
            }

            if(!$filesystem->exists(PathFinder::getRunnerPath(Scopes::SYSTEM)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getRunnerPath(Scopes::SYSTEM)));
                $filesystem->mkdir(PathFinder::getRunnerPath(Scopes::SYSTEM), 0755);
            }

            if(!$filesystem->exists(PathFinder::getPackagesPath(Scopes::SYSTEM)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getPackagesPath(Scopes::SYSTEM)));
                $filesystem->mkdir(PathFinder::getPackagesPath(Scopes::SYSTEM), 0755);
            }

            // Create credential store if needed
            try
            {
                Console::outVerbose('Processing Credential Store');
                $credential_manager = new CredentialManager();
                $credential_manager->constructStore();
            }
            catch (Exception $e)
            {
                Console::outError('Cannot construct credential store, ' . $e->getMessage() . ' (Error Code: ' . $e->getCode() . ')');
            }

            // Create package lock if needed
            try
            {
                Console::outVerbose('Processing Package Lock');
                $package_manager = new PackageLockManager();
                $package_manager->constructLockFile();
            }
            catch (Exception $e)
            {
                Console::outError('Cannot construct Package Lock, ' . $e->getMessage() . ' (Error Code: ' . $e->getCode() . ')');
            }
        }

        /**
         * Loads a composer json file and returns a ComposerJson object
         *
         * @param string $path
         * @return ComposerJson
         * @throws IOException
         * @throws PathNotFoundException
         */
        public static function loadComposerJson(string $path): ComposerJson
        {
            $json_contents = IO::fread($path);

            try
            {
                return ComposerJson::fromArray(json_decode($json_contents, true, 512, JSON_THROW_ON_ERROR));
            }
            catch(JsonException $e)
            {
                throw new IOException('Cannot parse composer.json, ' . $e->getMessage(), $e);
            }
        }

        /**
         * Attempts to convert the value to a bool
         *
         * @param $value
         * @return bool
         */
        public static function cbool($value): bool
        {
            if(is_null($value))
            {
                return false;
            }

            if(is_string($value))
            {
                switch(strtolower($value))
                {
                    case 'y':
                    case 'yes':
                    case 't':
                    case 'true':
                    case '1':
                        return true;

                    case 'n':
                    case 'no':
                    case 'f':
                    case 'false':
                    case '0':
                        return false;
                }
            }

            return (bool)$value;
        }

        /**
         * Returns a property value from the configuration
         *
         * @param string $property
         * @return mixed|null
         * @noinspection PhpMissingReturnTypeInspection
         */
        public static function getConfigurationProperty(string $property)
        {
            return (new ConfigurationManager())->getProperty($property);
        }

        /**
         * Parses the version and returns a valid version format
         *
         * @param string $version
         * @return string
         * @throws Exception
         */
        public static function parseVersion(string $version): string
        {
            if(str_starts_with(strtolower($version), 'v'))
            {
                $version = substr($version, 1);
            }

            return Parser::parse($version)->toString();
        }

        /**
         * Returns a random string
         *
         * @param int $length
         * @return string
         */
        public static function randomString(int $length=32): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $characters_length = strlen($characters);
            $random_string = '';

            for($i = 0; $i < $length; $i++)
            {
                try
                {
                    $random_string .= $characters[random_int(0, $characters_length - 1)];
                }
                catch (Exception $e)
                {
                    throw new RuntimeException('Cannot generate random string, ' . $e->getMessage(), $e->getCode(), $e);
                }
            }

            return $random_string;
        }

        /**
         * Returns a path to a temporary directory
         *
         * @param bool $create
         * @param bool $set_as_tmp
         * @return string
         */
        public static function getTmpDir(bool $create=true, bool $set_as_tmp=true): string
        {
            $path = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . self::randomString(16);

            if($create)
            {
                $filesystem = new Filesystem();
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $filesystem->mkdir($path, 0777);
            }

            if($set_as_tmp)
            {
                RuntimeCache::setFileAsTemporary($path);
            }

            return $path;
        }

        /**
         * Applies the authentication to the given HTTP request.
         *
         * @param HttpRequest $http_request
         * @param Entry|null $entry
         * @param bool $expect_json
         * @return HttpRequest
         * @throws AuthenticationException
         */
        public static function prepareGitServiceRequest(HttpRequest $http_request, ?Entry $entry=null, bool $expect_json=true): HttpRequest
        {
            if($entry !== null)
            {
                if(!$entry->isCurrentlyDecrypted())
                {
                    throw new RuntimeException('The given Vault entry is not decrypted.');
                }

                switch ($entry->getPassword()?->getAuthenticationType())
                {
                    case AuthenticationType::ACCESS_TOKEN:
                        $http_request->addHeader("Authorization: Bearer " . $entry->getPassword());
                        break;

                    case AuthenticationType::USERNAME_PASSWORD:
                        throw new AuthenticationException(sprintf('The given Vault entry is using the %s authentication type, which is not supported for Gitlab', AuthenticationType::USERNAME_PASSWORD));
                }
            }

            if($expect_json)
            {
                $http_request->addHeader("Accept: application/json");
                $http_request->addHeader("Content-Type: application/json");
            }

            return $http_request;
        }

        /**
         * Downloads a file from the given URL and saves it to the given path
         *
         * @param string $url
         * @param Entry|null $entry
         * @return string
         * @throws AuthenticationException
         * @throws NetworkException
         */
        public static function downloadGitServiceFile(string $url, ?Entry $entry=null): string
        {
            if(RuntimeCache::get('download_cache.' . $url) !== null)
            {
                return RuntimeCache::get('download_cache.' . $url);
            }

            $out_path = self::getTmpDir() . "/" . basename($url);
            $http_request = new HttpRequest();
            $http_request->setUrl($url);
            $http_request->setType(HttpRequestType::GET);
            $http_request = self::prepareGitServiceRequest($http_request, $entry, false);

            Console::out('Downloading file ' . $url);
            HttpClient::download($http_request, $out_path);
            RuntimeCache::set('download_cache.' . $url, $out_path);

            return $out_path;
        }

        /**
         * @param string $path
         * @return string|null
         * @throws ArchiveException
         */
        public static function extractArchive(string $path): ?string
        {
            $executable_finder = new ExecutableFinder();
            $unzip_executable = $executable_finder->find('unzip');
            $tar_executable = $executable_finder->find('tar');
            $out_path = dirname($path);
            $filesystem = new Filesystem();

            if(!$filesystem->exists($out_path))
            {
                $filesystem->mkdir($out_path);
            }

            RuntimeCache::setFileAsTemporary($out_path);

            $mime_type = mime_content_type($path);
            $supported_types = [];

            if($unzip_executable !== null)
            {
                array_push($supported_types, 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/x-compress', 'application/x-compressed', 'multipart/x-zip');
            }
            elseif(RuntimeCache::get('warning_zip_shown') !== true)
            {
                Console::out('unzip executable not found. ZIP archives will not be supported.');
                RuntimeCache::set('warning_zip_shown', true);
            }

            if($tar_executable !== null)
            {
                array_push($supported_types, 'application/x-tar', 'application/x-gzip', 'application/x-bzip2', 'application/x-xz');
            }
            elseif(RuntimeCache::get('warning_tar_shown') !== true)
            {
                Console::outWarning('tar executable not found. TAR archives will not be supported.');
                RuntimeCache::set('warning_tar_shown', true);
            }

            if(!in_array($mime_type, $supported_types, true))
            {
                throw new ArchiveException(sprintf('Cannot extract archive %s, unsupported archive type %s', $path, $mime_type));
            }

            $command = match ($mime_type) {
                'application/zip' => [$unzip_executable, $path, '-d', $out_path],
                'application/x-tar' => [$tar_executable, '--verbose', '-xf', $path, '-C', $out_path],
                'application/x-gzip' => [$tar_executable, '--verbose', '-xzf', $path, '-C', $out_path],
                'application/x-bzip2' => [$tar_executable, '--verbose', '-xjf', $path, '-C', $out_path],
                default => throw new ArchiveException(sprintf('Cannot extract archive %s, unsupported archive type %s', $path, $mime_type)),
            };

            Console::out("Extracting archive $path");
            $process = new Process($command);

            // display the output of the command
            $process->run(function ($type, $buffer)
            {
                Console::outVerbose($buffer);
            });

            if(!$process->isSuccessful())
            {
                throw new ArchiveException($process->getErrorOutput());
            }

            return $out_path;
        }

        /**
         * Scans the given directory for files and returns the found file
         *
         * @param string $path
         * @param array $files
         * @return string|null
         */
        public static function searchDirectory(string $path, array $files): ?string
        {
            if(!is_dir($path))
            {
                return null;
            }

            // Search files in the given directory recursively
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file)
            {
                if(in_array($file->getFilename(), $files, true))
                {
                    return $file->getPathname();
                }
            }

            return null;
        }

        /**
         * Attempts to convert a weird version number to a standard version number
         *
         * @param $version
         * @return string
         */
        public static function convertToSemVer($version): string
        {
            if(stripos(strtolower($version), 'v') === 0)
            {
                $version = substr($version, 1);
            }

            if(!Validate::version($version))
            {
                $parts = explode('.', $version);
                $major = (string)null;
                $minor = (string)null;
                $patch = (string)null;

                if(count($parts) >= 1)
                {
                    $major = $parts[0];
                }

                if(count($parts) >= 2)
                {
                    $minor = $parts[1];
                }

                if(count($parts) >= 3)
                {
                    $patch = $parts[2];
                }

                // Assemble the SemVer compatible string
                $version = "$major.$minor.$patch";
            }

            if(!Validate::version($version))
            {
                return '1.0.0';
            }

            return $version;
        }

        /**
         * Returns a complete RepositoryQueryResults object
         *
         * @param RemotePackageInput $packageInput
         * @param DefinedRemoteSource $definedRemoteSource
         * @param Entry|null $entry
         * @return RepositoryQueryResults
         */
        public static function getRepositoryQueryResults(RemotePackageInput $packageInput, DefinedRemoteSource $definedRemoteSource, ?Entry $entry): RepositoryQueryResults
        {
            $results = new RepositoryQueryResults();

            switch($definedRemoteSource->getType())
            {
                case DefinedRemoteSourceType::GITHUB:
                    $source = GithubService::class;
                    break;

                case DefinedRemoteSourceType::GITLAB:
                    $source = GitlabService::class;
                    break;

                default:
                    return $results;
            }

            // Check if the specified version is a release
            try
            {
                Console::outVerbose(sprintf('Attempting to fetch source code from %s', $definedRemoteSource->getHost()));
                $release_results = $source::getRelease($packageInput, $definedRemoteSource, $entry);
            }
            catch(Exception $e)
            {
                $release_results = null;
                unset($e);
            }

            // If the specified version is a release, download the source code
            if($release_results !== null)
            {
                $results->setReleaseName($release_results->getReleaseName());
                $results->setReleaseDescription($release_results->getReleaseDescription());
                $results->setFiles(self::mergeFilesResults($release_results->getFiles(), ($results->getFiles())));

                if($release_results->getVersion() !== null)
                {
                    $results->setVersion($release_results->getVersion());
                }
            }

            try
            {
                $git_results = $source::getGitRepository($packageInput, $definedRemoteSource, $entry);
            }
            catch(Exception $e)
            {
                $git_results = null;
                unset($e);
            }

            if($git_results !== null)
            {
                if($results->getReleaseName() === null)
                {
                    $results->setReleaseName($git_results->getReleaseName());
                }
                elseif($git_results->getReleaseName() !== null)
                {
                    if(strlen($git_results->getReleaseDescription()) > strlen($results->getReleaseDescription()))
                    {
                        $results->setReleaseName($git_results->getReleaseName());
                    }
                }

                if($results->getReleaseDescription() === null)
                {
                    $results->setReleaseDescription($git_results->getReleaseDescription());
                }
                elseif($git_results->getReleaseDescription() !== null)
                {
                    if(strlen($git_results->getReleaseDescription()) > strlen($results->getReleaseDescription()))
                    {
                        $results->setReleaseDescription($git_results->getReleaseDescription());
                    }
                }

                if($results->getVersion() === null)
                {
                    $results->setVersion($git_results->getVersion());
                }
                elseif($git_results->getVersion() !== null)
                {
                    // Version compare
                    if(VersionComparator::compareVersion($git_results->getVersion(), $results->getVersion()) > 0)
                    {
                        $results->setVersion($git_results->getVersion());
                    }
                }

                $results->setFiles(self::mergeFilesResults($git_results->getFiles(), ($results->getFiles())));
            }

            try
            {
                $ncc_package_results = $source::getNccPackage($packageInput, $definedRemoteSource, $entry);
            }
            catch(Exception $e)
            {
                unset($e);
                $ncc_package_results = null;
            }

            if($ncc_package_results !== null)
            {
                if($results->getReleaseName() === null)
                {
                    $results->setReleaseName($ncc_package_results->getReleaseName());
                }
                elseif($ncc_package_results->getReleaseName() !== null)
                {
                    if(strlen($ncc_package_results->getReleaseName()) > strlen($results->getReleaseName()))
                    {
                        $results->setReleaseName($ncc_package_results->getReleaseName());
                    }
                }

                if($results->getReleaseDescription() === null)
                {
                    $results->setReleaseDescription($ncc_package_results->getReleaseDescription());
                }
                elseif($ncc_package_results->getReleaseDescription() !== null)
                {
                    if(strlen($ncc_package_results->getReleaseDescription()) > strlen($results->getReleaseDescription()))
                    {
                        $results->setReleaseDescription($ncc_package_results->getReleaseDescription());
                    }
                }

                if($results->getVersion() === null)
                {
                    $results->setVersion($ncc_package_results->getVersion());
                }
                elseif($ncc_package_results->getVersion() !== null)
                {
                    // Version compare
                    if(VersionComparator::compareVersion($ncc_package_results->getVersion(), $results->getVersion()) > 0)
                    {
                        $results->setVersion($ncc_package_results->getVersion());
                    }
                }

                $results->setFiles(self::mergeFilesResults($ncc_package_results->getFiles(), ($results->getFiles())));
            }

            return $results;
        }

        /**
         * Merges the given Files an object with another Files object
         *
         * @param Files $input
         * @param Files|null $selected
         * @return Files
         */
        private static function mergeFilesResults(RepositoryQueryResults\Files $input, ?RepositoryQueryResults\Files $selected=null): RepositoryQueryResults\Files
        {
            if($selected === null)
            {
                $selected = new RepositoryQueryResults\Files();
            }

            if($input->GitSshUrl !== null)
            {
                $selected->GitSshUrl = $input->GitSshUrl;
            }

            if($input->GitHttpUrl !== null)
            {
                $selected->GitHttpUrl = $input->GitHttpUrl;
            }

            if($input->SourceUrl !== null)
            {
                $selected->SourceUrl = $input->SourceUrl;
            }

            if($input->TarballUrl !== null)
            {
                $selected->TarballUrl = $input->TarballUrl;
            }

            if($input->ZipballUrl !== null)
            {
                $selected->ZipballUrl = $input->ZipballUrl;
            }

            if($input->PackageUrl !== null)
            {
                $selected->PackageUrl = $input->PackageUrl;
            }

            return $selected;
        }

        /**
         * Attempts to cast the correct type of the given value
         *
         * @param string $input
         * @return float|int|mixed|string
         */
        public static function stringTypeCast(string $input): mixed
        {
            if (is_numeric($input))
            {
                if (str_contains($input, '.'))
                {
                    return (float)$input;
                }

                if (ctype_digit($input))
                {
                    return (int)$input;
                }
            }
            elseif (in_array(strtolower($input), ['true', 'false']))
            {
                return filter_var($input, FILTER_VALIDATE_BOOLEAN);
            }

            return (string)$input;
        }

        /**
         * Finalizes the permissions
         *
         * @return void
         */
        public static function finalizePermissions(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                return;
            }

            Console::outVerbose('Finalizing permissions...');
            $filesystem = new Filesystem();

            try
            {
                if($filesystem->exists(PathFinder::getDataPath(Scopes::SYSTEM)))
                {
                    $filesystem->chmod(PathFinder::getDataPath(Scopes::SYSTEM), 0777, 0000, true);
                }
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to finalize permissions for %s: %s', PathFinder::getDataPath() . DIRECTORY_SEPARATOR . 'data', $e->getMessage()));
            }

            try
            {
                if($filesystem->exists(PathFinder::getCachePath(Scopes::SYSTEM)))
                {
                    $filesystem->chmod(PathFinder::getCachePath(Scopes::SYSTEM), 0777, 0000, true);
                }
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to finalize permissions for %s: %s', PathFinder::getDataPath() . DIRECTORY_SEPARATOR . 'data', $e->getMessage()));
            }

        }

        /**
         * Determines if the current process is running in TTY mode
         *
         * @return bool
         */
        public static function isTtyMode(): bool
        {
            if(!is_null(RuntimeCache::get('posix_isatty')))
            {
                return RuntimeCache::get('posix_isatty');
            }

            if(!function_exists('posix_isatty'))
            {
                return false;
            }

            RuntimeCache::set('posix_isatty', posix_isatty(STDOUT));
            return (bool)RuntimeCache::get('posix_isatty');
        }

        /**
         * Cleans an array by removing empty values
         *
         * @param array $input
         * @return array
         */
        public static function cleanArray(array $input): array
        {
            foreach ($input as $key => $value)
            {
                if (is_array($value) && ($input[$key] = self::cleanArray($value)) === [])
                {
                    unset($input[$key]);
                }
            }
            return $input;
        }

        /**
         * Scans the given directory for files and returns the found file with the given patterns
         *
         * @param string $path
         * @param array $include
         * @param array $exclude
         * @return array
         */
        public static function scanDirectory(string $path, array $include=[], array $exclude=[]): array
        {
            $directory_scanner = new DirectoryScanner();

            try
            {
                $directory_scanner->unsetFlag(FilesystemIterator::FOLLOW_SYMLINKS);
            }
            catch (\ncc\ThirdParty\theseer\DirectoryScanner\Exception $e)
            {
                throw new RuntimeException('Cannot scan directory, unable to remove the FOLLOW_SYMLINKS flag from the iterator: ' . $e->getMessage(), $e->getCode(), $e);
            }

            if(count($include) > 0)
            {
                $directory_scanner->setIncludes($include);
            }

            if(count($exclude) > 0)
            {
                $directory_scanner->setExcludes($exclude);
            }

            $results = [];
            foreach($directory_scanner($path) as $item)
            {
                // Ignore directories, they're not important.
                if(is_dir($item->getPathName()))
                {
                    continue;
                }

                $results[] = $item->getPathName();
                Console::outVerbose(sprintf('Selected file %s', $item->getPathName()));
            }

            return $results;
        }

        /**
         * Returns a snake case representation of the given input
         *
         * @param string $input
         * @return string
         */
        public static function toSnakeCase(string $input): string
        {
            $input = str_replace('.', '_', $input);
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/([a-z])([A-Z])/', '$1_$2', $input)), '_'));
        }
    }