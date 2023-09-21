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
    use ncc\Objects\ProjectConfiguration\UpdateSource\Repository;
    use ncc\Utilities\Functions;

    class UpdateSource implements BytecodeObjectInterface
    {
        /**
         * The string format of where the source is located.
         *
         * @var string
         */
        private $source;

        /**
         * The repository to use for the source
         *
         * @var Repository|null
         */
        private $repository;

        /**
         * @param string $source
         * @param Repository|null $repository
         */
        public function __construct(string $source, ?Repository $repository=null)
        {
            $this->source = $source;
            $this->repository = $repository;
        }

        /**
         * @return string
         */
        public function getSource(): string
        {
            return $this->source;
        }

        /**
         * @param string $source
         */
        public function setSource(string $source): void
        {
            $this->source = $source;
        }

        /**
         * @return Repository|null
         */
        public function getRepository(): ?Repository
        {
            return $this->repository;
        }

        /**
         * @param Repository|null $repository
         */
        public function setRepository(?Repository $repository): void
        {
            $this->repository = $repository;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('source') : 'source') => $this->source,
                ($bytecode ? Functions::cbc('repository') : 'repository') => ($this->repository?->toArray($bytecode))
            ];
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): UpdateSource
        {
            $source = Functions::array_bc($data, 'source');
            if($source === null)
            {
                throw new ConfigurationException('The UpdateSource requires the "source" property');
            }

            $repository = Functions::array_bc($data, 'repository');
            if($repository !== null)
            {
                $repository = Repository::fromArray($repository);
            }


            return new self($source, $repository);
        }
    }