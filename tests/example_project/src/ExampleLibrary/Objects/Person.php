<?php

    namespace ExampleLibrary\Objects;

    use ExampleLibrary\Exceptions\InvalidNameException;

    class Person
    {
        /**
         * @var string
         */
        private string $FirstName;

        /**
         * @var string
         */
        private string $LastName;

        /**
         * Public Constructor
         *
         * @param string|null $name
         * @throws InvalidNameException
         */
        public function __construct(?string $name=null)
        {
            if($name !== null)
            {
                $exploded_name = explode(' ', $name);

                if(count($exploded_name) < 2)
                {
                    throw new InvalidNameException('The given name must contain a first and last name.');
                }

                $this->FirstName = $exploded_name[0];
                $this->LastName = $exploded_name[1];
            }

        }

        /**
         * Sets the first name of the person.
         *
         * @param string $FirstName
         */
        public function setFirstName(string $FirstName): void
        {
            $this->FirstName = $FirstName;
        }

        /**
         * Gets the last name of the person.
         *
         * @return string
         */
        public function getLastName(): string
        {
            return $this->LastName;
        }

        /**
         * Sets the last name of the person.
         *
         * @param string $LastName
         */
        public function setLastName(string $LastName): void
        {
            $this->LastName = $LastName;
        }

        /**
         * Gets the first name of the person.
         *
         * @return string
         */
        public function getFirstName(): string
        {
            return $this->FirstName;
        }

        /**
         * Returns a string representation of the person.
         *
         * @return string
         */
        public function __toString()
        {
            return implode(' ', [$this->FirstName, $this->LastName]);
        }

        /**
         * Returns an array representation of the person
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'first_name' => $this->FirstName,
                'last_name' => $this->LastName
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Person
         */
        public static function fromArray(array $data): Person
        {
            $person = new Person();

            if(isset($data['first_name']))
                $person->FirstName = $data['first_name'];

            if(isset($data['last_name']))
                $person->LastName = $data['last_name'];

            return $person;
        }
    }