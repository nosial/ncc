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

    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;

    class SymlinkManager
    {
        /**
         * Creates a symlink for a package in the system's PATH
         *
         * @param string $projectName The project name (e.g., "ConfigLib")
         * @param string $packageName The package name (e.g., "com.example.configlib")
         * @param bool $force Force creation even if a symlink already exists
         * @return string|null The path to the created symlink, or null if creation was skipped
         * @throws OperationException If symlink creation fails
         */
        public static function createSymlink(string $projectName, string $packageName, bool $force = false): ?string
        {
            Logger::getLogger()?->verbose(sprintf('Creating symlink for package %s (project: %s)', $packageName, $projectName));

            // Convert project name to lowercase for the symlink name
            $symlinkName = strtolower($projectName);
            
            // Find suitable bin directory
            $binDir = self::findBinDirectory();
            if ($binDir === null)
            {
                Logger::getLogger()?->warning('No suitable bin directory found in PATH for symlink creation');
                return null;
            }
            
            $symlinkPath = $binDir . DIRECTORY_SEPARATOR . $symlinkName;
            Logger::getLogger()?->debug(sprintf('Symlink path: %s', $symlinkPath));
            
            // Check if symlink already exists
            if (IO::exists($symlinkPath))
            {
                // Check if it's managed by ncc (contains our marker)
                if (IO::isFile($symlinkPath) && IO::isReadable($symlinkPath))
                {
                    $content = IO::readFile($symlinkPath);
                    $isNccManaged = strpos($content, '# NCC_MANAGED_SYMLINK') !== false;
                    
                    if (!$isNccManaged && !$force)
                    {
                        Logger::getLogger()?->warning(sprintf('Symlink %s already exists and is not managed by ncc. Use --force-symlink to overwrite.', $symlinkPath));
                        return null;
                    }
                    
                    if (!$force)
                    {
                        Logger::getLogger()?->verbose(sprintf('Symlink %s already exists and is managed by ncc', $symlinkPath));
                        return $symlinkPath;
                    }
                }
                else if (!$force)
                {
                    Logger::getLogger()?->warning(sprintf('Path %s already exists but is not a regular file. Use --force-symlink to overwrite.', $symlinkPath));
                    return null;
                }
                
                // Remove existing file/symlink
                Logger::getLogger()?->debug(sprintf('Removing existing symlink: %s', $symlinkPath));
                IO::delete($symlinkPath);
            }
            
            // Create the shell script
            $scriptContent = self::generateSymlinkScript($packageName);
            
            // Write the script
            try {
                IO::writeFile($symlinkPath, $scriptContent);
            } catch (IOException $e) {
                throw new OperationException(sprintf('Failed to create symlink script at: %s', $symlinkPath));
            }
            
            // Make it executable
            try {
                IO::chmod($symlinkPath, 0755);
            } catch (IOException $e) {
                throw new OperationException(sprintf('Failed to make symlink executable: %s', $symlinkPath));
            }
            
            Logger::getLogger()?->verbose(sprintf('Successfully created symlink: %s', $symlinkPath));
            return $symlinkPath;
        }

        /**
         * Removes a symlink for a package
         *
         * @param string $projectName The project name
         * @return bool True if the symlink was removed, false if it didn't exist or wasn't managed by ncc
         * @throws OperationException If symlink removal fails
         */
        public static function removeSymlink(string $projectName): bool
        {
            $symlinkName = strtolower($projectName);
            
            // Check common bin directories
            $binDirectories = self::getPossibleBinDirectories();
            
            foreach ($binDirectories as $binDir)
            {
                if (!IO::isDirectory($binDir))
                {
                    continue;
                }
                
                $symlinkPath = $binDir . DIRECTORY_SEPARATOR . $symlinkName;
                
                if (!IO::exists($symlinkPath))
                {
                    continue;
                }
                
                // Check if it's managed by ncc
                if (IO::isFile($symlinkPath) && IO::isReadable($symlinkPath))
                {
                    $content = IO::readFile($symlinkPath);
                    if (strpos($content, '# NCC_MANAGED_SYMLINK') !== false)
                    {
                        Logger::getLogger()?->verbose(sprintf('Removing ncc-managed symlink: %s', $symlinkPath));
                        try {
                            IO::delete($symlinkPath);
                        } catch (IOException $e) {
                            throw new OperationException(sprintf('Failed to remove symlink: %s', $symlinkPath));
                        }
                        return true;
                    }
                }
            }
            
            Logger::getLogger()?->debug(sprintf('No ncc-managed symlink found for project: %s', $projectName));
            return false;
        }

        /**
         * Generates the shell script content for the symlink
         *
         * @param string $packageName The package name
         * @return string The script content
         */
        private static function generateSymlinkScript(string $packageName): string
        {
            return <<<BASH
#!/bin/sh
# NCC_MANAGED_SYMLINK
# This symlink is managed by ncc. Do not edit manually.
# Package: $packageName

exec ncc exec --package="$packageName" --args "\$@"
BASH;
        }

        /**
         * Finds a suitable bin directory in the system's PATH
         *
         * @return string|null The path to a suitable bin directory, or null if none found
         */
        private static function findBinDirectory(): ?string
        {
            $binDirectories = self::getPossibleBinDirectories();
            
            foreach ($binDirectories as $dir)
            {
                if (IO::isDirectory($dir) && IO::isWritable($dir))
                {
                    Logger::getLogger()?->debug(sprintf('Found writable bin directory: %s', $dir));
                    return $dir;
                }
            }
            
            Logger::getLogger()?->debug('No writable bin directory found');
            return null;
        }

        /**
         * Gets a list of possible bin directories in order of preference
         *
         * @return array<string> Array of directory paths
         */
        private static function getPossibleBinDirectories(): array
        {
            $directories = [
                '/usr/local/bin',
                '/usr/bin',
                '/bin'
            ];
            
            // Add user-specific directories if HOME is set
            $home = getenv('HOME');
            if ($home !== false && !empty($home))
            {
                array_unshift($directories, $home . '/.local/bin');
            }
            
            return $directories;
        }

        /**
         * Checks if a symlink exists for a project
         *
         * @param string $projectName The project name
         * @return bool True if a symlink exists (regardless of whether it's managed by ncc)
         */
        public static function symlinkExists(string $projectName): bool
        {
            $symlinkName = strtolower($projectName);
            $binDirectories = self::getPossibleBinDirectories();
            
            foreach ($binDirectories as $binDir)
            {
                if (!IO::isDirectory($binDir))
                {
                    continue;
                }
                
                $symlinkPath = $binDir . DIRECTORY_SEPARATOR . $symlinkName;
                if (IO::exists($symlinkPath))
                {
                    return true;
                }
            }
            
            return false;
        }

        /**
         * Checks if a symlink is managed by ncc
         *
         * @param string $projectName The project name
         * @return bool True if the symlink exists and is managed by ncc
         */
        public static function isNccManaged(string $projectName): bool
        {
            $symlinkName = strtolower($projectName);
            $binDirectories = self::getPossibleBinDirectories();
            
            foreach ($binDirectories as $binDir)
            {
                if (!IO::isDirectory($binDir))
                {
                    continue;
                }
                
                $symlinkPath = $binDir . DIRECTORY_SEPARATOR . $symlinkName;
                
                if (IO::exists($symlinkPath) && IO::isFile($symlinkPath) && IO::isReadable($symlinkPath))
                {
                    $content = IO::readFile($symlinkPath);
                    if (strpos($content, '# NCC_MANAGED_SYMLINK') !== false)
                    {
                        return true;
                    }
                }
            }
            
            return false;
        }
    }
