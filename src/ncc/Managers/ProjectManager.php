<?php

    namespace ncc\Managers;

    use ncc\Abstracts\Options\InitializeProjectOptions;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Symfony\Component\Uid\Uuid;
    use ncc\Utilities\Validate;

    class ProjectManager
    {
        /**
         * The selected directory for managing the project
         *
         * @var string
         */
        private string $SelectedDirectory;

        /**
         * The path that points to the project's main project.json file
         *
         * @var string
         */
        private string $ProjectFilePath;

        /**
         * The path that points the project's main directory
         *
         * @var string
         */
        private string $ProjectPath;

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
         * @return bool
         */
        private function detectProjectPath(): bool
        {
            $selected_directory = $this->SelectedDirectory;

            // Auto-resolve the trailing slash
            /** @noinspection PhpStrFunctionsInspection */
            if(substr($selected_directory, -1) !== '/')
            {
                $selected_directory .= $selected_directory . DIRECTORY_SEPARATOR;
            }

            // Detect if the folder exists or not
            if(!file_exists($selected_directory) || !is_dir($selected_directory))
            {
                return false;
            }

            // Detect if project.json exists in the directory
            if(file_exists($selected_directory . 'project.json'))
            {
                $this->ProjectPath = $selected_directory;
                $this->ProjectFilePath = $selected_directory . 'project.json';
                return true;
            }

            return false;
        }

        /**
         * Initializes the project structure
         *
         * @param Compiler $compiler
         * @param string $source
         * @param string $name
         * @param string $package
         * @param array $options
         * @throws InvalidPackageNameException
         * @throws MalformedJsonException
         */
        public function initializeProject(Compiler $compiler, string $source, string $name, string $package, array $options=[])
        {
            // Validate the project information first
            if(!Validate::packageName($package))
            {
                throw new InvalidPackageNameException('The given package name \'' . $package . '\' is not a valid package name');
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
            $Project->Build->SourcePath = $source;
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
                        if(file_exists($source) == false)
                        {
                            mkdir($source);
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