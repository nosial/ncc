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
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageException;

    class PackageWriter
    {
        private $fileHandler;
        private WritingMode $writingMode;

        /**
         * Constructs a new PackageWriter instance.
         *
         * @param string $filePath The path to the package file to create or overwrite.
         * @param bool $overwrite Whether to overwrite the file if it already exists. Default is true.
         * @throws PackageException if the file cannot be created or opened.
         * @throws IOException Thrown if there was an IO error
         */
        public function __construct(string $filePath, bool $overwrite=true)
        {
            // Delete the file if it already exists, prevent overwriting if not allowed
            if(file_exists($filePath))
            {
                if(!$overwrite)
                {
                    throw new PackageException("File already exists: " . $filePath);
                }
                unlink($filePath);
            }


            // Create the file
            IO::mkdir(dirname($filePath));
            touch($filePath);
            $this->fileHandler = fopen($filePath, 'a+b');
            if($this->fileHandler === false)
            {
                throw new PackageException("Could not open file for writing: " . $filePath);
            }

            // Write the initial signature
            fwrite($this->fileHandler, PackageStructure::START_PACKAGE->value); // Beginning of package
            fwrite($this->fileHandler, PackageStructure::MAGIC_BYTES->value); // Magic bytes
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value); // Terminate
            // The magic bytes would be "\xA0\x4E\x43\x43\x50\x4B\x47\xE0\xE0"

            // Write the package structure version
            fwrite($this->fileHandler, PackageStructure::PACKAGE_VERSION->value);
            fwrite($this->fileHandler, 1000); // Version 1000
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value);

            // Set the writing mode to HEADER since this is the first thing we write after the signature
            $this->writingMode = WritingMode::HEADER;
            fwrite($this->fileHandler, PackageStructure::HEADER->value);
        }

        /**
         * Gets the current writing mode of the package writer.
         *
         * @return WritingMode The current writing mode.
         */
        public function getWritingMode(): WritingMode
        {
            return $this->writingMode;
        }

        /**
         * Checks if the package file is closed.
         *
         * @return bool True if the file is closed, false otherwise.
         */
        public function isClosed(): bool
        {
            return !is_resource($this->fileHandler);
        }

        /**
         * Writes data to the package in the current writing mode.
         *
         * @param string $data The data to write to the package.
         * @param string|null $name Optional name for the data, used in certain writing modes.
         * @return void
         * @throws PackageException if the file is not open or if the writing mode is unknown.
         */
        public function writeData(string $data, ?string $name=null): void
        {
            if($this->isClosed())
            {
                throw new PackageException("File is not open.");
            }

            switch($this->writingMode)
            {
                case WritingMode::ASSEMBLY:
                case WritingMode::HEADER:
                    fwrite($this->fileHandler, $this->createSizePrefix(strlen($data))); // 10-byte Size prefix
                    fwrite($this->fileHandler, PackageStructure::SOFT_TERMINATE->value); // Soft terminate
                    fwrite($this->fileHandler, $data); // The data
                    fwrite($this->fileHandler, PackageStructure::TERMINATE->value); // Terminate
                    $this->endSection();
                    break;

                case WritingMode::EXECUTION_UNITS:
                case WritingMode::COMPONENTS:
                case WritingMode::RESOURCES:
                    fwrite($this->fileHandler, $name);
                    fwrite($this->fileHandler, PackageStructure::SOFT_TERMINATE->value);
                    fwrite($this->fileHandler, $this->createSizePrefix(strlen($data)));
                    fwrite($this->fileHandler, PackageStructure::SOFT_TERMINATE->value);
                    fwrite($this->fileHandler, $data);
                    fwrite($this->fileHandler, PackageStructure::TERMINATE->value);
                    break;
            }
        }

        /**
         * Ends the current section and prepares to write the next one.
         *
         * @return void
         * @throws PackageException if the file is not open or if the writing mode is unknown.
         */
        public function endSection(): void
        {
            if($this->isClosed())
            {
                throw new PackageException("File is not open.");
            }

            switch($this->writingMode)
            {
                case WritingMode::HEADER:
                    fwrite($this->fileHandler, PackageStructure::ASSEMBLY->value);
                    $this->writingMode = WritingMode::ASSEMBLY;
                    break;

                case WritingMode::ASSEMBLY:
                    fwrite($this->fileHandler, PackageStructure::EXECUTION_UNITS->value);
                    $this->writingMode = WritingMode::EXECUTION_UNITS;
                    break;

                case WritingMode::EXECUTION_UNITS:
                    fwrite($this->fileHandler, PackageStructure::COMPONENTS->value);
                    $this->writingMode = WritingMode::COMPONENTS;
                    break;

                case WritingMode::COMPONENTS:
                    fwrite($this->fileHandler, PackageStructure::RESOURCES->value);
                    $this->writingMode = WritingMode::RESOURCES;
                    break;

                case WritingMode::RESOURCES:
                    $this->close();
                    break;
            }
        }

        /**
         * Closes the package file, writing the end signature.
         *
         * @return void
         */
        public function close(): void
        {
            if(!is_resource($this->fileHandler))
            {
                return;
            }

            // Write the end signature and close the file
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value);
            fclose($this->fileHandler);
            $this->fileHandler = null;
        }

        /**
         * Creates a 10-byte size prefix for the given byte size.
         *
         * @param int $bytes The size in bytes to create the prefix for.
         * @return string A 10-byte string representing the size, padded with null bytes.
         * @throws InvalidArgumentException if the size exceeds the maximum allowed value.
         */
        private function createSizePrefix(int $bytes): string
        {
            // Maximum size that can fit in 10 bytes: 9,999,999,999
            if ($bytes > 9999999999)
            {
                throw new InvalidArgumentException("The file is too large for the package, size exceeds maximum allowed bytes (9,999,999,999 bytes)");
            }

            // Convert the integer to a string and pad with null bytes to make it 10 bytes
            $sizeStr = (string)$bytes;
            return $sizeStr . str_repeat("\x00", (10 - strlen($sizeStr)));
        }

        /**
         * Destructor to ensure the file is closed when the object is destroyed.
         */
        public function __destruct()
        {
            // Ensure the file is closed
            if(is_resource($this->fileHandler))
            {
                $this->close();
            }
        }
    }