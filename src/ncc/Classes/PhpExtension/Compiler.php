<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes\PhpExtension;

    use FilesystemIterator;
    use ncc\Abstracts\ComponentFileExtensions;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\ncc;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\ThirdParty\theseer\DirectoryScanner\Exception;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use SplFileInfo;

    class Compiler implements CompilerInterface
    {
        /**
         * @var ProjectConfiguration
         */
        private $project;

        /**
         * @var Package|null
         */
        private $package;

        /**
         * @param ProjectConfiguration $project
         */
        public function __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
        }

        /**
         * Prepares the PHP package by generating the Autoloader and detecting all components & resources
         * This function must be called before calling the build function, otherwise the operation will fail
         *
         * @param array $options
         * @param string $path
         * @param string $build_configuration
         * @return void
         * @throws PackagePreparationFailedException
         */
        public function prepare(array $options, string $path, string $build_configuration=BuildConfigurationValues::DefaultConfiguration): void
        {
            // Auto-select the default build configuration
            if($build_configuration == BuildConfigurationValues::DefaultConfiguration)
            {
                $build_configuration = $this->project->Build->DefaultConfiguration;
            }

            // Select the build configuration
            try
            {
                $selected_build_configuration = $this->project->Build->getBuildConfiguration($build_configuration);
            }
            catch (BuildConfigurationNotFoundException $e)
            {
                throw new PackagePreparationFailedException($e->getMessage(), $e);
            }

            // Create the package object
            $this->package = new Package();
            $this->package->Assembly = $this->project->Assembly;
            $this->package->Dependencies = $this->project->Build->Dependencies;

            $this->package->Header->RuntimeConstants = $selected_build_configuration->DefineConstants;
            $this->package->Header->CompilerExtension = $this->project->Project->Compiler;
            $this->package->Header->CompilerVersion = NCC_VERSION_NUMBER;

            if(ncc::cliMode())
            {
                Console::out('Scanning project files');
                Console::out('theseer\DirectoryScanner - Copyright (c) 2009-2014 Arne Blankerts <arne@blankerts.de> All rights reserved.');
            }

            // First scan the project files and create a file struct.
            $DirectoryScanner = new DirectoryScanner();

            try
            {
                $DirectoryScanner->unsetFlag(FilesystemIterator::FOLLOW_SYMLINKS);
            }
            catch (Exception $e)
            {
                throw new PackagePreparationFailedException('Cannot unset flag \'FOLLOW_SYMLINKS\' in DirectoryScanner, ' . $e->getMessage(), $e);
            }

            // Include file components that can be compiled
            $DirectoryScanner->setIncludes(ComponentFileExtensions::Php);
            $DirectoryScanner->setExcludes($selected_build_configuration->ExcludeFiles);

            // Append trailing slash to the end of the path if it's not already there
            if(substr($path, -1) !== DIRECTORY_SEPARATOR)
            {
                $path .= DIRECTORY_SEPARATOR;
            }

            $source_path = $path . $this->project->Build->SourcePath;

            // Scan for components first.
            Console::out('Scanning for components... ', false);
            /** @var SplFileInfo $item */
            foreach($DirectoryScanner($source_path, true) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPathName()))
                    continue;

                $Component = new Package\Component();
                $Component->Name = Functions::removeBasename($item->getPathname(), $path);
                $this->package->Components[] = $Component;
            }

            if(ncc::cliMode())
            {
                if(count($this->package->Components) > 0)
                {
                    Console::out(count($this->package->Components) . ' component(s) found');
                }
                else
                {
                    Console::out('No components found');
                }
            }


            // Clear previous excludes and includes
            $DirectoryScanner->setExcludes([]);
            $DirectoryScanner->setIncludes([]);

            // Ignore component files
            $DirectoryScanner->setExcludes(array_merge(
                $selected_build_configuration->ExcludeFiles, ComponentFileExtensions::Php
            ));

            Console::out('Scanning for resources... ', false);
            /** @var SplFileInfo $item */
            foreach($DirectoryScanner($source_path, true) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPathName()))
                    continue;

                $Resource = new Package\Resource();
                $Resource->Name = Functions::removeBasename($item->getPathname(), $path);
                $this->package->Resources[] = $Resource;

            }

            if(ncc::cliMode())
            {
                if(count($this->package->Resources) > 0)
                {
                    Console::out(count($this->package->Resources) . ' resources(s) found');
                }
                else
                {
                    Console::out('No resources found');
                }
            }
        }

        public function build(array $options, string $path): string
        {
            if($this->package == null)
            {
                throw new BuildException('The prepare() method must be called before building the package');
            }

            // TODO: Implement build() method
        }
    }