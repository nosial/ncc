<?php

    namespace ncc\Objects\ComposerJson;

    class Suggestion
    {
        /**
         * The name of the package suggestion
         *
         * @var string
         */
        public $PackageName;

        /**
         * The comment for the suggestion
         *
         * @var string
         */
        public $Comment;

        /**
         * @param string|null $packageName
         * @param string|null $comment
         */
        public function __construct(?string $packageName=null, ?string $comment=null)
        {
            $this->PackageName = $packageName;
            $this->Comment = $comment;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'package_name' => $this->PackageName,
                'comment' => $this->Comment,
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Suggestion
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['package_name']))
                $object->PackageName = $data['package_name'];

            if(isset($data['comment']))
                $object->Comment = $data['comment'];

            return $object;
        }
    }