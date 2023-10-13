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
    use ncc\Enums\Scopes;
    use ncc\Enums\Versions;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Managers\ConfigurationManager;
    use ncc\Managers\CredentialManager;
    use ncc\Managers\PackageLockManager;
    use ncc\Managers\RepositoryManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\ComposerJson;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
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
         * Removes a specified base directory from a given path.
         * If the $baseName parameter is not provided, the current working directory will be used as the base directory.
         *
         * @param string $path The path from which to remove the base directory.
         * @param string|null $base_name The base directory to remove from the path. If not provided, the current working directory is used.
         * @return string The modified path with the base directory removed.
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
         * Initializes ncc system files
         *
         * @param array $default_repositories
         * @return void
         * @throws OperationException
         */
        public static function initializeFiles(array $default_repositories=[]): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to initialize ncc files');
            }

            Console::outVerbose('Initializing ncc files');
            $filesystem = new Filesystem();

            if(!$filesystem->exists(PathFinder::getCachePath()))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getCachePath()));
                $filesystem->mkdir(PathFinder::getCachePath(), 0777);
            }

            if(!$filesystem->exists(PathFinder::getPackagesPath()))
            {
                Console::outDebug(sprintf('Initializing %s', PathFinder::getPackagesPath()));
                $filesystem->mkdir(PathFinder::getPackagesPath(), 0655);
            }

            try
            {
                CredentialManager::initializeCredentialStorage();
            }
            catch(Exception $e)
            {
                throw new OperationException('Failed to initialize credential storage, ' . $e->getMessage(), $e);
            }

            try
            {
                PackageLockManager::initializePackageLock();
            }
            catch(Exception $e)
            {
                throw new OperationException('Failed to initialize package lock, ' . $e->getMessage(), $e);
            }

            try
            {
                RepositoryManager::initializeDatabase($default_repositories);
            }
            catch(Exception $e)
            {
                throw new OperationException('Failed to initialize repository database, ' . $e->getMessage(), $e);
            }

            try
            {
                self::registerExtension($filesystem);
            }
            catch(Exception $e)
            {
                throw new OperationException('Failed to register ncc extension, ' . $e->getMessage(), $e);
            }
        }

        /**
         * Register the ncc extension with the given filesystem.
         *
         * @param Filesystem $filesystem The filesystem object used for file operations.
         * @throws IOException If the extension cannot be registered.
         * @throws NotSupportedException If `get_include_path()` function is not available.
         * @throws PathNotFoundException If the default include path is not available.
         */
        private static function registerExtension(Filesystem $filesystem): void
        {
            if(!function_exists('get_include_path'))
            {
                throw new NotSupportedException('Cannot register ncc extension, get_include_path() is not available');
            }

            $default_share = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'php';
            $include_paths = explode(':', get_include_path());
            $extension = str_ireplace('%ncc_install', NCC_EXEC_LOCATION, IO::fread(__DIR__ . DIRECTORY_SEPARATOR . 'extension'));

            if(in_array($default_share, $include_paths))
            {
                if($filesystem->exists($default_share . DIRECTORY_SEPARATOR . 'ncc'))
                {
                    $filesystem->remove($default_share . DIRECTORY_SEPARATOR . 'ncc');
                }

                IO::fwrite($default_share . DIRECTORY_SEPARATOR . 'ncc', $extension);
                return;
            }

            foreach($include_paths as $include_path)
            {
                if($include_path === '.')
                {
                    continue;
                }

                try
                {
                    if($filesystem->exists($include_path . DIRECTORY_SEPARATOR . 'ncc'))
                    {
                        $filesystem->remove($include_path . DIRECTORY_SEPARATOR . 'ncc');
                    }

                    IO::fwrite($include_path . DIRECTORY_SEPARATOR . 'ncc', $extension);
                    return;
                }
                catch(IOException $e)
                {
                    Console::outWarning(sprintf('Failed to register ncc extension in %s: %s', $include_path, $e->getMessage()));
                }
            }

            throw new IOException('Cannot register ncc extension, no include path is available');
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
            if(is_bool($value))
            {
                return $value;
            }

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
                if($filesystem->exists(PathFinder::getDataPath()))
                {
                    $filesystem->chmod(PathFinder::getDataPath(), 0777, 0000, true);
                }
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to finalize permissions for %s: %s', PathFinder::getDataPath() . DIRECTORY_SEPARATOR . 'data', $e->getMessage()));
            }

            try
            {
                if($filesystem->exists(PathFinder::getCachePath()))
                {
                    $filesystem->chmod(PathFinder::getCachePath(), 0777, 0000, true);
                }
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to finalize permissions for %s: %s', PathFinder::getDataPath() . DIRECTORY_SEPARATOR . 'data', $e->getMessage()));
            }

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

        /**
         * Returns a shell script that can be used to execute the given package
         *
         * @param string $package_name
         * @param string $version
         * @return string
         */
        public static function createExecutionPointer(string $package_name, string $version=Versions::LATEST): string
        {
            $content = "#!/bin/sh\n";
            $content .= sprintf('exec ncc exec --package "%s" --exec-version "%s" --exec-args "$@"', $package_name, $version);

            return $content;
        }
    }