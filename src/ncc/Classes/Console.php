<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\Classes;

    use Exception;
    use ncc\Libraries\LogLib2\Enums\LogLevel;
    use ncc\Libraries\Process\ExecutableFinder;
    use ncc\Libraries\Process\Process;

    class Console
    {
        private static bool $ansiColorsEnabled = true;
        private static ?string $currentProgressMessage = null;
        private static int $lastProgressLength = 0;
        private static ?bool $isVerboseOrDebugMode = null;

        public static function enableAnsiColors(): void
        {
            self::$ansiColorsEnabled = true;
        }

        public static function disableAnsiColors(): void
        {
            self::$ansiColorsEnabled = false;
        }

        /**
         * Checks if the Logger is in Verbose or Debug mode.
         * Caches the result to avoid repeated checks.
         *
         * @return bool True if in verbose or debug mode, false otherwise
         */
        private static function isVerboseOrDebugMode(): bool
        {
            if (self::$isVerboseOrDebugMode === null)
            {
                $currentLevel = \ncc\Libraries\LogLib2\Classes\Utilities::getEnvironmentLogLevel();
                self::$isVerboseOrDebugMode = in_array($currentLevel, [
                    LogLevel::VERBOSE,
                    LogLevel::DEBUG
                ], true);
            }
            
            return self::$isVerboseOrDebugMode;
        }

        public static function out(string $message): void
        {
            print($message . PHP_EOL);
        }

        public static function error(string $message): void
        {
            if (self::$ansiColorsEnabled)
            {
                $message = "\e[31m" . $message . "\e[0m"; // Red color
            }

            self::out($message);
        }

        public static function warning(string $message): void
        {
            if (self::$ansiColorsEnabled)
            {
                $message = "\e[33m" . $message . "\e[0m"; // Yellow color
            }

            self::out($message);
        }

        public static function prompt(string $prompt): string
        {
            print($prompt);
            return trim(fgets(STDIN));
        }

        /**
         * Prompts the user for a password in a secure way (without echoing input).
         * Tries multiple methods depending on platform and availability:
         * 1. GUI dialogs (zenity, kdialog, osascript) if in graphical environment
         * 2. Terminal-based secure input (stty on Unix, PowerShell on Windows)
         * 3. Falls back to raw input if no secure method is available
         *
         * @param string $prompt The prompt message to display
         * @return string The entered password
         */
        public static function getPassword(string $prompt = 'Enter password: '): string
        {
            // Check if the environment variable has the password
            $envPassword = getenv('NCC_AUTH');
            if ($envPassword !== false)
            {
                return $envPassword;
            }

            // Try GUI methods first (if DISPLAY is set or on macOS/Windows with GUI)
            $password = self::tryGuiPasswordInput($prompt);
            if ($password !== null)
            {
                return $password;
            }

            // Try terminal-based methods
            $password = self::tryTerminalPasswordInput($prompt);
            if ($password !== null)
            {
                return $password;
            }

            // Fall back to raw input with warning
            self::error('Warning: Unable to hide password input. Password will be visible.');
            print($prompt);
            return trim(fgets(STDIN));
        }

        /**
         * Attempts to get password input using GUI dialog tools
         *
         * @param string $prompt
         * @return string|null Returns the password or null if GUI method not available
         */
        private static function tryGuiPasswordInput(string $prompt): ?string
        {
            $executableFinder = new ExecutableFinder();

            // Try zenity (common on Linux with GNOME/GTK)
            if (getenv('DISPLAY') && $executableFinder->find('zenity'))
            {
                try
                {
                    $process = new Process(['zenity', '--password', '--title=' . $prompt]);
                    $process->mustRun();
                    return trim($process->getOutput());
                }
                catch (Exception $e)
                {
                    // Continue to next method
                }
            }

            // Try kdialog (common on Linux with KDE)
            if (getenv('DISPLAY') && $executableFinder->find('kdialog'))
            {
                try
                {
                    $process = new Process(['kdialog', '--password', $prompt]);
                    $process->mustRun();
                    return trim($process->getOutput());
                }
                catch (Exception)
                {
                    // Continue to next method
                }
            }

            // Try osascript (macOS)
            if (PHP_OS_FAMILY === 'Darwin' && $executableFinder->find('osascript'))
            {
                try
                {
                    $script = sprintf(
                        'display dialog "%s" default answer "" with hidden answer',
                        addslashes($prompt)
                    );
                    $process = new Process(['osascript', '-e', $script]);
                    $process->mustRun();
                    $output = trim($process->getOutput());
                    // osascript returns "button returned:OK, text returned:password"
                    if (preg_match('/text returned:(.*)$/', $output, $matches))
                    {
                        return $matches[1];
                    }
                }
                catch (Exception)
                {
                    // Continue to next method
                }
            }

            return null;
        }

        /**
         * Attempts to get password input using terminal methods
         *
         * @param string $prompt
         * @return string|null Returns the password or null if terminal method not available
         */
        private static function tryTerminalPasswordInput(string $prompt): ?string
        {
            // Unix/Linux/macOS: Use stty to disable echo
            if (PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec'))
            {
                try
                {
                    self::out($prompt);
                    shell_exec('stty -echo'); // Disable echo
                    $password = trim(fgets(STDIN)); // Read password
                    shell_exec('stty echo'); // Re-enable echo
                    self::out(''); // Print newline since user's enter wasn't echoed
                    return $password;
                }
                catch (Exception)
                {
                    // Make sure to re-enable echo even if there's an error
                    shell_exec('stty echo');
                }
            }

            // Windows: Try PowerShell's Read-Host -AsSecureString
            if (PHP_OS_FAMILY === 'Windows')
            {
                $executableFinder = new ExecutableFinder();
                
                if ($executableFinder->find('powershell'))
                {
                    try
                    {
                        $psCommand = sprintf(
                            '$p = Read-Host -Prompt "%s" -AsSecureString; ' .
                            '$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); ' .
                            '[System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)',
                            addslashes($prompt)
                        );
                        
                        $process = new Process(['powershell', '-Command', $psCommand]);
                        $process->mustRun();
                        return trim($process->getOutput());
                    }
                    catch (Exception $e)
                    {
                        // Continue to fall back
                    }
                }
            }

            return null;
        }

        /**
         * Displays an inline progress indicator with a message.
         * This will overwrite the current line each time it's called.
         * If Logger is in Verbose or Debug mode, uses Logger info statements instead.
         *
         * @param int $current The current progress value (e.g., current stage)
         * @param int $total The total number of steps
         * @param string $message The message to display
         * @return void
         */
        public static function inlineProgress(int $current, int $total, string $message): void
        {
            // If in verbose or debug mode, use logger info statements instead of animated progress
            if (self::isVerboseOrDebugMode())
            {
                $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
                $logMessage = sprintf('[%d/%d] (%d%%) %s', $current, $total, $percentage, $message);
                Logger::getLogger()->info($logMessage);
                return;
            }
            
            $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
            $progressBar = self::createProgressBar($current, $total, 30);
            
            // Add color if ANSI is enabled
            if (self::$ansiColorsEnabled)
            {
                $coloredBar = self::colorizeProgressBar($progressBar, $percentage);
                $percentageStr = sprintf("\e[1;36m%3d%%\e[0m", $percentage); // Cyan bold
                $output = sprintf('⟦%s⟧ %s %s', $coloredBar, $percentageStr, $message);
            }
            else
            {
                $output = sprintf('[%s] %3d%% - %s', $progressBar, $percentage, $message);
            }
            
            // Clear the entire line first, then print the new progress
            print("\r\033[K" . $output);
            
            // Store the length for next clear operation (strip ANSI codes for accurate length)
            self::$lastProgressLength = mb_strlen(preg_replace('/\e\[[0-9;]*m/', '', $output));
            self::$currentProgressMessage = $message;
            
            flush();
        }

        /**
         * Clears the current inline progress indicator and moves to a new line.
         * Does nothing if in verbose or debug mode (since we use logger instead).
         *
         * @return void
         */
        public static function clearInlineProgress(): void
        {
            // No need to clear if we're using logger mode
            if (self::isVerboseOrDebugMode())
            {
                return;
            }
            
            if (self::$lastProgressLength > 0)
            {
                // Use ANSI escape code to clear the entire line
                print("\r\033[K");
                self::$lastProgressLength = 0;
                self::$currentProgressMessage = null;
            }
        }

        /**
         * Completes the progress display and moves to a new line.
         * If in verbose or debug mode, uses Logger info for final message.
         *
         * @param string|null $finalMessage Optional message to display when complete
         * @return void
         */
        public static function completeProgress(?string $finalMessage = null): void
        {
            // If in verbose or debug mode, use logger for final message
            if (self::isVerboseOrDebugMode())
            {
                if ($finalMessage !== null)
                {
                    Logger::getLogger()->info($finalMessage);
                }
                return;
            }
            
            if (self::$lastProgressLength > 0)
            {
                // Use ANSI escape code to clear the entire line
                print("\r\033[K");
                self::$lastProgressLength = 0;
                self::$currentProgressMessage = null;
            }
            
            if ($finalMessage !== null)
            {
                print($finalMessage . PHP_EOL);
            }
        }

        /**
         * Creates a text-based progress bar.
         *
         * @param int $current Current progress value
         * @param int $total Total progress value
         * @param int $width Width of the progress bar in characters
         * @return string The progress bar string
         */
        private static function createProgressBar(int $current, int $total, int $width = 30): string
        {
            if ($total <= 0)
            {
                return str_repeat('█', $width);
            }

            $progress = min(1.0, $current / $total);
            $filled = (int)round($width * $progress);
            $empty = $width - $filled;

            // Use Unicode block characters for a smooth gradient effect
            $bar = str_repeat('█', $filled);
            
            // Add a transitional character for smoother animation
            if ($filled < $width && $progress > 0)
            {
                $fractional = ($width * $progress) - $filled;
                if ($fractional > 0.75)
                {
                    $bar .= '▓';
                }
                elseif ($fractional > 0.5)
                {
                    $bar .= '▒';
                }
                elseif ($fractional > 0.25)
                {
                    $bar .= '░';
                }
                else
                {
                    $bar .= '░';
                }
                $empty--;
            }
            
            $bar .= str_repeat('░', max(0, $empty));

            return $bar;
        }

        /**
         * Colorizes the progress bar based on percentage.
         *
         * @param string $bar The progress bar string
         * @param float $percentage The completion percentage
         * @return string The colorized progress bar
         */
        private static function colorizeProgressBar(string $bar, float $percentage): string
        {
            // Color scheme based on progress
            if ($percentage < 33)
            {
                $color = "\e[91m"; // Light red for 0-33%
            }
            elseif ($percentage < 66)
            {
                $color = "\e[93m"; // Yellow for 33-66%
            }
            else
            {
                $color = "\e[92m"; // Green for 66-100%
            }

            return $color . $bar . "\e[0m";
        }
    }