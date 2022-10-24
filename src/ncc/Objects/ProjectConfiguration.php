<?php

    namespace ncc\Objects;

    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidConstantNameException;
    use ncc\Exceptions\InvalidProjectBuildConfiguration;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\InvalidPropertyValueException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedExtensionVersionException;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Build;
    use ncc\Objects\ProjectConfiguration\Project;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
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
         * Public Constructor
         */
        public function __construct()
        {
            $this->Project = new Project();
            $this->Assembly = new Assembly();
            $this->Build = new Build();
        }

        /**
         * Validates the object for any errors
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidProjectConfigurationException
         * @throws InvalidPropertyValueException
         * @throws RuntimeException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedExtensionVersionException
         * @throws InvalidProjectBuildConfiguration
         * @throws InvalidConstantNameException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!$this->Project->validate($throw_exception))
                return false;

            if(!$this->Assembly->validate($throw_exception))
                return false;

            if(!$this->Build->validate($throw_exception))
                return false;

            return true;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('project') : 'project') => $this->Project->toArray($bytecode),
                ($bytecode ? Functions::cbc('assembly') : 'assembly') => $this->Assembly->toArray($bytecode),
                ($bytecode ? Functions::cbc('build') : 'build') => $this->Build->toArray($bytecode),
            ];
        }

        /**
         * Writes a json representation of the object to a file
         *
         * @param string $path
         * @param bool $bytecode
         * @return void
         * @throws MalformedJsonException
         * @noinspection PhpMissingReturnTypeInspection
         * @noinspection PhpUnused
         */
        public function toFile(string $path, bool $bytecode=false)
        {
            if(!$bytecode)
            {
                Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY | Functions::PRETTY | Functions::ESCAPE_UNICODE);
                return;
            }

            Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY);
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

            $ProjectConfigurationObject->Project = Project::fromArray(Functions::array_bc($data, 'project'));
            $ProjectConfigurationObject->Assembly = Assembly::fromArray(Functions::array_bc($data, 'assembly'));
            $ProjectConfigurationObject->Build = Build::fromArray(Functions::array_bc($data, 'build'));

            return $ProjectConfigurationObject;
        }

        /**
         * Loads the object from a file representation
         *
         * @param string $path
         * @return ProjectConfiguration
         * @throws FileNotFoundException
         * @throws MalformedJsonException
         * @noinspection PhpUnused
         */
        public static function fromFile(string $path): ProjectConfiguration
        {
            return ProjectConfiguration::fromArray(Functions::loadJsonFile($path, Functions::FORCE_ARRAY));
        }
    }