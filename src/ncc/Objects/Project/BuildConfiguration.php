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

    use ncc\Enums\BuildType;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;

    class BuildConfiguration implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private string $output;
        private BuildType $type;
        private ?array $options;

        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? 'release';
            $this->output = $data['output'] ?? 'out';
            $this->type = BuildType::tryFrom($data['type']) ?? BuildType::NCC_PACKAGE;
            $this->options = $data['options'] ?? null;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function setName(string $name): void
        {
            if(empty($name))
            {
                throw new \InvalidArgumentException('Build configuration name cannot be empty');
            }

            $this->name = $name;
        }

        public function getOutput(): string
        {
            return $this->output;
        }

        public function setOutput(string $output): void
        {
            if(empty($output))
            {
                throw new \InvalidArgumentException('Build configuration output cannot be empty');
            }

            $this->output = $output;
        }

        public function getType(): BuildType
        {
            return $this->type;
        }

        public function setType(BuildType $type): void
        {
            $this->type = $type;
        }

        public function getOptions(): ?array
        {
            return $this->options;
        }

        public function setOptions(?array $options): void
        {
            $this->options = $options;
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
                throw new InvalidPropertyException('build_configurations.?', 'name', 'Build configuration name must be a non-empty string');
            }

            if(!isset($data['output']) || !is_string($data['output']) || trim($data['output']) === '')
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.output', 'Build configuration output must be a non-empty string');
            }

            if(!isset($data['type']) || !is_string($data['type']) || !BuildType::tryFrom($data['type']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.type', 'Build configuration type must be a valid build type');
            }

            if(isset($data['options']) && !is_array($data['options']))
            {
                throw new InvalidPropertyException('build_configurations.' . $data['name'] . '.options', 'Build configuration options must be an array if set');
            }
        }
    }