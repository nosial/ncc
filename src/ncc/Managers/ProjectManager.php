<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Managers;

    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\Options\InitializeProjectOptions;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Exceptions\ProjectAlreadyExistsException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use ncc\Utilities\Validate;
    use RuntimeException;

    class ProjectManager
    {
        /**
         * The path that points to the project's main project.json file
         *
         * @var string
         */
        private $project_file_path;

        /**
         * The path that points the project's main directory
         *
         * @var string
         */
        private $project_path;

        /**
         * The loaded project configuration, null if no project file is loaded
         *
         * @var ProjectConfiguration|null
         */
        private $project_configuration;

        /**
         * Public Constructor
         *
         * @param string $path
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PathNotFoundException
         */
        public function __construct(string $path)
        {
            // Auto-resolve the trailing slash
            if(!str_ends_with($path, '/'))
            {
                $path .= DIRECTORY_SEPARATOR;
            }

            // Detect if the folder exists or not
            if(!file_exists($path) || !is_dir($path))
            {
                throw new PathNotFoundException($path);
            }

            $this->project_path = $path;
            $this->project_file_path = $path . 'project.json';

            if(file_exists($this->project_file_path))
            {
                $this->load();
            }
        }

        /**
         * Initializes the project structure
         *
         * @param Compiler $compiler
         * @param string $name
         * @param string $package
         * @param string|null $src
         * @param array $options
         * @throws ConfigurationException
         * @throws MalformedJsonException
         * @throws ProjectAlreadyExistsException
         */
        public function initializeProject(Compiler $compiler, string $name, string $package, ?string $src=null, array $options=[]): void
        {
            // Validate the project information first
            if(!Validate::packageName($package))
            {
                throw new ConfigurationException('The given package name \'' . $package . '\' is not a valid package name');
            }

            if(!Validate::projectName($name))
            {
                throw new ConfigurationException('The given project name \'' . $name . '\' is not valid');
            }

            if(file_exists($this->project_path . DIRECTORY_SEPARATOR . 'project.json'))
            {
                throw new ProjectAlreadyExistsException('A project has already been initialized in \'' . $this->project_path . DIRECTORY_SEPARATOR . 'project.json' . '\'');
            }

            $this->project_configuration = new ProjectConfiguration();

            // Set the compiler information
            $this->project_configuration->project->compiler = $compiler;

            // Set the assembly information
            $this->project_configuration->assembly->name = $name;
            $this->project_configuration->assembly->package = $package;
            $this->project_configuration->assembly->version = '1.0.0';
            $this->project_configuration->assembly->uuid = Uuid::v1()->toRfc4122();

            // Set the build information
            $this->project_configuration->build->source_path = $src;

            if($this->project_configuration->build->source_path === null)
            {
                $this->project_configuration->build->source_path = $this->project_path;
            }

            $this->project_configuration->build->default_configuration = 'debug';

            // Assembly constants if the program wishes to check for this
            $this->project_configuration->build->define_constants['ASSEMBLY_NAME'] = '%ASSEMBLY.NAME%';
            $this->project_configuration->build->define_constants['ASSEMBLY_PACKAGE'] = '%ASSEMBLY.PACKAGE%';
            $this->project_configuration->build->define_constants['ASSEMBLY_VERSION'] = '%ASSEMBLY.VERSION%';
            $this->project_configuration->build->define_constants['ASSEMBLY_UID'] = '%ASSEMBLY.UID%';

            // Generate configurations
            $DebugConfiguration = new ProjectConfiguration\Build\BuildConfiguration();
            $DebugConfiguration->name = 'debug';
            $DebugConfiguration->output_path = 'build/debug';
            $DebugConfiguration->define_constants["DEBUG"] = '1'; // Debugging constant if the program wishes to check for this
            $this->project_configuration->build->build_configurations[] = $DebugConfiguration;
            $ReleaseConfiguration = new ProjectConfiguration\Build\BuildConfiguration();
            $ReleaseConfiguration->name = 'release';
            $ReleaseConfiguration->output_path = 'build/release';
            $ReleaseConfiguration->define_constants["DEBUG"] = '0'; // Debugging constant if the program wishes to check for this
            $this->project_configuration->build->build_configurations[] = $ReleaseConfiguration;

            // Finally, create project.json
            $this->project_configuration->toFile($this->project_path . DIRECTORY_SEPARATOR . 'project.json');

            // And create the project directory for additional assets/resources
            $Folders = [
                $this->project_path . DIRECTORY_SEPARATOR . 'ncc',
                $this->project_path . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'cache',
                $this->project_path . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'config',
            ];

            foreach($Folders as $folder)
            {
                if(!file_exists($folder) && !mkdir($folder) && !is_dir($folder))
                {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $folder));
                }
            }

            // Process options
            foreach($options as $option)
            {
                if (
                    $option === InitializeProjectOptions::CREATE_SOURCE_DIRECTORY &&
                    !file_exists($this->project_configuration->build->source_path) &&
                    !mkdir($concurrentDirectory = $this->project_configuration->build->source_path) &&
                    !is_dir($concurrentDirectory)
                )
                {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }
        }

        /**
         * Determines if a project configuration is loaded or not
         *
         * @return bool
         */
        public function projectLoaded(): bool
        {
            return $this->project_configuration !== null;
        }

        /**
         * Attempts to load the project configuration
         *
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PathNotFoundException
         */
        public function load(): void
        {
            if(!file_exists($this->project_file_path) && !is_file($this->project_file_path))
            {
                throw new ConfigurationException('The project configuration file \'' . $this->project_file_path . '\' was not found');
            }

            $this->project_configuration = ProjectConfiguration::fromFile($this->project_file_path);
        }

        /**
         * Saves the project configuration
         *
         * @return void
         * @throws MalformedJsonException
         */
        public function save(): void
        {
            if(!$this->projectLoaded())
            {
                return;
            }

            $this->project_configuration->toFile($this->project_file_path);
        }

        /**
         * Returns the ProjectConfiguration object
         *
         * @return ProjectConfiguration
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PathNotFoundException
         */
        public function getProjectConfiguration(): ProjectConfiguration
        {
            if($this->project_configuration === null)
            {
                $this->load();
            }

            return $this->project_configuration;
        }

        /**
         * Returns the project's path.
         *
         * @return string|null
         */
        public function getProjectPath(): ?string
        {
            return $this->project_path;
        }


        /**
         * Compiles the project into a package
         *
         * @param string $build_configuration
         * @return string
         * @throws BuildException
         * @throws ConfigurationException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public function build(string $build_configuration=BuildConfigurationValues::DEFAULT): string
        {
            return PackageCompiler::compile($this, $build_configuration);
        }
    }