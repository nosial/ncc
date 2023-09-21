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
    use ncc\Enums\ConsoleColors;
    use ncc\Enums\LogLevel;
    use ncc\CLI\Main;
    use ncc\ncc;
    use Throwable;

    class Console
    {
        /**
         * @var int
         */
        private static $largest_tick_length = 0;

        /**
         * @var float|int
         */
        private static $last_tick_time;

        /**
         * Inline Progress bar, created by dealnews.com.
         *
         * @param int $value
         * @param int $total
         * @param int $size
         * @param array $options
         * @return void
         * @copyright Copyright (c) 2010, dealnews.com, Inc. All rights reserved.
         * @copyright Copyright (c) 2023, Nosial. All rights reserved
         */
        public static function inlineProgressBar(int $value, int $total, int $size = 10, array $options = []): void
        {
            if(Resolver::checkLogLevel(LogLevel::VERBOSE, Main::getLogLevel()))
            {
                return;
            }

            static $start_time;

            // Start time initialization
            if (!$start_time)
            {
                $start_time = time();
            }

            // If the value is out of bounds or zero, return early
            if ($value > $total || $value === 0)
            {
                return;
            }

            // Build status bar
            $percentage = $value / $total;
            $barLength = floor($percentage * $size);
            $statusBar = "\r[ "
                . str_repeat("=", $barLength)
                . ($barLength < $size ? ">" : "=")
                . str_repeat(" ", $size - $barLength)
                . " ] "
                . number_format($percentage * 100) . "% $value/$total";

            // ETA and elapsed time calculation
            $rate = (time() - $start_time) / $value;
            $eta = round($rate * ($total - $value), 2);
            $elapsed = time() - $start_time;
            $remaining_text = $options['remaining_text'] ?? 'remaining: ';
            $statusBar .= " $remaining_text " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";
            print("$statusBar  ");

            flush();

            // Reset variables once the progress is complete
            if ($value === $total)
            {
                print("\n");
                $start_time = null; // This resets the start time for the next progress bar
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
                LogLevel::VERBOSE => self::formatColor('VRB:', ConsoleColors::LIGHT_CYAN) . " $input",
                LogLevel::DEBUG => self::formatColor('DBG:', ConsoleColors::LIGHT_MAGENTA) . " $input",
                LogLevel::INFO => self::formatColor('INF:', ConsoleColors::WHITE) . " $input",
                LogLevel::WARNING => self::formatColor('WRN:', ConsoleColors::YELLOW) . " $input",
                LogLevel::ERROR => self::formatColor('ERR:', ConsoleColors::LIGHT_RED) . " $input",
                LogLevel::FATAL => self::formatColor('FTL:', ConsoleColors::LIGHT_RED) . " $input",
                default => self::formatColor('MSG:', ConsoleColors::DEFAULT) . " $input",
            };

            $tick_time = (string)microtime(true);

            if(strlen($tick_time) > self::$largest_tick_length)
            {
                self::$largest_tick_length = strlen($tick_time);
            }

            if(strlen($tick_time) < self::$largest_tick_length)
            {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $tick_time = str_pad($tick_time, (strlen($tick_time) + (self::$largest_tick_length - strlen($tick_time))), ' ', STR_PAD_RIGHT);
            }

            $fmt_tick = $tick_time;
            if(self::$last_tick_time !== null)
            {
                $timeDiff = microtime(true) - self::$last_tick_time;

                if ($timeDiff > 1.0)
                {
                    $fmt_tick = self::formatColor($tick_time, ConsoleColors::LIGHT_RED);
                }
                elseif ($timeDiff > 0.5)
                {
                    $fmt_tick = self::formatColor($tick_time, ConsoleColors::LIGHT_YELLOW);
                }
            }

            self::$last_tick_time = $tick_time;
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
            {
                return;
            }

            if(!Resolver::checkLogLevel(LogLevel::INFO, Main::getLogLevel()))
            {
                return;
            }

            if(!$no_prefix && Resolver::checkLogLevel(LogLevel::VERBOSE, Main::getLogLevel()))
            {
                $message = self::setPrefix(LogLevel::INFO, $message);
            }

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
            {
                return;
            }

            if(!Resolver::checkLogLevel(LogLevel::DEBUG, Main::getLogLevel()))
            {
                return;
            }

            $backtrace = null;
            if(function_exists('debug_backtrace'))
            {
                $backtrace = debug_backtrace();
            }
            $trace_msg = null;
            if($backtrace !== null && isset($backtrace[1]))
            {
                $trace_msg = self::formatColor($backtrace[1]['class'], ConsoleColors::LIGHT_GREY);
                $trace_msg .= $backtrace[1]['type'];
                $trace_msg .= self::formatColor($backtrace[1]['function'] . '()', ConsoleColors::LIGHT_GREEN);
                $trace_msg .= ' > ';
            }

            $message = self::setPrefix(LogLevel::DEBUG, $trace_msg . $message);
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
            {
                return;
            }

            if(!Resolver::checkLogLevel(LogLevel::VERBOSE, Main::getLogLevel()))
            {
                return;
            }

            self::out(self::setPrefix(LogLevel::VERBOSE, $message), $newline, true);
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
            if(isset(Main::getArgs()['no-color']))
            {
                return $input;
            }

            if($persist)
            {
                return $color_code . $input . ConsoleColors::DEFAULT;
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
            {
                return;
            }

            if(!Resolver::checkLogLevel(LogLevel::WARNING, Main::getLogLevel()))
            {
                return;
            }

            if(Resolver::checkLogLevel(LogLevel::VERBOSE, Main::getLogLevel()))
            {
                self::out(self::setPrefix(LogLevel::WARNING, $message), $newline, true);
                return;
            }

            self::out(self::formatColor('Warning: ', ConsoleColors::YELLOW) . $message, $newline);
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
            {
                return;
            }

            if(!Resolver::checkLogLevel(LogLevel::ERROR, Main::getLogLevel()))
            {
                return;
            }

            if(Resolver::checkLogLevel(LogLevel::VERBOSE, Main::getLogLevel()))
            {
                self::out(self::setPrefix(LogLevel::ERROR, $message), $newline, true);
            }
            else
            {
                self::out(self::formatColor(ConsoleColors::RED, 'Error: ') . $message, $newline);
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
            {
                return;
            }

            if($message !== '' && Resolver::checkLogLevel(LogLevel::ERROR, Main::getLogLevel()))
            {
                self::out(PHP_EOL . self::formatColor('Error: ', ConsoleColors::RED) . $message);
            }

            self::out(PHP_EOL . '===== Exception Details =====');
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
            {
                return;
            }

            // Exception name without namespace
            $trace_header = self::formatColor($e->getFile() . ':' . $e->getLine(), ConsoleColors::MAGENTA);
            $trace_error = self::formatColor( 'Error: ', ConsoleColors::RED);
            self::out($trace_header . ' ' . $trace_error . $e->getMessage());
            self::out(sprintf('Exception: %s', get_class($e)));
            self::out(sprintf('Error code: %s', $e->getCode()));
            $trace = $e->getTrace();
            if(count($trace) > 1)
            {
                self::out('Stack Trace:');
                foreach($trace as $item)
                {
                    self::out( ' - ' . self::formatColor($item['file'], ConsoleColors::RED) . ':' . $item['line']);
                }
            }

            // Check if previous is the same as the current
            if(($e->getPrevious() !== null) && $e->getPrevious()->getMessage() !== $e->getMessage())
            {
                self::outExceptionDetails($e->getPrevious(), true);
            }

            if(!$sub)
            {
                if(isset(Main::getArgs()['dbg-ex']))
                {
                    try
                    {
                        $dump = [
                            'constants' => ncc::getConstants(),
                            'exception' => Functions::exceptionToArray($e)
                        ];

                        IO::fwrite(getcwd() . DIRECTORY_SEPARATOR . time() . '.json', json_encode($dump, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 0777);
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
            return $args[$option] ?? self::getInput($prompt);
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
                    $r = self::getInput($prompt . ' [Y/n]: ');
                }
                else
                {
                    $r = self::getInput($prompt);
                }

                if($r !== '')
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
            {
                return null;
            }

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
            {
                return;
            }

            $padding = Functions::detectParametersPadding($sections);

            foreach($sections as $section)
            {
                self::out('   ' . $section->toString($padding));
            }
        }

    }