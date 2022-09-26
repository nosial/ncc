<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ExampleLibrary;

    use ExampleLibrary\Exceptions\FileNotFoundException;
    use ExampleLibrary\Objects\Person;

    class ExampleLibrary
    {
        /**
         * @var string[]
         */
        private $FirstNames;

        /**
         * @var string[]
         */
        private $LastNames;

        /**
         * Public Constructor
         *
         * @throws FileNotFoundException
         */
        public function __construct()
        {
            if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'first_names.txt'))
                throw new FileNotFoundException('The file first_names.txt does not exist in the data directory.');

            if(!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'last_names.txt'))
                throw new FileNotFoundException('The file last_names.txt does not exist in the data directory.');

            $first_names = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'first_names.txt');
            $this->FirstNames = explode("\n", $first_names);

            $last_names = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'last_names.txt');
            $this->LastNames = explode("\n", $last_names);
        }

        /**
         * Returns an array of randomly generated
         *
         * @param int $amount
         * @return array
         * @throws Exceptions\InvalidNameException
         */
        public function generatePeople(int $amount=10): array
        {
            $results = [];

            for ($k = 0 ; $k < $amount; $k++)
            {
                $FullName = implode(' ', [
                    $this->FirstNames[array_rand($this->FirstNames)],
                    $this->LastNames[array_rand($this->LastNames)]
                ]);

                $results[] = new Person($FullName);
            }

            return $results;
        }
    }