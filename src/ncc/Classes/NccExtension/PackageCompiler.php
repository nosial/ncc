<?php

    namespace ncc\Classes\NccExtension;

    use Exception;
    use ncc\Abstracts\CompilerExtensions;
    use ncc\Abstracts\ConstantReferences;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Options\BuildConfigurationValues;
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
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedRunnerException;
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
         * @throws UnsupportedRunnerException
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
         * Compiles the execution policies of the package
         *
         * @param string $path
         * @param ProjectConfiguration $configuration
         * @return array
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws UnsupportedRunnerException
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
         * @throws BuildException
         * @throws IOException
         */
        public static function writePackage(string $path, Package $package, ProjectConfiguration $configuration, string $build_configuration=BuildConfigurationValues::DefaultConfiguration): string
        {
            // Write the package to disk
            $FileSystem = new Filesystem();
            $BuildConfiguration = $configuration->Build->getBuildConfiguration($build_configuration);
            if($FileSystem->exists($path . $BuildConfiguration->OutputPath))
            {
                try
                {
                    $FileSystem->remove($path . $BuildConfiguration->OutputPath);
                }
                catch(\ncc\ThirdParty\Symfony\Filesystem\Exception\IOException $e)
                {
                    throw new BuildException('Cannot delete directory \'' . $path . $BuildConfiguration->OutputPath . '\', ' . $e->getMessage(), $e);
                }
            }

            // Finally write the package to the disk
            $FileSystem->mkdir($path . $BuildConfiguration->OutputPath);
            $output_file = $path . $BuildConfiguration->OutputPath . DIRECTORY_SEPARATOR . $package->Assembly->Package . '.ncc';
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
         * Compiles the special formatted constants
         *
         * @param Package $package
         * @param int $timestamp
         * @return array
         */
        public static function compileRuntimeConstants(Package $package, int $timestamp): array
        {
            $compiled_constants = [];

            foreach($package->Header->RuntimeConstants as $name => $value)
            {
                $compiled_constants[$name] = self::compileConstants($value, [
                    ConstantReferences::Assembly => $package->Assembly,
                    ConstantReferences::DateTime => $timestamp,
                    ConstantReferences::Build => null
                ]);
            }

            return $compiled_constants;
        }

        /**
         * Compiles the constants in the package object
         *
         * @param Package $package
         * @param array $refs
         * @return void
         */
        public static function compilePackageConstants(Package &$package, array $refs): void
        {
            if($package->Assembly !== null)
            {
                $assembly = [];
                foreach($package->Assembly->toArray() as $key => $value)
                {
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
                    $units[] = self::compileExecutionUnitConstants($executionUnit, $refs);
                }
                $package->ExecutionUnits = $units;
                unset($units);
            }
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

            return $value;
        }
    }