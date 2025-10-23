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

    use InvalidArgumentException;
    use ncc\Enums\PackageStructure;

    class PackageReader
    {
        private string $filePath;
        private $fileHandle;
        private int $startOffset;

        /**
         * Public constructor for the PackageReader class.
         *
         * @param string $filePath The path to the package file. Must be a valid file path.
         */
        public function __construct(string $filePath)
        {
            $this->filePath = $filePath;
            if(!file_exists($this->filePath))
            {
                throw new InvalidArgumentException("File does not exist: " . $this->filePath);
            }

            if(!is_readable($this->filePath))
            {
                throw new InvalidArgumentException("File is not readable: " . $this->filePath);
            }

            $this->fileHandle = fopen($this->filePath, 'rb');
            if(!$this->fileHandle)
            {
                throw new InvalidArgumentException("Could not open file: " . $this->filePath);
            }

            $this->startOffset = $this->findPackageStartOffset();

            // Seek to the package start position
            if (fseek($this->fileHandle, $this->startOffset) !== 0)
            {
                fclose($this->fileHandle);
                throw new InvalidArgumentException("Could not seek to package start position: " . $this->startOffset);
            }
        }

        /**
         * Reads the package file and returns its contents as a string.
         *
         * @return int The contents of the package file.
         */
        private function findPackageStartOffset(): int
        {
            $searchSequence = PackageStructure::START_PACKAGE->value . PackageStructure::MAGIC_BYTES->value . PackageStructure::TERMINATE->value;
            $searchLength = strlen($searchSequence);

            $fileSize = filesize($this->filePath);
            if ($fileSize === false || $fileSize < $searchLength)
            {
                throw new InvalidArgumentException("File is too small to contain a valid package: " . $this->filePath);
            }

            $chunkSize = 8192;
            $buffer = '';
            $position = 0;

            while ($position < $fileSize)
            {
                $bytesToRead = min($chunkSize, $fileSize - $position);
                $newData = fread($this->fileHandle, $bytesToRead);

                if ($newData === false)
                {
                    throw new InvalidArgumentException("Error reading file: " . $this->filePath);
                }

                $buffer .= $newData;

                if (strlen($buffer) > $chunkSize + $searchLength)
                {
                    $buffer = substr($buffer, -$chunkSize - $searchLength);
                }

                $pos = strpos($buffer, $searchSequence);
                if ($pos !== false)
                {
                    $offset = $position - (strlen($buffer) - $pos);
                    return $offset;
                }

                $position += $bytesToRead;
            }

            throw new InvalidArgumentException("Package signature (START_PACKAGE + MAGIC_BYTES) not found in file: " . $this->filePath);
        }

        /**
         * Reads the package file and returns its contents as a string.
         *
         * @return void The contents of the package file.
         */
        public function __destruct()
        {
            if (is_resource($this->fileHandle))
            {
                fclose($this->fileHandle);
            }
        }
    }