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

    namespace ncc\Objects\Package;

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Installer implements BytecodeObjectInterface
    {
        /**
         * An array of execution policies to execute pre install
         *
         * @var string[]|null
         */
        private $pre_install;

        /**
         * An array of execution policies to execute post install
         *
         * @var string[]|null
         */
        private $post_install;

        /**
         * An array of execution policies to execute pre uninstall
         *
         * @var string[]|null
         */
        private $pre_uninstall;

        /**
         * An array of execution policies to execute post uninstall
         *
         * @var string[]|null
         */
        private $post_uninstall;

        /**
         * An array of execution policies to execute pre update
         *
         * @var string[]|null
         */
        private $pre_update;

        /**
         * An array of execution policies to execute post update
         *
         * @var string[]|null
         */
        private $post_update;

        /**
         * @return string[]|null
         */
        public function getPreInstall(): ?array
        {
            return $this->pre_install;
        }

        /**
         * @param string[]|null $pre_install
         */
        public function setPreInstall(?array $pre_install): void
        {
            $this->pre_install = $pre_install;
        }

        /**
         * @return string[]|null
         */
        public function getPostInstall(): ?array
        {
            return $this->post_install;
        }

        /**
         * @param string[]|null $post_install
         */
        public function setPostInstall(?array $post_install): void
        {
            $this->post_install = $post_install;
        }

        /**
         * @return string[]|null
         */
        public function getPreUninstall(): ?array
        {
            return $this->pre_uninstall;
        }

        /**
         * @param string[]|null $pre_uninstall
         */
        public function setPreUninstall(?array $pre_uninstall): void
        {
            $this->pre_uninstall = $pre_uninstall;
        }

        /**
         * @return string[]|null
         */
        public function getPostUninstall(): ?array
        {
            return $this->post_uninstall;
        }

        /**
         * @param string[]|null $post_uninstall
         */
        public function setPostUninstall(?array $post_uninstall): void
        {
            $this->post_uninstall = $post_uninstall;
        }

        /**
         * @return string[]|null
         */
        public function getPreUpdate(): ?array
        {
            return $this->pre_update;
        }

        /**
         * @param string[]|null $pre_update
         */
        public function setPreUpdate(?array $pre_update): void
        {
            $this->pre_update = $pre_update;
        }

        /**
         * @return string[]|null
         */
        public function getPostUpdate(): ?array
        {
            return $this->post_update;
        }

        /**
         * @param string[]|null $post_update
         */
        public function setPostUpdate(?array $post_update): void
        {
            $this->post_update = $post_update;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            if(
                $this->pre_install === null && $this->post_install === null &&
                $this->pre_uninstall === null && $this->post_uninstall === null &&
                $this->pre_update === null && $this->post_update === null
            )
            {
                return [];
            }

            return [
                ($bytecode ? Functions::cbc('pre_install') : 'pre_install') => $this->pre_install,
                ($bytecode ? Functions::cbc('post_install') : 'post_install') => $this->post_install,
                ($bytecode ? Functions::cbc('pre_uninstall') : 'pre_uninstall') => $this->pre_uninstall,
                ($bytecode? Functions::cbc('post_uninstall') : 'post_uninstall') => $this->post_uninstall,
                ($bytecode? Functions::cbc('pre_update') : 'pre_update') => $this->pre_update,
                ($bytecode? Functions::cbc('post_update') : 'post_update') => $this->post_update
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Installer
        {
            $object = new self();

            $object->pre_install = Functions::array_bc($data, 'pre_install');
            $object->post_install = Functions::array_bc($data, 'post_install');
            $object->pre_uninstall = Functions::array_bc($data, 'pre_uninstall');
            $object->post_uninstall = Functions::array_bc($data, 'post_uninstall');
            $object->pre_update = Functions::array_bc($data, 'pre_update');
            $object->post_update = Functions::array_bc($data, 'post_update');

            return $object;
        }
    }