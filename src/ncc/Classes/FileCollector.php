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
    use FilesystemIterator;
    use InvalidArgumentException;
    use ncc\Classes\IO;
    use ncc\CLI\Logger;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use RuntimeException;
    use SplFileInfo;

    class FileCollector
    {
        /**
         * Recursively scans the given directory for files and returns the realpath of all files that match the include patterns and excluding
         * those that match the exclude patterns. For example to include all PHP and PHTML files while excluding test directories, you might call:
         * FileCollector::collectFiles('/path/to/project', ['*.php', '*.phtml'], ['/tests', '/vendor']);
         * This would return an array of realpath() strings for all .php and .phtml files in the project directory, excluding any files
         * located in /tests or /vendor directories.
         *
         * @param string $directory The directory to search for component files.
         * @param string[] $include The patterns of files to include (e.g., ['*.php', '*.phtml']).
         * @param string[] $exclude The patterns of files or directories to exclude. (e.g., ['*.md', '/tests', '/tmp/*'])
         * @return string[] An array of realpath() strings for each collected file.
         * @throws InvalidArgumentException If the source path is not a valid directory.
         * @throws RuntimeException If an error occurs while accessing the filesystem.
         */
        public static function collectFiles(string $directory, array $include=[], array $exclude=[]): array
        {
            Logger::getLogger()->debug(sprintf('Collecting files from: %s', $directory));
            Logger::getLogger()->verbose(sprintf('Include patterns: %d, Exclude patterns: %d', count($include), count($exclude)));
            
            // Validate the directory
            if (!IO::isDir($directory))
            {
                throw new InvalidArgumentException(sprintf('The provided path \'%s\' is not a valid directory.', $directory));
            }

            $realDirectory = realpath($directory);
            if ($realDirectory === false)
            {
                throw new RuntimeException(sprintf('Failed to resolve the real path for directory \'%s\'.', $directory));
            }

            $collectedFiles = [];

            try
            {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($realDirectory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file)
                {
                    if (!$file instanceof SplFileInfo || !$file->isFile())
                    {
                        continue;
                    }

                    $filePath = $file->getRealPath();
                    if ($filePath === false)
                    {
                        continue;
                    }

                    // Get the relative path from the base directory
                    $relativePath = self::getRelativePath($realDirectory, $filePath);

                    // Check if the file should be excluded
                    if (self::isExcluded($relativePath, $exclude))
                    {
                        continue;
                    }

                    // Check if the file matches any include pattern (if patterns are provided)
                    if (!empty($include) && !self::isIncluded($relativePath, $include))
                    {
                        continue;
                    }

                    $collectedFiles[] = $filePath;
                }
            }
            catch (Exception $e)
            {
                throw new RuntimeException(sprintf('Error while scanning directory \'%s\': %s', $directory, $e->getMessage()), 0, $e);
            }
            
            Logger::getLogger()->verbose(sprintf('Collected %d files from %s', count($collectedFiles), $directory));

            return $collectedFiles;
        }

        /**
         * Gets the relative path from a base directory to a target path.
         *
         * @param string $baseDir The base directory path.
         * @param string $targetPath The target file path.
         * @return string The relative path.
         */
        private static function getRelativePath(string $baseDir, string $targetPath): string
        {
            $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            
            if (str_starts_with($targetPath, $baseDir))
            {
                return substr($targetPath, strlen($baseDir));
            }

            return $targetPath;
        }

        /**
         * Checks if a file path matches any of the include patterns.
         *
         * @param string $relativePath The relative file path to check.
         * @param string[] $includePatterns Array of include patterns (supports wildcards).
         * @return bool True if the path matches any include pattern, false otherwise.
         */
        private static function isIncluded(string $relativePath, array $includePatterns): bool
        {
            foreach ($includePatterns as $pattern)
            {
                if (self::matchesPattern($relativePath, $pattern))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if a file path matches any of the exclusion patterns.
         *
         * @param string $relativePath The relative file path to check.
         * @param string[] $excludePatterns Array of exclusion patterns (supports wildcards).
         * @return bool True if the path should be excluded, false otherwise.
         */
        private static function isExcluded(string $relativePath, array $excludePatterns): bool
        {
            foreach ($excludePatterns as $pattern)
            {
                if (self::matchesPattern($relativePath, $pattern))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if a relative path matches a given pattern.
         * Supports glob patterns like *.php, /tests/*, etc.
         *
         * @param string $relativePath The relative file path to check.
         * @param string $pattern The pattern to match against.
         * @return bool True if the path matches the pattern, false otherwise.
         */
        private static function matchesPattern(string $relativePath, string $pattern): bool
        {
            // Normalize path separators to forward slashes for consistent matching
            $normalizedPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $normalizedPattern = str_replace(DIRECTORY_SEPARATOR, '/', $pattern);

            // If pattern starts with '/', it's a directory-based pattern
            if (str_starts_with($normalizedPattern, '/'))
            {
                // Match from the beginning of the path
                $normalizedPath = '/' . $normalizedPath;
            }

            // Use fnmatch for glob-style pattern matching
            return fnmatch($normalizedPattern, $normalizedPath);
        }

    }