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
    use ncc\Interfaces\ReferenceInterface;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\ExecutionUnitReference;
    use ncc\Objects\Package\Header;
    use ncc\Objects\Package\ResourceReference;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Project\ExecutionUnit;
    use RuntimeException;
    use function msgpack_unpack;

    class PackageReader
    {
        private string $filePath;
        private $fileHandle;
        private int $startOffset;
        private ?string $packageVersion = null;
        private ?Header $header = null;
        private ?Assembly $assembly = null;
        /** @var array<string, ExecutionUnitReference> */
        private array $executionUnitReferences = [];
        /** @var array<string, ComponentReference> */
        private array $componentReferences = [];
        /** @var array<string, ResourceReference> */
        private array $resourceReferences = [];
        private bool $parsed = false;

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
         * Gets the package version.
         *
         * @return string The package version.
         */
        public function getPackageVersion(): string
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->packageVersion;
        }

        /**
         * Gets the package header.
         *
         * @return Header|null The header object or null if not present.
         */
        public function getHeader(): ?Header
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->header;
        }

        /**
         * Gets the package assembly.
         *
         * @return Assembly|null The assembly object or null if not present.
         */
        public function getAssembly(): ?Assembly
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->assembly;
        }

        /**
         * Gets all execution unit references.
         *
         * @return ExecutionUnitReference[] Array of execution unit references.
         */
        public function getExecutionUnitReferences(): array
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->executionUnitReferences;
        }

        /**
         * Gets all component references.
         *
         * @return ComponentReference[] Array of component references.
         */
        public function getComponentReferences(): array
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->componentReferences;
        }

        /**
         * Gets all resource references.
         *
         * @return ResourceReference[] Array of resource references.
         */
        public function getResourceReferences(): array
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->resourceReferences;
        }

        /**
         * Reads an execution unit by its reference.
         *
         * @param ExecutionUnitReference $reference The execution unit reference.
         * @return ExecutionUnit The execution unit object.
         */
        public function readExecutionUnit(ExecutionUnitReference $reference): ExecutionUnit
        {
            fseek($this->fileHandle, $reference->getOffset());
            $data = fread($this->fileHandle, $reference->getSize());

            if(strlen($data) !== $reference->getSize())
            {
                throw new RuntimeException("Failed to read execution unit data");
            }

            return ExecutionUnit::fromArray(msgpack_unpack($data));
        }

        /**
         * Reads a component by its reference.
         *
         * @param ComponentReference $reference The component reference.
         * @return string The component data.
         */
        public function readComponent(ComponentReference $reference): string
        {
            fseek($this->fileHandle, $reference->getOffset());
            $data = fread($this->fileHandle, $reference->getSize());

            if(strlen($data) !== $reference->getSize())
            {
                throw new RuntimeException("Failed to read component data");
            }

            return $data;
        }

        /**
         * Reads a resource by its reference.
         *
         * @param ResourceReference $reference The resource reference.
         * @return string The resource data.
         */
        public function readResource(ResourceReference $reference): string
        {
            fseek($this->fileHandle, $reference->getOffset());
            $data = fread($this->fileHandle, $reference->getSize());
            if(strlen($data) !== $reference->getSize())
            {
                throw new RuntimeException("Failed to read resource data");
            }

            return $data;
        }

        /**
         * Finds a component reference by path.
         *
         * @param string $path The component path to search for.
         * @return ComponentReference|null The component reference or null if not found.
         */
        public function findComponent(string $path): ?ComponentReference
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->componentReferences[$path] ?? null;
        }

        /**
         * Finds a resource reference by path.
         *
         * @param string $path The resource path to search for.
         * @return ResourceReference|null The resource reference or null if not found.
         */
        public function findResource(string $path): ?ResourceReference
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->resourceReferences[$path] ?? null;
        }

        /**
         * Finds an execution unit reference by name.
         *
         * @param string $name The execution unit name to search for.
         * @return ExecutionUnitReference|null The execution unit reference or null if not found.
         */
        public function findExecutionUnit(string $name): ?ExecutionUnitReference
        {
            if(!$this->parsed)
            {
                $this->parsePackage();
            }

            return $this->executionUnitReferences[$name] ?? null;
        }

        /**
         * Returns all components, resources and execution units as one array
         *
         * @return ReferenceInterface[]
         */
        public function getAllReferences(): array
        {
            return array_merge(
                $this->getComponentReferences(),
                $this->getResourceReferences(),
                $this->getExecutionUnitReferences()
            );
        }

        /**
         * Reads data based on the reference type.
         *
         * @param ReferenceInterface $reference The reference to read.
         * @return string|ExecutionUnit The data read from the reference.w
         */
        public function read(ReferenceInterface $reference): string|ExecutionUnit
        {
            if($reference instanceof ComponentReference)
            {
                return $this->readComponent($reference);
            }
            elseif($reference instanceof ResourceReference)
            {
                return $this->readResource($reference);
            }
            elseif($reference instanceof ExecutionUnitReference)
            {
                return $this->readExecutionUnit($reference);
            }
            else
            {
                throw new InvalidArgumentException("Unknown reference type");
            }
        }

        public function find(string $name): ?ReferenceInterface
        {
            $reference = $this->findComponent($name);
            if($reference !== null)
            {
                return $reference;
            }

            $reference = $this->findResource($name);
            if($reference !== null)
            {
                return $reference;
            }

            return $this->findExecutionUnit($name);
        }

        /**
         * Finds the package start offset by scanning the file for the package signature.
         *
         * @return int The offset where the package starts.
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

            $chunkSize = 126;
            $buffer = '';
            $bufferStartPosition = 0;

            while (ftell($this->fileHandle) < $fileSize)
            {
                $currentPosition = ftell($this->fileHandle);
                $bytesToRead = min($chunkSize, $fileSize - $currentPosition);
                $newData = fread($this->fileHandle, $bytesToRead);

                if ($newData === false)
                {
                    throw new InvalidArgumentException("Error reading file: " . $this->filePath);
                }

                // If buffer is getting too large, trim from the left but keep overlap for boundary matches
                if (strlen($buffer) > $searchLength)
                {
                    $trimAmount = strlen($buffer) - $searchLength + 1;
                    $buffer = substr($buffer, $trimAmount);
                    $bufferStartPosition += $trimAmount;
                }

                $buffer .= $newData;

                $pos = strpos($buffer, $searchSequence);
                if ($pos !== false)
                {
                    // Calculate absolute position in file
                    return $bufferStartPosition + $pos;
                }
            }

            throw new InvalidArgumentException("Package signature (START_PACKAGE + MAGIC_BYTES) not found in file: " . $this->filePath);
        }

        /**
         * Parses the package structure and builds references to all sections.
         *
         * @return void
         */
        private function parsePackage(): void
        {
            if($this->parsed)
            {
                return;
            }

            // Seek to the package start + skip the START_PACKAGE marker
            fseek($this->fileHandle, $this->startOffset + strlen(PackageStructure::START_PACKAGE->value) + strlen(PackageStructure::MAGIC_BYTES->value) + strlen(PackageStructure::TERMINATE->value));

            // Read package version
            $this->packageVersion = $this->readPackageVersion();

            // Read all sections
            while(!feof($this->fileHandle))
            {
                $marker = fread($this->fileHandle, 1);
                if($marker === false || $marker === '' || $marker === PackageStructure::TERMINATE->value[0])
                {
                    break;
                }

                switch($marker)
                {
                    case PackageStructure::HEADER->value:
                        $this->header = $this->readHeader();
                        break;

                    case PackageStructure::ASSEMBLY->value:
                        $this->assembly = $this->readAssembly();
                        break;

                    case PackageStructure::EXECUTION_UNITS->value:
                        $this->executionUnitReferences = $this->readExecutionUnitReferences();
                        break;

                    case PackageStructure::COMPONENTS->value:
                        $this->componentReferences = $this->readComponentReferences();
                        break;

                    case PackageStructure::RESOURCES->value:
                        $this->resourceReferences = $this->readResourceReferences();
                        break;

                    default:
                        throw new RuntimeException("Unknown section marker: " . bin2hex($marker));
                }
            }

            $this->parsed = true;
        }

        /**
         * Reads the package version from the file.
         *
         * @return string The package version.
         */
        private function readPackageVersion(): string
        {
            $marker = fread($this->fileHandle, 1);
            if($marker !== PackageStructure::PACKAGE_VERSION->value)
            {
                throw new RuntimeException("Expected PACKAGE_VERSION marker, got: " . bin2hex($marker));
            }

            // Read 4 characters for version
            $version = fread($this->fileHandle, 4);
            if(strlen($version) !== 4)
            {
                throw new RuntimeException("Invalid package version length");
            }

            // Read TERMINATE
            $terminate = fread($this->fileHandle, strlen(PackageStructure::TERMINATE->value));
            if($terminate !== PackageStructure::TERMINATE->value)
            {
                throw new RuntimeException("Expected TERMINATE after package version");
            }

            return $version;
        }

        /**
         * Reads a size prefix (packed unsigned long long).
         *
         * @return int The size value.
         */
        private function readSizePrefix(): int
        {
            $packed = fread($this->fileHandle, 8);
            if(strlen($packed) !== 8)
            {
                throw new RuntimeException("Failed to read size prefix");
            }

            $unpacked = unpack('Q', $packed);
            return $unpacked[1];
        }

        /**
         * Reads the header section.
         *
         * @return Header The header object.
         */
        private function readHeader(): Header
        {
            $size = $this->readSizePrefix();

            // Read SOFT_TERMINATE
            $softTerminate = fread($this->fileHandle, strlen(PackageStructure::SOFT_TERMINATE->value));
            if($softTerminate !== PackageStructure::SOFT_TERMINATE->value)
            {
                throw new RuntimeException("Expected SOFT_TERMINATE after header size");
            }

            // Read data
            $data = fread($this->fileHandle, $size);
            if(strlen($data) !== $size)
            {
                throw new RuntimeException("Failed to read header data");
            }

            // Read TERMINATE
            $terminate = fread($this->fileHandle, strlen(PackageStructure::TERMINATE->value));
            if($terminate !== PackageStructure::TERMINATE->value)
            {
                throw new RuntimeException("Expected TERMINATE after header data");
            }

            return new Header(msgpack_unpack($data));
        }

        /**
         * Reads the assembly section.
         *
         * @return Assembly The assembly object.
         */
        private function readAssembly(): Assembly
        {
            $size = $this->readSizePrefix();

            // Read SOFT_TERMINATE
            $softTerminate = fread($this->fileHandle, strlen(PackageStructure::SOFT_TERMINATE->value));
            if($softTerminate !== PackageStructure::SOFT_TERMINATE->value)
            {
                throw new RuntimeException("Expected SOFT_TERMINATE after assembly size");
            }

            // Read data
            $data = fread($this->fileHandle, $size);
            if(strlen($data) !== $size)
            {
                throw new RuntimeException("Failed to read assembly data");
            }

            // Read TERMINATE
            $terminate = fread($this->fileHandle, strlen(PackageStructure::TERMINATE->value));
            if($terminate !== PackageStructure::TERMINATE->value)
            {
                throw new RuntimeException("Expected TERMINATE after assembly data");
            }

            return Assembly::fromArray(msgpack_unpack($data));
        }

        /**
         * Reads execution unit references from the file.
         *
         * @return array<string, ExecutionUnitReference> Associative array of execution unit references.
         */
        private function readExecutionUnitReferences(): array
        {
            $references = [];
            $nextSectionMarkers = [
                PackageStructure::COMPONENTS->value,
                PackageStructure::RESOURCES->value
            ];

            while(true)
            {
                $peek = $this->peekNextByte();
                if($this->isEndOfSection($peek))
                {
                    break;
                }

                if($this->isNextSection($peek, $nextSectionMarkers))
                {
                    fseek($this->fileHandle, ftell($this->fileHandle) - 1);
                    break;
                }

                $name = $this->readNameUntilSoftTerminate($peek);
                $size = $this->readSizePrefix();
                $this->expectSoftTerminate('execution unit size');
                $offset = ftell($this->fileHandle);
                $this->skipData($size);
                $this->expectTerminate('execution unit data');

                $references[$name] = new ExecutionUnitReference($name, $offset, $size);
            }

            return $references;
        }

        /**
         * Reads component references from the file.
         *
         * @return array<string, ComponentReference> Associative array of component references.
         */
        private function readComponentReferences(): array
        {
            $references = [];
            $nextSectionMarkers = [PackageStructure::RESOURCES->value];

            while(true)
            {
                $peek = $this->peekNextByte();
                if($this->isEndOfSection($peek))
                {
                    break;
                }

                if($this->isNextSection($peek, $nextSectionMarkers))
                {
                    fseek($this->fileHandle, ftell($this->fileHandle) - 1);
                    break;
                }

                $componentName = $this->readNameUntilSoftTerminate($peek);
                $size = $this->readSizePrefix();
                $this->expectSoftTerminate('component size');
                $offset = ftell($this->fileHandle);
                $this->skipData($size);
                $this->expectTerminate('component data');

                $references[$componentName] = new ComponentReference($componentName, $offset, $size);
            }

            return $references;
        }

        /**
         * Reads resource references from the file.
         *
         * @return array<string, ResourceReference> Associative array of resource references.
         */
        private function readResourceReferences(): array
        {
            $references = [];

            while(true)
            {
                $peek = $this->peekNextByte();
                if($this->isEndOfSection($peek))
                {
                    break;
                }

                $resourcePath = $this->readNameUntilSoftTerminate($peek);
                $size = $this->readSizePrefix();
                $this->expectSoftTerminate('resource size');
                $offset = ftell($this->fileHandle);
                $this->skipData($size);
                $this->expectTerminate('resource data');

                $references[$resourcePath] = new ResourceReference($resourcePath, $offset, $size);
            }

            return $references;
        }

        /**
         * Peeks at the next byte in the file without consuming it permanently.
         *
         * @return string|false The peeked byte or false on error.
         */
        private function peekNextByte(): string|false
        {
            return fread($this->fileHandle, 1);
        }

        /**
         * Checks if the peeked byte indicates end of section.
         *
         * @param string|false $peek The peeked byte.
         * @return bool True if end of section.
         */
        private function isEndOfSection(string|false $peek): bool
        {
            return $peek === false || $peek === '' || $peek === PackageStructure::TERMINATE->value[0];
        }

        /**
         * Checks if the peeked byte is a marker for the next section.
         *
         * @param string|false $peek The peeked byte.
         * @param array $markers Array of section markers to check.
         * @return bool True if it's a next section marker.
         */
        private function isNextSection(string|false $peek, array $markers): bool
        {
            return $peek !== false && in_array($peek, $markers, true);
        }

        /**
         * Reads a name/path string until SOFT_TERMINATE is encountered.
         *
         * @param string $peek The first peeked byte.
         * @return string The name/path string (may be empty).
         */
        private function readNameUntilSoftTerminate(string $peek): string
        {
            if($peek === PackageStructure::SOFT_TERMINATE->value)
            {
                // Empty name, already past SOFT_TERMINATE
                return '';
            }

            // Rewind to read the name
            fseek($this->fileHandle, ftell($this->fileHandle) - 1);

            $name = '';
            while(true)
            {
                $byte = fread($this->fileHandle, 1);
                if($byte === PackageStructure::SOFT_TERMINATE->value)
                {
                    break;
                }
                $name .= $byte;
            }

            return $name;
        }

        /**
         * Expects a SOFT_TERMINATE marker and throws exception if not found.
         *
         * @param string $context Context description for error message.
         * @return void
         */
        private function expectSoftTerminate(string $context): void
        {
            if(fread($this->fileHandle, strlen(PackageStructure::SOFT_TERMINATE->value)) !== PackageStructure::SOFT_TERMINATE->value)
            {
                throw new RuntimeException("Expected SOFT_TERMINATE after {$context}");
            }
        }

        /**
         * Expects a TERMINATE marker and throws exception if not found.
         *
         * @param string $context Context description for error message.
         * @return void
         */
        private function expectTerminate(string $context): void
        {
            if(fread($this->fileHandle, strlen(PackageStructure::TERMINATE->value)) !== PackageStructure::TERMINATE->value)
            {
                throw new RuntimeException("Expected TERMINATE after {$context}");
            }
        }

        /**
         * Skips a specified amount of data in the file.
         *
         * @param int $size Number of bytes to skip.
         * @return void
         */
        private function skipData(int $size): void
        {
            fseek($this->fileHandle, ftell($this->fileHandle) + $size);
        }

        /**
         * Closes the file handle and releases resources.
         *
         * @return void
         */
        public function __destruct()
        {
            if (is_resource($this->fileHandle))
            {
                fclose($this->fileHandle);
            }
        }
    }