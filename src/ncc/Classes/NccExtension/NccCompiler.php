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
    use ncc\Enums\ComponentDataType;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\Package;
    use ncc\Objects\Package\Resource;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\Resolver;

    class NccCompiler
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
         * @param string $build_configuration
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public function build(string $build_configuration=BuildConfigurationValues::DEFAULT): string
        {
            $configuration = $this->project_manager->getProjectConfiguration()->getBuild()->getBuildConfiguration($build_configuration);
            $package_path = $configuration->getOutputPath() . DIRECTORY_SEPARATOR . $this->project_manager->getProjectConfiguration()->getAssembly()->getPackage() . '.ncc';
            $package_writer = new PackageWriter($package_path);

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
            $package_writer->setMetadata($this->buildMetadata($build_configuration));

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
                Console::out('Processing execution policies...');
                $execution_units = $this->project_manager->getExecutionUnits($build_configuration);

                if(count($execution_units) === 0)
                {
                    Console::outWarning('The project contains execution policies but none of them are used');
                }

                foreach($execution_units as $unit)
                {
                    $package_writer->addExecutionUnit($unit);
                }
            }

            // Compile package components
            foreach($this->project_manager->getComponents($build_configuration) as $component)
            {
                Console::outVerbose(sprintf('Compiling \'%s\'', $component));
                $package_writer->addComponent($this->buildComponent($component));
            }

            // Compile package resources
            foreach($this->project_manager->getResources($build_configuration) as $resource)
            {
                Console::outVerbose(sprintf('Processing \'%s\'', $resource));
                $package_writer->addResource($this->buildResource($resource));
            }

            $package_writer->close();
            return $package_path;
        }

        /**
         * Compiles a single component as a base64 encoded string
         *
         * @param string $file_path
         * @return Package\Component
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function buildComponent(string $file_path): Package\Component
        {
            return new Package\Component(
                Functions::removeBasename($file_path),
                Base64::encode(IO::fread($file_path)), ComponentDataType::BASE64_ENCODED
            );
        }

        /**
         * @param string $file_path
         * @return Resource
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function buildResource(string $file_path): Package\Resource
        {
            return new Package\Resource(
                basename($file_path), IO::fread($file_path)
            );
        }

        /**
         * Builds the package header
         *
         * @param ProjectManager $project_manager
         * @param string $build_configuration
         * @return Package\Metadata
         * @throws ConfigurationException
         */
        public function buildMetadata(string $build_configuration=BuildConfigurationValues::DEFAULT): Package\Metadata
        {
            $header = new Package\Metadata($this->project_manager->getProjectConfiguration()->getProject()->getCompiler());

            $header->setRuntimeConstants($this->project_manager->getRuntimeConstants($build_configuration));
            $header->setOptions($this->project_manager->getCompilerOptions($build_configuration));
            $header->setUpdateSource($this->project_manager->getProjectConfiguration()->getProject()->getUpdateSource());
            $header->setMainExecutionPolicy($this->project_manager->getProjectConfiguration()->getBuild()->getMain());
            $header->setInstaller($this->project_manager->getProjectConfiguration()->getInstaller());

            return $header;
        }
    }