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

    namespace ncc\Classes\NccExtension;

    use ncc\Classes\PackageWriter;
    use ncc\CLI\Main;
    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\Types\ComponentDataType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\Package\Component;
    use ncc\Objects\Package\Metadata;
    use ncc\Objects\Package\Resource;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\Resolver;

    class NccCompiler implements CompilerInterface
    {
        /**
         * @var ProjectManager
         */
        private $project_manager;

        /**
         * @param ProjectManager $project_manager
         */
        public function __construct(ProjectManager $project_manager)
        {
            $this->project_manager = $project_manager;
        }

        /**
         * Returns the project manager
         *
         * @return ProjectManager
         */
        public function getProjectManager(): ProjectManager
        {
            return $this->project_manager;
        }

        /**
         * @inheritDoc
         * @param string $build_configuration
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         * @noinspection UnusedFunctionResultInspection
         */
        public function build(string $build_configuration=BuildConfigurationValues::DEFAULT, array $options=[]): string
        {
            $configuration = $this->project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_configuration);
            $configuration->setOptions(array_merge($configuration->getOptions(), $options));

            if(count($options) > 0)
            {
                $configuration->setOptions(array_merge($configuration->getOptions(), $options));
            }

            if($configuration->getOutputName() !== null)
            {
                $package_path =
                    ConstantCompiler::compileConstants($this->project_manager->getProjectConfiguration(), $configuration->getOutputPath())
                    . DIRECTORY_SEPARATOR .
                    ConstantCompiler::compileConstants($this->project_manager->getProjectConfiguration(), $configuration->getOutputName());
            }
            elseif(isset($configuration->getOptions()[BuildConfigurationOptions::OUTPUT_FILE]))
            {
                $package_path = ConstantCompiler::compileConstants(
                    $this->project_manager->getProjectConfiguration(), $configuration->getOptions()[BuildConfigurationOptions::OUTPUT_FILE]
                );
            }
            else
            {
                $package_path =
                    ConstantCompiler::compileConstants($this->project_manager->getProjectConfiguration(), $configuration->getOutputPath())
                    . DIRECTORY_SEPARATOR .
                    ConstantCompiler::compileConstants($this->project_manager->getProjectConfiguration(), $this->project_manager->getProjectConfiguration()->getAssembly()->getPackage() . '.ncc');
            }

            $progress = 0;
            $steps =
                count($this->project_manager->getProjectConfiguration()->getExecutionPolicies()) +
                count($this->project_manager->getComponents($build_configuration)) +
                count($this->project_manager->getResources($build_configuration));
            $package_writer = $this->createPackageWriter($package_path, $configuration);


            Console::out(sprintf('Building project \'%s\'', $this->project_manager->getProjectConfiguration()->getAssembly()->getName()));

            // Debugging information
            if(Resolver::checkLogLevel(LogLevel::DEBUG, Main::getLogLevel()))
            {
                foreach($this->project_manager->getProjectConfiguration()->getAssembly()->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                }

                foreach($this->project_manager->getProjectConfiguration()->getProject()->getCompiler()->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('compiler.%s: %s', $prop, ($value ?? 'n/a')));
                }
            }

            Console::outVerbose('Building package header...');
            $this->processMetadata($package_writer, $build_configuration);

            Console::outVerbose('Adding assembly information...');
            $package_writer->setAssembly($this->project_manager->getProjectConfiguration()->getAssembly());

            if($this->project_manager->getProjectConfiguration()->getInstaller() !== null)
            {
                Console::outVerbose('Adding installer information...');
                /** @noinspection NullPointerExceptionInspection */
                $package_writer->setInstaller($this->project_manager->getProjectConfiguration()->getInstaller());
            }

            // Process execution policies
            if(count($this->project_manager->getProjectConfiguration()->getExecutionPolicies()) > 0)
            {
                Console::outVerbose('Processing execution policies...');
                $execution_units = $this->project_manager->getExecutionUnits($build_configuration);

                if(count($execution_units) === 0)
                {
                    $progress = count($this->project_manager->getProjectConfiguration()->getExecutionPolicies());
                    Console::inlineProgressBar($progress, $steps);
                    Console::outWarning('The project contains execution policies but none of them are used');
                }

                foreach($execution_units as $unit)
                {
                    $progress++;
                    Console::inlineProgressBar($progress, $steps);
                    $package_writer->addExecutionUnit($unit);
                }
            }

            // Compile package components
            foreach($this->project_manager->getComponents($build_configuration) as $component)
            {
                $progress++;
                Console::inlineProgressBar($progress, $steps);
                Console::outVerbose(sprintf('Compiling \'%s\'', $component));

                $this->processComponent($package_writer, $component);
            }

            // Compile package resources
            foreach($this->project_manager->getResources($build_configuration) as $resource)
            {
                $progress++;
                Console::inlineProgressBar($progress, $steps);
                Console::outVerbose(sprintf('Processing \'%s\'', $resource));

                $this->processResource($package_writer, $resource);
            }

            // Add the project dependencies
            foreach($this->project_manager->getProjectConfiguration()->getBuild()->getDependencies() as $dependency)
            {
                $package_writer->addDependencyConfiguration($dependency);
            }

            // Add the build dependencies
            foreach($configuration->getDependencies() as $dependency)
            {
                $package_writer->addDependencyConfiguration($dependency);
            }

            $package_writer->close();
            return $package_path;
        }

        /**
         * Creates a package writer with the specified options
         *
         * @param string $path
         * @param BuildConfiguration $build_configuration
         * @return PackageWriter
         * @throws IOException
         * @throws NotSupportedException
         */
        private function createPackageWriter(string $path, BuildConfiguration $build_configuration): PackageWriter
        {
            $package_writer = new PackageWriter($path);

            if(isset($build_configuration->getOptions()[BuildConfigurationOptions::COMPRESSION]))
            {
                $package_writer->addFlag(PackageFlags::COMPRESSION);
                switch(strtolower($build_configuration->getOptions()[BuildConfigurationOptions::COMPRESSION]))
                {
                    case BuildConfigurationOptions\CompressionOptions::HIGH:
                        $package_writer->addFlag(PackageFlags::HIGH_COMPRESSION);
                        break;

                    case BuildConfigurationOptions\CompressionOptions::MEDIUM:
                        $package_writer->addFlag(PackageFlags::MEDIUM_COMPRESSION);
                        break;

                    case BuildConfigurationOptions\CompressionOptions::LOW:
                        $package_writer->addFlag(PackageFlags::LOW_COMPRESSION);
                        break;

                    default:
                        throw new NotSupportedException(sprintf('The compression level \'%s\' is not supported', $build_configuration->getOptions()[BuildConfigurationOptions::COMPRESSION]));
                }
            }

            return $package_writer;
        }

        /**
         * Compiles a single component as a base64 encoded string
         *
         * @param PackageWriter $package_writer
         * @param string $file_path
         * @throws IOException
         * @throws PathNotFoundException
         * @noinspection UnusedFunctionResultInspection
         */
        public function processComponent(PackageWriter $package_writer, string $file_path): void
        {
            $package_writer->addComponent(new Component(
                Functions::removeBasename($file_path, $this->project_manager->getProjectPath()),
                Base64::encode(IO::fread($file_path)), ComponentDataType::BASE64_ENCODED
            ));
        }

        /**
         * Packs a resource into the package
         *
         * @param PackageWriter $package_writer
         * @param string $file_path
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         * @noinspection UnusedFunctionResultInspection
         */
        public function processResource(PackageWriter $package_writer, string $file_path): void
        {
            $package_writer->addResource(new Resource(
                Functions::removeBasename($file_path, $this->project_manager->getProjectPath()), IO::fread($file_path)
            ));
        }

        /**
         * Processes the package metadata
         *
         * @param PackageWriter $package_writer
         * @param string $build_configuration
         * @return void
         * @throws ConfigurationException
         */
        public function processMetadata(PackageWriter $package_writer, string $build_configuration=BuildConfigurationValues::DEFAULT): void
        {
            $metadata = new Metadata($this->project_manager->getProjectConfiguration()->getProject()->getCompiler());

            $metadata->setOptions($this->project_manager->getCompilerOptions($build_configuration));
            $metadata->setUpdateSource($this->project_manager->getProjectConfiguration()->getProject()->getUpdateSource());
            $metadata->setMainExecutionPolicy($this->project_manager->getProjectConfiguration()->getBuild()->getMain());
            $metadata->setInstaller($this->project_manager->getProjectConfiguration()->getInstaller());

            /** @noinspection UnusedFunctionResultInspection */
            $package_writer->setMetadata($metadata);
        }
    }