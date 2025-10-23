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

    namespace ncc\Objects\Project;

    use InvalidArgumentException;
    use ncc\Enums\BuildType;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;

    class BuildConfiguration implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private string $output;
        private BuildType $type;
        private array $definitions;
        private array $includeComponents;
        private array $excludeComponents;
        private array $includeResources;
        private array $excludeResources;
        private ?array $options;

        /**
         * BuildConfiguration constructor.
         *
         * @param array $data The data to initialize the build configuration.
         */
        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? 'release';
            $this->output = $data['output'] ?? 'out';
            $this->type = BuildType::tryFrom($data['type']) ?? BuildType::NCC_PACKAGE;
            $this->definitions = $data['definitions'] ?? [];
            $this->options = $data['options'] ?? null;
            $this->includeComponents = $data['include_components'] ?? [];
            $this->excludeComponents = $data['exclude_components'] ?? [];
            $this->includeResources = $data['include_resources'] ?? [];
            $this->excludeResources = $data['exclude_resources'] ?? [];
        }

        /**
         * Get the name of the build configuration.
         *
         * @return string The name of the build configuration.
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Set the name of the build configuration.
         *
         * @param string $name The name to set.
         * @throws InvalidArgumentException If the name is empty.
         */
        public function setName(string $name): void
        {
            if(empty($name))
            {
                throw new InvalidArgumentException('Build configuration name cannot be empty');
            }

            $this->name = $name;
        }

        /**
         * Get the output path of the build configuration.
         *
         * @return string The output path.
         */
        public function getOutput(): string
        {
            return $this->output;
        }

        /**
         * Set the output path of the build configuration.
         *
         * @param string $output The output path to set.
         * @throws InvalidArgumentException If the output path is empty.
         */
        public function setOutput(string $output): void
        {
            if(empty($output))
            {
                throw new InvalidArgumentException('Build configuration output cannot be empty');
            }

            $this->output = $output;
        }

        /**
         * Get the build type of the configuration.
         *
         * @return BuildType The build type.
         */
        public function getType(): BuildType
        {
            return $this->type;
        }

        /**
         * Set the build type of the configuration.
         *
         * @param BuildType $type The build type to set.
         */
        public function setType(BuildType $type): void
        {
            $this->type = $type;
        }

        /**
         * Get the definitions of the build configuration.
         *
         * @return array The definitions.
         */
        public function getDefinitions(): array
        {
            return $this->definitions;
        }

        /**
         * Set the definitions of the build configuration.
         *
         * @param array $definitions The definitions to set.
         */
        public function setDefinitions(array $definitions): void
        {
            $this->definitions = $definitions;
        }

        /**
         * Get the options of the build configuration.
         *
         * @return array|null The options, or null if none are set.
         */
        public function getOptions(): ?array
        {
            return $this->options;
        }

        /**
         * Set the options of the build configuration.
         *
         * @param array|null $options The options to set, or null to unset.
         */
        public function setOptions(?array $options): void
        {
            $this->options = $options;
        }

        /**
         * Returns an array of file patterns to include certain component files
         *
         * @return string[] An array of file patterns to include components
         */
        public function getIncludedComponents(): array
        {
            return $this->includeComponents;
        }

        /**
         * Returns an array of file patterns to exclude certain component files
         *
         * @return string[] An array of file patterns to exclude components
         */
        public function getExcludedComponents(): array
        {
            return $this->excludeComponents;
        }

        /**
         * Returns an array of file patterns used to include certain resource files
         *
         * @return string[] An array of file patterns to include resources
         */
        public function getIncludedResources(): array
        {
            return $this->includeResources;
        }

        /**
         * Returns an array of file patterns used to exclude certain resource files
         *
         * @return string[] An array of file patterns to exclude resources
         */
        public function getExcludedResources(): array
        {
            return $this->excludeResources;
        }

        /**
         * Creates a default release build configuration.
         *
         * @return BuildConfiguration The default release build configuration.
         */
        public static function defaultRelease(): BuildConfiguration
        {
            return new self([
                'name' => 'release',
                'output' => 'target/release/${ASSEMBLY.PACKAGE}.ncc',
                'type' => BuildType::NCC_PACKAGE->value,
            ]);
        }

        /**
         * Creates a default debug build configuration.
         *
         * @return BuildConfiguration The default debug build configuration.
         */
        public static function defaultDebug(): BuildConfiguration
        {
            return new self([
                'name' => 'debug',
                'output' => 'target/debug/${ASSEMBLY.PACKAGE}.ncc',
                'definitions' => [
                    'NCC_DEBUG' => true
                ],
                'type' => BuildType::NCC_PACKAGE->value,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'output' => $this->output,
                'type' => $this->type->value,
                'definitions' => $this->definitions,
                'options' => $this->options
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): BuildConfiguration
        {
            return new self($data);
        }

        /**
         * @inheritDoc
         */
        public static function validateArray(array $data): void
        {
            if(!isset($data['name']) || !is_string($data['name']) || trim($data['name']) === '')
            {
                throw new InvalidPropertyException('build_configurations.?.name', 'Build configuration name must be a non-empty string');
            }

            if(!isset($data['output']) || !is_string($data['output']) || trim($data['output']) === '')
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.output', 'Build configuration output must be a non-empty string');
            }

            if(!isset($data['type']) || !is_string($data['type']) || !BuildType::tryFrom($data['type']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.type', 'Build configuration type must be a valid build type');
            }

            if(isset($data['definitions']) && !is_array($data['definitions']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.definitions', 'Build configuration definitions must be an array if set');
            }

            if(isset($data['options']) && !is_array($data['options']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.options', 'Build configuration options must be an array if set');
            }
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            self::validateArray($this->toArray());
        }
    }