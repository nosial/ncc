<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ComposerJson;

    class Author
    {
        /**
         * The author's name. Usually their real name
         *
         * @var string|null
         */
        public $Name;

        /**
         * The author's email address
         *
         * @var string|null
         */
        public $Email;

        /**
         * URL to the author's website
         *
         * @var string|null
         */
        public $Homepage;

        /**
         * The author's role in the project (eg. developer or translator)
         *
         * @var string|null
         */
        public $Role;

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'name' => $this->Name,
                'email' => $this->Email,
                'homepage' => $this->Homepage,
                'role' => $this->Role
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Author
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['name']))
                $object->Name = $data['name'];

            if(isset($data['email']))
                $object->Email = $data['email'];

            if(isset($data['homepage']))
                $object->Homepage = $data['homepage'];

            if(isset($data['role']))
                $object->Role = $data['role'];

            return $object;
        }
    }