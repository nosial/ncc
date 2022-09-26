<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes\PhpExtension;

    use FilesystemIterator;
    use ncc\Abstracts\CompilerOptions;
    use ncc\Abstracts\ComponentFileExtensions;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Abstracts\Versions;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\ncc;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\ThirdParty\theseer\DirectoryScanner\Exception;
    use ncc\Utilities\Console;
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
         * @param string $src
         * @param string $build_configuration
         * @return void
         * @throws PackagePreparationFailedException
         */
        public function prepare(array $options, string $src, string $build_configuration=BuildConfigurationValues::DefaultConfiguration)
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
                Console::out('Building autoloader');
                Console::out('theseer\DirectoryScanner - Copyright (c) 2009-2014 Arne Blankerts <arne@blankerts.de> All rights reserved.');
                Console::out('theseer\Autoload - Copyright (c) 2010-2016 Arne Blankerts <arne@blankerts.de> and Contributors All rights reserved.');
            }

            // First scan the project files and create a file struct.
            $DirectoryScanner = new DirectoryScanner();

            try
            {
                $DirectoryScanner->unsetFlag(FilesystemIterator::
                FOLLOW_SYMLINKS);
            }
            catch (Exception $e)
            {
                throw new PackagePreparationFailedException('Cannot unset flag \'FOLLOW_SYMLINKS\' in DirectoryScanner, ' . $e->getMessage(), $e);
            }

            // Include file components that can be compiled
            $DirectoryScanner->setIncludes(ComponentFileExtensions::Php);
            $DirectoryScanner->setExcludes($selected_build_configuration->ExcludeFiles);

            // Scan for components first.
            Console::out('Scanning for components...', false);
            /** @var SplFileInfo $item */
            foreach($DirectoryScanner($src, true) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPath()))
                    continue;

                $Component = new Package\Component();
                $Component->Name = $item->getPath();
                $this->package->Components[] = $Component;

                var_dump($item->getPath());
                var_dump($item);
            }

            if(count($this->package->Components) > 0)
            {
                Console::out(count($this->package->Components) . ' component(s) found');
            }
            else
            {
                Console::out('No components found');
            }

            // Now scan for resources
            Console::out('Scanning for resources...', false);
            $DirectoryScanner->setExcludes(array_merge(
                $selected_build_configuration->ExcludeFiles, ComponentFileExtensions::Php
            ));

            // Scan for components first.
            /** @var SplFileInfo $item */
            foreach($DirectoryScanner($src, true) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPath()))
                    continue;

                $Resource = new Package\Resource();
                $Resource->Name = $item->getPath();
                $this->package->Resources[] = $Resource;

                var_dump($item->getPath());
                var_dump($item);
            }

            if(count($this->package->Resources) > 0)
            {
                Console::out(count($this->package->Resources) . ' resources(s) found');
            }
            else
            {
                Console::out('No resources found');
            }

            var_dump($this->package);

        }

        public function build(array $options, string $src)
        {
            // TODO: Implement build() method.
        }
    }