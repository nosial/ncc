<?php

    namespace ncc\Objects\ComposerJson;

    class NamespacePointer
    {
        /**
         * Namespace name that maps to the path route
         *
         * @var string|null
         */
        public $Namespace;

        /**
         * The relative path to the source code to index
         *
         * @var string
         */
        public $Path;

        /**
         * Public constructor
         *
         * @param string|null $name
         * @param string|null $path
         */
        public function __construct(?string $name=null, ?string $path=null)
        {
            $this->Namespace = $name;
            $this->Path = $path;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'namespace' => $this->Namespace,
                'path' => $this->Path
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $array
         * @return static
         */
        public static function fromArray(array $array): self
        {
            $object = new self();

            if(isset($array['namespace']))
                $object->Namespace = $array['namespace'];

            if(isset($array['path']))
                $object->Path = $array['path'];

            return $object;
        }
    }