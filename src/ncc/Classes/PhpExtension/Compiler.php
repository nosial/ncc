<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes\PhpExtension;

    use Exception;
    use FilesystemIterator;
    use ncc\Abstracts\ComponentFileExtensions;
    use ncc\Abstracts\ComponentDataType;
    use ncc\Abstracts\ConstantReferences;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\ncc;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\nikic\PhpParser\ParserFactory;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
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
         * @var string
         */
        private $path;

        /**
         * @param ProjectConfiguration $project
         * @param string $path
         */
        public function __construct(ProjectConfiguration $project, string $path)
        {
            $this->project = $project;
            $this->path = $path;
        }

        /**
         * Prepares the PHP package by generating the Autoloader and detecting all components & resources
         * This function must be called before calling the build function, otherwise the operation will fail
         *
         * @param string $build_configuration
         * @return void
         * @throws PackagePreparationFailedException
         * @throws BuildConfigurationNotFoundException
         */
        public function prepare(string $build_configuration=BuildConfigurationValues::DefaultConfiguration): void
        {
            try
            {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $this->project->validate(True);
            }
            catch (Exception $e)
            {
                throw new PackagePreparationFailedException($e->getMessage(), $e);
            }

            // Select the build configuration
            $selected_build_configuration = $this->project->Build->getBuildConfiguration($build_configuration);

            // Create the package object
            $this->package = new Package();
            $this->package->Assembly = $this->project->Assembly;
            $this->package->Dependencies = $this->project->Build->Dependencies;
            $this->package->MainExecutionPolicy = $this->project->Build->Main;

            // Add both the defined constants from the build configuration and the global constants.
            // Global constants are overridden
            $this->package->Header->RuntimeConstants = [];
            $this->package->Header->RuntimeConstants = array_merge(
                $selected_build_configuration->DefineConstants,
                $this->project->Build->DefineConstants,
                $this->package->Header->RuntimeConstants
            );

            $this->package->Header->CompilerExtension = $this->project->Project->Compiler;
            $this->package->Header->CompilerVersion = NCC_VERSION_NUMBER;

            Console::out('Scanning project files');
            Console::out('theseer\DirectoryScanner - Copyright (c) 2009-2014 Arne Blankerts <arne@blankerts.de> All rights reserved.');

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
            $source_path = $this->path . $this->project->Build->SourcePath;

            // TODO: Re-implement the scanning process outside the compiler, as this is will be redundant
            // Scan for components first.
            Console::out('Scanning for components... ');
            /** @var SplFileInfo $item */
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            foreach($DirectoryScanner($source_path, True) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPathName()))
                    continue;

                $Component = new Package\Component();
                $Component->Name = Functions::removeBasename($item->getPathname(), $this->path);
                $this->package->Components[] = $Component;

                Console::outVerbose(sprintf('found component %s', $Component->Name));
            }

            if(count($this->package->Components) > 0)
            {
                Console::out(count($this->package->Components) . ' component(s) found');
            }
            else
            {
                Console::out('No components found');
            }

            // Clear previous excludes and includes
            $DirectoryScanner->setExcludes([]);
            $DirectoryScanner->setIncludes([]);

            // Ignore component files
            $DirectoryScanner->setExcludes(array_merge(
                $selected_build_configuration->ExcludeFiles, ComponentFileExtensions::Php
            ));

            Console::out('Scanning for resources... ');
            /** @var SplFileInfo $item */
            foreach($DirectoryScanner($source_path) as $item)
            {
                // Ignore directories, they're not important. :-)
                if(is_dir($item->getPathName()))
                    continue;

                $Resource = new Package\Resource();
                $Resource->Name = Functions::removeBasename($item->getPathname(), $this->path);
                $this->package->Resources[] = $Resource;

                Console::outVerbose(sprintf('found resource %s', $Resource->Name));
            }

            if(count($this->package->Resources) > 0)
            {
                Console::out(count($this->package->Resources) . ' resources(s) found');
            }
            else
            {
                Console::out('No resources found');
            }
        }

        /**
         * Executes the compile process in the correct order and returns the finalized Package object
         *
         * @return Package|null
         * @throws AccessDeniedException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws UnsupportedRunnerException
         */
        public function build(): ?Package
        {
            $this->compileExecutionPolicies();
            $this->compileComponents();
            $this->compileResources();

            PackageCompiler::compilePackageConstants($this->package, [
                ConstantReferences::Assembly => $this->project->Assembly,
                ConstantReferences::Build => null,
                ConstantReferences::DateTime => time()
            ]);

            return $this->getPackage();
        }

        /**
         * Compiles the resources of the package
         *
         * @return void
         * @throws AccessDeniedException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function compileResources(): void
        {
            if($this->package == null)
                throw new BuildException('The prepare() method must be called before building the package');

            if(count($this->package->Resources) == 0)
                return;

            // Process the resources
            Console::out('Processing resources');
            $total_items = count($this->package->Resources);
            $processed_items = 0;
            $resources = [];

            foreach($this->package->Resources as $resource)
            {
                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                // Get the data and
                $resource->Data = IO::fread(Functions::correctDirectorySeparator($this->path . $resource->Name));
                $resource->Data = Base64::encode($resource->Data);
                $resource->Name = str_replace($this->project->Build->SourcePath, (string)null, $resource->Name);
                $resource->updateChecksum();
                $resources[] = $resource;

                Console::outDebug(sprintf('processed resource %s', $resource->Name));
            }

            // Update the resources
            $this->package->Resources = $resources;
        }

        /**
         * Compiles the components of the package
         *
         * @return void
         * @throws AccessDeniedException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function compileComponents(): void
        {
            if($this->package == null)
                throw new BuildException('The prepare() method must be called before building the package');

            if(count($this->package->Components) == 0)
                return;

            Console::out('Compiling components');
            $total_items = count($this->package->Components);
            $processed_items = 0;
            $components = [];

            // Process the components and attempt to create an AST representation of the source
            foreach($this->package->Components as $component)
            {
                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                $content = IO::fread(Functions::correctDirectorySeparator($this->path . $component->Name));
                $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

                try
                {
                    $stmts = $parser->parse($content);
                    $encoded = json_encode($stmts);
                    unset($stmts);

                    if($encoded === false)
                    {
                        $component->DataType = ComponentDataType::b64encoded;
                        $component->Data = Base64::encode($content);
                    }
                    else
                    {
                        $component->DataType = ComponentDataType::AST;
                        $component->Data = json_decode($encoded, true);
                    }
                }
                catch(Exception $e)
                {
                    $component->DataType = ComponentDataType::b64encoded;
                    $component->Data = Base64::encode($content);
                    unset($e);
                }

                unset($parser);

                $component->Name = str_replace($this->project->Build->SourcePath, (string)null, $component->Name);
                $component->updateChecksum();
                $components[] = $component;
                $processed_items += 1;

                Console::outDebug(sprintf('processed component %s (%s)', $component->Name, $component->DataType));
            }

            if(ncc::cliMode() && $total_items > 5)
            {
                print(PHP_EOL);
            }

            // Update the components
            $this->package->Components = $components;
        }

        /**
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws UnsupportedRunnerException
         */
        public function compileExecutionPolicies(): void
        {
            PackageCompiler::compileExecutionPolicies($this->path, $this->project);
        }

        /**
         * @inheritDoc
         */
        public function getPackage(): ?Package
        {
            return $this->package;
        }

    }