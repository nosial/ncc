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
    use ncc\Libraries\Process\Process;
    use ncc\Libraries\Process\ExecutableFinder;

    class Console
    {
        private static bool $ansiColorsEnabled = true;

        public static function enableAnsiColors(): void
        {
            self::$ansiColorsEnabled = true;
        }

        public static function disableAnsiColors(): void
        {
            self::$ansiColorsEnabled = false;
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
            self::out($prompt);
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
            self::out($prompt);
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
    }