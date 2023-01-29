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
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\ConstantReferences;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Abstracts\ProjectType;
    use ncc\Classes\ComposerExtension\ComposerSourceBuiltin;
    use ncc\Classes\PhpExtension\PhpCompiler;
    use ncc\CLI\Main;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\PackagePreparationFailedException;
    use ncc\Exceptions\ProjectConfigurationNotFoundException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedProjectTypeException;
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
         * @throws AccessDeniedException
         * @throws BuildConfigurationNotFoundException
         * @throws BuildException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws MalformedJsonException
         * @throws PackagePreparationFailedException
         * @throws ProjectConfigurationNotFoundException
         * @throws UnsupportedCompilerExtensionException
         */
        public static function compile(ProjectManager $manager, string $build_configuration=BuildConfigurationValues::DefaultConfiguration): string
        {
            $configuration = $manager->getProjectConfiguration();

            if(Main::getLogLevel() !== null && Resolver::checkLogLevel(LogLevel::Debug, Main::getLogLevel()))
            {
                foreach($configuration->Assembly->toArray() as $prop => $value)
                    Console::outDebug(sprintf('assembly.%s: %s', $prop, ($value ?? 'n/a')));
                foreach($configuration->Project->Compiler->toArray() as $prop => $value)
                    Console::outDebug(sprintf('compiler.%s: %s', $prop, ($value ?? 'n/a')));
            }

            // Select the correct compiler for the specified extension
            /** @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection */
            switch(strtolower($configuration->Project->Compiler->Extension))
            {
                case CompilerExtensions::PHP:
                    /** @var CompilerInterface $Compiler */
                    $Compiler = new PhpCompiler($configuration, $manager->getProjectPath());
                    break;

                default:
                    throw new UnsupportedCompilerExtensionException('The compiler extension \'' . $configuration->Project->Compiler->Extension . '\' is not supported');
            }

            $build_configuration = $configuration->Build->getBuildConfiguration($build_configuration)->Name;
            Console::out(sprintf('Building %s=%s', $configuration->Assembly->Package, $configuration->Assembly->Version));
            $Compiler->prepare($build_configuration);
            $Compiler->build();

            return PackageCompiler::writePackage(
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
                if($project_type->ProjectType == ProjectType::Composer)
                {
                    $project_path = ComposerSourceBuiltin::fromLocal($project_type->ProjectPath);
                }
                elseif($project_type->ProjectType == ProjectType::Ncc)
                {
                    $project_manager = new ProjectManager($project_type->ProjectPath);
                    $project_manager->getProjectConfiguration()->Assembly->Version = $version;
                    $project_path = $project_manager->build();
                }
                else
                {
                    throw new UnsupportedProjectTypeException('The project type \'' . $project_type->ProjectType . '\' is not supported');
                }

                if($version !== null)
                {
                    $package = Package::load($project_path);
                    $package->Assembly->Version = Functions::convertToSemVer($version);
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws RunnerExecutionException
         */
        public static function compileExecutionPolicies(string $path, ProjectConfiguration $configuration): array
        {
            if(count($configuration->ExecutionPolicies) == 0)
                return [];

            Console::out('Compiling Execution Policies');
            $total_items = count($configuration->ExecutionPolicies);
            $execution_units = [];
            $processed_items = 1;

            /** @var ProjectConfiguration\ExecutionPolicy $policy */
            foreach($configuration->ExecutionPolicies as $policy)
            {
                Console::outVerbose(sprintf('Compiling Execution Policy %s', $policy->Name));

                if($total_items > 5)
                {
                    Console::inlineProgressBar($processed_items, $total_items);
                }

                $unit_path = Functions::correctDirectorySeparator($path . $policy->Execute->Target);
                $execution_units[] = Functions::compileRunner($unit_path, $policy);
            }

            if(ncc::cliMode() && $total_items > 5)
                print(PHP_EOL);

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
         * @throws BuildConfigurationNotFoundException
         * @throws IOException
         */
        public static function writePackage(string $path, Package $package, ProjectConfiguration $configuration, string $build_configuration=BuildConfigurationValues::DefaultConfiguration): string
        {
            Console::outVerbose(sprintf('Writing package to %s', $path));

            // Write the package to disk
            $FileSystem = new Filesystem();
            $BuildConfiguration = $configuration->Build->getBuildConfiguration($build_configuration);
            if(!$FileSystem->exists($path . $BuildConfiguration->OutputPath))
            {
                Console::outDebug(sprintf('creating output directory %s', $path . $BuildConfiguration->OutputPath));
                $FileSystem->mkdir($path . $BuildConfiguration->OutputPath);
            }

            // Finally write the package to the disk
            $FileSystem->mkdir($path . $BuildConfiguration->OutputPath);
            $output_file = $path . $BuildConfiguration->OutputPath . DIRECTORY_SEPARATOR . $package->Assembly->Package . '.ncc';
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
         * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
         */
        public static function compilePackageConstants(Package &$package, array $refs): void
        {
            if($package->Assembly !== null)
            {
                $assembly = [];
                foreach($package->Assembly->toArray() as $key => $value)
                {
                    Console::outDebug(sprintf('compiling consts Assembly.%s (%s)', $key, implode(', ', array_keys($refs))));
                    $assembly[$key] = self::compileConstants($value, $refs);
                }
                $package->Assembly = Assembly::fromArray($assembly);
                unset($assembly);
            }

            if($package->ExecutionUnits !== null && count($package->ExecutionUnits) > 0)
            {
                $units = [];
                foreach($package->ExecutionUnits as $executionUnit)
                {
                    Console::outDebug(sprintf('compiling execution unit consts %s (%s)', $executionUnit->ExecutionPolicy->Name, implode(', ', array_keys($refs))));
                    $units[] = self::compileExecutionUnitConstants($executionUnit, $refs);
                }
                $package->ExecutionUnits = $units;
                unset($units);
            }

            $compiled_constants = [];
            foreach($package->Header->RuntimeConstants as $name => $value)
            {
                Console::outDebug(sprintf('compiling runtime const %s (%s)', $name, implode(', ', array_keys($refs))));
                $compiled_constants[$name] = self::compileConstants($value, $refs);
            }

            $options = [];
            foreach($package->Header->Options as $name => $value)
            {
                if(is_array($value))
                {
                    $options[$name] = [];
                    foreach($value as $key => $val)
                    {
                        if(!is_string($val))
                            continue;

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

            $package->Header->Options = $options;
            $package->Header->RuntimeConstants = $compiled_constants;
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
            $unit->ExecutionPolicy->Message = self::compileConstants($unit->ExecutionPolicy->Message, $refs);

            if($unit->ExecutionPolicy->ExitHandlers !== null)
            {
                if($unit->ExecutionPolicy->ExitHandlers->Success !== null)
                {
                    $unit->ExecutionPolicy->ExitHandlers->Success->Message = self::compileConstants($unit->ExecutionPolicy->ExitHandlers->Success->Message, $refs);
                }

                if($unit->ExecutionPolicy->ExitHandlers->Error !== null)
                {
                    $unit->ExecutionPolicy->ExitHandlers->Error->Message = self::compileConstants($unit->ExecutionPolicy->ExitHandlers->Error->Message, $refs);
                }

                if($unit->ExecutionPolicy->ExitHandlers->Warning !== null)
                {
                    $unit->ExecutionPolicy->ExitHandlers->Warning->Message = self::compileConstants($unit->ExecutionPolicy->ExitHandlers->Warning->Message, $refs);
                }
            }

            if($unit->ExecutionPolicy->Execute !== null)
            {
                if($unit->ExecutionPolicy->Execute->Target !== null)
                {
                    $unit->ExecutionPolicy->Execute->Target = self::compileConstants($unit->ExecutionPolicy->Execute->Target, $refs);
                }

                if($unit->ExecutionPolicy->Execute->WorkingDirectory !== null)
                {
                    $unit->ExecutionPolicy->Execute->WorkingDirectory = self::compileConstants($unit->ExecutionPolicy->Execute->WorkingDirectory, $refs);
                }

                if($unit->ExecutionPolicy->Execute->Options !== null && count($unit->ExecutionPolicy->Execute->Options) > 0)
                {
                    $options = [];
                    foreach($unit->ExecutionPolicy->Execute->Options as $key=>$value)
                    {
                        $options[self::compileConstants($key, $refs)] = self::compileConstants($value, $refs);
                    }
                    $unit->ExecutionPolicy->Execute->Options = $options;
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
            if($value == null)
                return null;

            if(isset($refs[ConstantReferences::Assembly]))
                $value = ConstantCompiler::compileAssemblyConstants($value, $refs[ConstantReferences::Assembly]);

            if(isset($refs[ConstantReferences::Build]))
                $value = ConstantCompiler::compileBuildConstants($value);

            if(isset($refs[ConstantReferences::DateTime]))
                $value = ConstantCompiler::compileDateTimeConstants($value, $refs[ConstantReferences::DateTime]);

            if(isset($refs[ConstantReferences::Install]))
                $value = ConstantCompiler::compileInstallConstants($value, $refs[ConstantReferences::Install]);

            if(isset($refs[ConstantReferences::Runtime]))
                $value = ConstantCompiler::compileRuntimeConstants($value);

            return $value;
        }
    }