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
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Dependency implements BytecodeObjectInterface
    {
        /**
         * The name of the dependency
         *
         * @var string
         */
        public $name;

        /**
         * Optional. The type of source from where ncc can fetch the dependency from
         *
         * @var string|null
         */
        public $source_type;

        /**
         * Optional. The actual source where NCC can fetch the dependency from
         *
         * @var string|null
         */
        public $source;

        /**
         * Optional. The required version of the dependency or "latest"
         *
         * @var string|null
         */
        public $version;

        /**
         * Validates the dependency configuration
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         */
        public function validate(bool $throw_exception): bool
        {
            if(!Validate::packageName($this->name))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid dependency name "%s"', $this->name));
                }

                return false;
            }

            if($this->version !== null && !Validate::version($this->version))
            {
                if($throw_exception)
                {
                    throw new ConfigurationException(sprintf('Invalid dependency version "%s"', $this->version));
                }

                return false;
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            $results[($bytecode ? Functions::cbc('name') : 'name')] = $this->name;

            if($this->source_type !== null && $this->source_type !== '')
            {
                $results[($bytecode ? Functions::cbc('source_type') : 'source_type')] = $this->source_type;
            }

            if($this->source !== null && $this->source !== '')
            {
                $results[($bytecode ? Functions::cbc('source') : 'source')] = $this->source;
            }

            if($this->version !== null && $this->version !== '')
            {
                $results[($bytecode ? Functions::cbc('version') : 'version')] = $this->version;
            }

            return $results;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Dependency
        {
            $object = new self();

            $object->name = Functions::array_bc($data, 'name');
            $object->source_type = Functions::array_bc($data, 'source_type');
            $object->source = Functions::array_bc($data, 'source');
            $object->version = Functions::array_bc($data, 'version');

            return $object;
        }
    }