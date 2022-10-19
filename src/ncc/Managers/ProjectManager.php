<?php

    namespace ncc\Managers;

    use ncc\Abstracts\Options\InitializeProjectOptions;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\InvalidProjectNameException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\ProjectAlreadyExistsException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use ncc\Utilities\Validate;

    class ProjectManager
    {
        /**
         * The selected directory for managing the project
         *
         * @var string|null
         */
        private ?string $SelectedDirectory;

        /**
         * The path that points to the project's main project.json file
         *
         * @var string|null
         */
        private ?string $ProjectFilePath;

        /**
         * The path that points the project's main directory
         *
         * @var string|null
         */
        private ?string $ProjectPath;

        /**
         * Public Constructor
         *
         * @param string $selected_directory
         */
        public function __construct(string $selected_directory)
        {
            $this->SelectedDirectory = $selected_directory;
            $this->ProjectFilePath = null;
            $this->ProjectPath = null;

            $this->detectProjectPath();
        }

        /**
         * Attempts to resolve the project path from the selected directory
         * Returns false if the selected directory is not a proper project or an initialized project
         *
         * @return void
         */
        private function detectProjectPath(): void
        {
            $selected_directory = $this->SelectedDirectory;

            // Auto-resolve the trailing slash
            /** @noinspection PhpStrFunctionsInspection */
            if(substr($selected_directory, -1) !== '/')
            {
                $selected_directory .= DIRECTORY_SEPARATOR;
            }

            // Detect if the folder exists or not
            if(!file_exists($selected_directory) || !is_dir($selected_directory))
            {
                return;
            }

            $this->ProjectPath = $selected_directory;
            $this->ProjectFilePath = $selected_directory . 'project.json';
        }

        /**
         * Initializes the project structure
         *
         * // TODO: Correct the unexpected path behavior issue when initializing a project
         *
         * @param Compiler $compiler
         * @param string $name
         * @param string $package
         * @param array $options
         * @throws InvalidPackageNameException
         * @throws InvalidProjectNameException
         * @throws MalformedJsonException
         * @throws ProjectAlreadyExistsException
         */
        public function initializeProject(Compiler $compiler, string $name, string $package, array $options=[]): void
        {
            // Validate the project information first
            if(!Validate::packageName($package))
            {
                throw new InvalidPackageNameException('The given package name \'' . $package . '\' is not a valid package name');
            }

            if(!Validate::projectName($name))
            {
                throw new InvalidProjectNameException('The given project name \'' . $name . '\' is not valid');
            }

            if(file_exists($this->ProjectPath . DIRECTORY_SEPARATOR . 'project.json'))
            {
                throw new ProjectAlreadyExistsException('A project has already been initialized in \'' . $this->ProjectPath . DIRECTORY_SEPARATOR . 'project.json' . '\'');
            }

            $Project = new ProjectConfiguration();

            // Set the compiler information
            $Project->Project->Compiler = $compiler;

            // Set the assembly information
            $Project->Assembly->Name = $name;
            $Project->Assembly->Package = $package;
            $Project->Assembly->Version = '1.0.0';
            $Project->Assembly->UUID = Uuid::v1()->toRfc4122();

            // Set the build information
            $Project->Build->SourcePath = $this->SelectedDirectory;
            $Project->Build->DefaultConfiguration = 'debug';

            // Assembly constants if the program wishes to check for this
            $Project->Build->DefineConstants['ASSEMBLY_NAME'] = '%ASSEMBLY.NAME%';
            $Project->Build->DefineConstants['ASSEMBLY_PACKAGE'] = '%ASSEMBLY.PACKAGE%';
            $Project->Build->DefineConstants['ASSEMBLY_VERSION'] = '%ASSEMBLY.VERSION%';
            $Project->Build->DefineConstants['ASSEMBLY_UID'] = '%ASSEMBLY.UID%';

            // Generate configurations
            $DebugConfiguration = new ProjectConfiguration\BuildConfiguration();
            $DebugConfiguration->Name = 'debug';
            $DebugConfiguration->OutputPath = 'build/debug';
            $DebugConfiguration->DefineConstants["DEBUG"] = '1'; // Debugging constant if the program wishes to check for this
            $Project->Build->Configurations[] = $DebugConfiguration;
            $ReleaseConfiguration = new ProjectConfiguration\BuildConfiguration();
            $ReleaseConfiguration->Name = 'release';
            $ReleaseConfiguration->OutputPath = 'build/release';
            $ReleaseConfiguration->DefineConstants["DEBUG"] = '0'; // Debugging constant if the program wishes to check for this
            $Project->Build->Configurations[] = $ReleaseConfiguration;

            // Finally create project.json
            $Project->toFile($this->ProjectPath . DIRECTORY_SEPARATOR . 'project.json');

            // And create the project directory for additional assets/resources
            $Folders = [
                $this->ProjectPath . DIRECTORY_SEPARATOR . 'ncc',
                $this->ProjectPath . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'cache',
                $this->ProjectPath . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'config',
            ];

            foreach($Folders as $folder)
            {
                if(!file_exists($folder))
                {
                    mkdir($folder);
                }
            }

            // Process options
            foreach($options as $option)
            {
                switch($option)
                {
                    case InitializeProjectOptions::CREATE_SOURCE_DIRECTORY:
                        if(!file_exists($this->ProjectPath . DIRECTORY_SEPARATOR . 'src'))
                        {
                            mkdir($this->ProjectPath . DIRECTORY_SEPARATOR . 'src');
                        }
                        break;
                }
            }
        }

        /**
         * @return string|null
         */
        public function getProjectFilePath(): ?string
        {
            return $this->ProjectFilePath;
        }
    }