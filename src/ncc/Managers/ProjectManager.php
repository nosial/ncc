<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Abstracts\Options\InitializeProjectOptions;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\DirectoryNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\InvalidProjectNameException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Exceptions\ProjectAlreadyExistsException;
    use ncc\Exceptions\ProjectConfigurationNotFoundException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\ThirdParty\Symfony\Uid\Uuid;
    use ncc\Utilities\Validate;

    class ProjectManager
    {
        /**
         * The path that points to the project's main project.json file
         *
         * @var string
         */
        private $ProjectFilePath;

        /**
         * The path that points the project's main directory
         *
         * @var string
         */
        private $ProjectPath;

        /**
         * The loaded project configuration, null if no project file is loaded
         *
         * @var ProjectConfiguration|null
         */
        private $ProjectConfiguration;

        /**
         * Public Constructor
         *
         * @param string $path
         * @throws AccessDeniedException
         * @throws DirectoryNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws ProjectConfigurationNotFoundException
         */
        public function __construct(string $path)
        {
            $this->ProjectFilePath = null;
            $this->ProjectPath = null;

            // Auto-resolve the trailing slash
            /** @noinspection PhpStrFunctionsInspection */
            if(substr($path, -1) !== '/')
            {
                $path .= DIRECTORY_SEPARATOR;
            }

            // Detect if the folder exists or not
            if(!file_exists($path) || !is_dir($path))
            {
                throw new DirectoryNotFoundException('The given directory \'' . $path .'\' does not exist');
            }

            $this->ProjectPath = $path;
            $this->ProjectFilePath = $path . 'project.json';

            if(file_exists($this->ProjectFilePath))
                $this->load();
        }

        /**
         * Initializes the project structure
         *
         * @param Compiler $compiler
         * @param string $name
         * @param string $package
         * @param string|null $src
         * @param array $options
         * @throws InvalidPackageNameException
         * @throws InvalidProjectNameException
         * @throws MalformedJsonException
         * @throws ProjectAlreadyExistsException
         */
        public function initializeProject(Compiler $compiler, string $name, string $package, ?string $src=null, array $options=[]): void
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

            $this->ProjectConfiguration = new ProjectConfiguration();

            // Set the compiler information
            $this->ProjectConfiguration->Project->Compiler = $compiler;

            // Set the assembly information
            $this->ProjectConfiguration->Assembly->Name = $name;
            $this->ProjectConfiguration->Assembly->Package = $package;
            $this->ProjectConfiguration->Assembly->Version = '1.0.0';
            $this->ProjectConfiguration->Assembly->UUID = Uuid::v1()->toRfc4122();

            // Set the build information
            $this->ProjectConfiguration->Build->SourcePath = $src;
            if($this->ProjectConfiguration->Build->SourcePath == null)
                $this->ProjectConfiguration->Build->SourcePath = $this->ProjectPath;
            $this->ProjectConfiguration->Build->DefaultConfiguration = 'debug';

            // Assembly constants if the program wishes to check for this
            $this->ProjectConfiguration->Build->DefineConstants['ASSEMBLY_NAME'] = '%ASSEMBLY.NAME%';
            $this->ProjectConfiguration->Build->DefineConstants['ASSEMBLY_PACKAGE'] = '%ASSEMBLY.PACKAGE%';
            $this->ProjectConfiguration->Build->DefineConstants['ASSEMBLY_VERSION'] = '%ASSEMBLY.VERSION%';
            $this->ProjectConfiguration->Build->DefineConstants['ASSEMBLY_UID'] = '%ASSEMBLY.UID%';

            // Generate configurations
            $DebugConfiguration = new ProjectConfiguration\BuildConfiguration();
            $DebugConfiguration->Name = 'debug';
            $DebugConfiguration->OutputPath = 'build/debug';
            $DebugConfiguration->DefineConstants["DEBUG"] = '1'; // Debugging constant if the program wishes to check for this
            $this->ProjectConfiguration->Build->Configurations[] = $DebugConfiguration;
            $ReleaseConfiguration = new ProjectConfiguration\BuildConfiguration();
            $ReleaseConfiguration->Name = 'release';
            $ReleaseConfiguration->OutputPath = 'build/release';
            $ReleaseConfiguration->DefineConstants["DEBUG"] = '0'; // Debugging constant if the program wishes to check for this
            $this->ProjectConfiguration->Build->Configurations[] = $ReleaseConfiguration;

            // Finally create project.json
            $this->ProjectConfiguration->toFile($this->ProjectPath . DIRECTORY_SEPARATOR . 'project.json');

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
                        if(!file_exists($this->ProjectConfiguration->Build->SourcePath))
                        {
                            mkdir($this->ProjectConfiguration->Build->SourcePath);
                        }
                        break;
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
            if($this->ProjectConfiguration == null)
                return false;

            return true;
        }

        /**
         * Attempts to load the project configuration
         *
         * @return void
         * @throws MalformedJsonException
         * @throws ProjectConfigurationNotFoundException
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function load()
        {
            if(!file_exists($this->ProjectFilePath) && !is_file($this->ProjectFilePath))
                throw new ProjectConfigurationNotFoundException('The project configuration file \'' . $this->ProjectFilePath . '\' was not found');

            $this->ProjectConfiguration = ProjectConfiguration::fromFile($this->ProjectFilePath);
        }

        /**
         * Saves the project configuration
         *
         * @return void
         * @throws MalformedJsonException
         */
        public function save()
        {
            if(!$this->projectLoaded())
                return;
            $this->ProjectConfiguration->toFile($this->ProjectFilePath);
        }

        /**
         * @return string|null
         */
        public function getProjectFilePath(): ?string
        {
            return $this->ProjectFilePath;
        }

        /**
         * @return ProjectConfiguration|null
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws ProjectConfigurationNotFoundException
         */
        public function getProjectConfiguration(): ?ProjectConfiguration
        {
            if($this->ProjectConfiguration == null)
                $this->load();
            return $this->ProjectConfiguration;
        }

        /**
         * @return string|null
         */
        public function getProjectPath(): ?string
        {
            return $this->ProjectPath;
        }


        /**
         * Compiles the project into a package
         *
         * @param string $build_configuration
         * @return string
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws ProjectConfigurationNotFoundException
         * @throws BuildConfigurationNotFoundException
         * @throws BuildException
         * @throws PackagePreparationFailedException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedRunnerException
         */
        public function build(string $build_configuration=BuildConfigurationValues::DefaultConfiguration): string
        {
            return PackageCompiler::compile($this, $build_configuration);
        }
    }