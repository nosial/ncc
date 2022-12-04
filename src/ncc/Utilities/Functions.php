<?php

    namespace ncc\Utilities;

    use Exception;
    use ncc\Abstracts\Runners;
    use ncc\Abstracts\Scopes;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Managers\ConfigurationManager;
    use ncc\Managers\CredentialManager;
    use ncc\Managers\PackageLockManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\ComposerJson;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\ThirdParty\jelix\Version\Parser;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;

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
         * @throws UnsupportedRunnerException
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public static function compileRunner(string $path, ExecutionPolicy $policy): ExecutionUnit
        {
            return match (strtolower($policy->Runner)) {
                Runners::php => PhpRunner::processUnit($path, $policy),
                default => throw new UnsupportedRunnerException('The runner \'' . $policy->Runner . '\' is not supported'),
            };
        }

        /**
         * Returns an array representation of the exception
         *
         * @param Exception $e
         * @return array
         */
        public static function exceptionToArray(Exception $e): array
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
                $exception['trace'] = self::exceptionToArray($e);
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
    }