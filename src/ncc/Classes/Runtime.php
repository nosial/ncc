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

    namespace ncc\Classes;

    use Exception;
    use InvalidArgumentException;
    use ncc\Enums\FileDescriptor;
    use ncc\Enums\Flags\PackageFlags;
    use ncc\Enums\Options\BuildConfigurationOptions;
    use ncc\Enums\Options\ComponentDecodeOptions;
    use ncc\Enums\PackageDirectory;
    use ncc\Enums\Versions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\ImportException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Managers\PackageManager;
    use ncc\Objects\Package\Metadata;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\Validate;
    use RuntimeException;
    use Throwable;

    class Runtime
    {
        /**
         * @var array
         */
        private static $imported_packages = [];

        /**
         * @var array
         */
        private static $class_map = [];

        /**
         * @var PackageManager|null
         */
        private static $package_manager;

        /**
         * @var array
         */
        private static $included_files = [];

        /**
         * Executes the main execution point of an imported package and returns the evaluated result
         * This method may exit the program without returning a value
         *
         * @param string $package
         * @return mixed
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         * @throws OperationException
         */
        public static function execute(string $package): int
        {
            if(!self::isImported($package))
            {
                throw new InvalidArgumentException(sprintf('Package %s is not imported', $package));
            }

            if(self::$imported_packages[$package] instanceof PackageReader)
            {
                return ExecutionUnitRunner::executeFromPackage(
                    self::$imported_packages[$package],
                    self::$imported_packages[$package]->getMetadata()->getMainExecutionPolicy()
                );
            }

            if(is_string(self::$imported_packages[$package]))
            {
                $metadata_path = self::$imported_packages[$package] . DIRECTORY_SEPARATOR . FileDescriptor::METADATA;

                if(!is_file($metadata_path))
                {
                    throw new RuntimeException(sprintf('The package %s does not have a metadata file (is it corrupted?)', $package));
                }

                return ExecutionUnitRunner::executeFromSystem(
                    self::$imported_packages[$package],
                    Metadata::fromArray(ZiProto::decode(IO::fread($metadata_path)))->getMainExecutionPolicy()
                );
            }

            throw new RuntimeException('Unable to execute the main execution point of the package, this is probably a bug');
        }

        /**
         * @param string $package
         * @param string $version
         * @return string
         * @throws ImportException
         */
        public static function import(string $package, string $version=Versions::LATEST->value): string
        {
            if(self::isImported($package))
            {
                return $package;
            }

            if(is_file($package))
            {
                try
                {
                    return self::importFromPackage(realpath($package));
                }
                catch(ImportException $e)
                {
                    throw $e;
                }
                catch(Exception $e)
                {
                    throw new ImportException(sprintf('Failed to import package from file "%s" due to an exception: %s', $package, $e->getMessage()), $e);
                }
            }

            if(self::getPackageManager()->getPackageLock()->entryExists($package))
            {
                try
                {
                    return self::importFromSystem($package, $version);
                }
                catch(ImportException $e)
                {
                    throw $e;
                }
                catch(Exception $e)
                {
                    throw new ImportException(sprintf('Failed to import package from system "%s" due to an exception: %s', $package, $e->getMessage()), $e);
                }
            }

            throw new RuntimeException('Importing from a package name is not supported yet');
        }

        /**
         * @param string $package
         * @param string $version
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws ImportException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        private static function importFromSystem(string $package, string $version=Versions::LATEST->value): string
        {
            if(!self::getPackageManager()->getPackageLock()->entryExists($package))
            {
                throw new ImportException(sprintf('The package "%s" does not exist in the package lock', $package));
            }

            $entry = self::getPackageManager()->getPackageLock()->getEntry($package);
            self::$imported_packages[$package] = $entry->getPath($version);

            foreach($entry->getClassMap($version) as $class => $component_name)
            {
                $component_path = $entry->getPath($version) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $component_name;
                self::$class_map[strtolower($class)] = $component_path;
            }

            if($entry->getMetadata($version)->getOption(BuildConfigurationOptions::REQUIRE_FILES) !== null)
            {
                foreach($entry->getMetadata($version)->getOption(BuildConfigurationOptions::REQUIRE_FILES) as $item)
                {
                    try
                    {
                        // Get the file contents and prepare it
                        $required_file = IO::fread($entry->getPath($version) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $item);
                        $required_file = preg_replace('/^<\?php|<\?PHP/', '', $required_file, 1);

                        eval($required_file);
                        unset($required_file);
                    }
                    catch(ConfigurationException $e)
                    {
                        throw new ImportException(sprintf('Failed to import "%s" from %s: %s', $item, $package, $e->getMessage()), $e);
                    }
                }
            }

            if(isset($entry->getMetadata($version)->getOptions()[PackageFlags::STATIC_DEPENDENCIES]))
            {
                // Fake import the dependencies
                foreach($entry->getVersion($version)->getDependencies() as $dependency)
                {
                    self::$imported_packages[$dependency->getName()] = $entry->getPath($version);
                }
            }
            else
            {
                // Import dependencies recursively
                foreach($entry->getVersion($version)->getDependencies() as $dependency)
                {
                    /** @noinspection UnusedFunctionResultInspection */
                    self::import($dependency->getName(), $dependency->getVersion());
                }
            }

            return $package;
        }

        /**
         * Imports a package from a package file
         *
         * @param string $package_path
         * @return string
         * @throws ConfigurationException
         * @throws ImportException
         * @throws NotSupportedException
         * @throws OperationException
         */
        private static function importFromPackage(string $package_path): string
        {
            try
            {
                $package_reader = new PackageReader($package_path);
            }
            catch(Exception $e)
            {
                throw new RuntimeException(sprintf('Failed to import package from file "%s" due to an exception: %s', $package_path, $e->getMessage()), 0, $e);
            }

            // Check if the package is already imported
            if(in_array($package_reader->getAssembly()->getPackage(), self::$imported_packages, true))
            {
                $package_name = $package_reader->getAssembly()->getPackage();
                unset($package_reader);
                return $package_name;
            }

            // Import the package
            $package_name = $package_reader->getAssembly()->getPackage();
            self::$imported_packages[$package_name] = $package_reader;

            // Register the autoloader
            foreach($package_reader->getClassMap() as $value)
            {
                self::$class_map[strtolower($value)] = static function() use ($value, $package_name)
                {
                    return self::$imported_packages[$package_name]->getComponentByClass($value)->getData();
                };
            }

            // Import the required files
            if($package_reader->getMetadata()->getOption(BuildConfigurationOptions::REQUIRE_FILES) !== null)
            {
                foreach($package_reader->getMetadata()->getOption(BuildConfigurationOptions::REQUIRE_FILES) as $item)
                {
                    try
                    {
                        eval($package_reader->getComponent($item)->getData());
                    }
                    catch(ConfigurationException $e)
                    {
                        throw new ImportException(sprintf('Failed to import "%s" from %s: %s', $item, $package_name, $e->getMessage()), $e);
                    }
                }
            }

            if($package_reader->getFlag(PackageFlags::STATIC_DEPENDENCIES))
            {
                // Fake import the dependencies
                foreach($package_reader->getDependencies() as $dependency_name)
                {
                    $dependency = $package_reader->getDependency($dependency_name);
                    self::$imported_packages[$dependency->getName()] = $package_reader;
                }
            }
            else
            {
                // Import dependencies recursively
                foreach($package_reader->getDependencies() as $dependency)
                {
                    $dependency = $package_reader->getDependency($dependency);

                    /** @noinspection UnusedFunctionResultInspection */
                    self::import($dependency->getName(), $dependency->getVersion());
                }
            }

            return $package_reader->getAssembly()->getPackage();
        }

        /**
         * Determines if the package is already imported
         *
         * @param string $package
         * @return bool
         */
        public static function isImported(string $package): bool
        {
            return isset(self::$imported_packages[$package]);
        }

        /**
         * Returns an array of all the packages that is currently imported
         *
         * @return array
         */
        public static function getImportedPackages(): array
        {
            return array_keys(self::$imported_packages);
        }

        /**
         * @param string $class
         * @return void
         */
        public static function autoloadHandler(string $class): void
        {
            $class = strtolower($class);

            if(!isset(self::$class_map[$class]))
            {
                return;
            }

            if(is_callable(self::$class_map[$class]))
            {
                eval(self::$class_map[$class]());
                return;
            }

            if(is_string(self::$class_map[$class]) && is_file(self::$class_map[$class]))
            {
                require_once self::$class_map[$class];
            }
        }

        /**
         * @return PackageManager
         */
        private static function getPackageManager(): PackageManager
        {
            if(self::$package_manager === null)
            {
                self::$package_manager = new PackageManager();
            }

            return self::$package_manager;
        }

        /**
         * Returns an array of included files both from the php runtime and ncc runtime
         *
         * @return array
         */
        public static function runtimeGetIncludedFiles(): array
        {
            return array_merge(get_included_files(), self::$included_files);
        }

        /**
         * Evaluates and executes PHP code with error handling, this function
         * gracefully handles <?php ?> tags and exceptions the same way as the
         * require/require_once/include/include_once expressions
         *
         * @param string $code The PHP code to be executed
         */
        public static function extendedEvaluate(string $code): void
        {
            if(ob_get_level() > 0)
            {
                ob_clean();
            }

            $exceptions = [];
            $code = preg_replace_callback('/<\?php(.*?)\?>/s', static function ($matches) use (&$exceptions)
            {
                ob_start();

                try
                {
                    eval($matches[1]);
                }
                catch (Throwable $e)
                {
                    $exceptions[] = $e;
                }

                return ob_get_clean();
            }, $code);

            ob_start();

            try
            {
                eval('?>' . $code);
            }
            catch (Throwable $e)
            {
                $exceptions[] = $e;
            }

            if (!empty($exceptions))
            {
                print(ob_get_clean());

                $exception_stack = null;
                foreach ($exceptions as $e)
                {
                    if($exception_stack === null)
                    {
                        $exception_stack = $e;
                    }
                    else
                    {
                        $exception_stack = new Exception($exception_stack->getMessage(), $exception_stack->getCode(), $e);
                    }
                }

                throw new RuntimeException('An exception occurred while evaluating the code', 0, $exception_stack);
            }

            print(ob_get_clean());
        }

        /**
         * Returns the content of the aquired file
         *
         * @param string $path
         * @param string|null $package
         * @return string
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        private static function acquireFile(string $path, ?string $package=null): string
        {
            $cwd_checked = false; // sanity check to prevent checking the cwd twice

            // Check if the file is absolute
            if(is_file($path))
            {
                Console::outDebug(sprintf('Acquired file "%s" from absolute path', $path));
                return IO::fread($path);
            }

            // Since $package is not null, let's try to acquire the file from the package
            if($package !== null && isset(self::$imported_packages[$package]))
            {
                $base_path = basename($path);

                if(self::$imported_packages[$package] instanceof PackageReader)
                {
                    $acquired_file = self::$imported_packages[$package]->find($base_path);
                    Console::outDebug(sprintf('Acquired file "%s" from package "%s"', $path, $package));

                    return match (Resolver::componentType($acquired_file))
                    {
                        PackageDirectory::RESOURCES => self::$imported_packages[$package]->getResource(Resolver::componentName($acquired_file))->getData(),
                        PackageDirectory::COMPONENTS => self::$imported_packages[$package]->getComponent(Resolver::componentName($acquired_file))->getData([ComponentDecodeOptions::AS_FILE]),
                        default => throw new IOException(sprintf('Unable to acquire file "%s" from package "%s" because it is not a resource or component', $path, $package)),
                    };
                }

                if(is_dir(self::$imported_packages[$package]))
                {
                    $base_path = basename($path);
                    foreach(IO::scan(self::$imported_packages[$package]) as $file)
                    {
                        if(str_ends_with($file, $base_path))
                        {
                            Console::outDebug(sprintf('Acquired file "%s" from package "%s"', $path, $package));
                            return IO::fread($file);
                        }
                    }
                }
            }

            // If not, let's try the include_path
            foreach(explode(PATH_SEPARATOR, get_include_path()) as $file_path)
            {
                if($file_path === '.' && !$cwd_checked)
                {
                    $cwd_checked = true;
                    $file_path = getcwd();
                }

                if(is_file($file_path . DIRECTORY_SEPARATOR . $path))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from include_path', $path));
                    return IO::fread($file_path . DIRECTORY_SEPARATOR . $path);
                }

                if(is_file($file_path . DIRECTORY_SEPARATOR . basename($path)))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from include_path (using basename)', $path));
                    return IO::fread($file_path . DIRECTORY_SEPARATOR . basename($path));
                }
            }

            // Check the current working directory
            if(!$cwd_checked)
            {
                if(is_file(getcwd() . DIRECTORY_SEPARATOR . $path))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from current working directory', $path));
                    return IO::fread(getcwd() . DIRECTORY_SEPARATOR . $path);
                }

                if(is_file(getcwd() . DIRECTORY_SEPARATOR . basename($path)))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from current working directory (using basename)', $path));
                    return IO::fread(getcwd() . DIRECTORY_SEPARATOR . basename($path));
                }
            }

            // Check the calling script's directory
            $called_script_directory = dirname(debug_backtrace()[0]['file']);
            $file_path = $called_script_directory . DIRECTORY_SEPARATOR . $path;
            if(is_file($file_path))
            {
                Console::outDebug(sprintf('Acquired file "%s" from calling script\'s directory', $path));
                return IO::fread($file_path);
            }

            throw new IOException(sprintf('Unable to acquire file "%s" because it does not exist', $path));
        }

        /**
         * Includes a file at runtime
         *
         * @param string $path
         * @param string|null $package
         * @return void
         */
        public static function runtimeInclude(string $path, ?string $package=null): void
        {
            try
            {
                $acquired_file = self::acquireFile($path, $package);
            }
            catch(Exception $e)
            {
                $package ?
                    Console::outWarning(sprintf('Failed to acquire file "%s" from package "%s" at runtime: %s', $path, $package, $e->getMessage())) :
                    Console::outWarning(sprintf('Failed to acquire file "%s" at runtime: %s', $path, $e->getMessage()));

                return;
            }

            if(!in_array($path, self::$included_files, true))
            {
                self::$included_files[] = $path;
            }

            self::extendedEvaluate($acquired_file);
        }

        /**
         * Includes a file at runtime if it's not already included
         *
         * @param string $path
         * @param string|null $package
         * @return void
         */
        public static function runtimeIncludeOnce(string $path, ?string $package=null): void
        {
            if(in_array($path, self::runtimeGetIncludedFiles(), true))
            {
                return;
            }

            self::runtimeInclude($path, $package);
        }

        /**
         * Requires a file at runtime, throws an exception if the file failed to require
         *
         * @param string $path
         * @param string|null $package
         * @return void
         */
        public static function runtimeRequire(string $path, ?string $package=null): void
        {
            try
            {
                $acquired_file = self::acquireFile($path, $package);
            }
            catch(Exception $e)
            {
                $package ?
                    throw new RuntimeException(sprintf('Failed to acquire file "%s" from package "%s" at runtime: %s', $path, $package, $e->getMessage()), $e->getCode(), $e) :
                    throw new RuntimeException(sprintf('Failed to acquire file "%s" at runtime: %s', $path, $e->getMessage()), $e->getCode(), $e);
            }

            if(!in_array($path, self::$included_files, true))
            {
                self::$included_files[] = $path;
            }

            self::extendedEvaluate($acquired_file);
        }

        /**
         * Requires a file at runtime if it's not already required
         *
         * @param string $path
         * @return void
         */
        public static function runtimeRequireOnce(string $path): void
        {
            if(in_array($path, self::runtimeGetIncludedFiles(), true))
            {
                return;
            }

            self::runtimeRequire($path);
        }
    }