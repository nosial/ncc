<?php

    namespace ncc\Objects\ProjectConfiguration;

    class Dependency
    {
        /**
         * The name of the dependency
         *
         * @var string
         */
        public $Name;

        /**
         * Optional. The source from where ncc can fetch the dependency from
         *
         * @var string|null
         */
        public $Source;

        /**
         * Optional. The required version of the dependency or "latest"
         *
         * @var string|null
         */
        public $Version;

        // TODO: Add validation function here

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $ReturnResults = [];

            $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('name') : 'name')] = $this->Name;

            if($this->Source !== null && strlen($this->Source) > 0)
            {
                $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('source') : 'source')] = $this->Source;
            }

            if($this->Version !== null && strlen($this->Version) > 0)
            {
                $ReturnResults[($bytecode ? \ncc\Utilities\Functions::cbc('version') : 'version')] = $this->Version;
            }
        
            return $ReturnResults;
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return Dependency
         */
        public static function fromArray(array $data): Dependency
        {
            $DependencyObject = new Dependency();

            $DependencyObject->Name = \ncc\Utilities\Functions::array_bc($data, 'name');
            $DependencyObject->Source = \ncc\Utilities\Functions::array_bc($data, 'source');
            $DependencyObject->Version = \ncc\Utilities\Functions::array_bc($data, 'version');

            return $DependencyObject;
        }
    }