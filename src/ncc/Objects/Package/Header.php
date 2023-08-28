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

    namespace ncc\Objects\Package;

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Objects\ProjectConfiguration\UpdateSource;
    use ncc\Utilities\Functions;

    class Header implements BytecodeObjectInterface
    {
        /**
         * The compiler extension information that was used to build the package
         *
         * @var Compiler
         */
        private $compiler_extension;

        /**
         * An array of constants that are set when the package is imported or executed during runtime.
         *
         * @var array
         */
        private $runtime_constants;

        /**
         * The version of NCC that was used to compile the package, can be used for backwards compatibility
         *
         * @var string
         */
        private $compiler_version;

        /**
         * An array of options to pass on to the extension
         *
         * @var array|null
         */
        private $options;

        /**
         * The optional update source to where the package can be updated from
         *
         * @var UpdateSource|null
         */
        private $update_source;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->compiler_extension = new Compiler();
            $this->runtime_constants = [];
            $this->options = [];
        }

        /**
         * @return Compiler
         */
        public function getCompilerExtension(): Compiler
        {
            return $this->compiler_extension;
        }

        /**
         * @param Compiler $compiler_extension
         */
        public function setCompilerExtension(Compiler $compiler_extension): void
        {
            $this->compiler_extension = $compiler_extension;
        }

        /**
         * @return array
         */
        public function getRuntimeConstants(): array
        {
            return $this->runtime_constants;
        }

        /**
         * @param array $runtime_constants
         */
        public function setRuntimeConstants(array $runtime_constants): void
        {
            $this->runtime_constants = $runtime_constants;
        }

        /**
         * @return string
         */
        public function getCompilerVersion(): string
        {
            return $this->compiler_version;
        }

        /**
         * @param string $compiler_version
         */
        public function setCompilerVersion(string $compiler_version): void
        {
            $this->compiler_version = $compiler_version;
        }

        /**
         * @return array|null
         */
        public function getOptions(): ?array
        {
            return $this->options;
        }

        /**
         * @param array|null $options
         */
        public function setOptions(?array $options): void
        {
            $this->options = $options;
        }

        /**
         * @param string $key
         * @param $value
         * @return void
         */
        public function setOption(string $key, $value): void
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
         * @param string $key
         * @return mixed|null
         */
        public function getOption(string $key): mixed
        {
            return $this->options[$key] ?? null;
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
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('compiler_extension') : 'compiler_extension') => $this->compiler_extension->toArray(),
                ($bytecode ? Functions::cbc('runtime_constants') : 'runtime_constants') => $this->runtime_constants,
                ($bytecode ? Functions::cbc('compiler_version') : 'compiler_version') => $this->compiler_version,
                ($bytecode ? Functions::cbc('update_source') : 'update_source') => ($this->update_source?->toArray($bytecode)),
                ($bytecode ? Functions::cbc('options') : 'options') => $this->options,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Header
        {
            $object = new self();

            $object->compiler_extension = Functions::array_bc($data, 'compiler_extension');
            $object->runtime_constants = Functions::array_bc($data, 'runtime_constants');
            $object->compiler_version = Functions::array_bc($data, 'compiler_version');
            $object->update_source = Functions::array_bc($data, 'update_source');
            $object->options = Functions::array_bc($data, 'options');

            if($object->compiler_extension !== null)
            {
                $object->compiler_extension = Compiler::fromArray($object->compiler_extension);
            }

            if($object->update_source !== null)
            {
                $object->update_source = UpdateSource::fromArray($object->update_source);
            }

            return $object;
        }
    }