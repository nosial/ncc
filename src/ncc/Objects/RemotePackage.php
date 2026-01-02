<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Objects;

    use ncc\Enums\RemotePackageType;

    class RemotePackage
    {
        private string $downloadUrl;
        private RemotePackageType $type;
        private string $group;
        private string $project;
        private ?string $version;

        public function __construct(string $downloadUrl, RemotePackageType $type, string $group, string $project, ?string $version = null)
        {
            $this->downloadUrl = $downloadUrl;
            $this->type = $type;
            $this->group = $group;
            $this->project = $project;
            $this->version = $version;
        }

        public function getDownloadUrl(): string
        {
            return $this->downloadUrl;
        }

        public function getType(): RemotePackageType
        {
            return $this->type;
        }

        public function getGroup(): string
        {
            return $this->group;
        }

        public function getProject(): string
        {
            return $this->project;
        }

        public function getVersion(): ?string
        {
            return $this->version;
        }
    }