<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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
    
    namespace ncc\Objects\ComposerJson;

    use ncc\Interfaces\SerializableObjectInterface;

    class NamespacePointer implements SerializableObjectInterface
    {
        /**
         * Namespace name that maps to the path route
         *
         * @var string|null
         */
        private $namespace;

        /**
         * The relative path to the source code to index
         *
         * @var string
         */
        private $path;

        /**
         * @return string|null
         */
        public function getNamespace(): ?string
        {
            return $this->namespace;
        }

        /**
         * @return string|null
         */
        public function getPath(): ?string
        {
            return $this->path;
        }

        /**
         * Public constructor
         *
         * @param string|null $name
         * @param string|null $path
         */
        public function __construct(?string $name=null, ?string $path=null)
        {
            $this->namespace = $name;
            $this->path = $path;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'namespace' => $this->namespace,
                'path' => $this->path
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): NamespacePointer
        {
            $object = new self();

            if(isset($array['namespace']))
            {
                $object->namespace = $array['namespace'];
            }

            if(isset($array['path']))
            {
                $object->path = $array['path'];
            }

            return $object;
        }
    }