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

    namespace ncc\Enums;

    use FilesystemIterator;
    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\ProjectConverters\ComposerProjectConverter;
    use ncc\ProjectConverters\LegacyProjectConverter;
    use RecursiveCallbackFilterIterator;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

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
            // Directories to skip during recursive search (commonly contain test/example project files)
            $skipDirectories = ['.github', 'tests', 'test', 'vendor', 'examples', 'docs', 'doc', 'samples', '.git'];
            
            // First, check if the file exists in the current directory
            $directFilePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->value;
            if (file_exists($directFilePath) && is_file($directFilePath))
            {
                return $directFilePath;
            }

            // If not found in current directory, recursively search subdirectories
            // but skip common directories that contain test/example fixtures
            $iterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
            $filteredIterator = new RecursiveCallbackFilterIterator($iterator, function ($current, $key, $iterator) use ($skipDirectories) {
                // Skip directories in the skip list
                if ($iterator->hasChildren() && in_array($current->getFilename(), $skipDirectories, true))
                {
                    return false;
                }
                return true;
            });
            
            foreach (new RecursiveIteratorIterator($filteredIterator, RecursiveIteratorIterator::SELF_FIRST) as $file)
            {
                if ($file->isFile() && $file->getFilename() === $this->value)
                {
                    return $file->getPathname();
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
         * Detects the project path by scanning the directory recursively for project files.
         *
         * @param string $path The path to search for project files.
         * @return string|null The path to the project file if found, null otherwise.
         */
        public static function detectProjectPath(string $path): ?string
        {
            foreach (self::cases() as $case)
            {
                if ($case === null)
                {
                    continue;
                }

                $filePath = $case->getFilePath($path);
                if ($filePath !== null)
                {
                    return $filePath;
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
