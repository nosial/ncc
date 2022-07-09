<?php

    namespace ncc\Managers;

    use ncc\Objects\ProjectConfiguration\Compiler;

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
         * @var string|null
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
            if(file_exists($selected_directory . 'project.json') == true)
            {
                $this->ProjectPath = $selected_directory . 'project.json';
                return true;
            }

            // If not, check for pointer files
            $pointer_files = [
                '.ppm_package', // Backwards Compatibility
                '.ncc_project'
            ];

            foreach($pointer_files as $pointer_file)
            {
                if(file_exists($selected_directory . $pointer_file) && is_file($selected_directory . $pointer_file))
                {
                    // TODO: Complete this
                }
            }

            return true;
        }

        /**
         * Initializes the project sturcture
         *
         * @param Compiler $compiler
         * @param string $name
         * @param string $package
         * @return bool
         */
        public function initializeProject(Compiler $compiler, string $name, string $package): bool
        {

        }
    }