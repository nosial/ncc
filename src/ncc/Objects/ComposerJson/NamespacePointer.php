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

namespace ncc\Objects\ComposerJson;

    class NamespacePointer
    {
        /**
         * Namespace name that maps to the path route
         *
         * @var string|null
         */
        public $Namespace;

        /**
         * The relative path to the source code to index
         *
         * @var string
         */
        public $Path;

        /**
         * Public constructor
         *
         * @param string|null $name
         * @param string|null $path
         */
        public function __construct(?string $name=null, ?string $path=null)
        {
            $this->Namespace = $name;
            $this->Path = $path;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'namespace' => $this->Namespace,
                'path' => $this->Path
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $array
         * @return static
         */
        public static function fromArray(array $array): self
        {
            $object = new self();

            if(isset($array['namespace']))
                $object->Namespace = $array['namespace'];

            if(isset($array['path']))
                $object->Path = $array['path'];

            return $object;
        }
    }