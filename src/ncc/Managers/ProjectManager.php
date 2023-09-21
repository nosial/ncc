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

    use JsonException;
    use ncc\Classes\PhpExtension\ExecutableCompiler;
    use ncc\Classes\PhpExtension\NccCompiler;
    use ncc\Classes\PhpExtension\Templates\CliTemplate;
    use ncc\Classes\PhpExtension\Templates\LibraryTemplate;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\ComponentFileExtensions;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\Options\InitializeProjectOptions;
    use ncc\Enums\ProjectTemplates;
    use ncc\Enums\Types\BuildOutputType;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\ComposerJson;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\Resolver;
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
         * @var ProjectConfiguration
         */
        private $project_configuration;

        /**
         * Public Constructor
         *
         * @param string $path
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         * @throws NotSupportedException
         */
        public function __construct(string $path)
        {
            // Auto-resolve the trailing slash
            if(str_ends_with($path, '/'))
            {
                $path = substr($path, 0, -1);
            }

            // Detect if the folder exists or not
            if(!is_dir($path))
            {
                throw new PathNotFoundException($path);
            }

            if(str_ends_with($path, 'project.json'))
            {
                $path = dirname($path);
            }

            $this->project_path = $path;
            $this->project_file_path = $this->project_path . DIRECTORY_SEPARATOR . 'project.json';
            $this->project_configuration = ProjectConfiguration::fromFile($this->project_file_path);
        }

        /**
         * Returns the ProjectConfiguration object
         *
         * @return ProjectConfiguration
         */
        public function getProjectConfiguration(): ProjectConfiguration
        {
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
         * Returns the project's source path
         *
         * @return string|null
         */
        public function getProjectSourcePath(): ?string
        {
            return $this->project_path . DIRECTORY_SEPARATOR . $this->project_configuration->getBuild()->getSourcePath();
        }

        /**
         * Compiles the project into a package, returns the path to the build
         *
         * @param string $build_configuration
         * @return string
         * @throws BuildException
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public function build(string $build_configuration=BuildConfigurationValues::DEFAULT): string
        {
            $configuration = $this->project_configuration->getBuild()->getBuildConfiguration($build_configuration);

            return match (strtolower($this->project_configuration->getProject()->getCompiler()->getExtension()))
            {
                CompilerExtensions::PHP => match (strtolower($configuration->getBuildType()))
                {
                    BuildOutputType::NCC_PACKAGE => (new NccCompiler($this))->build($build_configuration),
                    BuildOutputType::EXECUTABLE => (new ExecutableCompiler($this))->build($build_configuration),
                    default => throw new BuildException(sprintf('php cannot produce the build type \'%s\'', $configuration->getBuildType())),
                },
                default => throw new NotSupportedException(sprintf('The compiler extension \'%s\' is not supported', $this->project_configuration->getProject()->getCompiler()->getExtension())),
            };
        }

        /**
         * Applies the given template to the project
         *
         * @param string $template_name
         * @return void
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public function applyTemplate(string $template_name): void
        {
            switch(strtolower($template_name))
            {
                case ProjectTemplates::PHP_CLI:
                    CliTemplate::applyTemplate($this);
                    break;

                case ProjectTemplates::PHP_LIBRARY:
                    LibraryTemplate::applyTemplate($this);
                    break;

                default:
                    throw new NotSupportedException('The given template \'' . $template_name . '\' is not supported');
            }
        }

        /**
         * Returns an array of file extensions for the components that are part of this project
         *
         * @return array
         * @throws NotSupportedException
         */
        public function getComponentFileExtensions(): array
        {
            return match ($this->getProjectConfiguration()->getProject()->getCompiler()->getExtension())
            {
                CompilerExtensions::PHP => ComponentFileExtensions::PHP,
                default => throw new NotSupportedException(
                    sprintf('The compiler extension \'%s\' is not supported', $this->getProjectConfiguration()->getProject()->getCompiler()->getExtension())
                ),
            };
        }

        /**
         * Returns an array of ExecutionUnits associated with the project by selecting all required execution units
         * from the project configuration and reading the contents of the files
         *
         * @param string $build_configuration
         * @return ExecutionUnit[]
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getExecutionUnits(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            $execution_units = [];

            foreach($this->project_configuration->getRequiredExecutionPolicies($build_configuration) as $policy)
            {
                $execution_policy = $this->project_configuration->getExecutionPolicy($policy);
                $execution_file = $this->getProjectPath() . DIRECTORY_SEPARATOR . $execution_policy->getExecute()->getTarget();
                if(!is_file($execution_file))
                {
                    throw new IOException(sprintf('The execution policy %s points to a non-existent file \'%s\'', $execution_policy->getName(), $execution_file));
                }

                $execution_units[] = new ExecutionUnit($execution_policy, IO::fread($execution_file));
            }

            return $execution_units;
        }

        /**
         * Returns an array of file paths for the components that are part of this project
         *
         * @param string $build_configuration
         * @return array
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function getComponents(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            $configuration = $this->project_configuration->getBuild()->getBuildConfiguration($build_configuration);

            return array_map(static function ($file) {
                return $file;
            }, Functions::scanDirectory($this->getProjectSourcePath(), $this->getComponentFileExtensions(), $configuration->getExcludeFiles()));
        }

        /**
         * Returns an array of file paths for the resources that are part of this project
         *
         * @param string $build_configuration
         * @return array
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function getResources(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            $configuration = $this->project_configuration->getBuild()->getBuildConfiguration($build_configuration);

            return array_map(static function ($file) {
                return $file;
            }, Functions::scanDirectory($this->getProjectSourcePath(), [], array_merge(
                $configuration->getExcludeFiles(), $this->getComponentFileExtensions()
            )));
        }

        /**
         * Returns an array of runtime constants for the project & build configuration
         *
         * @param string $build_configuration
         * @return array
         * @throws ConfigurationException
         */
        public function getRuntimeConstants(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            $configuration = $this->project_configuration->getBuild()->getBuildConfiguration($build_configuration);

            return array_merge(
                $configuration->getDefineConstants(),
                $this->project_configuration->getBuild()->getDefineConstants()
            );
        }

        /**
         * Returns an array of compiler options associated with the build configuration
         *
         * @param string $build_configuration
         * @return array
         * @throws ConfigurationException
         */
        public function getCompilerOptions(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            $configuration = $this->project_configuration->getBuild()->getBuildConfiguration($build_configuration);

            return array_merge(
                $configuration->getOptions(),
                $this->project_configuration->getBuild()->getOptions()
            );
        }

        /**
         * Saves the project configuration
         *
         * @return void
         * @throws IOException
         */
        public function save(): void
        {
            $this->project_configuration->toFile($this->project_file_path);
        }

        /**
         * Initializes the project structure
         *
         * @param string $project_path The directory for the project to be initialized in
         * @param string $name The name of the project eg; ProjectLib
         * @param string $package The standard package name eg; com.example.project
         * @param string $compiler The compiler to use for this project
         * @param array $options An array of options to use when initializing the project
         * @return ProjectManager
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public static function initializeProject(string $project_path, string $name, string $package, string $compiler, array $options=[]): ProjectManager
        {
            if(str_ends_with($project_path, DIRECTORY_SEPARATOR))
            {
                $project_path = substr($project_path, 0, -1);
            }

            if(is_file($project_path . DIRECTORY_SEPARATOR . 'project.json'))
            {
                if(!isset($options[InitializeProjectOptions::OVERWRITE_PROJECT_FILE]))
                {
                    throw new IOException('A project has already been initialized in \'' . $project_path . DIRECTORY_SEPARATOR . 'project.json' . '\'');
                }

                Console::out(sprintf('Overwriting project.json in \'%s\'', $project_path));
                unlink($project_path . DIRECTORY_SEPARATOR . 'project.json');
            }

            $project_src = $options[InitializeProjectOptions::PROJECT_SRC_PATH] ?? ('src' . DIRECTORY_SEPARATOR . $name);
            if(str_ends_with($project_src, DIRECTORY_SEPARATOR))
            {
                $project_src = substr($project_src, 0, -1);
            }

            if(!mkdir($project_path . DIRECTORY_SEPARATOR . $project_src, 0777, true) && !is_dir($project_path . DIRECTORY_SEPARATOR . $project_src))
            {
                throw new IOException(sprintf('Project source directory "%s" was not created', $project_path . DIRECTORY_SEPARATOR . $project_src));
            }

            // Create the build configuration
            $build = new ProjectConfiguration\Build($project_src);
            $build->addDefineConstant('ASSEMBLY_PACKAGE', '%ASSEMBLY.PACKAGE%');
            $build->addDefineConstant('ASSEMBLY_VERSION', '%ASSEMBLY.VERSION%');
            $build->addDefineConstant('ASSEMBLY_UID', '%ASSEMBLY.UID%');

            // Generate the Debug & Release build configurations
            $debug_configuration = new ProjectConfiguration\Build\BuildConfiguration('debug', 'build' . DIRECTORY_SEPARATOR . 'debug');
            $debug_configuration->setDefinedConstant('DEBUG', '1');
            $build->addBuildConfiguration(new ProjectConfiguration\Build\BuildConfiguration('release', 'build' . DIRECTORY_SEPARATOR . 'release'));
            $build->addBuildConfiguration($debug_configuration);
            $build->setDefaultConfiguration('release');

            $project_configuration = new ProjectConfiguration(
                new ProjectConfiguration\Project($compiler),
                new ProjectConfiguration\Assembly($name, $package),
                $build
            );

            // Finally, create project.json and return a new ProjectManager
            $project_configuration->toFile($project_path . DIRECTORY_SEPARATOR . 'project.json');
            return new ProjectManager($project_path);
        }

        /**
         * Initializes the project structure from a composer-based project
         *
         * @param string $project_path
         * @param array $options
         * @return ProjectManager
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public static function initializeFromComposer(string $project_path, array $options=[]): ProjectManager
        {
            if(str_ends_with($project_path, DIRECTORY_SEPARATOR))
            {
                $project_path = substr($project_path, 0, -1);
            }

            $project_file = $project_path . DIRECTORY_SEPARATOR . 'project.json';
            $composer_file = $project_path . DIRECTORY_SEPARATOR . 'composer.json';

            if(!is_file($composer_file))
            {
                throw new IOException('Unable to find composer.json in \'' . $project_path . '\'');
            }

            if(is_file($project_file))
            {
                if(!isset($options[InitializeProjectOptions::OVERWRITE_PROJECT_FILE]))
                {
                    throw new IOException('A project has already been initialized in \'' . $project_file . '\'');
                }

                Console::out(sprintf('Overwriting project.json in \'%s\'', $project_path));
                unlink($project_file);
            }

            if(!isset($options[InitializeProjectOptions::COMPOSER_PACKAGE_VERSION]))
            {
                throw new OperationException('Unable to initialize project from composer.json without a version option');
            }

            try
            {
                $composer_json = ComposerJson::fromArray(json_decode(IO::fread($composer_file), true, 512, JSON_THROW_ON_ERROR));
            }
            catch(JsonException $e)
            {
                throw new OperationException(sprintf('Unable to parse composer.json in \'%s\'', $project_path), $e);
            }

            // Create an auto-source directory
            $project_src = $project_path . DIRECTORY_SEPARATOR . 'auto_src';
            if(!is_dir($project_src) && !mkdir($project_src, 0777, true) && !is_dir($project_src))
            {
                throw new IOException(sprintf('Project source directory "%s" was not created', $project_src));
            }

            $project = new ProjectConfiguration\Project(new ProjectConfiguration\Compiler(CompilerExtensions::PHP));
            $assembly = new ProjectConfiguration\Assembly(
                Resolver::composerName($composer_json->getName()),
                Resolver::composerNameToPackage($composer_json->getName()),
                $options[InitializeProjectOptions::COMPOSER_PACKAGE_VERSION]
            );
            $assembly->setDescription($composer_json->getDescription());

            // Create the build configuration
            $build = new ProjectConfiguration\Build('auto_src');
            $build->setDefaultConfiguration('release_ncc');

            // Process dependencies
            if($composer_json->getRequire() !== null)
            {
                /** @var ComposerJson\PackageLink $package_link */
                foreach($composer_json->getRequire() as $package_link)
                {
                    if(!preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/', $package_link->getPackageName()))
                    {
                        continue;
                    }

                    $source = sprintf('%s=%s@packagist', $package_link->getPackageName(), $package_link->getVersion());
                    $build->addDependency(new ProjectConfiguration\Dependency(
                        Resolver::composerNameToPackage($package_link->getPackageName()), $source, $package_link->getVersion()
                    ));
                }
            }

            // Process developer dependencies
            $require_dev = [];
            if($composer_json->getRequireDev() !== null)
            {
                /** @var ComposerJson\PackageLink $package_link */
                foreach($composer_json->getRequireDev() as $package_link)
                {
                    if(!preg_match('/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/', $package_link->getPackageName()))
                    {
                        continue;
                    }

                    $source = sprintf('%s=%s@packagist', $package_link->getPackageName(), $package_link->getVersion());
                    $build->addDependency(new ProjectConfiguration\Dependency(
                        Resolver::composerNameToPackage($package_link->getPackageName()), $source, $package_link->getVersion()
                    ));
                    $require_dev[] = $package_link->getPackageName();
                }
            }

            // Process classmap
            if($composer_json->getAutoload()?->getClassMap() !== null)
            {
                foreach($composer_json->getAutoload()?->getClassMap() as $path)
                {
                    /** @noinspection UnusedFunctionResultInspection */
                    self::copyContents($project_path, $project_src, $path);
                }
            }

            // Process PSR-4 namespaces
            if($composer_json->getAutoload()?->getPsr4() !== null)
            {
                foreach($composer_json->getAutoload()?->getPsr4() as $namespace_pointer)
                {
                    if(is_string($namespace_pointer->getPath()))
                    {
                        /** @noinspection UnusedFunctionResultInspection */
                        self::copyContents($project_path, $project_src, $namespace_pointer->getPath());
                    }
                    elseif(is_array($namespace_pointer->getPath()))
                    {
                        foreach($namespace_pointer->getPath() as $path)
                        {
                            /** @noinspection UnusedFunctionResultInspection */
                            self::copyContents($project_path, $project_src, $path);
                        }
                    }
                    else
                    {
                        throw new RuntimeException('Invalid namespace pointer path');
                    }
                }
            }

            // Process PSR-0 namespaces
            if($composer_json->getAutoload()?->getPsr0() !== null)
            {
                foreach($composer_json->getAutoload()?->getPsr0() as $namespace_pointer)
                {
                    if(is_string($namespace_pointer->getPath()))
                    {
                        /** @noinspection UnusedFunctionResultInspection */
                        self::copyContents($project_path, $project_src, $namespace_pointer->getPath());
                    }
                    elseif(is_array($namespace_pointer->getPath()))
                    {
                        foreach($namespace_pointer->getPath() as $path)
                        {
                            /** @noinspection UnusedFunctionResultInspection */
                            self::copyContents($project_path, $project_src, $path);
                        }
                    }
                    else
                    {
                        throw new RuntimeException('Invalid namespace pointer path');
                    }
                }
            }

            // Process files
            if($composer_json->getAutoload()?->getFiles() !== null)
            {
                $required_files = [];
                foreach($composer_json->getAutoload()?->getFiles() as $path)
                {
                    $required_files = array_merge($required_files, self::copyContents($project_path, $project_src, $path));
                }

                foreach($required_files as $index => $file)
                {
                    $required_files[$index] = Functions::removeBasename($file, $project_path);
                }

                $build->setOption(BuildConfigurationOptions::REQUIRE_FILES, $required_files);
            }

            // Generate debug build configuration
            $ncc_debug_configuration = new ProjectConfiguration\Build\BuildConfiguration('debug_ncc', 'build' . DIRECTORY_SEPARATOR . 'debug');
            $ncc_debug_configuration->setBuildType(BuildOutputType::NCC_PACKAGE);
            $ncc_debug_configuration->setDependencies($require_dev);
            $build->addBuildConfiguration($ncc_debug_configuration);
            $executable_debug_configuration = new ProjectConfiguration\Build\BuildConfiguration('debug_executable', 'build' . DIRECTORY_SEPARATOR . 'debug');
            $executable_debug_configuration->setBuildType(BuildOutputType::EXECUTABLE);
            $executable_debug_configuration->setOption(BuildConfigurationOptions::NCC_CONFIGURATION, 'debug_ncc');
            $executable_debug_configuration->setDependencies($require_dev);
            $build->addBuildConfiguration($executable_debug_configuration);

            // Generate release build configuration
            $ncc_release_configuration = new ProjectConfiguration\Build\BuildConfiguration('release_ncc', 'build' . DIRECTORY_SEPARATOR . 'release');
            $ncc_release_configuration->setBuildType(BuildOutputType::NCC_PACKAGE);
            $build->addBuildConfiguration($ncc_release_configuration);
            $executable_release_configuration = new ProjectConfiguration\Build\BuildConfiguration('release_executable', 'build' . DIRECTORY_SEPARATOR . 'release');
            $executable_release_configuration->setOption(BuildConfigurationOptions::NCC_CONFIGURATION, 'release_ncc');
            $executable_release_configuration->setBuildType(BuildOutputType::EXECUTABLE);
            $build->addBuildConfiguration($executable_release_configuration);

            // Create an update source for the project
            if(isset($options[InitializeProjectOptions::COMPOSER_REMOTE_SOURCE]))
            {
                $project->setUpdateSource(new ProjectConfiguration\UpdateSource(
                    $options[InitializeProjectOptions::COMPOSER_REMOTE_SOURCE],
                    (new RepositoryManager())->getRepository('packagist')->getProjectRepository()
                ));
            }
            else
            {
                Console::outWarning(sprintf('No update source was specified (COMPOSER_REMOTE_SOURCE), the project %s=%s will not be able to preform updates', $assembly->getName(), $assembly->getVersion()));
            }

            // Finally, create project.json and return a new ProjectManager
            $project_configuration = new ProjectConfiguration($project, $assembly, $build);
            $project_configuration->toFile($project_file);

            return new ProjectManager($project_path);
        }

        /**
         * Copies the contents of a directory to a destination recursively
         *
         * @param string $project_path The path to the project
         * @param string $destination_path The path to copy the contents to
         * @param string $path The path to copy
         * @return array Returns the array of copied files
         * @throws IOException
         */
        private static function copyContents(string $project_path, string $destination_path, string $path): array
        {
            $source_path = $project_path . DIRECTORY_SEPARATOR . $path;
            if(str_ends_with($path, DIRECTORY_SEPARATOR))
            {
                $path = substr($path, 0, -1);
            }

            $destination_path .= DIRECTORY_SEPARATOR . hash('crc32', $path);

            if(is_file($source_path))
            {
                $parent_directory = dirname($destination_path . DIRECTORY_SEPARATOR . $path);
                if(!is_dir($parent_directory) && !mkdir($parent_directory, 0777, true) && !is_dir($parent_directory))
                {
                    throw new IOException(sprintf('Directory "%s" was not created', $parent_directory));
                }

                copy($source_path, $destination_path . DIRECTORY_SEPARATOR . $path);
                return [$destination_path . DIRECTORY_SEPARATOR . $path];
            }

            $results = [];
            foreach(Functions::scanDirectory($source_path) as $file)
            {
                $destination = $destination_path . DIRECTORY_SEPARATOR . Functions::removeBasename($file, $source_path);
                $parent_directory = dirname($destination);

                if(!is_dir($parent_directory) && !mkdir($parent_directory, 0777, true) && !is_dir($parent_directory))
                {
                    throw new IOException(sprintf('Directory "%s" was not created', $parent_directory));
                }

                copy($file, $destination);
                $results[] = $destination;
            }

            return $results;
        }
    }