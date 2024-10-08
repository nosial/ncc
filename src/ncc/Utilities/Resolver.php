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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Utilities;

    use InvalidArgumentException;
    use ncc\Enums\LogLevel;
    use ncc\Enums\PackageDirectory;
    use ncc\Enums\Scopes;
    use ncc\Enums\Types\ProjectType;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Objects\ProjectDetectionResults;

    class Resolver
    {
        /**
         * The cache value of the User ID
         *
         * @var string|null
         */
        private static $user_id_cache;

        /**
         * @var array
         */
        private static $resolved_hosts = [];

        /**
         * Returns the current scope of the application
         *
         * @return string Scopes::SYSTEM if the user is root, Scopes::USER otherwise
         * @see Scopes
         */
        public static function resolveScope(): string
        {
            if(self::$user_id_cache === null)
            {
                self::$user_id_cache = posix_getuid();
            }

            if(self::$user_id_cache === 0)
            {
                return Scopes::SYSTEM->value;
            }

            return Scopes::USER->value;
        }

        /**
         * Parse arguments
         *
         * @param array|string $message [$message] input arguments
         * @return array Configs Key/Value
         * @noinspection RegExpRedundantEscape
         * @noinspection RegExpSimplifiable
         * @noinspection PhpMissingParamTypeInspection
         */
        public static function parseArguments($message=null): array
        {
            if (is_string($message))
            {
                $flags = $message;
            }
            elseif(is_array($message))
            {
                $flags = implode(' ', $message);
            }
            else
            {
                global $argv;
                if(isset($argv) && count($argv) > 1)
                {
                    array_shift($argv);
                }
                $flags = implode(' ',  $argv);
            }

            $configs = array();
            $regex = "/(?(?=-)-(?(?=-)-(?'big'[^\\s=]+)|(?'small'\\S))(?:\\s*=\\s*|\\s+)(?(?!-)(?(?=[\\\"\\'])((?<![\\\\])['\"])(?'string'(?:.(?!(?<![\\\\])\\3))*.?)\\3|(?'value'\\S+)))(?:\\s+)?|(?'unmatched'\\S+))/";
            preg_match_all($regex, $flags, $matches, PREG_SET_ORDER);

            foreach ($matches as $index => $match)
            {
                if (isset($match['value']) && $match['value'] !== '')
                {
                    $value = $match['value'];
                }
                else if (isset($match['string']) && $match['string'] !== '')
                {
                    // fix escaped quotes
                    $value = str_replace(["\\\"", "\\'"], ["\"", "'"], $match['string']);
                }
                else
                {
                    $value = true;
                }

                if(isset($match['big']) && $match['big'] !== '')
                {
                    $configs[$match['big']] = $value;
                }

                if(isset($match['small']) && $match['small'] !== '')
                {
                    $configs[$match['small']] = $value;
                }

                if(isset($match['unmatched']) && $match['unmatched'] !== '')
                {
                    $configs[$match['unmatched']] = true;
                }
            }

            return $configs;
        }

        /**
         * Split arguments
         *
         * @param array $args List of arguments
         * @param string $arg The argument to split from
         * @return array Split arguments starting from specified argument
         */
        public static function splitArguments(array $args, string $arg): array
        {
            $index = array_search($arg, $args);

            if($index === false)
            {
                return [];
            }

            return array_slice($args, $index + 1);
        }

        /**
         * Resolves the constant's full name
         *
         * @param string $scope
         * @param string $name
         * @return string
         */
        public static function resolveFullConstantName(string $scope, string $name): string
        {
            return $scope . '.(' . $name . ')';
        }

        /**
         * Resolves the constant's unique hash
         *
         * @param string $scope
         * @param string $name
         * @return string
         */
        public static function resolveConstantHash(string $scope, string $name): string
        {
            return hash('haval128,3', self::resolveFullConstantName($scope, $name));
        }

        /**
         * Returns the ProjectDetectionResults of the project in the specified directory
         *
         * @param string $directory
         * @return ProjectDetectionResults
         * @throws NotSupportedException
         */
        public static function detectProject(string $directory): ProjectDetectionResults
        {
            foreach(Functions::scanDirectory($directory, ['*project.json', '*composer.json']) as $file)
            {
                if(str_ends_with($file, 'project.json'))
                {
                    return new ProjectDetectionResults($file, ProjectType::NCC);
                }

                if(str_ends_with($file, 'composer.json'))
                {
                    return new ProjectDetectionResults($file, ProjectType::COMPOSER);
                }
            }

            throw new NotSupportedException(sprintf('Unable to detect project type in directory "%s"', $directory));
        }

        /**
         * Converts a composer package name to a java standard package name, returns false if the input is invalid
         *
         * @param string $name
         * @return string|false
         */
        public static function composerNameToPackage(string $name): string|false
        {
            $parts = explode("/", $name, 2);

            if (count($parts) === 2)
            {
                return "com." . str_replace('-', '_', str_replace("/", ".", $name));
            }

            return false;
        }

        /**
         * Returns the name of a composer package name, returns false if the input is invalid
         *
         * @param string $name
         * @return string|false
         */
        public static function composerName(string $name): string|false
        {
            $parts = explode("/", $name, 2);

            if (count($parts) === 2)
            {
                return $parts[1];
            }

            return false;
        }

        /**
         * Get the component type based on the file name
         *
         * @param string $component_path Component name
         * @return int Component type
         * @throws InvalidArgumentException If the component name is invalid
         * @see PackageDirectory
         */
        public static function componentType(string $component_path): int
        {
            // Check for empty string and presence of ":"
            if (empty($component_path) || !str_contains($component_path, ':'))
            {
                throw new InvalidArgumentException(sprintf('Invalid component format "%s"', $component_path));
            }

            // Get the prefix before ":" and remove "@" character
            $file_stub_code = str_ireplace('@', '', explode(':', $component_path, 2)[0]);

            // Check if the prefix is numeric
            if (!is_numeric($file_stub_code))
            {
                throw new InvalidArgumentException(sprintf('Invalid component prefix "%s"', $file_stub_code));
            }

            // TODO: What the hell is this?
            return match ((int)$file_stub_code)
            {
                PackageDirectory::METADATA->value => PackageDirectory::METADATA->value,
                PackageDirectory::ASSEMBLY->value => PackageDirectory::ASSEMBLY->value,
                PackageDirectory::EXECUTION_UNITS->value => PackageDirectory::EXECUTION_UNITS->value,
                PackageDirectory::INSTALLER->value => PackageDirectory::INSTALLER->value,
                PackageDirectory::DEPENDENCIES->value => PackageDirectory::DEPENDENCIES->value,
                PackageDirectory::CLASS_POINTER->value => PackageDirectory::CLASS_POINTER->value,
                PackageDirectory::RESOURCES->value => PackageDirectory::RESOURCES->value,
                PackageDirectory::COMPONENTS->value => PackageDirectory::COMPONENTS->value,
                default => throw new InvalidArgumentException(sprintf('Invalid component type "%s"', $component_path)),
            };
        }

        /**
         * Returns the component name based on the file name
         *
         * @param string $component_path
         * @return string
         */
        public static function componentName(string $component_path): string
        {
            // Check for empty string and presence of ":"
            if (empty($component_path) || !str_contains($component_path, ':'))
            {
                throw new InvalidArgumentException(sprintf('Invalid component format "%s"', $component_path));
            }

            // Get the prefix before ":" and remove "@" character
            $file_stub_code = str_ireplace('@', '', explode(':', $component_path, 2)[0]);

            // Check if the prefix is numeric
            if (!is_numeric($file_stub_code))
            {
                throw new InvalidArgumentException(sprintf('Invalid component prefix "%s"', $file_stub_code));
            }

            return explode(':', $component_path, 2)[1];
        }

        /**
         * Resolve host and cache the result
         *
         * @param string $host The host to resolve
         * @return string The resolved host IP address
         */
        public static function resolveHost(string $host): string
        {
            $resolved_host = self::$resolved_hosts[$host] ?? null;

            if($resolved_host !== null)
            {
                Console::outDebug(sprintf('Resolved host "%s" to "%s" from cache', $host, $resolved_host));
                return $resolved_host;
            }

            $resolved_host = gethostbyname($host);

            if($resolved_host === $host)
            {
                Console::outDebug(sprintf('Unable to resolve host "%s"', $host));
                return $host;
            }

            Console::outDebug(sprintf('Resolved host "%s" to "%s"', $host, $resolved_host));
            self::$resolved_hosts[$host] = $resolved_host;
            return $resolved_host;
        }

        /**
         * Get resolved option for the given URL
         *
         * @param string $url The input URL
         * @return string|null Resolved option in the format "{host}:{port}:{ip_address}", or null if $ip_address is same as $host
         */
        public static function getResolveOption(string $url): ?string
        {
            $parsed_url = parse_url($url);
            $host = $parsed_url['host'];
            $port = str_starts_with($url, 'https') ? 443 : 80;
            $ip_address = self::resolveHost($host);

            if($ip_address === $host)
            {
                return null;
            }

            return "{$host}:{$port}:{$ip_address}";
        }
    }