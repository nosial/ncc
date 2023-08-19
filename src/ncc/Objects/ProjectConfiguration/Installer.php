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

    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Utilities\Functions;

    class Installer implements BytecodeObjectInterface
    {
        /**
         * An array of execution policies to execute pre-installation
         *
         * @var string[]|null
         */
        public $pre_install;

        /**
         * An array of execution policies to execute post-installation
         *
         * @var string[]|null
         */
        public $post_install;

        /**
         * An array of execution policies to execute pre-uninstallation
         *
         * @var string[]|null
         */
        public $pre_uninstall;

        /**
         * An array of execution policies to execute post-uninstallation
         *
         * @var string[]|null
         */
        public $post_uninstall;

        /**
         * An array of execution policies to execute pre-update
         *
         * @var string[]|null
         */
        public $pre_update;

        /**
         * An array of execution policies to execute post-update
         *
         * @var string[]|null
         */
        public $post_update;

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->pre_install !== null && count($this->pre_install) > 0)
            {
                $results[($bytecode ? Functions::cbc('pre_install') : 'pre_install')] = $this->pre_install;
            }

            if($this->post_install !== null && count($this->post_install) > 0)
            {
                $results[($bytecode ? Functions::cbc('post_install') : 'post_install')] = $this->post_install;
            }

            if($this->pre_uninstall !== null && count($this->pre_uninstall) > 0)
            {
                $results[($bytecode ? Functions::cbc('pre_uninstall') : 'pre_uninstall')] = $this->pre_uninstall;
            }

            if($this->post_uninstall !== null && count($this->post_uninstall) > 0)
            {
                $results[($bytecode ? Functions::cbc('post_uninstall') : 'post_uninstall')] = $this->post_uninstall;
            }

            if($this->pre_update !== null && count($this->pre_update) > 0)
            {
                $results[($bytecode ? Functions::cbc('pre_update') : 'pre_update')] = $this->pre_update;
            }

            if($this->post_update !== null && count($this->post_update) > 0)
            {
                $results[($bytecode ? Functions::cbc('post_update') : 'post_update')] = $this->post_update;
            }

            return $results;
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