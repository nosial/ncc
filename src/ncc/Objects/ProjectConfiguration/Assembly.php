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

    namespace ncc\Objects\ProjectConfiguration;

    use ncc\Interfaces\SerializableInterface;

    class Assembly implements SerializableInterface
    {
        private string $name;
        private string $package;
        private string $version;
        private ?string $description;
        private ?string $author;
        private ?string $organization;
        private ?string $product;
        private ?string $copyright;
        private ?string $trademark;

        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? 'N/A';
            $this->package = $data['package'] ?? 'N/A';
            $this->version = $data['version'] ?? '0.0.0';
            $this->description = $data['description'] ?? null;
            $this->author = $data['author'] ?? null;
            $this->organization = $data['organization'] ?? null;
            $this->product = $data['product'] ?? null;
            $this->copyright = $data['copyright'] ?? null;
            $this->trademark = $data['trademark'] ?? null;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function getPackage(): string
        {
            return $this->package;
        }

        public function getVersion(): string
        {
            return $this->version;
        }

        public function getDescription(): ?string
        {
            return $this->description;
        }

        public function getAuthor(): ?string
        {
            return $this->author;
        }

        public function getOrganization(): ?string
        {
            return $this->organization;
        }

        public function getProduct(): ?string
        {
            return $this->product;
        }

        public function getCopyright(): ?string
        {
            return $this->copyright;
        }

        public function getTrademark(): ?string
        {
            return $this->trademark;
        }

        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'package' => $this->package,
                'version' => $this->version,
                'description' => $this->description,
                'author' => $this->author,
                'organization' => $this->organization,
                'product' => $this->product,
                'copyright' => $this->copyright,
                'trademark' => $this->trademark
            ];
        }

        public static function fromArray(array $data): Assembly
        {
            return new self($data);
        }
    }