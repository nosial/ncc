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

    use ncc\Interfaces\SerializableInterface;

    class WebTemplateConfiguration implements SerializableInterface
    {
        private string $name;
        private array $resources;
        private array $webApplication;
        private array $webLocale;

        /**
         * WebTemplateConfiguration constructor.
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->name = $data['name'] ?? '';
            $this->resources = $data['resources'] ?? [];
            $this->webApplication = $data['web_application'] ?? [];
            $this->webLocale = $data['web_locale'] ?? [];
        }

        /**
         * Returns the name of the template.
         *
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Returns the list of resources included in the template.
         *
         * @return array
         */
        public function getResources(): array
        {
            return $this->resources;
        }

        /**
         * Returns the web application configuration included in the template.
         *
         * @return array
         */
        public function getWebApplication(): array
        {
            return $this->webApplication;
        }

        /**
         * Returns the web locale configuration included in the template.
         *
         * @return array
         */
        public function getWebLocale(): array
        {
            return $this->webLocale;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'resources' => $this->resources,
                'web_application' => $this->webApplication,
                'web_locale' => $this->webLocale,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): WebTemplateConfiguration
        {
            return new self($data);
        }
    }