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

    namespace ncc\Objects;

    use ncc\Interfaces\SerializableObjectInterface;
    use ncc\Objects\NccVersionInformation\Component;

    class NccVersionInformation implements SerializableObjectInterface
    {
        /**
         * The current version of the build
         *
         * @var string|null
         */
        private $version;

        /**
         * The branch of the version
         *
         * @var string|null
         */
        private $branch;

        /**
         * Flags for the current build
         *
         * @var array|null
         */
        private $flags;

        /**
         * An array of components that ncc uses and comes pre-built with
         *
         * @var Component[]
         */
        private $components;

        /**
         * The remote source for where NCC can check for available updates and how to
         * install these updates
         *
         * @var string|null
         */
        private $update_source;

        /**
         * @return string|null
         */
        public function getVersion(): ?string
        {
            return $this->version;
        }

        /**
         * @param string|null $version
         */
        public function setVersion(?string $version): void
        {
            $this->version = $version;
        }

        /**
         * @return string|null
         */
        public function getBranch(): ?string
        {
            return $this->branch;
        }

        /**
         * @param string|null $branch
         */
        public function setBranch(?string $branch): void
        {
            $this->branch = $branch;
        }

        /**
         * @return array|null
         */
        public function getFlags(): ?array
        {
            return $this->flags;
        }

        /**
         * @param array|null $flags
         */
        public function setFlags(?array $flags): void
        {
            $this->flags = $flags;
        }

        /**
         * @return Component[]
         */
        public function getComponents(): array
        {
            return $this->components;
        }

        /**
         * @param Component[] $components
         */
        public function setComponents(array $components): void
        {
            $this->components = $components;
        }

        /**
         * @return string|null
         */
        public function getUpdateSource(): ?string
        {
            return $this->update_source;
        }

        /**
         * @param string|null $update_source
         */
        public function setUpdateSource(?string $update_source): void
        {
            $this->update_source = $update_source;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $components = [];

            foreach($this->components as $component)
            {
                $components[] = $component->toArray();
            }

            return [
                'version' => $this->version,
                'branch' => $this->branch,
                'components' =>$components,
                'flags' => $this->flags
            ];
        }

        /**
         * Constructs an object from an array representation 
         *
         * @param array $data
         * @return NccVersionInformation
         */
        public static function fromArray(array $data): NccVersionInformation
        {
            $object = new self();

            if(isset($data['flags']))
            {
                $object->flags = $data['flags'];
            }

            if(isset($data['branch']))
            {
                $object->branch = $data['branch'];
            }

            if(isset($data['components']))
            {
                foreach($data['components'] as $datum)
                {
                    $object->components[] = Component::fromArray($datum);
                }
            }

            if(isset($data['version']))
            {
                $object->version = $data['version'];
            }

            return $object;
        }
    }