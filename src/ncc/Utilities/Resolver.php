<?php

        /** @noinspection PhpMissingFieldTypeInspection */

        namespace ncc\Utilities;

    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Scopes;

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
                $input = Scopes::Auto;
            }

            $input = strtoupper($input);

            if(self::$UserIdCache == null)
                self::$UserIdCache = posix_getuid();

            // Resolve the scope if it's set to automatic
            if($input == Scopes::Auto)
            {
                if(self::$UserIdCache == 0)
                {
                    $input = Scopes::System;
                }
                else
                {
                    $input = Scopes::User;
                }
            }

            // Auto-Correct the scope if the current user ID is 0
            if($input == Scopes::User && self::$UserIdCache == 0)
            {
                $input = Scopes::System;
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
                case LogLevel::Debug:
                    $levels = [
                        LogLevel::Debug,
                        LogLevel::Verbose,
                        LogLevel::Info,
                        LogLevel::Warning,
                        LogLevel::Fatal,
                        LogLevel::Error
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::Verbose:
                    $levels = [
                        LogLevel::Verbose,
                        LogLevel::Info,
                        LogLevel::Warning,
                        LogLevel::Fatal,
                        LogLevel::Error
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::Info:
                    $levels = [
                        LogLevel::Info,
                        LogLevel::Warning,
                        LogLevel::Fatal,
                        LogLevel::Error
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::Warning:
                    $levels = [
                        LogLevel::Warning,
                        LogLevel::Fatal,
                        LogLevel::Error
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::Error:
                    $levels = [
                        LogLevel::Fatal,
                        LogLevel::Error
                    ];
                    if(in_array($input, $levels))
                        return true;
                    return false;

                case LogLevel::Fatal:
                    if($input == LogLevel::Fatal)
                        return true;
                    return false;

                default:
                case LogLevel::Silent:
                    return false;
            }
        }
    }