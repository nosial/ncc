<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ProjectConfiguration;

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
         * Public Constructor
         */
        public function __construct()
        {
            $this->Compiler = new Compiler();
            $this->Options = [];
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

            $ReturnResults[($bytecode ? Functions::cbc('compiler') : 'compiler')] = $this->Compiler->toArray($bytecode);
            $ReturnResults[($bytecode ? Functions::cbc('options') : 'options')] = $this->Options;

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

            return $ProjectObject;
        }
    }