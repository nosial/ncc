<?php

    namespace ncc\Utilities;

    use Exception;
    use ncc\Abstracts\ConsoleColors;
    use ncc\Abstracts\LogLevel;
    use ncc\CLI\Main;
    use ncc\ncc;

    class Console
    {
        /**
         * @var int
         */
        private static $largestTickLength = 0;

        /**
         * Inline Progress bar, created by dealnews.com.
         *
         * @param int $value
         * @param int $total
         * @param int $size
         * @param array $options
         * @return void
         *@copyright Copyright (c) 2010, dealnews.com, Inc. All rights reserved.
         */
        public static function inlineProgressBar(int $value, int $total, int $size=38, array $options=[]): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null)
            {
                switch(Main::getLogLevel())
                {
                    case LogLevel::Verbose:
                    case LogLevel::Debug:
                    case LogLevel::Silent:
                        return;

                    default:
                        break;
                }
            }

            static $start_time;

            // if we go over our bound, just ignore it
            if($value > $total)
                return;

            if(empty($start_time)) $start_time=time();
            $now = time();
            $perc=(double)($value/$total);

            $bar=floor($perc*$size);

            $status_bar="\r[ ";
            $status_bar.=str_repeat("=", $bar);
            if($bar<$size){
                $status_bar.=">";
                $status_bar.=str_repeat(" ", $size-$bar);
            } else {
                $status_bar.="=";
            }

            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $disp=number_format($perc*100, 0);

            $status_bar.=" ] $disp%  $value/$total";

            if($value == 0)
                return;

            $rate = ($now-$start_time)/$value;
            $left = $total - $value;
            $eta = round($rate * $left, 2);
            $elapsed = $now - $start_time;

            $remaining_text = 'remaining: ';
            if(isset($options['remaining_text']))
            {
                $remaining_text = $options['remaining_text'];
            }

            $status_bar.= " $remaining_text ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

            echo "$status_bar  ";

            flush();

            // when done, send a newline
            if($value == $total) {
                echo "\n";
            }
        }

        /**
         * Appends a verbose prefix to the message
         *
         * @param string $log_level
         * @param string $input
         * @return string
         */
        private static function setPrefix(string $log_level, string $input): string
        {
            $input = match ($log_level) {
                LogLevel::Verbose => self::formatColor('VRB:', ConsoleColors::LightCyan) . " $input",
                LogLevel::Debug => self::formatColor('DBG:', ConsoleColors::LightMagenta) . " $input",
                LogLevel::Info => self::formatColor('INF:', ConsoleColors::White) . " $input",
                LogLevel::Warning => self::formatColor('WRN:', ConsoleColors::Yellow) . " $input",
                LogLevel::Error => self::formatColor('ERR:', ConsoleColors::LightRed) . " $input",
                LogLevel::Fatal => self::formatColor('FTL:', ConsoleColors::LightRed) . " $input",
                default => self::formatColor('MSG:', ConsoleColors::Default) . " $input",
            };

            $tick_time = (string)microtime(true);
            if(strlen($tick_time) > self::$largestTickLength)
                self::$largestTickLength = strlen($tick_time);
            if(strlen($tick_time) < self::$largestTickLength)
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $tick_time = str_pad($tick_time, (strlen($tick_time) + (self::$largestTickLength - strlen($tick_time))), ' ', STR_PAD_RIGHT);

            return '[' . $tick_time . ' - ' . date('TH:i:sP') . '] ' . $input;
        }

        /**
         * Simple output function
         *
         * @param string $message
         * @param bool $newline
         * @param bool $no_prefix
         * @return void
         */
        public static function out(string $message, bool $newline=true, bool $no_prefix=false): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null && !Resolver::checkLogLevel(LogLevel::Info, Main::getLogLevel()))
                return;

            if(Main::getLogLevel() !== null && Resolver::checkLogLevel(LogLevel::Verbose, Main::getLogLevel()) && !$no_prefix)
                $message = self::setPrefix(LogLevel::Info, $message);

            if($newline)
            {
                print($message . PHP_EOL);
                return;
            }

            print($message);
        }

        /**
         * Output debug message
         *
         * @param string $message
         * @param bool $newline
         * @return void
         */
        public static function outDebug(string $message, bool $newline=true): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null && !Resolver::checkLogLevel(LogLevel::Debug, Main::getLogLevel()))
                return;

            $backtrace = null;
            if(function_exists('debug_backtrace'))
                $backtrace = debug_backtrace();
            $trace_msg = null;
            if($backtrace !== null && isset($backtrace[1]))
            {
                $trace_msg = Console::formatColor($backtrace[1]['class'], ConsoleColors::LightGray);
                $trace_msg .= $backtrace[1]['type'];
                $trace_msg .= Console::formatColor($backtrace[1]['function'] . '()', ConsoleColors::LightGreen);
                $trace_msg .= ' > ';
            }

            /** @noinspection PhpUnnecessaryStringCastInspection */
            $message = self::setPrefix(LogLevel::Debug, (string)$trace_msg . $message);

            self::out($message, $newline, true);
        }

        /**
         * Output debug message
         *
         * @param string $message
         * @param bool $newline
         * @return void
         */
        public static function outVerbose(string $message, bool $newline=true): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null && !Resolver::checkLogLevel(LogLevel::Verbose, Main::getLogLevel()))
                return;

            self::out(self::setPrefix(LogLevel::Verbose, $message), $newline, true);
        }


        /**
         * Formats the text to have a different color and returns the formatted value
         *
         * @param string $input The input of the text value
         * @param string $color_code The color code of the escaped character (\e[91m)
         * @param bool $persist If true, the formatting will terminate in the default color
         * @return string
         */
        public static function formatColor(string $input, string $color_code, bool $persist=true): string
        {
            if(Main::getArgs() !== null && isset(Main::getArgs()['no-color']))
            {
                return $input;
            }

            if($persist)
            {
                return $color_code . $input . ConsoleColors::Default;
            }

            return $color_code . $input;
        }

        /**
         * Prints out a warning output
         *
         * @param string $message
         * @param bool $newline
         * @return void
         */
        public static function outWarning(string $message, bool $newline=true): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null && !Resolver::checkLogLevel(LogLevel::Warning, Main::getLogLevel()))
                return;

            if(Main::getLogLevel() !== null && Resolver::checkLogLevel(LogLevel::Verbose, Main::getLogLevel()))
            {
                self::out(self::setPrefix(LogLevel::Warning, $message), $newline, true);
                return;
            }

            self::out(self::formatColor('Warning: ', ConsoleColors::Yellow) . $message, $newline);
        }

        /**
         * Prints out a generic error output, optionally exits the process with an exit code.
         *
         * @param string $message
         * @param bool $newline
         * @param int|null $exit_code
         * @return void
         */
        public static function outError(string $message, bool $newline=true, ?int $exit_code=null): void
        {
            if(!ncc::cliMode())
                return;

            if(Main::getLogLevel() !== null && !Resolver::checkLogLevel(LogLevel::Error, Main::getLogLevel()))
                return;

            if(Main::getLogLevel() !== null && Resolver::checkLogLevel(LogLevel::Verbose, Main::getLogLevel()))
            {
                self::out(self::setPrefix(LogLevel::Error, $message), $newline, true);
            }
            else
            {
                self::out(self::formatColor(ConsoleColors::Red, 'Error: ') . $message, $newline);
            }

            if($exit_code !== null)
            {
                exit($exit_code);
            }
        }

        /**
         * Prints out an exception message and exits the program if needed
         *
         * @param string $message
         * @param Exception $e
         * @param int|null $exit_code
         * @return void
         */
        public static function outException(string $message, Exception $e, ?int $exit_code=null): void
        {
            if(!ncc::cliMode())
                return;

            if(strlen($message) > 0 && Resolver::checkLogLevel(LogLevel::Error, Main::getLogLevel()))
            {
                self::out(PHP_EOL . self::formatColor('Error: ', ConsoleColors::Red) . $message);
            }

            Console::out(PHP_EOL . '===== Exception Details =====');
            self::outExceptionDetails($e);

            if($exit_code !== null)
            {
                exit($exit_code);
            }
        }

        /**
         * Prints out a detailed exception display (unfinished)
         *
         * @param Exception $e
         * @return void
         */
        private static function outExceptionDetails(Exception $e): void
        {
            if(!ncc::cliMode())
                return;

            $trace_header = self::formatColor($e->getFile() . ':' . $e->getLine(), ConsoleColors::Magenta);
            $trace_error = self::formatColor('error: ', ConsoleColors::Red);
            self::out($trace_header . ' ' . $trace_error . $e->getMessage());
            self::out(sprintf('Error code: %s', $e->getCode()));
            $trace = $e->getTrace();
            if(count($trace) > 1)
            {
                self::out('Stack Trace:');
                foreach($trace as $item)
                {
                    self::out( ' - ' . self::formatColor($item['file'], ConsoleColors::Red) . ':' . $item['line']);
                }
            }

            if(Main::getArgs() !== null)
            {
                if(isset(Main::getArgs()['dbg-ex']))
                {
                    try
                    {
                        $dump = [
                            'constants' => ncc::getConstants(),
                            'exception' => Functions::exceptionToArray($e)
                        ];
                        IO::fwrite(getcwd() . DIRECTORY_SEPARATOR . time() . '.json', json_encode($dump, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 0777);
                    }
                    catch (Exception $e)
                    {
                        self::outWarning('Cannot dump exception details, ' . $e->getMessage());
                    }
                }
                else
                {
                    self::out('You can pass on \'--dbg-ex\' option to dump the exception details to a json file');
                }
            }
        }

        /**
         * @param string|null $prompt
         * @return string
         */
        public static function getInput(?string $prompt=null): string
        {
            if($prompt !== null)
            {
                print($prompt);
            }

            return rtrim(fgets(STDIN), "\n");
        }

        /**
         * @param array $args
         * @param string $option
         * @param string $prompt
         * @return string
         */
        public static function getOptionInput(array $args, string $option, string $prompt): string
        {
            if(isset($args[$option]))
            {
                return $args[$option];
            }

            return self::getInput($prompt);
        }

        /**
         * Prompts the user for a yes/no input
         *
         * @param string $prompt
         * @param bool $display_options
         * @return bool
         */
        public static function getBooleanInput(string $prompt, bool $display_options=true): bool
        {
            while(true)
            {
                if($display_options)
                {
                    $r = self::getInput($prompt . ' (Y/N): ');
                }
                else
                {
                    $r = self::getInput($prompt);
                }

                if(strlen($r) > 0)
                {
                    switch(strtoupper($r))
                    {
                        case '1':
                        case 'Y':
                        case 'YES':
                            return true;

                        case '0':
                        case 'N':
                        case 'NO':
                            return false;
                    }
                }
            }
        }
    }