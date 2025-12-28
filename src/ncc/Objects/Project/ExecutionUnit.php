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
    use ncc\Enums\ExecutionMode;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Interfaces\SerializableInterface;
    use ncc\Interfaces\ValidatorInterface;

    class ExecutionUnit implements SerializableInterface, ValidatorInterface
    {
        private string $name;
        private ExecutionUnitType $type;
        private ExecutionMode $mode;
        private string $entryPoint;
        private ?string $workingDirectory;
        private ?array $arguments;
        private ?array $environment;
        private ?array $requiredFiles;
        private ?int $timeout;

        /**
         * Public Constructor for the Execution Unit
         *
         * @param array $data The array representation of the execution unit object to construct from
         */
        public function __construct(array $data)
        {
            if(empty($data['name']))
            {
                throw new InvalidArgumentException('Property \'name\' is required');
            }

            if(empty($data['entry']))
            {
                throw new InvalidArgumentException('Property \'entry\' is required');
            }

            $this->name = $data['name'];
            $this->type = ExecutionUnitType::tryFrom($data['type'] ?? '') ?? ExecutionUnitType::PHP;
            $this->mode = ExecutionMode::tryFrom($data['mode'] ?? '') ?? ExecutionMode::AUTO;
            $this->entryPoint = $data['entry'];
            $this->workingDirectory = $data['working_directory'] ?? null;
            $this->arguments = $data['arguments'] ?? null;
            $this->environment = $data['environment'] ?? null;
            $this->requiredFiles = $data['required_files'] ?? null;
            $this->timeout = $data['timeout'] ?? null;
        }

        /**
         * Get the name of the execution unit.
         *
         * @return string The name of the execution unit
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Set the name of the execution unit.
         *
         * @param string $name The new name of the execution unit
         * @throws InvalidArgumentException if the name is empty
         */
        public function setName(string $name): void
        {
            if(empty($name))
            {
                throw new InvalidArgumentException('Execution Unit name cannot be empty');
            }

            $this->name = $name;
        }

        /**
         * Get the type of the execution unit.
         *
         * @return ExecutionUnitType The type of the execution unit
         */
        public function getType(): ExecutionUnitType
        {
            return $this->type;
        }

        /**
         * Set the type of the execution unit.
         *
         * @param ExecutionUnitType $type The new type of the execution unit
         */
        public function setType(ExecutionUnitType $type): void
        {
            $this->type = $type;
        }

        /**
         * Get the execution mode of the execution unit.
         *
         * @return ExecutionMode The execution mode of the execution unit
         */
        public function getMode(): ExecutionMode
        {
            return $this->mode;
        }

        /**
         * Set the execution mode of the execution unit.
         *
         * @param ExecutionMode $mode The new execution mode of the execution unit
         */
        public function setMode(ExecutionMode $mode): void
        {
            $this->mode = $mode;
        }

        /**
         * Get the entry point of the execution unit. When compiled, this property becomes the data to be executed,
         * otherwise
         *
         * @return string The entry point of the execution unit
         */
        public function getEntryPoint(): string
        {
            return $this->entryPoint;
        }

        /**
         * Set the entry point of the execution unit.
         *
         * @param string $entryPoint The new entry point of the execution unit
         * @throws InvalidArgumentException if the entry point is empty
         */
        public function setEntryPoint(string $entryPoint): void
        {
            if(empty($entryPoint))
            {
                throw new InvalidArgumentException('The Execution Unit\'s Entry Point cannot be empty!');
            }

            $this->entryPoint = $entryPoint;
        }

        /**
         * Get the working directory of the execution unit.
         *
         * @return string|null The working directory of the execution unit
         */
        public function getWorkingDirectory(): ?string
        {
            return $this->workingDirectory;
        }

        /**
         * Set the working directory of the execution unit.
         *
         * @param string|null $workingDirectory The new working directory of the execution unit
         */
        public function setWorkingDirectory(?string $workingDirectory): void
        {
            if($workingDirectory !== null && empty($workingDirectory))
            {
                throw new InvalidArgumentException('The Execution Unit\'s Working Directory cannot be empty if it\'s not null');
            }

            $this->workingDirectory = $workingDirectory;
        }

        /**
         * Get the arguments of the execution unit.
         *
         * @return array|null The arguments of the execution unit or null if none are set
         */
        public function getArguments(): ?array
        {
            return $this->arguments;
        }

        /**
         * Set the arguments of the execution unit.
         *
         * @param array|null $arguments The new arguments of the execution unit or null to unset
         * @throws InvalidArgumentException if any argument is not a string
         */
        public function setArguments(?array $arguments): void
        {
            if($arguments !== null)
            {
                foreach($arguments as $arg)
                {
                    if(!is_string($arg))
                    {
                        throw new InvalidArgumentException('Arguments must be strings');
                    }
                }
            }

            $this->arguments = $arguments;
        }

        /**
         * Get the environment variables of the execution unit.
         *
         * @return array|null The environment variables of the execution unit or null if none are set
         */
        public function getEnvironment(): ?array
        {
            return $this->environment;
        }

        /**
         * Set the environment variables of the execution unit.
         *
         * @param array|null $environment The new environment variables of the execution unit or null to unset
         * @throws InvalidArgumentException if any key is not a non-empty string or any value is not a string
         */
        public function setEnvironment(?array $environment): void
        {
            if($environment !== null)
            {
                foreach($environment as $key => $value)
                {
                    if(!is_string($key) || trim($key) === '')
                    {
                        throw new InvalidArgumentException('Environment variable keys must be non-empty strings');
                    }

                    if(!is_string($value))
                    {
                        throw new InvalidArgumentException('Environment variable values must be strings');
                    }
                }
            }

            $this->environment = $environment;
        }

        public function getRequiredFiles(): ?array
        {
            return $this->requiredFiles;
        }

        public function setRequiredFiles(?array $requiredFiles): void
        {
            $this->requiredFiles = $requiredFiles;
        }

        public function addRequiredFile(string $requiredFile): void
        {
            if($this->requiredFiles === null)
            {
                $this->requiredFiles = [];
            }

            if(isset($this->requiredFiles[$requiredFile]))
            {
                return;
            }

            $this->requiredFiles[] = $requiredFile;
        }

        public function getTimeout(): ?int
        {
            return $this->timeout;
        }

        public function setTimeout(?int $timeout): void
        {
            $this->timeout = $timeout;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'type' => $this->type->value,
                'mode' => $this->mode->value,
                'entry' => $this->entryPoint,
                'working_directory' => $this->workingDirectory,
                'arguments' => $this->arguments,
                'environment' => $this->environment,
                'required_files' => $this->requiredFiles,
                'timeout' => $this->timeout,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ExecutionUnit
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
                throw new InvalidPropertyException('execution_units.?', 'Property \'name\' is required and must be a non-empty string');
            }

            if(!isset($data['entry']) || !is_string($data['entry']) || trim($data['entry']) === '')
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.entry' , 'Property \'entry\' is required and must be a non-empty string');
            }

            if(isset($data['type']) && !in_array($data['type'], array_map(fn($e) => $e->value, ExecutionUnitType::cases()), true))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.type', 'Property \'type\' must be one of: ' . implode(', ', array_map(fn($e) => $e->value, ExecutionUnitType::cases())));
            }

            if(isset($data['mode']) && !in_array($data['mode'], array_map(fn($e) => $e->value, ExecutionMode::cases()), true))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.mode', 'Property \'mode\' must be one of: ' . implode(', ', array_map(fn($e) => $e->value, ExecutionMode::cases())));
            }

            if(isset($data['working_directory']) && (!is_string($data['working_directory']) || trim($data['working_directory']) === ''))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.working_directory', 'Property \'working_directory\' must be a non-empty string if set');
            }

            if(isset($data['arguments']) && !is_array($data['arguments']))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.arguments', 'Property \'arguments\' must be an array if set');
            }

            if(isset($data['arguments']) && is_array($data['arguments']))
            {
                foreach($data['arguments'] as $index => $arg)
                {
                    if(!is_string($arg))
                    {
                        throw new InvalidPropertyException('execution_units.' . $data['name'] . '.arguments.' . $index, 'Each argument must be a string');
                    }
                }
            }

            if(isset($data['environment']) && !is_array($data['environment']))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.environment', 'Property \'environment\' must be an array if set');
            }

            if(isset($data['environment']) && is_array($data['environment']))
            {
                foreach($data['environment'] as $key => $value)
                {
                    if(!is_string($key) || trim($key) === '')
                    {
                        throw new InvalidPropertyException('execution_units.' . $data['name'] . '.environment', 'Environment variable keys must be non-empty strings');
                    }

                    if(!is_string($value))
                    {
                        throw new InvalidPropertyException('execution_units.' . $data['name'] . '.environment.' . $key, 'Environment variable values must be strings');
                    }
                }
            }

            if(isset($data['required_files']) && $data['required_files'] !== null)
            {
                if(!is_array($data['required_files']))
                {
                    throw new InvalidPropertyException('execution_units.' . $data['name'] . '.required_files', 'Property \'required_files\' must be an array if set');
                }

                foreach($data['required_files'] as $index => $file)
                {
                    if(!is_string($file) || trim($file) === '')
                    {
                        throw new InvalidPropertyException('execution_units.' . $data['name'] . '.required_files.' . $index, 'Each required file must be a non-empty string');
                    }
                }
            }

            if(isset($data['timeout']) && !is_int($data['timeout']))
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.timeout', 'Property \'timeout\' must be an integer if set');
            }

            if(isset($data['timeout']) && is_int($data['timeout']) && $data['timeout'] <= 0)
            {
                throw new InvalidPropertyException('execution_units.' . $data['name'] . '.timeout', 'Timeout must be a positive integer');
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