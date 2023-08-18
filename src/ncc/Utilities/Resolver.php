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

    use ncc\Enums\BuiltinRemoteSourceType;
    use ncc\Enums\LogLevel;
    use ncc\Enums\ProjectType;
    use ncc\Enums\RemoteSourceType;
    use ncc\Enums\Scopes;
    use ncc\Managers\RemoteSourcesManager;
    use ncc\Objects\ProjectDetectionResults;

    class Resolver
    {
        /**
         * The cache value of the User ID
         *
         * @var string|null
         */
        private static $UserIdCache;

        /**
         * @param string|null $input
         * @return string
         */
        public static function resolveScope(?string $input=null): string
        {
            // Set the scope to automatic if it's null
            if($input == null)
            {
                $input = Scopes::AUTO;
            }

            $input = strtoupper($input);

            if(self::$UserIdCache == null)
                self::$UserIdCache = posix_getuid();

            // Resolve the scope if it's set to automatic
            if($input == Scopes::AUTO)
            {
                if(self::$UserIdCache == 0)
                {
                    $input = Scopes::SYSTEM;
                }
                else
                {
                    $input = Scopes::USER;
                }
            }

            // Auto-Correct the scope if the current user ID is 0
            if($input == Scopes::USER && self::$UserIdCache == 0)
            {
                $input = Scopes::SYSTEM;
            }

            return $input;
        }

        /**
         * Parse arguments
         *
         * @param array|string $message [$message] input arguments
         * @param int $max_arguments
         * @return array Configs Key/Value
         * @noinspection RegExpRedundantEscape
         * @noinspection RegExpSimplifiable
         * @noinspection PhpMissingParamTypeInspection
         */
        public static function parseArguments($message=null, int $max_arguments=1000): array
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
                $regex = "/(?(?=-)-(?(?=-)-(?'bigflag'[^\\s=]+)|(?'smallflag'\\S))(?:\\s*=\\s*|\\s+)(?(?!-)(?(?=[\\\"\\'])((?<![\\\\])['\"])(?'string'(?:.(?!(?<![\\\\])\\3))*.?)\\3|(?'value'\\S+)))(?:\\s+)?|(?'unmatched'\\S+))/";
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
                    $value = str_replace("\\\"", "\"", $match['string']);
                    $value = str_replace("\\'", "'", $value);
                }
                else
                {
                    $value = true;
                }

                if (isset($match['bigflag']) && $match['bigflag'] !== '')
                {
                    $configs[$match['bigflag']] = $value;
                }

                if (isset($match['smallflag']) && $match['smallflag'] !== '')
                {
                    $configs[$match['smallflag']] = $value;
                }

                if (isset($match['unmatched']) && $match['unmatched'] !== '')
                {
                    $configs[$match['unmatched']] = true;
                }

                if ($index >= $max_arguments)
                    break;
            }

            return $configs;
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
         * Checks if the input level matches the current level
         *
         * @param string|null $input
         * @param string|null $current_level
         * @return bool
         */
        public static function checkLogLevel(?string $input, ?string $current_level): bool
        {
            if($input == null)
                return false;
            if($current_level == null)
                return false;

            $input = strtolower($input);
            if(!Validate::checkLogLevel($input))
                return false;

            $current_level = strtolower($current_level);
            if(!Validate::checkLogLevel($current_level))
                return false;

            switch($current_level)
            {
                case LogLevel::DEBUG:
                    $levels = [
                        LogLevel::DEBUG,
                        LogLevel::VERBOSE,
                        LogLevel::INFO,
                        LogLevel::WARNING,
                        LogLevel::FATAL,
                        LogLevel::ERROR
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::VERBOSE:
                    $levels = [
                        LogLevel::VERBOSE,
                        LogLevel::INFO,
                        LogLevel::WARNING,
                        LogLevel::FATAL,
                        LogLevel::ERROR
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::INFO:
                    $levels = [
                        LogLevel::INFO,
                        LogLevel::WARNING,
                        LogLevel::FATAL,
                        LogLevel::ERROR
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::WARNING:
                    $levels = [
                        LogLevel::WARNING,
                        LogLevel::FATAL,
                        LogLevel::ERROR
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::ERROR:
                    $levels = [
                        LogLevel::FATAL,
                        LogLevel::ERROR
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::FATAL:
                    if($input == LogLevel::FATAL)
                        return true;
                    return false;

                default:
                case LogLevel::SILENT:
                    return false;
            }
        }

        /**
         * Detects the remote source type, can also accept defined remote
         * sources as the input, the function will look for the source
         * type and return it
         *
         * @param string $input
         * @return string
         */
        public static function detectRemoteSourceType(string $input): string
        {
            if(in_array($input, BuiltinRemoteSourceType::ALL))
                return RemoteSourceType::BUILTIN;

            $source_manager = new RemoteSourcesManager();
            $defined_source = $source_manager->getRemoteSource($input);
            if($defined_source == null)
                return RemoteSourceType::UNKNOWN;

            return RemoteSourceType::DEFINED;
        }

        /**
         * Detects the project type from the specified path
         *
         * @param string $path
         * @return ProjectDetectionResults
         */
        public static function detectProjectType(string $path): ProjectDetectionResults
        {
            $project_files = [
                'project.json',
                'composer.json'
            ];

            $project_file = Functions::searchDirectory($path, $project_files);

            $project_detection_results = new ProjectDetectionResults();
            $project_detection_results->ProjectType = ProjectType::UNKNOWN;

            if($project_file == null)
            {
                return $project_detection_results;
            }

            // Get filename of the project file
            switch(basename($project_file))
            {
                case 'project.json':
                    $project_detection_results->ProjectType = ProjectType::NCC;
                    break;

                case 'composer.json':
                    $project_detection_results->ProjectType = ProjectType::COMPOSER;
                    break;
            }

            $project_detection_results->ProjectPath = dirname($project_file);
            return $project_detection_results;
        }
    }