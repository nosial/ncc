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
    use ncc\Abstracts\AuthenticationType;
    use ncc\Abstracts\DefinedRemoteSourceType;
    use ncc\Abstracts\HttpRequestType;
    use ncc\Abstracts\Runners;
    use ncc\Abstracts\Scopes;
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
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\ArchiveException;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\GitlabServiceException;
    use ncc\Exceptions\HttpException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\UnsupportedArchiveException;
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
    use ncc\Runtime;
    use ncc\ThirdParty\jelix\Version\Parser;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\ThirdParty\Symfony\Process\Process;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use Throwable;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Functions
    {
        public const FORCE_ARRAY = 0b0001;

        public const PRETTY = 0b0010;

        public const ESCAPE_UNICODE = 0b0100;

        /**
         * Calculates a byte-code representation of the input using CRC32
         *
         * @param string $input
         * @return string
         */
        public static function cbc(string $input): string
        {
            $cache = RuntimeCache::get("cbc_$input");
            if($cache !== null)
                return $cache;

            return RuntimeCache::set("cbc_$input", hash('crc32', $input, true));
        }

        /**
         * Returns the specified of a value of an array using plaintext, if none is found it will
         * attempt to use the cbc method to find the selected input, if all fails then null will be returned.
         *
         * @param array $data
         * @param string $select
         * @return mixed|null
         * @noinspection PhpMissingReturnTypeInspection
         */
        public static function array_bc(array $data, string $select)
        {
            if(isset($data[$select]))
                return $data[$select];

            if(isset($data[self::cbc($select)]))
                return $data[self::cbc($select)];

            return null;
        }

        /**
         * Loads a json file
         *
         * @param string $path
         * @param int $flags
         * @return mixed
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @noinspection PhpMissingReturnTypeInspection
         */
        public static function loadJsonFile(string $path, int $flags=0)
        {
            if(!file_exists($path))
            {
                throw new FileNotFoundException($path);
            }

            return self::loadJson(IO::fread($path), $flags);
        }

        /**
         * Parses a json string
         *
         * @param string $json
         * @param int $flags
         * @return mixed
         * @throws MalformedJsonException
         * @noinspection PhpMissingReturnTypeInspection
         */
        public static function loadJson(string $json, int $flags=0)
        {
            $forceArray = (bool) ($flags & self::FORCE_ARRAY);
            $json_decoded = json_decode($json, $forceArray, 512, JSON_BIGINT_AS_STRING);

            if($json_decoded == null && json_last_error() !== JSON_ERROR_NONE)
            {
                throw new MalformedJsonException(json_last_error_msg() . ' (' . json_last_error() . ')');
            }

            return $json_decoded;
        }

        /**
         * Returns the JSON representation of a value. Accepts flag Json::PRETTY.
         *
         * @param mixed $value
         * @param int $flags
         * @return string
         * @throws MalformedJsonException
         * @noinspection PhpMissingParamTypeInspection
         * @noinspection PhpUnusedLocalVariableInspection
         */
        public static function encodeJson($value, int $flags=0): string
        {
            $flags = ($flags & self::ESCAPE_UNICODE ? 0 : JSON_UNESCAPED_UNICODE)
                | JSON_UNESCAPED_SLASHES
                | ($flags & self::PRETTY ? JSON_PRETTY_PRINT : 0)
                | (defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0); // since PHP 5.6.6 & PECL JSON-C 1.3.7

            $json = json_encode($value, $flags);
            if ($error = json_last_error())
            {
                throw new MalformedJsonException(json_last_error_msg() . ' (' . json_last_error() . ')');
            }
            return $json;
        }

        /**
         * Writes a json file to disk
         *
         * @param $value
         * @param string $path
         * @param int $flags
         * @return void
         * @throws MalformedJsonException
         */
        public static function encodeJsonFile($value, string $path, int $flags=0): void
        {
            file_put_contents($path, self::encodeJson($value, $flags));
        }

        /**
         * @param CliHelpSection[] $input
         * @return int
         */
        public static function detectParametersPadding(array $input): int
        {
            $current_count = 0;

            foreach($input as $optionsSection)
            {
                if(count($optionsSection->Parameters) > 0)
                {
                    foreach($optionsSection->Parameters as $parameter)
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
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

            $banner = str_ireplace('%A', $banner_version, $banner);
            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $banner = str_ireplace('%B', $banner_copyright, $banner);

            return $banner;
        }

        /**
         * Removes the basename from a path, eg; extracts "foo/bar.txt" from "/root/foo/bar.txt" if "/root" is the
         * current working directory, optionally accepts a different basename using the $basename parameter.
         *
         * @param string $path
         * @param string|null $basename
         * @return string
         */
        public static function removeBasename(string $path, ?string $basename=null): string
        {
            if($basename == null)
                $basename = getcwd();

            // Append the trailing slash if it's not already there
            // "/etc/foo" becomes "/etc/foo/"
            if(substr($basename, -1) !== DIRECTORY_SEPARATOR)
            {
                $basename .= DIRECTORY_SEPARATOR;
            }

            // If the path is "/etc/foo/text.txt" and the basename is "/etc" then the returned path will be "foo/test.txt"
            return str_replace($basename, (string)null, $path);
        }

        /**
         * Corrects the directory separator for the given path
         *
         * @param $path
         * @return string
         */
        public static function correctDirectorySeparator($path): string
        {
            $path = str_ireplace('/', DIRECTORY_SEPARATOR, $path);
            return str_ireplace('\\', DIRECTORY_SEPARATOR, $path);
        }

        /**
         * @param string $path
         * @param ExecutionPolicy $policy
         * @return ExecutionUnit
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws RunnerExecutionException
         */
        public static function compileRunner(string $path, ExecutionPolicy $policy): ExecutionUnit
        {
            return match (strtolower($policy->Runner)) {
                Runners::bash => BashRunner::processUnit($path, $policy),
                Runners::php => PhpRunner::processUnit($path, $policy),
                Runners::perl => PerlRunner::processUnit($path, $policy),
                Runners::python => PythonRunner::processUnit($path, $policy),
                Runners::python2 => Python2Runner::processUnit($path, $policy),
                Runners::python3 => Python3Runner::processUnit($path, $policy),
                Runners::lua => LuaRunner::processUnit($path, $policy),
                default => throw new RunnerExecutionException('The runner \'' . $policy->Runner . '\' is not supported'),
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
            return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
        }

        /**
         * Initializes NCC files
         *
         * @return void
         * @throws AccessDeniedException
         * @throws InvalidScopeException
         */
        public static function initializeFiles(): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot initialize NCC files, insufficient permissions');

            Console::outVerbose('Initializing NCC files');

            $filesystem = new Filesystem();
            if(!$filesystem->exists(PathFinder::getDataPath(Scopes::System)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getDataPath(Scopes::System)));
                $filesystem->mkdir(PathFinder::getDataPath(Scopes::System), 0755);
            }

            if(!$filesystem->exists(PathFinder::getCachePath(Scopes::System)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getCachePath(Scopes::System)));
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $filesystem->mkdir(PathFinder::getCachePath(Scopes::System), 0777);
            }

            if(!$filesystem->exists(PathFinder::getRunnerPath(Scopes::System)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getRunnerPath(Scopes::System)));
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $filesystem->mkdir(PathFinder::getRunnerPath(Scopes::System), 0755);
            }

            if(!$filesystem->exists(PathFinder::getPackagesPath(Scopes::System)))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getPackagesPath(Scopes::System)));
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $filesystem->mkdir(PathFinder::getPackagesPath(Scopes::System), 0755);
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @noinspection PhpUnused
         */
        public static function loadComposerJson(string $path): ComposerJson
        {
            $json_contents = IO::fread($path);
            return ComposerJson::fromArray(json_decode($json_contents, true));
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
                return false;
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

            if(is_int($value))
            {
                if ($value == 0)
                    return false;
                if ($value == 1)
                    return true;
                return false;
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
            $config_manager = new ConfigurationManager();
            return $config_manager->getProperty($property);
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
            /** @noinspection PhpStrFunctionsInspection */
            if(substr($version, 0, 1) === 'v')
                $version = substr($version, 1);

            return Parser::parse($version)->toString();
        }

        /**
         * Returns a random string
         *
         * @param int $length
         * @return string
         */
        public static function randomString(int $length = 32): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++)
            {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

        /**
         * Returns a path to a temporary directory
         *
         * @param bool $create
         * @param bool $set_as_tmp
         * @return string
         * @throws InvalidScopeException
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
                RuntimeCache::setFileAsTemporary($path);
            return $path;
        }

        /**
         * Applies the authentication to the given HTTP request.
         *
         * @param HttpRequest $httpRequest
         * @param Entry|null $entry
         * @param bool $expect_json
         * @return HttpRequest
         * @throws AuthenticationException
         * @throws GitlabServiceException
         */
        public static function prepareGitServiceRequest(HttpRequest $httpRequest, ?Entry $entry=null, bool $expect_json=true): HttpRequest
        {
            if($entry !== null)
            {
                if (!$entry->isCurrentlyDecrypted())
                    throw new GitlabServiceException('The given Vault entry is not decrypted.');

                switch ($entry->getPassword()->getAuthenticationType()) {
                    case AuthenticationType::AccessToken:
                        $httpRequest->Headers[] = "Authorization: Bearer " . $entry->getPassword();
                        break;

                    case AuthenticationType::UsernamePassword:
                        throw new AuthenticationException('Username/Password authentication is not supported');
                }
            }

            if($expect_json)
            {
                $httpRequest->Headers[] = "Accept: application/json";
                $httpRequest->Headers[] = "Content-Type: application/json";
            }

            return $httpRequest;
        }

        /**
         * Downloads a file from the given URL and saves it to the given path
         *
         * @param string $url
         * @param Entry|null $entry
         * @return string
         * @throws AuthenticationException
         * @throws GitlabServiceException
         * @throws InvalidScopeException
         * @throws HttpException
         */
        public static function downloadGitServiceFile(string $url, ?Entry $entry=null): string
        {
            if(RuntimeCache::get('download_cache.' . $url) !== null)
                return RuntimeCache::get('download_cache.' . $url);

            $out_path = Functions::getTmpDir() . "/" . basename($url);

            $httpRequest = new HttpRequest();
            $httpRequest->Url = $url;
            $httpRequest->Type = HttpRequestType::GET;
            $httpRequest = Functions::prepareGitServiceRequest($httpRequest, $entry, false);

            Console::out('Downloading file ' . $url);
            HttpClient::download($httpRequest, $out_path);
            RuntimeCache::set('download_cache.' . $url, $out_path);

            return $out_path;
        }

        /**
         * @param string $path
         * @return string|null
         * @throws ArchiveException
         * @throws UnsupportedArchiveException
         */
        public static function extractArchive(string $path): ?string
        {
            $executable_finder = new ExecutableFinder();
            $unzip_executable = $executable_finder->find('unzip');
            $tar_executable = $executable_finder->find('tar');
            $out_path = dirname($path);
            $filesystem = new Filesystem();

            if(!$filesystem->exists($out_path))
                $filesystem->mkdir($out_path);

            RuntimeCache::setFileAsTemporary($out_path);

            $mimeType = mime_content_type($path);
            $supportedTypes = [];

            if($unzip_executable !== null)
            {
                $supportedTypes = array_merge($supportedTypes, [
                    'application/zip',
                    'application/x-zip',
                    'application/x-zip-compressed',
                    'application/octet-stream',
                    'application/x-compress',
                    'application/x-compressed',
                    'multipart/x-zip'
                ]);
            }
            else
            {
                if(RuntimeCache::get('warning_zip_shown') !== true)
                {
                    Console::out('unzip executable not found. ZIP archives will not be supported.');
                    RuntimeCache::set('warning_zip_shown', true);
                }
            }

            if($tar_executable !== null)
            {
                $supportedTypes = array_merge($supportedTypes, [
                    'application/x-tar',
                    'application/x-gzip',
                    'application/x-bzip2',
                    'application/x-xz'
                ]);
            }
            else
            {
                if(RuntimeCache::get('warning_tar_shown') !== true)
                {
                    Console::outWarning('tar executable not found. TAR archives will not be supported.');
                    RuntimeCache::set('warning_tar_shown', true);
                }
            }

            if (!in_array($mimeType, $supportedTypes))
                throw new UnsupportedArchiveException("Unsupported archive type: $mimeType");

            $command = match ($mimeType) {
                'application/zip' => [$unzip_executable, $path, '-d', $out_path],
                'application/x-tar' => [$tar_executable, '--verbose', '-xf', $path, '-C', $out_path],
                'application/x-gzip' => [$tar_executable, '--verbose', '-xzf', $path, '-C', $out_path],
                'application/x-bzip2' => [$tar_executable, '--verbose', '-xjf', $path, '-C', $out_path],
                default => throw new UnsupportedArchiveException("Unsupported archive type: $mimeType"),
            };

            Console::out("Extracting archive $path");
            $process = new Process($command);

            // display the output of the command
            $process->run(function ($type, $buffer) {
                Console::outVerbose($buffer);
            });

            if (!$process->isSuccessful())
                throw new ArchiveException($process->getErrorOutput());

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
            if (!is_dir($path))
                return null;

            // Search files in the given directory recursively
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file)
            {
                if (in_array($file->getFilename(), $files))
                    return $file->getPathname();
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
            if(stripos($version, 'v') === 0)
                $version = substr($version, 1);
            if(!Validate::version($version))
            {
                $parts = explode('.', $version);
                $major = (string)null;
                $minor = (string)null;
                $patch = (string)null;

                if(count($parts) >= 1)
                    $major = $parts[0];
                if(count($parts) >= 2)
                    $minor = $parts[1];
                if(count($parts) >= 3)
                    $patch = $parts[2];

                // Assemble the SemVer compatible string
                $version = "$major.$minor.$patch";
            }
            if(!Validate::version($version))
                return '1.0.0';

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

            switch($definedRemoteSource->Type)
            {
                case DefinedRemoteSourceType::Github:
                    $source = GithubService::class;
                    break;

                case DefinedRemoteSourceType::Gitlab:
                    $source = GitlabService::class;
                    break;

                default:
                    return $results;
            }

            // Check if the specified version is a release
            try
            {
                Console::outVerbose(sprintf('Attempting to fetch source code from %s', $definedRemoteSource->Host));
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
                $results->ReleaseName = ($release_results->ReleaseName ?? null);
                $results->ReleaseDescription = ($release_results->ReleaseDescription ?? null);
                $results->Files = self::mergeFilesResults($release_results->Files, ($results->Files ?? null));
                if($release_results->Version !== null)
                    $results->Version = $release_results->Version;
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
                if($results->ReleaseName == null)
                {
                    $results->ReleaseName = ($git_results->ReleaseName ?? null);
                }
                elseif($git_results->ReleaseName !== null)
                {
                    if(strlen($git_results->ReleaseName) > strlen($results->ReleaseName))
                        $results->ReleaseName = $git_results->ReleaseName;
                }

                if($results->ReleaseDescription == null)
                {
                    $results->ReleaseDescription = ($git_results->ReleaseDescription ?? null);
                }
                elseif($git_results->ReleaseDescription !== null)
                {
                    if(strlen($git_results->ReleaseDescription) > strlen($results->ReleaseDescription))
                        $results->ReleaseDescription = $git_results->ReleaseDescription;
                }

                if($results->Version == null)
                {
                    $results->Version = ($git_results->Version ?? null);
                }
                elseif($git_results->Version !== null)
                {
                    // Version compare
                    if(VersionComparator::compareVersion($git_results->Version, $results->Version) > 0)
                        $results->Version = $git_results->Version;
                }

                $results->Files = self::mergeFilesResults($git_results->Files, ($results->Files ?? null));
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
                if($results->ReleaseName == null)
                {
                    $results->ReleaseName = ($ncc_package_results->ReleaseName ?? null);
                }
                elseif($ncc_package_results->ReleaseName !== null)
                {
                    if(strlen($ncc_package_results->ReleaseName) > strlen($results->ReleaseName))
                        $results->ReleaseName = $ncc_package_results->ReleaseName;
                }

                if($results->ReleaseDescription == null)
                {
                    $results->ReleaseDescription = ($ncc_package_results->ReleaseDescription ?? null);
                }
                elseif($ncc_package_results->ReleaseDescription !== null)
                {
                    if(strlen($ncc_package_results->ReleaseDescription) > strlen($results->ReleaseDescription))
                        $results->ReleaseDescription = $ncc_package_results->ReleaseDescription;
                }

                if($results->Version == null)
                {
                    $results->Version = ($ncc_package_results->Version ?? null);
                }
                elseif($ncc_package_results->Version !== null)
                {
                    // Version compare
                    if(VersionComparator::compareVersion($ncc_package_results->Version, $results->Version) > 0)
                        $results->Version = $ncc_package_results->Version;
                }

                $results->Files = self::mergeFilesResults($ncc_package_results->Files, ($results->Files ?? null));
            }

            return $results;
        }

        /**
         * Merges the given Files object with another Files object
         *
         * @param Files $input
         * @param Files|null $selected
         * @return Files
         */
        private static function mergeFilesResults(RepositoryQueryResults\Files $input, ?RepositoryQueryResults\Files $selected=null): RepositoryQueryResults\Files
        {
            if($selected == null)
                $selected = new RepositoryQueryResults\Files();

            if($input->GitSshUrl !== null)
                $selected->GitSshUrl = $input->GitSshUrl;

            if($input->GitHttpUrl !== null)
                $selected->GitHttpUrl = $input->GitHttpUrl;

            if($input->SourceUrl !== null)
                $selected->SourceUrl = $input->SourceUrl;

            if($input->TarballUrl !== null)
                $selected->TarballUrl = $input->TarballUrl;

            if($input->ZipballUrl !== null)
                $selected->ZipballUrl = $input->ZipballUrl;

            if($input->PackageUrl !== null)
                $selected->PackageUrl = $input->PackageUrl;

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
                    return (float)$input;

                if (ctype_digit($input))
                    return (int)$input;
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
         * @throws InvalidScopeException
         */
        public static function finalizePermissions(): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                return;

            Console::outVerbose('Finalizing permissions...');
            $filesystem = new Filesystem();

            try
            {
                if($filesystem->exists(PathFinder::getDataPath(Scopes::System)))
                    $filesystem->chmod(PathFinder::getDataPath(Scopes::System), 0777, 0000, true);
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to finalize permissions for %s: %s', PathFinder::getDataPath() . DIRECTORY_SEPARATOR . 'data', $e->getMessage()));
            }

            try
            {
                if($filesystem->exists(PathFinder::getCachePath(Scopes::System)))
                    $filesystem->chmod(PathFinder::getCachePath(Scopes::System), 0777, 0000, true);
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
                return RuntimeCache::get('posix_isatty');

            if(function_exists('posix_isatty') === false)
                return false;

            RuntimeCache::set('posix_isatty', posix_isatty(STDOUT));
            return (bool)RuntimeCache::get('posix_isatty');
        }
    }