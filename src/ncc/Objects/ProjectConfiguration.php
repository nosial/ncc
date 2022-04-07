<?php

    namespace ncc\Objects;

    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Build;
    use ncc\Objects\ProjectConfiguration\Project;
    use ncc\Utilities\Functions;

    class ProjectConfiguration
    {
        /**
         * The project configuration
         *
         * @var Project
         */
        public $Project;

        /**
         * Assembly information for the build output
         *
         * @var Assembly
         */
        public $Assembly;

        /**
         * Build configuration for the project
         *
         * @var Build
         */
        public $Build;

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('assembly') : 'assembly') => $this->Assembly->toArray($bytecode)
            ];
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return ProjectConfiguration
         */
        public static function fromArray(array $data): ProjectConfiguration
        {
            $ProjectConfigurationObject = new ProjectConfiguration();

            $ProjectConfigurationObject->Assembly = Assembly::fromArray((Functions::array_bc($data, 'assembly') ?? []));

            return $ProjectConfigurationObject;
        }
    }