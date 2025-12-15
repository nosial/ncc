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

    namespace ncc\ArchiveExtractors;

    use ncc\Exceptions\OperationException;
    use ncc\Interfaces\ArchiveInterface;

    class ZipArchive implements ArchiveInterface
    {
        /**
         * @inheritDoc
         */
        public static function extract(string $archivePath, string $destinationPath): void
        {
            if(!extension_loaded('zip'))
            {
                throw new OperationException('ZIP extension is not loaded.');
            }

            $zip = new \ZipArchive();
            if (!$zip->open($archivePath))
            {
                throw new OperationException(sprintf('Failed to open ZIP archive: %s', $archivePath));
            }

            if (!$zip->extractTo($destinationPath))
            {
                $zip->close();
                throw new OperationException(sprintf('Failed to extract ZIP archive to: %s', $destinationPath));
            }

            $zip->close();
        }
    }