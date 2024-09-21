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

    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Interfaces\ValidatableObjectInterface;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Dependency implements BytecodeObjectInterface, ValidatableObjectInterface
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var string
         */
        private $version;

        /**
         * @var string|null
         */
        private $source;

        /**
         * Dependency constructor.
         *
         * @param string $name
         * @param string|null $source
         * @param string|null $version
         */
        public function __construct(string $name, ?string $source=null, ?string $version=null)
        {
            $this->name = $name;
            $this->source = $source;
            $this->version = $version ?? Versions::LATEST->value;
        }

        /**
         * Returns the name of the dependency
         *
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the name of the dependency
         *
         * @param string $name
         * @return void
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * Optional. Returns The actual source where NCC can fetch the dependency from
         *
         * @return string|null
         */
        public function getSource(): ?string
        {
            return $this->source;
        }

        /**
         * Sets the actual source where NCC can fetch the dependency from
         *
         * @param string|null $source
         * @return void
         */
        public function setSource(?string $source): void
        {
            $this->source = $source;
        }

        /**
         * Optional. The required version of the dependency or "latest"
         *
         * @return string
         */
        public function getVersion(): string
        {
            return $this->version ?? Versions::LATEST->value;
        }

        /**
         * Returns the required version of the dependency or null if no version is required
         * if the version is not defined, it will be set to Versions::LATEST
         *
         * @param string|null $version
         * @return void
         */
        public function setVersion(?string $version): void
        {
            $this->version = ($version ?? Versions::LATEST->value);
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            if(!Validate::packageName($this->name))
            {
                throw new ConfigurationException(sprintf('Invalid dependency name "%s"', $this->name));
            }

            if($this->version !== Versions::LATEST->value && !Validate::version($this->version))
            {
                throw new ConfigurationException(sprintf('Invalid dependency version "%s"', $this->version));
            }
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;
            $results[($bytecode ? Functions::cbc('version') : 'version')] = $this->version;

            if($this->source !== null && $this->source !== '')
            {
                $results[($bytecode ? Functions::cbc('source') : 'source')] = $this->source;
            }

            return $results;
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Dependency
        {
            $name = Functions::array_bc($data, 'name');

            if($name === null)
            {
                throw new ConfigurationException('Dependency name is required');
            }

            return new self($name, Functions::array_bc($data, 'source'), Functions::array_bc($data, 'version'));
        }
    }