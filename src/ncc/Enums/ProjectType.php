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

    namespace ncc\Enums;

    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\ProjectConverters\ComposerProjectConverter;
    use ncc\ProjectConverters\LegacyProjectConverter;

    enum ProjectType : string
    {
        case NCC = 'project.yml';
        case NCC_V2 = 'project.json';
        case COMPOSER = 'composer.json';

        /**
         * Gets the full file path of the project file if it exists in the given path.
         *
         * @param string $path The path to search for the project file.
         * @return string|null The full file path if found, null otherwise.
         */
        public function getFilePath(string $path): ?string
        {
            // Directories to skip when searching for project files
            $skipDirs = ['build', 'dist', 'target', 'out'];
            
            // First, check if the file exists in the current directory
            $directFilePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->value;
            if (file_exists($directFilePath) && is_file($directFilePath))
            {
                return $directFilePath;
            }

            // For packagist packages, check one level deep (common structure)
            $items = @scandir($path);
            if ($items !== false)
            {
                foreach ($items as $item)
                {
                    if ($item === '.' || $item === '..')
                    {
                        continue;
                    }
                    
                    // Skip common build directories
                    if (in_array($item, $skipDirs, true))
                    {
                        continue;
                    }
                    
                    $subPath = $path . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($subPath))
                    {
                        $subFilePath = $subPath . DIRECTORY_SEPARATOR . $this->value;
                        if (file_exists($subFilePath) && is_file($subFilePath))
                        {
                            return $subFilePath;
                        }
                    }
                }
            }

            return null;
        }

        /**
         * Gets the appropriate project converter for the project type.
         *
         * @return AbstractProjectConverter|null The project converter instance or null if not applicable.
         */
        public function getConverter(): ?AbstractProjectConverter
        {
            return match ($this)
            {
                self::NCC_V2 => new LegacyProjectConverter(),
                self::COMPOSER => new ComposerProjectConverter(),
                default => null
            };
        }

        /**
         * Detects the project path by scanning the directory for project files.
         * Optimized to scan only once instead of per project type.
         *
         * @param string $path The path to search for project files.
         * @return string|null The path to the project file if found, null otherwise.
         */
        public static function detectProjectPath(string $path): ?string
        {
            // Build list of all project filenames to search for
            $projectFiles = [];
            foreach (self::cases() as $case)
            {
                $projectFiles[] = $case->value;
            }

            // First, check if any file exists in the root directory
            foreach ($projectFiles as $filename)
            {
                $directFilePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($directFilePath) && is_file($directFilePath))
                {
                    return $directFilePath;
                }
            }

            // For packagist packages, check one level deep (common structure)
            // This handles the case where packagist extracts to a subfolder
            $items = @scandir($path);
            if ($items !== false)
            {
                foreach ($items as $item)
                {
                    if ($item === '.' || $item === '..')
                    {
                        continue;
                    }
                    
                    $subPath = $path . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($subPath))
                    {
                        foreach ($projectFiles as $filename)
                        {
                            $subFilePath = $subPath . DIRECTORY_SEPARATOR . $filename;
                            if (file_exists($subFilePath) && is_file($subFilePath))
                            {
                                return $subFilePath;
                            }
                        }
                    }
                }
            }

            return null;
        }

        /**
         * Detects the project type based on the presence of specific files in the given path.
         *
         * @param string $path The path to check for project files.
         * @return ProjectType|null The detected project type.
         */
        public static function detectProjectType(string $path): ?ProjectType
        {
            $projectPath = self::detectProjectPath($path);
            if ($projectPath === null)
            {
                return null;
            }

            $filename = basename($projectPath);
            foreach (self::cases() as $case)
            {
                if ($case->value === $filename)
                {
                    return $case;
                }
            }

            return null;
        }
    }
