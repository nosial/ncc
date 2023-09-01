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

    use JetBrains\PhpStorm\NoReturn;
    use ncc\Classes\PhpExtension\PhpCliTemplate;
    use ncc\Classes\PhpExtension\PhpLibraryTemplate;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\Options\InitializeProjectOptions;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Enums\ProjectTemplates;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Compiler;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Validate;

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

            $this->project_path = $path;
            $this->project_file_path = $this->project_path . DIRECTORY_SEPARATOR . 'project.json';
            $this->project_configuration = ProjectConfiguration::fromFile($this->project_file_path);
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
         * Compiles the project into a package
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
            return PackageCompiler::compile($this, $build_configuration);
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
                    PhpCliTemplate::applyTemplate($this);
                    break;

                case ProjectTemplates::PHP_LIBRARY:
                    PhpLibraryTemplate::applyTemplate($this);
                    break;

                default:
                    throw new NotSupportedException('The given template \'' . $template_name . '\' is not supported');
            }
        }

        /**
         * Initializes the project structure
         *
         * @param string $project_path The directory for the project to be initialized in
         * @param string $name The name of the project eg; ProjectLib
         * @param string $package The standard package name eg; com.example.project
         * @param Compiler $compiler The compiler to use for this project
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

            if(!mkdir($project_path, 0777, true) && !is_dir($project_path))
            {
                throw new IOException(sprintf('Project directory "%s" was not created', $project_path));
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
    }