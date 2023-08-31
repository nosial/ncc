<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Project implements BytecodeObjectInterface
    {
        /**
         * @var Compiler
         */
        private $compiler;

        /**
         * @var array
         */
        private $options;

        /**
         * @var UpdateSource|null
         */
        private $update_source;

        /**
         * Public Constructor
         */
        public function __construct(Compiler $compiler)
        {
            $this->compiler = $compiler;
            $this->options = [];
        }

        /**
         * @return Compiler
         */
        public function getCompiler(): Compiler
        {
            return $this->compiler;
        }

        /**
         * @param Compiler $compiler
         */
        public function setCompiler(Compiler $compiler): void
        {
            $this->compiler = $compiler;
        }

        /**
         * @return array
         */
        public function getOptions(): array
        {
            return $this->options;
        }

        /**
         * @param array $options
         */
        public function setOptions(array $options): void
        {
            $this->options = $options;
        }

        /**
         * @param string $key
         * @param mixed $value
         * @return void
         */
        public function addOption(string $key, mixed $value): void
        {
            $this->options[$key] = $value;
        }

        /**
         * @param string $key
         * @return void
         */
        public function removeOption(string $key): void
        {
            unset($this->options[$key]);
        }

        /**
         * @return UpdateSource|null
         */
        public function getUpdateSource(): ?UpdateSource
        {
            return $this->update_source;
        }

        /**
         * @param UpdateSource|null $update_source
         */
        public function setUpdateSource(?UpdateSource $update_source): void
        {
            $this->update_source = $update_source;
        }

        /**
         * Validates the Project object
         *
         * @return bool
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function validate(): bool
        {
            $this->compiler->validate();

            return True;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            $results[($bytecode ? Functions::cbc('compiler') : 'compiler')] = $this->compiler->toArray();
            $results[($bytecode ? Functions::cbc('options') : 'options')] = $this->options;

            if($this->update_source !== null)
            {
                $results[($bytecode ? Functions::cbc('update_source') : 'update_source')] = $this->update_source->toArray($bytecode);
            }

            return $results;
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return Project
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public static function fromArray(array $data): Project
        {
            if(Functions::array_bc($data, 'compiler') !== null)
            {
                $object = new self(Compiler::fromArray(Functions::array_bc($data, 'compiler')));
            }
            else
            {
                throw new ConfigurationException('The project configuration is missing the required property "compiler" in the project section.');
            }

            $object->options = Functions::array_bc($data, 'options') ?? [];
            $object->update_source = Functions::array_bc($data, 'update_source');
            if($object->update_source !== null)
            {
                $object->update_source = UpdateSource::fromArray(Functions::array_bc($data, 'update_source'));
            }

            return $object;
        }
    }