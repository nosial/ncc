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
    use Phar;
    use PharData;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use RuntimeException;

    class PharExtractor
    {
        /**
         * Cache of extracted phar files to avoid re-extraction
         * @var array<string, array<string>>
         */
        private static array $extractionCache = [];

        /**
         * Set of phar files that have been extracted (to exclude from further collection)
         * @var array<string, bool>
         */
        private static array $extractedPhars = [];

        /**
         * Extracts a .phar file and returns an array of all file paths within it.
         * Files are extracted to a temporary location and their paths are returned.
         *
         * @param string $pharPath The path to the .phar file
         * @param string $baseDir The base directory where the phar file resides (for relative path calculation)
         * @return array An array of file paths from within the phar archive
         * @throws RuntimeException If the phar cannot be opened or extracted
         */
        public static function extractPharFiles(string $pharPath, string $baseDir): array
        {
            Logger::getLogger()?->debug(sprintf('Extracting phar file: %s', $pharPath));

            // Check cache first
            $cacheKey = realpath($pharPath);
            if ($cacheKey && isset(self::$extractionCache[$cacheKey]))
            {
                Logger::getLogger()?->verbose(sprintf('Using cached extraction for: %s', $pharPath));
                return self::$extractionCache[$cacheKey];
            }

            if (!IO::exists($pharPath))
            {
                throw new RuntimeException(sprintf('Phar file does not exist: %s', $pharPath));
            }

            try
            {
                // Create a temporary directory for extraction
                $tempDir = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . 'phar_extraction_' . uniqid();
                IO::mkdir($tempDir, true);

                Logger::getLogger()?->verbose(sprintf('Extracting phar to temporary directory: %s', $tempDir));

                // Try to open as Phar first, fall back to PharData for non-executable archives
                $phar = null;
                try
                {
                    $phar = new Phar($pharPath);
                }
                catch (Exception $e)
                {
                    Logger::getLogger()?->debug(sprintf('Could not open as Phar, trying PharData: %s', $e->getMessage()));
                    try
                    {
                        $phar = new PharData($pharPath);
                    }
                    catch (Exception $e2)
                    {
                        throw new RuntimeException(sprintf('Failed to open phar file %s: %s', $pharPath, $e2->getMessage()), 0, $e2);
                    }
                }

                // Extract the phar to the temporary directory
                $phar->extractTo($tempDir, null, true);
                Logger::getLogger()?->verbose(sprintf('Successfully extracted phar to: %s', $tempDir));

                // Collect all extracted files
                $extractedFiles = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file)
                {
                    if ($file->isFile())
                    {
                        $filePath = $file->getRealPath();
                        if ($filePath !== false)
                        {
                            $extractedFiles[] = $filePath;
                        }
                    }
                }

                Logger::getLogger()?->verbose(sprintf('Collected %d files from phar: %s', count($extractedFiles), $pharPath));

                // Cache the result
                if ($cacheKey)
                {
                    self::$extractionCache[$cacheKey] = $extractedFiles;
                    // Mark this phar as extracted
                    self::$extractedPhars[$cacheKey] = true;
                }

                // Register for cleanup on shutdown
                ShutdownHandler::flagTemporary($tempDir);

                return $extractedFiles;
            }
            catch (Exception $e)
            {
                throw new RuntimeException(sprintf('Error extracting phar file %s: %s', $pharPath, $e->getMessage()), 0, $e);
            }
        }

        /**
         * Checks if a file is a phar archive based on its extension and/or content.
         *
         * @param string $filePath The path to the file to check
         * @return bool True if the file is a phar archive, false otherwise
         */
        public static function isPharFile(string $filePath): bool
        {
            // Quick check by extension
            if (str_ends_with(strtolower($filePath), '.phar'))
            {
                return true;
            }

            // Additional check: try to read the file header
            // Phar files start with a recognizable structure
            if (IO::isReadable($filePath))
            {
                try
                {
                    $handle = fopen($filePath, 'rb');
                    if ($handle !== false)
                    {
                        // Read first few bytes to check for phar signature
                        $header = fread($handle, 4);
                        fclose($handle);

                        // Check for PHP opening tag which pharsofen have
                        if ($header !== false && str_starts_with($header, '<?ph'))
                        {
                            // Try to verify it's a phar by attempting to open it
                            try
                            {
                                new Phar($filePath);
                                return true;
                            }
                            catch (Exception $e)
                            {
                                try
                                {
                                    new PharData($filePath);
                                    return true;
                                }
                                catch (Exception $e2)
                                {
                                    // Not a valid phar
                                }
                            }
                        }
                    }
                }
                catch (Exception $e)
                {
                    // If we can't read it, assume it's not a phar
                }
            }

            return false;
        }

        /**
         * Checks if a phar file has been extracted.
         *
         * @param string $pharPath The path to check
         * @return bool True if the phar has been extracted, false otherwise
         */
        public static function wasExtracted(string $pharPath): bool
        {
            $realPath = realpath($pharPath);
            return $realPath !== false && isset(self::$extractedPhars[$realPath]);
        }

        /**
         * Clears the extraction cache.
         *
         * @return void
         */
        public static function clearCache(): void
        {
            self::$extractionCache = [];
            self::$extractedPhars = [];
        }

        /**
         * Resolves the actual entry point from a phar file.
         * Parses the phar stub to determine what file is actually executed.
         *
         * @param string $pharPath The path to the .phar file
         * @return string|null The relative entry point within the phar, or null if it cannot be determined
         */
        public static function resolvePharEntryPoint(string $pharPath): ?string
        {
            Logger::getLogger()?->debug(sprintf('Resolving entry point for phar: %s', $pharPath));

            if (!IO::exists($pharPath))
            {
                Logger::getLogger()?->warning(sprintf('Phar file does not exist: %s', $pharPath));
                return null;
            }

            try
            {
                // Try to open as Phar first
                $phar = null;
                try
                {
                    $phar = new Phar($pharPath);
                }
                catch (Exception $e)
                {
                    Logger::getLogger()?->debug(sprintf('Could not open as Phar, trying PharData: %s', $e->getMessage()));
                    try
                    {
                        $phar = new PharData($pharPath);
                    }
                    catch (Exception $e2)
                    {
                        Logger::getLogger()?->warning(sprintf('Failed to open phar file %s: %s', $pharPath, $e2->getMessage()));
                        return null;
                    }
                }

                // Get the stub (the code that executes when the phar is run)
                $stub = $phar->getStub();

                // Parse the stub to find the entry point
                // Common patterns:
                // 1. Phar::webPhar(null, 'index.php')
                // 2. require 'phar://' . __FILE__ . '/index.php'
                // 3. Phar::mapPhar(); include 'phar://alias/index.php'
                
                // Pattern 1: Phar::webPhar or Phar::mungServer with file argument
                if (preg_match('/Phar::(?:webPhar|mungServer)\s*\([^,]*,\s*[\'"]([^\'"]+)[\'"]/', $stub, $matches))
                {
                    Logger::getLogger()?->verbose(sprintf('Found entry point via Phar::webPhar/mungServer: %s', $matches[1]));
                    return $matches[1];
                }

                // Pattern 2: require/include with phar:// protocol
                if (preg_match('/(?:require|include)(?:_once)?\s+[\'"]phar:\/\/[^\'"]*\/([^\'"]+)[\'"]/', $stub, $matches))
                {
                    Logger::getLogger()?->verbose(sprintf('Found entry point via phar:// require: %s', $matches[1]));
                    return $matches[1];
                }

                // Pattern 3: Look for Phar::createDefaultStub pattern
                // The default stub includes the main file specified during creation
                if (preg_match('/__HALT_COMPILER/', $stub))
                {
                    // Try to find any .php file mentioned in the stub before __HALT_COMPILER
                    if (preg_match('/[\'"]([^\/\'"]+\.php)[\'"]/', $stub, $matches))
                    {
                        Logger::getLogger()?->verbose(sprintf('Found entry point from stub: %s', $matches[1]));
                        return $matches[1];
                    }
                }

                // Fallback: Check for common entry point files in the phar root
                $commonEntryPoints = ['index.php', 'main.php', 'bootstrap.php', 'init.php'];
                foreach ($commonEntryPoints as $entryPoint)
                {
                    if (isset($phar[$entryPoint]))
                    {
                        Logger::getLogger()?->verbose(sprintf('Using common entry point: %s', $entryPoint));
                        return $entryPoint;
                    }
                }

                Logger::getLogger()?->warning(sprintf('Could not determine entry point for phar: %s', $pharPath));
                return null;
            }
            catch (Exception $e)
            {
                Logger::getLogger()?->warning(sprintf('Error resolving phar entry point %s: %s', $pharPath, $e->getMessage()));
                return null;
            }
        }
    }
