<?php
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes\PhpExtension;

    use Exception;
    use FilesystemIterator;
    use ncc\Enums\ComponentFileExtensions;
    use ncc\Enums\ComponentDataType;
    use ncc\Enums\ConstantReferences;
    use ncc\Enums\DependencySourceType;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Classes\NccExtension\PackageCompiler;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageException;
    use ncc\Exceptions\PackageLockException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\VersionNotFoundException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\Managers\PackageLockManager;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\nikic\PhpParser\ParserFactory;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use SplFileInfo;

    class PhpCompiler implements CompilerInterface
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
         * @throws ConfigurationException
         * @throws PackageException
         */
        public function prepare(string $build_configuration=BuildConfigurationValues::DEFAULT): void
        {
            try
            {
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $this->project->validate(True);
            }
            catch (Exception $e)
            {
                throw new PackageException($e->getMessage(), $e);
            }

            // Select the build configuration
            $selected_build_configuration = $this->project->build->getBuildConfiguration($build_configuration);

            // Create the package object
            $this->package = new Package();
            $this->package->assembly = $this->project->assembly;
            $this->package->dependencies = $this->project->build->dependencies;
            $this->package->main_execution_policy = $this->project->build->main;

            // Add the option to create a symbolic link to the package
            if(isset($this->project->project->options['create_symlink']) && $this->project->project->options['create_symlink'] === True)
            {
                $this->package->header->Options['create_symlink'] = true;
            }

            // Add both the defined constants from the build configuration and the global constants.
            // Global constants are overridden
            $this->package->header->RuntimeConstants = [];
            $this->package->header->RuntimeConstants = array_merge(
                ($selected_build_configuration->define_constants ?? []),
                ($this->project->build->define_constants ?? []),
                ($this->package->header->RuntimeConstants ?? [])
            );

            $this->package->header->CompilerExtension = $this->project->project->compiler;
            $this->package->header->CompilerVersion = NCC_VERSION_NUMBER;
            $this->package->header->Options = $this->project->project->options;

            if($this->project->project->update_source !== null)
            {
                $this->package->header->UpdateSource = $this->project->project->update_source;
            }

            Console::outDebug('scanning project files');
            Console::outDebug('theseer\DirectoryScanner - Copyright (c) 2009-2014 Arne Blankerts <arne@blankerts.de> All rights reserved.');

            // First scan the project files and create a file struct.
            $DirectoryScanner = new DirectoryScanner();

            try
            {
                $DirectoryScanner->unsetFlag(FilesystemIterator::FOLLOW_SYMLINKS);
            }
            catch (Exception $e)
            {
                throw new PackageException('Cannot unset flag \'FOLLOW_SYMLINKS\' in DirectoryScanner, ' . $e->getMessage(), $e);
            }

            // Include file components that can be compiled
            $DirectoryScanner->setIncludes(ComponentFileExtensions::PHP);

            if($selected_build_configuration->exclude_files !== null && count($selected_build_configuration->exclude_files) > 0)
            {
                $DirectoryScanner->setExcludes($selected_build_configuration->exclude_files);
            }

            $source_path = $this->path . $this->project->build->source_path;

            // TODO: Re-implement the scanning process outside the compiler, as this is will be redundant
            // Scan for components first.
            if(file_exists($source_path))
            {
                Console::outVerbose('Scanning for components... ');
                /** @var SplFileInfo $item */
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                foreach($DirectoryScanner($source_path, True) as $item)
                {
                    // Ignore directories, they're not important. :-)
                    if(is_dir($item->getPathName()))
                    {
                        continue;
                    }

                    $Component = new Package\Component();
                    $Component->name = Functions::removeBasename($item->getPathname(), $this->path);
                    $this->package->components[] = $Component;

                    Console::outVerbose(sprintf('Found component %s', $Component->name));
                }

                if(count($this->package->components) > 0)
                {
                    Console::outVerbose(count($this->package->components) . ' component(s) found');
                }
                else
                {
                    Console::outVerbose('No components found');
                }

                // Clear previously excludes and includes
                $DirectoryScanner->setExcludes();
                $DirectoryScanner->setIncludes();

                // Ignore component files
                if($selected_build_configuration->exclude_files !== null && count($selected_build_configuration->exclude_files) > 0)
                {
                    $DirectoryScanner->setExcludes(array_merge($selected_build_configuration->exclude_files, ComponentFileExtensions::PHP));
                }
                else
                {
                    $DirectoryScanner->setExcludes(ComponentFileExtensions::PHP);
                }

                Console::outVerbose('Scanning for resources... ');
                /** @var SplFileInfo $item */
                foreach($DirectoryScanner($source_path) as $item)
                {
                    // Ignore directories, they're not important. :-)
                    if(is_dir($item->getPathName()))
                    {
                        continue;
                    }

                    $Resource = new Package\Resource();
                    $Resource->Name = Functions::removeBasename($item->getPathname(), $this->path);
                    $this->package->resources[] = $Resource;

                    Console::outVerbose(sprintf('found resource %s', $Resource->Name));
                }

                if(count($this->package->resources) > 0)
                {
                    Console::outVerbose(count($this->package->resources) . ' resources(s) found');
                }
                else
                {
                    Console::outVerbose('No resources found');
                }
            }
            else
            {
                Console::outWarning('Source path does not exist, skipping resource and component scanning');
            }

            $selected_dependencies = [];

            if($this->project->build->dependencies !== null && count($this->project->build->dependencies) > 0)
            {
                $selected_dependencies = array_merge($selected_dependencies, $this->project->build->dependencies);
            }

            if($selected_build_configuration->dependencies !== null && count($selected_build_configuration->dependencies) > 0)
            {
                $selected_dependencies = array_merge($selected_dependencies, $selected_build_configuration->dependencies);
            }

            // Process the dependencies
            if(count($selected_dependencies) > 0)
            {
                $package_lock_manager = new PackageLockManager();
                $filesystem = new Filesystem();

                $lib_path = $selected_build_configuration->output_path . DIRECTORY_SEPARATOR . 'libs';
                if($filesystem->exists($lib_path))
                {
                    $filesystem->remove($lib_path);
                }

                Console::outVerbose('Scanning for dependencies... ');
                foreach($selected_dependencies as $dependency)
                {
                    Console::outVerbose(sprintf('processing dependency %s', $dependency->Name));
                    switch($dependency->SourceType)
                    {
                        case DependencySourceType::STATIC:

                            try
                            {
                                $out_path = $lib_path . DIRECTORY_SEPARATOR . sprintf('%s=%s.lib', $dependency->Name, $dependency->Version);

                                $package = $package_lock_manager->getPackageLock()?->getPackage($dependency->Name);
                                if($package === null)
                                {
                                    throw new PackageLockException('Cannot find package lock for dependency ' . $dependency->Name);
                                }

                                $version = $package->getVersion($dependency->Version);
                                if($version === null)
                                {
                                    throw new VersionNotFoundException('Cannot find version ' . $dependency->Version . ' for dependency ' . $dependency->Name);
                                }

                                Console::outDebug(sprintf('copying shadow package %s=%s to %s', $dependency->Name, $dependency->Version, $out_path));

                                if(!$filesystem->exists($lib_path))
                                {
                                    $filesystem->mkdir($lib_path);
                                }

                                $filesystem->copy($version->Location, $out_path);
                                $dependency->Source = 'libs' . DIRECTORY_SEPARATOR . sprintf('%s=%s.lib', $dependency->Name, $dependency->Version);

                            }
                            catch (VersionNotFoundException $e)
                            {
                                throw new PackageException('Static linking not possible, cannot find version ' . $dependency->Version . ' for dependency ' . $dependency->Name, $e);
                            }
                            catch (PackageLockException $e)
                            {
                                throw new PackageException('Static linking not possible, cannot find package lock for dependency ' . $dependency->Name, $e);
                            }

                            break;

                        default:
                        case DependencySourceType::REMOTE:
                            break;
                    }

                    $this->package->addDependency($dependency);
                }

                if(count($this->package->dependencies) > 0)
                {
                    Console::outVerbose(count($this->package->dependencies) . ' dependency(ies) found');
                }
                else
                {
                    Console::outVerbose('No dependencies found');
                }
            }

        }

        /**
         * Executes the compile process in the correct order and returns the finalized Package object
         *
         * @return Package|null
         * @throws BuildException
         * @throws IOException
         * @throws PathNotFoundException
         * @throws RunnerExecutionException
         */
        public function build(): ?Package
        {
            $this->compileExecutionPolicies();
            $this->compileComponents();
            $this->compileResources();

            PackageCompiler::compilePackageConstants($this->package, [
                ConstantReferences::ASSEMBLY => $this->project->assembly,
                ConstantReferences::BUILD => null,
                ConstantReferences::DATE_TIME => time()
            ]);

            return $this->getPackage();
        }

        /**
         * Compiles the resources of the package
         *
         * @return void
         * @throws BuildException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function compileResources(): void
        {
            if($this->package === null)
            {
                throw new BuildException('The prepare() method must be called before building the package');
            }

            if(count($this->package->resources) === 0)
            {
                return;
            }

            // Process the resources
            $total_items = count($this->package->resources);
            $processed_items = 1;
            $resources = [];

            if($total_items > 5)
            {
                Console::out('Processing resources');
            }

            foreach($this->package->resources as $resource)
            {
                /** @noinspection DisconnectedForeachInstructionInspection */
                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                // Get the data and
                $resource->Data = IO::fread(Functions::correctDirectorySeparator($this->path . $resource->Name));
                $resource->Data = Base64::encode($resource->Data);
                $resource->Name = str_replace($this->project->build->source_path, (string)null, $resource->Name);
                $resource->updateChecksum();
                $resources[] = $resource;

                Console::outDebug(sprintf('processed resource %s', $resource->Name));
            }

            // Update the resources
            $this->package->resources = $resources;
        }

        /**
         * Compiles the components of the package
         *
         * @return void
         * @throws BuildException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function compileComponents(): void
        {
            if($this->package === null)
            {
                throw new BuildException('The prepare() method must be called before building the package');
            }

            if(count($this->package->components) === 0)
            {
                return;
            }

            $total_items = count($this->package->components);
            $processed_items = 1;
            $components = [];

            if($total_items > 5)
            {
                Console::out('Compiling components');
            }

            // Process the components and attempt to create an AST representation of the source
            foreach($this->package->components as $component)
            {
                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                $content = IO::fread(Functions::correctDirectorySeparator($this->path . $component->name));
                $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

                try
                {
                    $stmts = $parser->parse($content);
                    $encoded = json_encode($stmts, JSON_THROW_ON_ERROR);
                    unset($stmts);
                    $component->data_types = ComponentDataType::AST;
                    $component->data = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
                }
                catch(Exception $e)
                {
                    $component->data_types = ComponentDataType::BASE64_ENCODED;
                    $component->data = Base64::encode($content);
                    unset($e);
                }

                unset($parser);

                $component->name = str_replace($this->project->build->source_path, (string)null, $component->name);
                $component->updateChecksum();
                $components[] = $component;
                ++$processed_items;

                Console::outDebug(sprintf('processed component %s (%s)', $component->name, $component->data_types));
            }

            // Update the components
            $this->package->components = $components;
        }

        /**
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         * @throws RunnerExecutionException
         */
        public function compileExecutionPolicies(): void
        {
            $this->package->execution_units = PackageCompiler::compileExecutionPolicies($this->path, $this->project);
        }

        /**
         * @inheritDoc
         */
        public function getPackage(): ?Package
        {
            return $this->package;
        }

    }