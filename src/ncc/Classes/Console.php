<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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
        private static bool $simplifiedMode = false;
        private static ?string $currentSimplifiedOperation = null;
        /** @var array<int, array{0: float, 1: float}> Absolute [start, end] fractions of the active progress scopes */
        private static array $progressScopes = [];
        private static float $lastFraction = 0.0;
        private static ?string $lastProgressOutput = null;
        /** @var bool|null Whether the terminal can draw the eighth block progress bar, null until detected */
        private static ?bool $smoothProgressSupported = null;

        /** Left aligned eighth blocks, index n draws (n + 1) eighths of a cell */
        private const array PROGRESS_EIGHTHS = ['▏', '▎', '▍', '▌', '▋', '▊', '▉'];

        public static function enableAnsiColors(): void
        {
            self::$ansiColorsEnabled = true;
        }

        public static function disableAnsiColors(): void
        {
            self::$ansiColorsEnabled = false;
        }

        /**
         * Enables simplified output mode - reduces output frequency, hides progress bars
         *
         * @return void
         */
        public static function enableSimplifiedMode(): void
        {
            self::$simplifiedMode = true;
        }

        /**
         * Disables simplified output mode
         *
         * @return void
         */
        public static function disableSimplifiedMode(): void
        {
            self::$simplifiedMode = false;
        }

        /**
         * Checks if simplified mode is enabled
         *
         * @return bool True if simplified mode is enabled
         */
        public static function isSimplifiedMode(): bool
        {
            return self::$simplifiedMode;
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

        /**
         * Outputs a message to the standard output followed by a newline.
         *
         * @param string $message The message to be printed.
         * @return void
         */
        public static function out(string $message): void
        {
            if (self::$lastProgressLength > 0 && self::$lastProgressOutput !== null && !self::$simplifiedMode && !self::isVerboseOrDebugMode())
            {
                print("\r\033[K" . $message . PHP_EOL . self::$lastProgressOutput);
                flush();
                return;
            }

            print($message . PHP_EOL);
        }

        /**
         * Displays an error message, optionally formatted with red color if ANSI colors are enabled.
         *
         * @param string $message The error message to display.
         * @return void
         */
        public static function error(string $message): void
        {
            if (self::$ansiColorsEnabled)
            {
                $message = "\e[31m" . $message . "\e[0m"; // Red color
            }

            self::out($message);
        }

        /**
         * Outputs a warning message. If ANSI colors are enabled, the message will be styled in yellow.
         *
         * @param string $message The warning message to display.
         * @return void
         */
        public static function warning(string $message): void
        {
            if (self::$ansiColorsEnabled)
            {
                $message = "\e[33m" . $message . "\e[0m"; // Yellow color
            }

            self::out($message);
        }

        /**
         * Returns whether the user can answer prompts, false when STDIN is not a terminal (eg; Docker builds, CI
         * pipelines or piped input)
         *
         * @return bool True if STDIN is an interactive terminal
         */
        public static function isInteractive(): bool
        {
            return defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN);
        }

        /**
         * Displays a prompt message to the user and captures their input.
         *
         * @param string $prompt The message to display to the user.
         * @return string The user input after trimming whitespace.
         */
        public static function prompt(string $prompt): string
        {
            self::clearInlineProgress();
            print($prompt);
            return trim(fgets(STDIN));
        }

        /**
         * Retrieves a password securely using various input methods.
         * This method tries to fetch the password from an environment variable,
         * graphical input methods, or terminal-based input methods in sequence.
         * It falls back to visible plain-text input if all other methods fail.
         *
         * @param string $prompt The prompt message displayed to the user.
         * @param bool $useEnvironment Whether to attempt retrieving the password from the NCC_AUTH environment variable.
         * @return string The entered password.
         */
        public static function getPassword(string $prompt = 'Enter password: ', bool $useEnvironment = true): string
        {
            self::clearInlineProgress();

            // Check if the environment variable has the password
            $envPassword = $useEnvironment ? getenv('NCC_AUTH') : false;
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
         * If simplified mode is enabled, only shows the message once when operation starts.
         *
         * @param int $current The current progress value (e.g., current stage)
         * @param int $total The total number of steps
         * @param string $message The message to display
         * @return void
         */
        public static function inlineProgress(int $current, int $total, string $message): void
        {
            // In simplified mode, only show the message when it changes (operation starts)
            if (self::$simplifiedMode)
            {
                if (self::$currentSimplifiedOperation !== $message)
                {
                    self::$currentSimplifiedOperation = $message;
                    self::out($message);
                }
                return;
            }

            $fraction = self::mapProgress($total > 0 ? ($current / $total) : 0.0);
            $percentage = round($fraction * 100);

            // If in verbose or debug mode, use logger info statements instead of animated progress
            if (self::isVerboseOrDebugMode())
            {
                if (empty(self::$progressScopes))
                {
                    $logMessage = sprintf('[%d/%d] (%d%%) %s', $current, $total, $percentage, $message);
                }
                else
                {
                    $logMessage = sprintf('(%d%%) %s', $percentage, $message);
                }

                Logger::getLogger()?->info($logMessage);
                return;
            }

            self::renderProgress($fraction, $message);
        }

        /**
         * Updates the message shown on the progress bar without changing its progress. Inside a progress scope the
         * bar keeps its current position and only the inline text changes; with no active scope the message is
         * printed as a regular line. In verbose or debug mode the message is logged instead, in simplified mode it
         * is printed once when it changes.
         *
         * @param string $message The message to display
         * @return void
         */
        public static function progressMessage(string $message): void
        {
            if (self::$simplifiedMode)
            {
                if (self::$currentSimplifiedOperation !== $message)
                {
                    self::$currentSimplifiedOperation = $message;
                    self::out($message);
                }
                return;
            }

            if (self::isVerboseOrDebugMode())
            {
                Logger::getLogger()?->info($message);
                return;
            }

            if (empty(self::$progressScopes))
            {
                self::out($message);
                return;
            }

            [$start] = self::$progressScopes[array_key_last(self::$progressScopes)];
            self::$lastFraction = max(self::$lastFraction, $start);
            self::renderProgress(self::$lastFraction, $message);
        }

        /**
         * Temporarily removes the drawn progress bar so that output written directly to the terminal (eg; log
         * events) starts on a clean line. The bar is drawn again by resumeProgress().
         *
         * @return void
         */
        public static function suspendProgress(): void
        {
            if (self::$simplifiedMode || self::isVerboseOrDebugMode() || self::$lastProgressLength <= 0)
            {
                return;
            }

            print("\r\033[K");
            flush();
        }

        /**
         * Draws the progress bar again after suspendProgress(), does nothing if no progress bar was drawn.
         *
         * @return void
         */
        public static function resumeProgress(): void
        {
            if (self::$simplifiedMode || self::isVerboseOrDebugMode() || self::$lastProgressLength <= 0 || self::$lastProgressOutput === null)
            {
                return;
            }

            print("\r\033[K" . self::$lastProgressOutput);
            flush();
        }

        /**
         * Runs an operation within a slice of the current progress range, any progress reported by the operation
         * (0..total) is mapped into that slice so that nested operations share a single progress bar. When the
         * outermost scope ends the progress bar is cleared.
         *
         * @param float $start The start of the slice, relative to the current scope (0.0 - 1.0)
         * @param float $end The end of the slice, relative to the current scope (0.0 - 1.0)
         * @param callable $operation The operation to run within the slice
         * @return mixed The return value of the operation
         */
        public static function withProgressScope(float $start, float $end, callable $operation): mixed
        {
            $start = max(0.0, min(1.0, $start));
            $end = max($start, min(1.0, $end));

            [$parentStart, $parentEnd] = empty(self::$progressScopes) ? [0.0, 1.0] : self::$progressScopes[array_key_last(self::$progressScopes)];
            $absoluteStart = $parentStart + ($start * ($parentEnd - $parentStart));
            $absoluteEnd = $parentStart + ($end * ($parentEnd - $parentStart));

            self::$progressScopes[] = [$absoluteStart, $absoluteEnd];
            $completed = false;

            try
            {
                $result = $operation();
                $completed = true;
                return $result;
            }
            finally
            {
                array_pop(self::$progressScopes);

                if (empty(self::$progressScopes))
                {
                    // The outermost scope ended, the progress bar is no longer needed
                    self::clearProgressLine();
                    self::$lastFraction = 0.0;
                    self::$currentSimplifiedOperation = null;
                }
                elseif ($completed)
                {
                    // Advance the bar to the end of the finished slice
                    self::$lastFraction = max(self::$lastFraction, $absoluteEnd);
                    if (self::$lastProgressLength > 0 && self::$currentProgressMessage !== null)
                    {
                        self::renderProgress(self::$lastFraction, self::$currentProgressMessage);
                    }
                }
            }
        }

        /**
         * Returns the current progress scope depth, 0 when no scope is active.
         *
         * @return int The number of active progress scopes
         */
        public static function getProgressScopeDepth(): int
        {
            return count(self::$progressScopes);
        }

        /**
         * Maps a fraction of the current scope into the overall progress, the result never decreases while a scope
         * is active so the progress bar never moves backwards.
         *
         * @param float $fraction The fraction of the current scope (0.0 - 1.0)
         * @return float The overall fraction (0.0 - 1.0)
         */
        private static function mapProgress(float $fraction): float
        {
            $fraction = max(0.0, min(1.0, $fraction));
            if (empty(self::$progressScopes))
            {
                return $fraction;
            }

            [$start, $end] = self::$progressScopes[array_key_last(self::$progressScopes)];
            self::$lastFraction = max(self::$lastFraction, $start + ($fraction * ($end - $start)));
            return self::$lastFraction;
        }

        /**
         * Renders the progress bar on the current line.
         *
         * @param float $fraction The overall fraction (0.0 - 1.0)
         * @param string $message The message to display
         * @return void
         */
        private static function renderProgress(float $fraction, string $message): void
        {
            $percentage = round($fraction * 100);
            [$filledPart, $emptyPart] = self::createProgressBar((int)round($fraction * 10000), 10000, 30);
            $progressBar = $filledPart . $emptyPart;

            if (self::$ansiColorsEnabled)
            {
                $coloredBar = self::colorizeProgressBar($filledPart, $emptyPart, $percentage);
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
            self::$lastProgressOutput = $output;
            self::$currentProgressMessage = $message;

            flush();
        }

        /**
         * Clears the progress bar line if one is currently drawn.
         *
         * @return void
         */
        private static function clearProgressLine(): void
        {
            if (self::$lastProgressLength > 0)
            {
                // Use ANSI escape code to clear the entire line
                print("\r\033[K");
                self::$lastProgressLength = 0;
                self::$lastProgressOutput = null;
                self::$currentProgressMessage = null;
            }
        }

        /**
         * Clears the current inline progress indicator and moves to a new line.
         * Does nothing if in verbose, debug, or simplified mode.
         *
         * @return void
         */
        public static function clearInlineProgress(): void
        {
            // No need to clear if we're using logger mode or simplified mode
            if (self::isVerboseOrDebugMode() || self::$simplifiedMode)
            {
                return;
            }

            // Within a progress scope the bar is drawn again on the next progress update
            self::clearProgressLine();
        }

        /**
         * Completes the progress display and moves to a new line.
         *
         * @param string|null $finalMessage Optional message to display when complete
         * @return void
         */
        public static function completeProgress(?string $finalMessage = null): void
        {
            $inScope = !empty(self::$progressScopes);

            // In simplified mode, show completion message and reset operation tracking
            if (self::$simplifiedMode)
            {
                if ($finalMessage !== null)
                {
                    self::out($finalMessage);
                }

                if (!$inScope)
                {
                    self::$currentSimplifiedOperation = null;
                }
                return;
            }

            // If in verbose or debug mode, use logger for final message
            if (self::isVerboseOrDebugMode())
            {
                if ($finalMessage !== null)
                {
                    Logger::getLogger()?->info($finalMessage);
                }
                return;
            }

            if ($inScope)
            {
                if ($finalMessage !== null)
                {
                    self::out($finalMessage);
                }
                return;
            }

            self::clearProgressLine();

            if ($finalMessage !== null)
            {
                print($finalMessage . PHP_EOL);
            }
        }

        /**
         * Creates a text-based progress bar, split into its filled and empty parts so they can be colored apart.
         *
         * @param int $current Current progress value
         * @param int $total Total progress value
         * @param int $width Width of the progress bar in characters
         * @return array{0: string, 1: string} The filled and the empty part of the progress bar
         */
        private static function createProgressBar(int $current, int $total, int $width = 30): array
        {
            $smooth = self::supportsSmoothProgress();
            $progress = $total > 0 ? max(0.0, min(1.0, $current / $total)) : 1.0;

            if ($smooth)
            {
                $units = (int)round($progress * $width * 8);
                $full = intdiv($units, 8);
                $remainder = $units % 8;

                $filled = str_repeat('█', $full) . ($remainder > 0 ? self::PROGRESS_EIGHTHS[$remainder - 1] : '');
                return [$filled, str_repeat(' ', $width - $full - ($remainder > 0 ? 1 : 0))];
            }

            $units = (int)round($progress * $width * 2);
            $full = intdiv($units, 2);
            $half = $units % 2;

            return [str_repeat('━', $full) . ($half > 0 ? '╸' : ''), str_repeat('─', $width - $full - $half)];
        }

        /**
         * Returns whether the terminal is expected to draw the eighth block progress bar, the result is cached.
         *
         * @return bool True to use the eighth block style, false to use the connected line style
         */
        private static function supportsSmoothProgress(): bool
        {
            if (self::$smoothProgressSupported === null)
            {
                $environment = [];
                foreach (['TERM', 'LC_ALL', 'LC_CTYPE', 'LANG', 'WT_SESSION', 'TERM_PROGRAM', 'ConEmuANSI'] as $name)
                {
                    $value = getenv($name);
                    if ($value !== false)
                    {
                        $environment[$name] = $value;
                    }
                }

                self::$smoothProgressSupported = self::detectSmoothProgress(PHP_OS_FAMILY, $environment);
            }

            return self::$smoothProgressSupported;
        }

        /**
         * Decides from the environment whether the terminal can draw eighth blocks. The Linux text console
         * (TERM=linux), a locale that is not UTF-8 and the legacy Windows console lack them; an unset locale is
         * treated as capable since containers and minimal shells often leave it unset on UTF-8 terminals.
         *
         * @param string $osFamily The operating system family (PHP_OS_FAMILY)
         * @param array<string, string> $environment The relevant environment variables that are set
         * @return bool True if the eighth block style should be used
         */
        private static function detectSmoothProgress(string $osFamily, array $environment): bool
        {
            if (($environment['TERM'] ?? '') === 'linux')
            {
                return false;
            }

            // The first locale variable that is set and not empty decides, the same precedence as setlocale()
            foreach (['LC_ALL', 'LC_CTYPE', 'LANG'] as $name)
            {
                $locale = $environment[$name] ?? '';
                if ($locale !== '')
                {
                    if (!preg_match('/utf-?8/i', $locale))
                    {
                        return false;
                    }
                    break;
                }
            }

            if ($osFamily === 'Windows')
            {
                // Windows Terminal, VS Code and ConEmu render them, the legacy console host does not
                return isset($environment['WT_SESSION']) || isset($environment['TERM_PROGRAM']) || ($environment['ConEmuANSI'] ?? '') === 'ON';
            }

            return true;
        }

        /**
         * Colorizes the progress bar based on percentage, the empty part is dimmed.
         *
         * @param string $filled The filled part of the progress bar
         * @param string $empty The empty part of the progress bar
         * @param float $percentage The completion percentage
         * @return string The colorized progress bar
         */
        private static function colorizeProgressBar(string $filled, string $empty, float $percentage): string
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

            return ($filled !== '' ? $color . $filled . "\e[0m" : '') . ($empty !== '' ? "\e[2m" . $empty . "\e[0m" : '');
        }
    }
