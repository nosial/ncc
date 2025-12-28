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
    use ncc\Enums\ExecutionUnitType;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\PackageStructure;
    use ncc\Exceptions\ExecutionUnitException;
    use ncc\Exceptions\IOException;
    use ncc\Interfaces\ReferenceInterface;
    use ncc\Libraries\pal\Autoloader;
    use ncc\Libraries\Process\ExecutableFinder;
    use ncc\Libraries\Process\Process;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\ExecutionUnitReference;
    use ncc\Objects\Package\Header;
    use ncc\Objects\Package\ResourceReference;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Project\ExecutionUnit;
    use ncc\Runtime;
    use RuntimeException;
    use function msgpack_unpack;

    class PackageReader
    {
        private string $filePath;
        private $fileHandle;
        private int $startOffset;
        private int $endOffset;
        private string $packageVersion;
        private Header $header;
        private Assembly $assembly;
        /** @var array<string, ExecutionUnitReference> */
        private array $executionUnitReferences = [];
        /** @var array<string, ComponentReference> */
        private array $componentReferences = [];
        /** @var array<string, ResourceReference> */
        private array $resourceReferences = [];

        /**
         * Public constructor for the PackageReader class.
         *
         * @param string $filePath The path to the package file. Must be a valid file path.
         * @param bool $tryCache If true, attempt to load from cache file if available
         */
        public function __construct(string $filePath, bool $tryCache = false)
        {
            Logger::getLogger()->debug(sprintf('Initializing PackageReader for: %s', $filePath));
            $this->filePath = $filePath;
            if(!IO::exists($this->filePath))
            {
                throw new InvalidArgumentException("File does not exist: " . $this->filePath);
            }

            if(!IO::isReadable($this->filePath))
            {
                throw new InvalidArgumentException("File is not readable: " . $this->filePath);
            }

            // Try to load from cache if requested
            if($tryCache)
            {
                $cacheFile = $this->filePath . '.cache';
                if(IO::exists($cacheFile))
                {
                    Logger::getLogger()->verbose('Attempting to load from cache file');
                    try
                    {
                        $this->importFromCacheFile($cacheFile);
                        Logger::getLogger()->verbose('Successfully loaded from cache');
                        return;
                    }
                    catch(\Exception $e)
                    {
                        Logger::getLogger()->debug(sprintf('Cache loading failed: %s, falling back to normal parsing', $e->getMessage()));
                        // If cache loading fails, fall back to normal parsing
                        // Silently continue to normal parsing
                    }
                }
            }
            
            Logger::getLogger()->verbose('Parsing package file');

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

            // Seek to the package start + skip the START_PACKAGE marker
            fseek($this->fileHandle, $this->startOffset + strlen(PackageStructure::START_PACKAGE->value) + strlen(PackageStructure::MAGIC_BYTES->value) + strlen(PackageStructure::TERMINATE->value));

            // Read package version
            $this->packageVersion = $this->readPackageVersion();
            Logger::getLogger()->debug(sprintf('Package version: %s', $this->packageVersion));

            // Read all sections
            Logger::getLogger()->verbose('Reading package sections');
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
                        Logger::getLogger()->debug('Reading HEADER section');
                        $this->header = $this->readHeader();
                        break;

                    case PackageStructure::ASSEMBLY->value:
                        Logger::getLogger()->debug('Reading ASSEMBLY section');
                        $this->assembly = $this->readAssembly();
                        break;

                    case PackageStructure::EXECUTION_UNITS->value:
                        Logger::getLogger()->debug('Reading EXECUTION_UNITS section');
                        $this->executionUnitReferences = $this->readExecutionUnitReferences();
                        Logger::getLogger()->verbose(sprintf('Loaded %d execution units', count($this->executionUnitReferences)));
                        break;

                    case PackageStructure::COMPONENTS->value:
                        Logger::getLogger()->debug('Reading COMPONENTS section');
                        $this->componentReferences = $this->readComponentReferences();
                        Logger::getLogger()->verbose(sprintf('Loaded %d components', count($this->componentReferences)));
                        break;

                    case PackageStructure::RESOURCES->value:
                        Logger::getLogger()->debug('Reading RESOURCES section');
                        $this->resourceReferences = $this->readResourceReferences();
                        Logger::getLogger()->verbose(sprintf('Loaded %d resources', count($this->resourceReferences)));
                        break;

                    default:
                        throw new RuntimeException("Unknown section marker: " . bin2hex($marker));
                }
            }

            // After reading all sections, check if we need to read the final TERMINATE
            // The marker check above breaks on TERMINATE, so we need to consume it if present
            if($marker === PackageStructure::TERMINATE->value[0])
            {
                // Read the second byte of TERMINATE (it's \xE0\xE0)
                fread($this->fileHandle, 1);
            }

            // Save the end offset - this is where the package ends
            $this->endOffset = ftell($this->fileHandle);
        }

        public function getFilePath(): string
        {
            return $this->filePath;
        }

        public function getPackageSource(): ?PackageSource
        {
            $packageSource = null;

            if($this->getHeader()->getUpdateSource() !== null)
            {
                return $this->getHeader()->getUpdateSource();
            }
            elseif($this->getHeader()->getMainRepository() !== null)
            {
                $packageSource = new PackageSource();
                $packageSource->setRepository($this->getHeader()->getMainRepository()->getName());
                $packageSource->setName($this->getAssembly()->getName());
                $packageSource->setVersion($this->getAssembly()->getVersion());
                if($this->getAssembly()->getOrganization() !== null)
                {
                    $packageSource->setOrganization($this->getAssembly()->getOrganization());
                }
            }

            return $packageSource;
        }

        public function getPackageName(bool $includeVersion = false): string
        {
            $name = $this->getAssembly()->getName();
            if($includeVersion)
            {
                $name .= '=' . $this->getAssembly()->getVersion();
            }

            return $name;
        }

        /**
         * Gets the package version.
         *
         * @return string The package version.
         */
        public function getPackageVersion(): string
        {
            return $this->packageVersion;
        }

        /**
         * Gets the package header.
         *
         * @return Header|null The header object or null if not present.
         */
        public function getHeader(): ?Header
        {
            return $this->header;
        }

        /**
         * Gets the package assembly.
         *
         * @return Assembly|null The assembly object or null if not present.
         */
        public function getAssembly(): ?Assembly
        {
            return $this->assembly;
        }

        /**
         * Gets all execution unit references.
         *
         * @return ExecutionUnitReference[] Array of execution unit references.
         */
        public function getExecutionUnitReferences(): array
        {
            return $this->executionUnitReferences;
        }

        /**
         * Gets all component references.
         *
         * @return ComponentReference[] Array of component references.
         */
        public function getComponentReferences(): array
        {
            return $this->componentReferences;
        }

        /**
         * Gets all resource references.
         *
         * @return ResourceReference[] Array of resource references.
         */
        public function getResourceReferences(): array
        {
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

            if($this->header->isCompressed())
            {
                $data = gzinflate($data);
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

            if($this->header->isCompressed())
            {
                $data = gzinflate($data);
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

        /**
         * Finds a reference by name or path.
         *
         * @param string $name The name or path to search for.
         * @return ReferenceInterface|null The found reference or null if not found.
         */
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
         * @throws IOException
         * @throws ExecutionUnitException
         */
        public function extract(string $outputDirectory): void
        {
            // Create the directory if it doesn't exist
            if(!IO::isDir($outputDirectory))
            {
                IO::mkdir($outputDirectory);
            }

            // Extract all the references from the package
            foreach($this->getAllReferences() as $reference)
            {
                // Execution Units are to be generated
                if($reference instanceof ExecutionUnitReference)
                {
                    $this->createExecutionUnit($reference, $outputDirectory);
                    continue;
                }

                // Everything is just written as-is
                $outputPath = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $reference->getName();
                if(!is_dir(dirname($outputPath)))
                {
                    IO::mkdir(dirname($outputPath));
                }

                IO::writeFile($outputPath, $this->read($reference));
            }

            // Generate the autoloader
            IO::writeFile($outputDirectory . DIRECTORY_SEPARATOR . 'autoload.php', Autoloader::generateAutoloader($outputDirectory, [
                'relative' => true
            ]));
        }

        /**
         * Creates a shell script to execute the given execution unit.
         *
         * @param ExecutionUnitReference $reference The execution unit reference.
         * @param string $outputDirectory The directory where the script will be created.
         * @return string The path to the created shell script.
         * @throws ExecutionUnitException If the shell cannot be found.
         * @throws IOException If there is an error writing the file.
         */
        public function createExecutionUnit(ExecutionUnitReference $reference, string $outputDirectory): string
        {
            $executionUnit = $this->readExecutionUnit($reference); // Find the execution unit
            $outputFile = $outputDirectory . DIRECTORY_SEPARATOR . $executionUnit->getName() . '.sh'; // Declare the output file
            $shell = (new ExecutableFinder())->find('sh'); // Find the system shell

            if($shell === null)
            {
                // If we can't find the shell, we can't proceed because the generated script would not be executable
                throw new ExecutionUnitException('Unable to locate \'sh\', cannot generate shell script.');
            }

            // Shell script begins with the first line.
            $shellScript = "#!{$shell}" . PHP_EOL;

            // Define environment variables
            if($executionUnit->getEnvironment() !== null && count($executionUnit->getEnvironment()) > 0)
            {
                // Define the section for the environment variables
                $shellScript .= PHP_EOL . "# Environment Variables" . PHP_EOL;
                foreach($executionUnit->getEnvironment() as $key => $value)
                {
                    $shellScript .= "{$key}=\"{$value}\"" . PHP_EOL;
                }
                $shellScript .= PHP_EOL;
            }

            // Change to working directory if specified
            if($executionUnit->getWorkingDirectory() !== null && $executionUnit->getWorkingDirectory() !== MacroVariable::CURRENT_WORKING_DIRECTORY)
            {
                $shellScript .= "cd {$executionUnit->getWorkingDirectory()}". PHP_EOL;
            }

            // Execute the entry point based on the type
            switch($executionUnit->getType())
            {
                case ExecutionUnitType::PHP:
                    $bin = (new ExecutableFinder())->find('php');
                    $shellScript .= "exec {$bin} {$outputDirectory}" . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint();
                    break;

                case ExecutionUnitType::SYSTEM:
                    $bin = (new ExecutableFinder())->find($executionUnit->getEntryPoint());
                    $shellScript .= "exec {$bin}";
                    break;
            }

            if($executionUnit->getArguments() !== null && count($executionUnit->getArguments()) > 0)
            {
                $shellScript .= ' ' . implode(' ', $executionUnit->getArguments()) . ' "$@"';
            }
            else
            {
                $shellScript .= ' "$@"';
            }

            IO::writeFile($outputFile, $shellScript);
            return $outputFile;
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

            $fileSize = IO::filesize($this->filePath);
            if ($fileSize < $searchLength)
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
                $peek = fread($this->fileHandle, 1);
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
         * Exports the package to a new file, extracting it from any embedded context.
         * This method reads the exact package data from the start offset to the end offset
         * and writes it to the specified file path. This is useful when the package is embedded
         * in another file (e.g., a compiled program or PHP script using __halt_compiler()).
         *
         * @param string $filePath The destination file path where the package should be exported.
         * @return void
         * @throws IOException If there is an error writing the file.
         */
        public function exportPackage(string $filePath): void
        {
            // Calculate the exact package size
            $packageSize = $this->endOffset - $this->startOffset;

            // Seek to the start of the package
            if (fseek($this->fileHandle, $this->startOffset) !== 0)
            {
                throw new RuntimeException("Could not seek to package start position: " . $this->startOffset);
            }

            // Read the exact package data from startOffset to endOffset
            $packageData = fread($this->fileHandle, $packageSize);
            
            if ($packageData === false || strlen($packageData) !== $packageSize)
            {
                throw new IOException("Failed to read complete package data from: " . $this->filePath . " (expected " . $packageSize . " bytes)");
            }

            // Write the package data to the specified file
            IO::writeFile($filePath, $packageData);
        }

        /**
         * Exports package metadata to a cache file for faster subsequent loading.
         * The cache file contains all parsed references and metadata, allowing the
         * PackageReader to be reconstructed without re-parsing the package file.
         *
         * @param string $cacheFilePath The path where the cache file should be saved
         * @throws IOException If the cache file cannot be written
         */
        public function exportCache(string $cacheFilePath): void
        {
            $cacheData = [
                'version' => 1, // Cache format version for future compatibility
                'file_path' => $this->filePath,
                'file_size' => filesize($this->filePath),
                'file_mtime' => filemtime($this->filePath),
                'start_offset' => $this->startOffset,
                'end_offset' => $this->endOffset,
                'package_version' => $this->packageVersion,
                'header' => $this->header->toArray(),
                'assembly' => $this->assembly->toArray(),
                'execution_unit_references' => $this->executionUnitReferences,
                'component_references' => $this->componentReferences,
                'resource_references' => $this->resourceReferences,
            ];

            $serialized = serialize($cacheData);
            IO::writeFile($cacheFilePath, $serialized);
        }

        /**
         * Creates a PackageReader instance from a cache file.
         * This method provides a static factory for creating PackageReader instances
         * from previously exported cache files.
         *
         * @param string $cacheFilePath The path to the cache file
         * @param string $packageFilePath The path to the original package file
         * @return static A new PackageReader instance
         * @throws InvalidArgumentException If the cache file is invalid or corrupted
         * @throws IOException If the cache file cannot be read
         */
        public static function importFromCache(string $cacheFilePath, string $packageFilePath): self
        {
            if(!IO::exists($cacheFilePath))
            {
                throw new InvalidArgumentException("Cache file does not exist: " . $cacheFilePath);
            }

            if(!IO::exists($packageFilePath))
            {
                throw new InvalidArgumentException("Package file does not exist: " . $packageFilePath);
            }

            // Use reflection to create instance without calling constructor
            $reflection = new \ReflectionClass(self::class);
            $instance = $reflection->newInstanceWithoutConstructor();
            
            $instance->filePath = $packageFilePath;
            $instance->importFromCacheFile($cacheFilePath);
            
            return $instance;
        }

        /**
         * Internal method to import cache data into the current instance.
         *
         * @param string $cacheFilePath The path to the cache file
         * @throws InvalidArgumentException If the cache is invalid or corrupted
         * @throws IOException If the cache file cannot be read
         */
        private function importFromCacheFile(string $cacheFilePath): void
        {
            $cacheContent = IO::readFile($cacheFilePath);
            $cacheData = unserialize($cacheContent);

            if(!is_array($cacheData) || !isset($cacheData['version']))
            {
                throw new InvalidArgumentException("Invalid cache file format");
            }

            // Validate cache against current package file
            $currentSize = filesize($this->filePath);
            $currentMtime = filemtime($this->filePath);

            if($cacheData['file_size'] !== $currentSize || $cacheData['file_mtime'] !== $currentMtime)
            {
                throw new InvalidArgumentException("Cache file is outdated (package file has been modified)");
            }

            // Reconstruct all properties from cache
            $this->startOffset = $cacheData['start_offset'];
            $this->endOffset = $cacheData['end_offset'];
            $this->packageVersion = $cacheData['package_version'];
            $this->header = Header::fromArray($cacheData['header']);
            $this->assembly = Assembly::fromArray($cacheData['assembly']);
            $this->executionUnitReferences = $cacheData['execution_unit_references'];
            $this->componentReferences = $cacheData['component_references'];
            $this->resourceReferences = $cacheData['resource_references'];

            // Open file handle for reading actual data when needed
            $this->fileHandle = fopen($this->filePath, 'rb');
            if(!$this->fileHandle)
            {
                throw new InvalidArgumentException("Could not open package file: " . $this->filePath);
            }
        }

        /**
         * Executes an execution unit from the package.
         *
         * @param string|null $executionUnit The name of the execution unit to execute. If null, uses the main entry point.
         * @param array $arguments Arguments to pass to the execution unit (similar to $argv in PHP).
         * @return mixed The return value from the executed script (for PHP/WEB) or exit code (for SYSTEM).
         * @throws ExecutionUnitException If the execution unit is not found or execution fails.
         * @throws IOException If there's an I/O error during execution.
         */
        public function execute(?string $executionUnit = null, array $arguments = []): mixed
        {
            Logger::getLogger()->debug(sprintf('Execute called with executionUnit: %s', $executionUnit ?? 'null'));
            
            // Determine which execution unit to use
            if($executionUnit === null)
            {
                // Use main entry point from header
                $entryPoint = $this->header->getEntryPoint();
                if($entryPoint === null)
                {
                    throw new ExecutionUnitException('No execution unit specified and no main entry point defined in package');
                }
                
                Logger::getLogger()->verbose(sprintf('Using main entry point: %s', $entryPoint));
                $executionUnit = $entryPoint;
            }
            
            // Find and read the execution unit
            $executionUnitRef = $this->findExecutionUnit($executionUnit);
            if($executionUnitRef === null)
            {
                throw new ExecutionUnitException(sprintf('Execution unit not found: %s', $executionUnit));
            }
            
            $unit = $this->readExecutionUnit($executionUnitRef);
            Logger::getLogger()->verbose(sprintf('Executing unit: %s (type: %s)', $executionUnit, $unit->getType()->value));
            
            // Handle execution based on type
            switch($unit->getType())
            {
                case ExecutionUnitType::PHP:
                case ExecutionUnitType::WEB:
                    return $this->executePhpUnit($unit, $arguments);
                    
                case ExecutionUnitType::SYSTEM:
                    return $this->executeSystemUnit($unit, $arguments);
                    
                default:
                    throw new ExecutionUnitException(sprintf('Unsupported execution unit type: %s', $unit->getType()->value));
            }
        }

        /**
         * Executes a PHP execution unit.
         *
         * @param ExecutionUnit $unit The execution unit to execute.
         * @param array $arguments Arguments to pass to the script.
         * @return mixed The return value from the executed script.
         * @throws ExecutionUnitException If execution fails.
         */
        private function executePhpUnit(ExecutionUnit $unit, array $arguments): mixed
        {
            // Ensure the package is imported in the runtime
            $packageName = $this->assembly->getPackage();
            Logger::getLogger()->debug(sprintf('Importing package in runtime: %s', $packageName));
            
            // Import the package if not already imported
            if(!Runtime::isImported($packageName))
            {
                Logger::getLogger()->verbose(sprintf('Package not yet imported, importing: %s', $packageName));
                Runtime::import($this->filePath);
            }
            
            // Build the ncc:// protocol path
            $scriptPath = 'ncc://' . $packageName . '/' . ltrim($unit->getEntryPoint(), '/');
            Logger::getLogger()->verbose(sprintf('Executing PHP script: %s', $scriptPath));

            // Verify the script exists, if not try with .php extension
            if(!file_exists($scriptPath))
            {
                // If the entry point doesn't have .php extension, try adding it
                if(!str_ends_with($scriptPath, '.php'))
                {
                    $scriptPathWithExtension = $scriptPath . '.php';
                    if(file_exists($scriptPathWithExtension))
                    {
                        Logger::getLogger()->verbose(sprintf('Script found with .php extension: %s', $scriptPathWithExtension));
                        $scriptPath = $scriptPathWithExtension;
                    }
                    else
                    {
                        throw new ExecutionUnitException(sprintf('Script not found in package: %s (also tried %s)', $scriptPath, $scriptPathWithExtension));
                    }
                }
                else
                {
                    throw new ExecutionUnitException(sprintf('Script not found in package: %s', $scriptPath));
                }
            }
            
            // Set up $argv for the script
            $oldArgv = $_SERVER['argv'] ?? [];
            $_SERVER['argv'] = array_merge([$scriptPath], $arguments);
            $argc = count($_SERVER['argv']);
            $_SERVER['argc'] = $argc;
            
            // Set working directory if specified
            $oldCwd = getcwd();
            if($unit->getWorkingDirectory() !== null)
            {
                // Resolve macros in working directory (e.g., ${CWD} -> current working directory)
                $workingDirectory = $this->resolveMacros($unit->getWorkingDirectory());
                Logger::getLogger()->debug(sprintf('Changing working directory to: %s', $workingDirectory));
                chdir($workingDirectory);
            }
            
            // Set environment variables if specified
            $oldEnv = [];
            if($unit->getEnvironment() !== null)
            {
                foreach($unit->getEnvironment() as $key => $value)
                {
                    $oldEnv[$key] = getenv($key);
                    putenv("$key=$value");
                }
            }
            
            try
            {
                // Execute the script
                $result = require $scriptPath;
                Logger::getLogger()->verbose('PHP script execution completed');
                return $result;
            }
            catch(\Throwable $e)
            {
                throw new ExecutionUnitException(sprintf('Failed to execute PHP script: %s', $e->getMessage()), 0, $e);
            }
            finally
            {
                // Restore environment
                $_SERVER['argv'] = $oldArgv;
                $_SERVER['argc'] = count($oldArgv);
                
                if($unit->getWorkingDirectory() !== null)
                {
                    chdir($oldCwd);
                }
                
                // Restore environment variables
                foreach($oldEnv as $key => $value)
                {
                    if($value === false)
                    {
                        putenv($key);
                    }
                    else
                    {
                        putenv("$key=$value");
                    }
                }
            }
        }

        /**
         * Executes a system execution unit.
         *
         * @param ExecutionUnit $unit The execution unit to execute.
         * @param array $arguments Arguments to pass to the command.
         * @return int The exit code from the executed process.
         * @throws ExecutionUnitException If execution fails.
         */
        private function executeSystemUnit(ExecutionUnit $unit, array $arguments): int
        {
            $entryPoint = $unit->getEntryPoint();
            Logger::getLogger()->debug(sprintf('Executing system command: %s', $entryPoint));
            
            // Try to resolve the executable path if it's not an absolute path
            $executablePath = $entryPoint;
            if(!file_exists($executablePath))
            {
                Logger::getLogger()->verbose(sprintf('Entry point not found as file, attempting to resolve: %s', $entryPoint));
                $resolvedPath = (new ExecutableFinder())->find($entryPoint);
                
                if($resolvedPath !== null)
                {
                    $executablePath = $resolvedPath;
                    Logger::getLogger()->verbose(sprintf('Resolved executable path: %s', $executablePath));
                }
                else
                {
                    Logger::getLogger()->verbose(sprintf('Could not resolve executable, using as-is: %s', $entryPoint));
                }
            }
            
            // Build the command with arguments
            $commandParts = array_merge([$executablePath], $arguments);
            
            // Create and configure the process
            $process = new Process($commandParts);
            
            // Set working directory if specified
            if($unit->getWorkingDirectory() !== null)
            {
                Logger::getLogger()->debug(sprintf('Setting working directory: %s', $unit->getWorkingDirectory()));
                $process->setWorkingDirectory($unit->getWorkingDirectory());
            }
            
            // Set environment variables if specified
            if($unit->getEnvironment() !== null)
            {
                Logger::getLogger()->debug(sprintf('Setting %d environment variables', count($unit->getEnvironment())));
                $process->setEnv($unit->getEnvironment());
            }
            
            // Set timeout if specified
            if($unit->getTimeout() !== null)
            {
                Logger::getLogger()->debug(sprintf('Setting timeout: %d seconds', $unit->getTimeout()));
                $process->setTimeout($unit->getTimeout());
            }
            
            try
            {
                Logger::getLogger()->verbose(sprintf('Starting process: %s', $process->getCommandLine()));
                $process->mustRun();
                
                $exitCode = $process->getExitCode();
                Logger::getLogger()->verbose(sprintf('Process completed with exit code: %d', $exitCode));
                
                return $exitCode;
            }
            catch(\Throwable $e)
            {
                throw new ExecutionUnitException(sprintf('Failed to execute system command: %s', $e->getMessage()), 0, $e);
            }
        }

        /**
         * Resolves macro variables in a string.
         *
         * @param string $input The input string containing macros
         * @return string The string with macros resolved
         */
        private function resolveMacros(string $input): string
        {
            // Handle ${CWD} - current working directory
            if(str_contains($input, '${CWD}'))
            {
                $input = str_replace('${CWD}', getcwd(), $input);
            }
            
            // Handle other common macros if needed in the future
            // ${PACKAGE_PATH}, ${TEMP}, etc.
            
            return $input;
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