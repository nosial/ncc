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

    use InvalidArgumentException;
    use ncc\Enums\PackageStructure;
    use ncc\Enums\WritingMode;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;

    class PackageWriter
    {
        private $fileHandler;
        private WritingMode $writingMode;
        private bool $sectionStarted;

        /**
         * Constructs a new PackageWriter instance and initializes the package header.
         *
         * The constructor writes the package signature in the following order:
         * 1. START_PACKAGE marker
         * 2. MAGIC_BYTES (NCCPKG identifier)
         * 3. TERMINATE byte
         * 4. PACKAGE_VERSION marker
         * 5. Version string (e.g., "1000")
         * 6. TERMINATE byte
         *
         * After initialization, the writer is in HEADER mode but the HEADER section
         * marker is not written until actual data is provided via writeData().
         *
         * @param string $filePath The path to the package file to create or overwrite.
         * @param bool $overwrite Whether to overwrite the file if it already exists. Default is true.
         * @throws OperationException Thrown if the file already exists and overwrite is false
         * @throws IOException Thrown if there are issues creating or writing to the file.
         */
        public function __construct(string $filePath, bool $overwrite=true)
        {
            Logger::getLogger()?->debug(sprintf('Initializing PackageWriter for: %s', $filePath));
            
            // Delete the file if it already exists, prevent overwriting if not allowed
            if(IO::exists($filePath))
            {
                if(!$overwrite)
                {
                    throw new OperationException("File already exists: " . $filePath);
                }
                Logger::getLogger()?->verbose('Overwriting existing file');
                IO::delete($filePath, false);
            }

            // Create the file
            Logger::getLogger()?->verbose('Creating package file');
            IO::createDirectory(dirname($filePath));
            IO::touch($filePath);
            $this->fileHandler = fopen($filePath, 'a+b');
            if($this->fileHandler === false)
            {
                throw new OperationException("Could not open file for writing: " . $filePath);
            }

            // Write the package header signature
            // Format: START_PACKAGE + MAGIC_BYTES + TERMINATE
            fwrite($this->fileHandler, PackageStructure::START_PACKAGE->value);
            fwrite($this->fileHandler, PackageStructure::MAGIC_BYTES->value);
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value);

            // Write the package structure version
            // Format: PACKAGE_VERSION + "1000" + TERMINATE
            fwrite($this->fileHandler, PackageStructure::PACKAGE_VERSION->value);
            fwrite($this->fileHandler, '1000'); // Version 1000
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value);

            // Initialize the writing mode to HEADER
            // Note: The HEADER section marker is NOT written yet - it will be written
            // only when writeData() is first called to avoid empty section markers
            $this->writingMode = WritingMode::HEADER;
            $this->sectionStarted = false;
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
         * For HEADER and ASSEMBLY modes:
         * - Writes the section marker (if not already written)
         * - Writes size prefix (packed integer)
         * - Writes SOFT_TERMINATE
         * - Writes the data payload
         * - Writes TERMINATE
         * - The section can only contain one data block and is automatically ended
         *
         * For EXECUTION_UNITS, COMPONENTS, and RESOURCES modes:
         * - Writes the section marker on first call (if not already written)
         * - Writes the name (ASCII string)
         * - Writes SOFT_TERMINATE
         * - Writes size prefix (packed integer)
         * - Writes SOFT_TERMINATE
         * - Writes the data payload
         * - Writes TERMINATE
         * - Multiple items can be written in the same section
         *
         * @param string $data The data to write to the package.
         * @param string|null $name The name identifier for the data. Required for EXECUTION_UNITS, COMPONENTS, and RESOURCES modes.
         * @return void
         */
        public function writeData(string $data, ?string $name=null): void
        {
            if($this->isClosed())
            {
                throw new OperationException("File is not open.");
            }

            switch($this->writingMode)
            {
                case WritingMode::HEADER:
                case WritingMode::ASSEMBLY:
                    // Write the section marker if this is the first data for this section
                    if(!$this->sectionStarted)
                    {
                        fwrite($this->fileHandler, $this->getSectionMarker());
                        $this->sectionStarted = true;
                    }

                    // Write data in format: [SIZE][SOFT_TERMINATE][DATA][TERMINATE]
                    fwrite($this->fileHandler, $this->createSizePrefix(strlen($data)));
                    fwrite($this->fileHandler, PackageStructure::SOFT_TERMINATE->value);
                    fwrite($this->fileHandler, $data);
                    fwrite($this->fileHandler, PackageStructure::TERMINATE->value);

                    // HEADER and ASSEMBLY can only contain one data block, so end the section automatically
                    $this->endSection();
                    break;

                case WritingMode::EXECUTION_UNITS:
                case WritingMode::COMPONENTS:
                case WritingMode::RESOURCES:
                    // Name is required for collection-type sections
                    if($name === null)
                    {
                        throw new OperationException("Name is required for " . $this->writingMode->name . " mode.");
                    }

                    // Write the section marker if this is the first item in this section
                    if(!$this->sectionStarted)
                    {
                        fwrite($this->fileHandler, $this->getSectionMarker());
                        $this->sectionStarted = true;
                    }

                    // Write data in format: [NAME][SOFT_TERMINATE][SIZE][SOFT_TERMINATE][DATA][TERMINATE]
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
         * Ends the current section and transitions to the next writing mode.
         *
         * This method advances through the section sequence:
         * HEADER -> ASSEMBLY -> EXECUTION_UNITS -> COMPONENTS -> RESOURCES -> CLOSED
         *
         * Important: This does NOT write section markers for the next section.
         * Section markers are only written when actual data is provided via writeData().
         * This allows empty sections to be skipped entirely, as per the package specification.
         *
         * @return void
         */
        public function endSection(): void
        {
            if($this->isClosed())
            {
                throw new OperationException("File is not open.");
            }

            // Transition to the next section mode without writing the section marker
            switch($this->writingMode)
            {
                case WritingMode::HEADER:
                    $this->writingMode = WritingMode::ASSEMBLY;
                    $this->sectionStarted = false;
                    break;

                case WritingMode::ASSEMBLY:
                    $this->writingMode = WritingMode::EXECUTION_UNITS;
                    $this->sectionStarted = false;
                    break;

                case WritingMode::EXECUTION_UNITS:
                    $this->writingMode = WritingMode::COMPONENTS;
                    $this->sectionStarted = false;
                    break;

                case WritingMode::COMPONENTS:
                    $this->writingMode = WritingMode::RESOURCES;
                    $this->sectionStarted = false;
                    break;

                case WritingMode::RESOURCES:
                    // Resources is the last section, so close the package
                    $this->close();
                    break;
            }
        }

        /**
         * Closes the package file, writing the final termination byte.
         *
         * This writes the final TERMINATE byte that marks the end of the package
         * and closes the file handle. This method is idempotent - calling it
         * multiple times has no adverse effects.
         *
         * @return void
         */
        public function close(): void
        {
            if(!is_resource($this->fileHandler))
            {
                return;
            }

            // Write the final termination byte to mark the end of the package
            fwrite($this->fileHandler, PackageStructure::TERMINATE->value);
            fclose($this->fileHandler);
            $this->fileHandler = null;
        }

        /**
         * Gets the PackageStructure marker for the current writing mode.
         *
         * @return string The section marker corresponding to the current writing mode.
         */
        private function getSectionMarker(): string
        {
            return match($this->writingMode)
            {
                WritingMode::HEADER => PackageStructure::HEADER->value,
                WritingMode::ASSEMBLY => PackageStructure::ASSEMBLY->value,
                WritingMode::EXECUTION_UNITS => PackageStructure::EXECUTION_UNITS->value,
                WritingMode::COMPONENTS => PackageStructure::COMPONENTS->value,
                WritingMode::RESOURCES => PackageStructure::RESOURCES->value,
            };
        }

        /**
         * Creates a size prefix for the given byte size.
         *
         * The size is packed as a machine-dependent long integer in little-endian byte order
         * using PHP's pack() function with the 'P' format (size_t equivalent).
         *
         * @param int $bytes The size in bytes.
         * @return string The packed size prefix as a binary string.
         * @throws InvalidArgumentException if the size exceeds PHP_INT_MAX or is negative.
         */
        private function createSizePrefix(int $bytes): string
        {
            if ($bytes > PHP_INT_MAX)
            {
                throw new InvalidArgumentException(sprintf("The file is too large for the package, size exceeds maximum allowed bytes (%d bytes)", PHP_INT_MAX));
            }
            elseif($bytes < 0)
            {
                throw new InvalidArgumentException("The size cannot be negative");
            }

            return pack('P', $bytes);
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