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

    namespace ncc\Objects\ProjectConfiguration\ExecutionPolicy;

    use ncc\Exceptions\ConfigurationException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class ExitHandlers implements BytecodeObjectInterface
    {
        /**
         * The handle to execute when the process exits with a success exit code
         *
         * @var ExitHandle|null
         */
        private $success;

        /**
         * The handle to execute when the process exits with a warning exit code
         *
         * @var ExitHandle|null
         */
        private $warning;

        /**
         * The handle to execute when the process exits with a error exit code
         *
         * @var ExitHandle|null
         */
        private $error;

        /**
         * @return ExitHandle|null
         */
        public function getSuccess(): ?ExitHandle
        {
            return $this->success;
        }

        /**
         * @param ExitHandle|null $success
         */
        public function setSuccess(?ExitHandle $success): void
        {
            $this->success = $success;
        }

        /**
         * @return ExitHandle|null
         */
        public function getWarning(): ?ExitHandle
        {
            return $this->warning;
        }

        /**
         * @param ExitHandle|null $warning
         */
        public function setWarning(?ExitHandle $warning): void
        {
            $this->warning = $warning;
        }

        /**
         * @return ExitHandle|null
         */
        public function getError(): ?ExitHandle
        {
            return $this->error;
        }

        /**
         * @param ExitHandle|null $error
         */
        public function setError(?ExitHandle $error): void
        {
            $this->error = $error;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('success') : 'success') => $this->success?->toArray($bytecode),
                ($bytecode ? Functions::cbc('warning') : 'warning') => $this->warning?->toArray($bytecode),
                ($bytecode ? Functions::cbc('error') : 'error') => $this->error?->toArray($bytecode),
            ];
        }

        /**
         * @inheritDoc
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): ExitHandlers
        {
            $object = new self();

            $object->setSuccess(Functions::array_bc($data, 'success'));
            if($object->getSuccess() !== null)
            {
                $object->setSuccess(ExitHandle::fromArray((array)$object->getSuccess()));
            }

            $object->setWarning(Functions::array_bc($data, 'warning'));
            if($object->getWarning() !== null)
            {
                $object->setWarning(ExitHandle::fromArray((array)$object->getWarning()));
            }

            $object->setError(Functions::array_bc($data, 'error'));
            if($object->getError() !== null)
            {
                $object->setError(ExitHandle::fromArray((array)$object->getError()));
            }

            return $object;
        }
    }