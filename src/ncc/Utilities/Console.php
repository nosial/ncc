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

    use Exception;
    use ncc\Abstracts\ConsoleColors;
    use ncc\Abstracts\LogLevel;
    use ncc\CLI\Main;
    use ncc\ncc;
    use Throwable;

    class Console
    {
        /**
         * @var int
         */
        private static $largestTickLength = 0;

        /**
         * @var float|int
         */
        private static $lastTickTime;

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
            if($value == $total)
                Console::out((string)null);
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
            {
                self::$largestTickLength = strlen($tick_time);
            }

            if(strlen($tick_time) < self::$largestTickLength)
            {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $tick_time = str_pad($tick_time, (strlen($tick_time) + (self::$largestTickLength - strlen($tick_time))), ' ', STR_PAD_RIGHT);
            }

            $fmt_tick = $tick_time;
            if(self::$lastTickTime !== null)
            {
                $timeDiff = microtime(true) - self::$lastTickTime;

                if ($timeDiff > 1.0)
                {
                    $fmt_tick = Console::formatColor($tick_time, ConsoleColors::LightRed);
                }
                elseif ($timeDiff > 0.5)
                {
                    $fmt_tick = Console::formatColor($tick_time, ConsoleColors::LightYellow);
                }
            }

            self::$lastTickTime = $tick_time;
            return '[' . $fmt_tick . '] ' . $input;
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

            /**  Apply syntax highlighting using regular expressions  */

            // Hyperlinks
            $message = preg_replace('/(https?:\/\/[^\s]+)/', Console::formatColor('$1', ConsoleColors::LightBlue), $message);

            // File Paths
            $message = preg_replace('/(\/[^\s]+)/', Console::formatColor('$1', ConsoleColors::LightCyan), $message);

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
         * @param bool $sub
         * @return void
         */
        private static function outExceptionDetails(Throwable $e, bool $sub=false): void
        {
            if(!ncc::cliMode())
                return;

            // Exception name without namespace

            $trace_header = self::formatColor($e->getFile() . ':' . $e->getLine(), ConsoleColors::Magenta);
            $trace_error = self::formatColor( 'Error: ', ConsoleColors::Red);
            self::out($trace_header . ' ' . $trace_error . $e->getMessage());
            self::out(sprintf('Exception: %s', get_class($e)));
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

            if($e->getPrevious() !== null)
            {
                // Check if previous is the same as the current
                if($e->getPrevious()->getMessage() !== $e->getMessage())
                {
                    self::outExceptionDetails($e->getPrevious(), true);
                }
            }

            if(Main::getArgs() !== null && !$sub)
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

        /**
         * Prompts for a password input while hiding the user's password
         *
         * @param string $prompt
         * @return string|null
         */
        public static function passwordInput(string $prompt): ?string
        {
            if(!ncc::cliMode())
                return null;

            // passwordInput() is not properly implemented yet, defaulting to prompt
            return self::getInput($prompt);

            /**
            $executable_finder = new ExecutableFinder();
            $bash_path = $executable_finder->find('bash');

            if($bash_path == null)
            {
                self::outWarning('Unable to find bash executable, cannot hide password input');
                return self::getInput($prompt);
            }

            $prompt = escapeshellarg($prompt);
            $random = Functions::randomString(10);
            $command = "$bash_path -c 'read -s -p $prompt $random && echo \$" . $random . "'";
            $password = rtrim(shell_exec($command));
            self::out((string)null);
            return $password;
             **/
        }

        /**
         * @param array $sections
         * @return void
         */
        public static function outHelpSections(array $sections): void
        {
            if(!ncc::cliMode())
                return;

            $padding = Functions::detectParametersPadding($sections);

            foreach($sections as $section)
                Console::out('   ' . $section->toString($padding));
        }

    }