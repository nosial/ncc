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

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Enums\ExceptionCodes;
    use Throwable;

    class PathNotFoundException extends Exception
    {
        /**
         * @var string
         */
        private string $path;

        /**
         * PathNotFoundException constructor.
         *
         * @param string $path
         * @param Throwable|null $previous
         */
        public function __construct(string $path, ?Throwable $previous = null)
        {
            parent::__construct(sprintf('Path "%s" not found', $path), ExceptionCodes::PATH_NOT_FOUND->value, $previous);
            $this->path = $path;
        }

        /**
         * Returns the path that was not found
         *
         * @return string
         */
        public function getPath(): string
        {
            return $this->path;
        }
    }