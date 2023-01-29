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

    use ncc\Exceptions\InvalidPropertyValueException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedExtensionVersionException;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Project
    {
        /**
         * @var Compiler
         */
        public $Compiler;

        /**
         * @var array
         */
        public $Options;

        /**
         * @var UpdateSource|null
         */
        public $UpdateSource;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Compiler = new Compiler();
            $this->Options = [];
        }

        /**
         * Validates the Project object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidPropertyValueException
         * @throws RuntimeException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedExtensionVersionException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!$this->Compiler->validate($throw_exception))
                return False;

            return True;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $ReturnResults = [];

            $ReturnResults[($bytecode ? Functions::cbc('compiler') : 'compiler')] = $this->Compiler->toArray();
            $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;

            if($this->UpdateSource !== null)
                $ReturnResults[($bytecode ? Functions::cbc('update_source') : 'update_source')] = $this->UpdateSource->toArray($bytecode);

            return $ReturnResults;
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return Project
         */
        public static function fromArray(array $data): Project
        {
            $ProjectObject = new Project();

            if(Functions::array_bc($data, 'compiler') !== null)
            {
                $ProjectObject->Compiler = Compiler::fromArray(Functions::array_bc($data, 'compiler'));
            }

            if(Functions::array_bc($data, 'options') !== null)
            {
                $ProjectObject->Options = Functions::array_bc($data, 'options');
            }

            if(Functions::array_bc($data, 'update_source') !== null)
            {
                $ProjectObject->UpdateSource = UpdateSource::fromArray(Functions::array_bc($data, 'update_source'));
            }

            return $ProjectObject;
        }
    }