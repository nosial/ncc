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

    namespace ncc\Classes\NccExtension;

    use Exception;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\ConstantReferences;
    use ncc\Enums\LogLevel;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Enums\ProjectType;
    use ncc\Classes\ComposerExtension\ComposerSourceBuiltin;
    use ncc\Classes\PhpExtension\PhpCompiler;
    use ncc\CLI\Main;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\CompilerInterface;
    use ncc\Managers\ProjectManager;
    use ncc\ncc;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;

    class PackageCompiler
    {
        /**
         * Compiles the project into a package
         *
         * @param ProjectManager $manager
         * @param string $build_configuration
         * @return string
         * @throws BuildException
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public static function compile(ProjectManager $manager, string $build_configuration=BuildConfigurationValues::DEFAULT): string
        {
            $configuration = $manager->getProjectConfiguration();

            if(Main::getLogLevel() !== null && Resolver::checkLogLevel(LogLevel::DEBUG, Main::getLogLevel()))
            {
                foreach($configuration->assembly->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                }
                foreach($configuration->project->compiler->toArray() as $prop => $value)
                {
                    Console::outDebug(sprintf('compiler.%s: %s', $prop, ($value ?? 'n/a')));
                }
            }

            // Select the correct compiler for the specified extension
            if (strtolower($configuration->project->compiler->getExtension()) === CompilerExtensions::PHP)
            {
                /** @var CompilerInterface $Compiler */
                $Compiler = new PhpCompiler($configuration, $manager->getProjectPath());
            }
            else
            {
                throw new NotSupportedException('The compiler extension \'' . $configuration->project->compiler->getExtension() . '\' is not supported');
            }

            $build_configuration = $configuration->build->getBuildConfiguration($build_configuration)->getName();
            Console::out(sprintf('Building %s=%s', $configuration->assembly->getPackage(), $configuration->assembly->getVersion()));
            $Compiler->prepare($build_configuration);
            $Compiler->build();

            return self::writePackage(
                $manager->getProjectPath(), $Compiler->getPackage(), $configuration, $build_configuration
            );
        }

        /**
         * Attempts to detect the project type and convert it accordingly before compiling
         * Returns the compiled package path
         *
         * @param string $path
         * @param string|null $version
         * @return string
         * @throws BuildException
         */
        public static function tryCompile(string $path, ?string $version=null): string
        {
            $project_type = Resolver::detectProjectType($path);

            try
            {
                if($project_type->ProjectType === ProjectType::COMPOSER)
                {
                    $project_path = ComposerSourceBuiltin::fromLocal($project_type->ProjectPath);
                }
                elseif($project_type->ProjectType === ProjectType::NCC)
                {
                    $project_manager = new ProjectManager($project_type->ProjectPath);
                    $project_manager->getProjectConfiguration()->assembly->setVersion($version);
                    $project_path = $project_manager->build();
                }
                else
                {
                    throw new NotSupportedException(sprintf('Failed to compile %s, project type %s is not supported', $project_type->ProjectPath, $project_type->ProjectType));
                }

                if($version !== null)
                {
                    $package = Package::load($project_path);
                    $package->assembly->setVersion(Functions::convertToSemVer($version));
                    $package->save($project_path);
                }

                return $project_path;
            }
            catch(Exception $e)
            {
                throw new BuildException('Failed to build project', $e);
            }
        }


        /**
         * Compiles the execution policies of the package
         *
         * @param string $path
         * @param ProjectConfiguration $configuration
         * @return array
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public static function compileExecutionPolicies(string $path, ProjectConfiguration $configuration): array
        {
            if(count($configuration->execution_policies) === 0)
            {
                return [];
            }

            Console::out('Compiling Execution Policies');
            $total_items = count($configuration->execution_policies);
            $execution_units = [];
            $processed_items = 1;

            /** @var ProjectConfiguration\ExecutionPolicy $policy */
            foreach($configuration->execution_policies as $policy)
            {
                Console::outVerbose(sprintf('Compiling Execution Policy %s', $policy->getName()));

                /** @noinspection DisconnectedForeachInstructionInspection */
                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                $unit_path = Functions::correctDirectorySeparator($path . $policy->getExecute()->getTarget());
                $execution_units[] = Functions::compileRunner($unit_path, $policy);
            }

            if($total_items > 5 && ncc::cliMode())
            {
                print(PHP_EOL);
            }

            return $execution_units;
        }

        /**
         * Writes the finished package to disk, returns the output path
         *
         * @param string $path
         * @param Package $package
         * @param ProjectConfiguration $configuration
         * @param string $build_configuration
         * @return string
         * @throws IOException
         * @throws ConfigurationException
         */
        public static function writePackage(string $path, Package $package, ProjectConfiguration $configuration, string $build_configuration=BuildConfigurationValues::DEFAULT): string
        {
            Console::outVerbose(sprintf('Writing package to %s', $path));

            // Write the package to disk
            $FileSystem = new Filesystem();
            $BuildConfiguration = $configuration->build->getBuildConfiguration($build_configuration);
            if(!$FileSystem->exists($path . $BuildConfiguration->getOutputPath()))
            {
                Console::outDebug(sprintf('creating output directory %s', $path . $BuildConfiguration->getOutputPath()));
                $FileSystem->mkdir($path . $BuildConfiguration->getOutputPath());
            }

            // Finally write the package to the disk
            $FileSystem->mkdir($path . $BuildConfiguration->getOutputPath());
            $output_file = $path . $BuildConfiguration->getOutputPath() . DIRECTORY_SEPARATOR . $package->assembly->getPackage() . '.ncc';
            if($FileSystem->exists($output_file))
            {
                Console::outDebug(sprintf('removing existing package %s', $output_file));
                $FileSystem->remove($output_file);
            }
            $FileSystem->touch($output_file);

            try
            {
                $package->save($output_file);
            }
            catch(Exception $e)
            {
                throw new IOException('Cannot write to output file', $e);
            }

            return $output_file;
        }

        /**
         * Compiles the constants in the package object
         *
         * @param Package $package
         * @param array $refs
         * @return void
         */
        public static function compilePackageConstants(Package $package, array $refs): void
        {
            if($package->assembly !== null)
            {
                $assembly = [];

                foreach($package->assembly->toArray() as $key => $value)
                {
                    Console::outDebug(sprintf('compiling constant Assembly.%s (%s)', $key, implode(', ', array_keys($refs))));
                    $assembly[$key] = self::compileConstants($value, $refs);
                }
                $package->assembly = Assembly::fromArray($assembly);

                unset($assembly);
            }

            if($package->execution_units !== null && count($package->execution_units) > 0)
            {
                $units = [];
                foreach($package->execution_units as $executionUnit)
                {
                    Console::outDebug(sprintf('compiling execution unit constant %s (%s)', $executionUnit->execution_policy->getName(), implode(', ', array_keys($refs))));
                    $units[] = self::compileExecutionUnitConstants($executionUnit, $refs);
                }
                $package->execution_units = $units;
                unset($units);
            }

            $compiled_constants = [];
            foreach($package->header->RuntimeConstants as $name => $value)
            {
                Console::outDebug(sprintf('compiling runtime constant %s (%s)', $name, implode(', ', array_keys($refs))));
                $compiled_constants[$name] = self::compileConstants($value, $refs);
            }

            $options = [];
            foreach($package->header->Options as $name => $value)
            {
                if(is_array($value))
                {
                    $options[$name] = [];
                    foreach($value as $key => $val)
                    {
                        if(!is_string($val))
                        {
                            continue;
                        }

                        Console::outDebug(sprintf('compiling option %s.%s (%s)', $name, $key, implode(', ', array_keys($refs))));
                        $options[$name][$key] = self::compileConstants($val, $refs);
                    }
                }
                else
                {
                    Console::outDebug(sprintf('compiling option %s (%s)', $name, implode(', ', array_keys($refs))));
                    $options[$name] = self::compileConstants((string)$value, $refs);
                }
            }

            $package->header->Options = $options;
            $package->header->RuntimeConstants = $compiled_constants;
        }

        /**
         * Compiles the constants in a given execution unit
         *
         * @param Package\ExecutionUnit $unit
         * @param array $refs
         * @return Package\ExecutionUnit
         */
        public static function compileExecutionUnitConstants(Package\ExecutionUnit $unit, array $refs): Package\ExecutionUnit
        {
            $unit->execution_policy->setMessage(self::compileConstants($unit->execution_policy->getMessage(), $refs));

            if($unit->execution_policy->getExitHandlers() !== null)
            {
                if($unit->execution_policy->getExitHandlers()->getSuccess()?->getMessage() !== null)
                {
                    $unit->execution_policy->getExitHandlers()->getSuccess()?->setMessage(
                        self::compileConstants($unit->execution_policy->getExitHandlers()->getSuccess()->getMessage(), $refs)
                    );
                }

                if($unit->execution_policy->getExitHandlers()->getError()?->getMessage() !== null)
                {
                    $unit->execution_policy->getExitHandlers()->getError()?->setMessage(
                        self::compileConstants($unit->execution_policy->getExitHandlers()->getError()->getMessage(), $refs)
                    );
                }

                if($unit->execution_policy->getExitHandlers()->getWarning()?->getMessage() !== null)
                {
                    $unit->execution_policy->getExitHandlers()->getWarning()?->setMessage(
                        self::compileConstants($unit->execution_policy->getExitHandlers()->getWarning()->getMessage(), $refs)
                    );
                }

            }

            if($unit->execution_policy->getExecute() !== null)
            {
                $unit->execution_policy->getExecute()->setTarget(self::compileConstants($unit->execution_policy->getExecute()->getTarget(), $refs));
                $unit->execution_policy->getExecute()->setWorkingDirectory(self::compileConstants($unit->execution_policy->getExecute()->getWorkingDirectory(), $refs));

                if(count($unit->execution_policy->getExecute()->getOptions()) > 0)
                {
                    $options = [];
                    foreach($unit->execution_policy->getExecute()->getOptions() as $key=> $value)
                    {
                        $options[self::compileConstants($key, $refs)] = self::compileConstants($value, $refs);
                    }

                    $unit->execution_policy->getExecute()->setOptions($options);
                }
            }

            return $unit;
        }

        /**
         * Compiles multiple types of constants
         *
         * @param string|null $value
         * @param array $refs
         * @return string|null
         */
        public static function compileConstants(?string $value, array $refs): ?string
        {
            if($value === null)
            {
                return null;
            }

            if(isset($refs[ConstantReferences::ASSEMBLY]))
            {
                $value = ConstantCompiler::compileAssemblyConstants($value, $refs[ConstantReferences::ASSEMBLY]);
            }

            if(isset($refs[ConstantReferences::BUILD]))
            {
                $value = ConstantCompiler::compileBuildConstants($value);
            }

            if(isset($refs[ConstantReferences::DATE_TIME]))
            {
                $value = ConstantCompiler::compileDateTimeConstants($value, $refs[ConstantReferences::DATE_TIME]);
            }

            if(isset($refs[ConstantReferences::INSTALL]))
            {
                $value = ConstantCompiler::compileInstallConstants($value, $refs[ConstantReferences::INSTALL]);
            }

            if(isset($refs[ConstantReferences::RUNTIME]))
            {
                $value = ConstantCompiler::compileRuntimeConstants($value);
            }

            return $value;
        }
    }