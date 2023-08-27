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

    class Suggestion
    {
        /**
         * The name of the package suggestion
         *
         * @var string
         */
        public $package_name;

        /**
         * The comment for the suggestion
         *
         * @var string
         */
        public $comment;

        /**
         * @param string|null $packageName
         * @param string|null $comment
         */
        public function __construct(?string $packageName=null, ?string $comment=null)
        {
            $this->package_name = $packageName;
            $this->comment = $comment;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'package_name' => $this->package_name,
                'comment' => $this->comment,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Suggestion
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['package_name']))
                $object->package_name = $data['package_name'];

            if(isset($data['comment']))
                $object->comment = $data['comment'];

            return $object;
        }
    }