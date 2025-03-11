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
    use ncc\Exceptions\IntegrityException;
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
    use ncc\Utilities\RuntimeCache;
    use ncc\Utilities\Validate;
    use RuntimeException;
    use Throwable;
    use function trigger_error;

    class Runtime
    {
        /**
         * @var array
         */
        private static $importedPackages = [];

        /**
         * @var array
         */
        private static $classMap = [];

        /**
         * @var PackageManager|null
         */
        private static $packageManager;

        /**
         * @var array
         */
        private static $includedFiles = [];

        /**
         * Executes the main execution point of an imported package and returns the evaluated result
         * This method may exit the program without returning a value
         *
         * @param string $package
         * @param array $arguments
         * @return mixed
         * @throws ConfigurationException
         * @throws IOException
         * @throws IntegrityException
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public static function execute(string $package, array $arguments=[]): int
        {
            if(!self::isImported($package))
            {
                throw new InvalidArgumentException(sprintf('Package %s is not imported', $package));
            }

            if(self::$importedPackages[$package] instanceof PackageReader)
            {
                if(self::$importedPackages[$package]?->getMetadata()?->getMainExecutionPolicy() === null)
                {
                    Console::out('The package does not have a main execution policy, skipping execution');
                    return 0;
                }

                return ExecutionUnitRunner::executeFromPackage(
                    self::$importedPackages[$package],
                    self::$importedPackages[$package]->getMetadata()->getMainExecutionPolicy()
                );
            }

            if(is_string(self::$importedPackages[$package]))
            {
                $metadata_path = self::$importedPackages[$package] . DIRECTORY_SEPARATOR . FileDescriptor::METADATA->value;

                if(!is_file($metadata_path))
                {
                    throw new RuntimeException(sprintf('The package %s does not have a metadata file (is it corrupted?)', $package));
                }

                return ExecutionUnitRunner::executeFromSystem(
                    self::$importedPackages[$package],
                    Metadata::fromArray(ZiProto::decode(IO::fread($metadata_path)))->getMainExecutionPolicy(),
                    $arguments
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

            throw new RuntimeException(sprintf('Failed to import package "%s" because it does not exist', $package));
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
            self::$importedPackages[$package] = $entry->getPath($version);

            foreach($entry->getClassMap($version) as $class => $componentName)
            {
                $componentPath = $entry->getPath($version) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $componentName;
                self::$classMap[strtolower($class)] = $componentPath;
            }

            if($entry->getMetadata($version)->getOption(BuildConfigurationOptions::REQUIRE_FILES->value) !== null)
            {
                foreach($entry->getMetadata($version)->getOption(BuildConfigurationOptions::REQUIRE_FILES->value) as $item)
                {
                    $required_file = $entry->getPath($version) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $item;

                    try
                    {
                        if(!file_exists($required_file))
                        {
                            throw new PathNotFoundException($required_file);
                        }

                        // Get the file contents and prepare it
                        $evaluatedCode = IO::fread($required_file);

                        // Remove the PHP tags
                        $evaluatedCode = preg_replace('/^<\?php|<\?PHP/', '', $evaluatedCode, 1);
                        // Replace __DIR__ with the actual directory that the file is in
                        $evaluatedCode = str_replace('__DIR__', sprintf('"%s"', dirname($required_file)), $evaluatedCode);

                        set_error_handler(function ($error_number, $message, $file, $line) use ($item, $package)
                        {
                            throw new ImportException(sprintf('Fatal Evaluation Error: Failed to import "%s" from %s on %s:%s: %s', $item, $package, $file, $line, $message));
                        });

                        // Evaluate the code
                        eval($evaluatedCode);
                        restore_error_handler();
                        unset($evaluatedCode);
                    }
                    catch (ConfigurationException $e)
                    {
                        throw new ImportException(sprintf('%s: Failed to import "%s" from %s: %s', $required_file, $item, $package, $e->getMessage()), $e);
                    }
                    catch(ImportException $e)
                    {
                        throw $e;
                    }
                    catch (Throwable $e)
                    {
                        throw new ImportException(sprintf('%s: Failed to import "%s" from %s: %s', $required_file, $item, $package, $e->getMessage()), $e);
                    }
                }
            }

            $safePackageName = strtoupper($entry->getAssembly($version)->getName());
            foreach($entry->getMetadata($version)->getConstants() as $constant => $value)
            {
                $constantFullName = sprintf("%s_%s", $safePackageName, $constant);

                // Skip if already defined.
                if(defined($constantFullName))
                {
                    if(RuntimeCache::get(sprintf("defined_%s", $constantFullName)))
                    {
                        continue;
                    }

                    trigger_error(sprintf('Cannot define constant %s from package %s because the constant is already defined', $constantFullName, $package), E_USER_WARNING);
                    continue;
                }

                if(!Validate::constantName($constantFullName))
                {
                    // trigger warning only
                    trigger_error(sprintf('Cannot define constant %s from package %s because the constant name is invalid', $constantFullName, $package), E_USER_WARNING);
                    continue;
                }

                RuntimeCache::set(sprintf("defined_%s", $constantFullName), true);
                define($constantFullName, $value);
            }

            if(isset($entry->getMetadata($version)->getOptions()[PackageFlags::STATIC_DEPENDENCIES->value]))
            {
                // Fake import the dependencies
                foreach($entry->getVersion($version)->getDependencies() as $dependency)
                {
                    self::$importedPackages[$dependency->getName()] = $entry->getPath($version);
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
         * @param string $packagePath
         * @return string
         * @throws ConfigurationException
         * @throws ImportException
         * @throws IntegrityException
         * @throws OperationException
         */
        private static function importFromPackage(string $packagePath): string
        {
            try
            {
                $packageReader = new PackageReader($packagePath);
            }
            catch(Exception $e)
            {
                throw new RuntimeException(sprintf('Failed to import package from file "%s" due to an exception: %s', $packagePath, $e->getMessage()), 0, $e);
            }

            // Check if the package is already imported
            if(in_array($packageReader->getAssembly()->getPackage(), self::$importedPackages, true))
            {
                $packageName = $packageReader->getAssembly()->getPackage();
                unset($packageReader);
                return $packageName;
            }

            // Import the package
            $packageName = $packageReader->getAssembly()->getPackage();
            self::$importedPackages[$packageName] = $packageReader;

            // Register the autoloader
            foreach($packageReader->getClassMap() as $value)
            {
                self::$classMap[strtolower($value)] = static function() use ($value, $packageName)
                {
                    return self::$importedPackages[$packageName]->getComponentByClass($value)->getData();
                };
            }

            // Import the required files
            if($packageReader->getMetadata()->getOption(BuildConfigurationOptions::REQUIRE_FILES->value) !== null)
            {
                foreach($packageReader->getMetadata()->getOption(BuildConfigurationOptions::REQUIRE_FILES->value) as $item)
                {
                    try
                    {
                        eval($packageReader->getComponent($item)->getData());
                    }
                    catch(ConfigurationException $e)
                    {
                        throw new ImportException(sprintf('Failed to import "%s" from %s: %s', $item, $packageName, $e->getMessage()), $e);
                    }
                }
            }

            if($packageReader->getFlag(PackageFlags::STATIC_DEPENDENCIES->value))
            {
                // Fake import the dependencies
                foreach($packageReader->getDependencies() as $dependency_name)
                {
                    $dependency = $packageReader->getDependency($dependency_name);
                    self::$importedPackages[$dependency->getName()] = $packageReader;
                }
            }
            else
            {
                // Import dependencies recursively
                foreach($packageReader->getDependencies() as $dependency)
                {
                    $dependency = $packageReader->getDependency($dependency);

                    /** @noinspection UnusedFunctionResultInspection */
                    self::import($dependency->getName(), $dependency->getVersion());
                }
            }

            return $packageReader->getAssembly()->getPackage();
        }

        /**
         * Determines if the package is already imported
         *
         * @param string $package
         * @return bool
         */
        public static function isImported(string $package): bool
        {
            return isset(self::$importedPackages[$package]);
        }

        /**
         * Returns an array of all the packages that is currently imported
         *
         * @return array
         */
        public static function getImportedPackages(): array
        {
            return array_keys(self::$importedPackages);
        }

        /**
         * @param string $class
         * @return void
         */
        public static function autoloadHandler(string $class): void
        {
            $class = strtolower($class);

            if(!isset(self::$classMap[$class]))
            {
                return;
            }

            if(is_callable(self::$classMap[$class]))
            {
                eval(self::$classMap[$class]());
                return;
            }

            if(is_string(self::$classMap[$class]) && is_file(self::$classMap[$class]))
            {
                require_once self::$classMap[$class];
            }
        }

        /**
         * @return PackageManager
         */
        private static function getPackageManager(): PackageManager
        {
            if(self::$packageManager === null)
            {
                self::$packageManager = new PackageManager();
            }

            return self::$packageManager;
        }

        /**
         * Returns an array of included files both from the php runtime and ncc runtime
         *
         * @return array
         */
        public static function runtimeGetIncludedFiles(): array
        {
            return array_merge(get_included_files(), self::$includedFiles);
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

                $exceptionStack = null;
                foreach ($exceptions as $e)
                {
                    if($exceptionStack === null)
                    {
                        $exceptionStack = $e;
                    }
                    else
                    {
                        $exceptionStack = new Exception($exceptionStack->getMessage(), $exceptionStack->getCode(), $e);
                    }
                }

                throw new RuntimeException('An exception occurred while evaluating the code', 0, $exceptionStack);
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
         * @throws IntegrityException
         */
        private static function acquireFile(string $path, ?string $package=null): string
        {
            $CwdChecked = false; // sanity check to prevent checking the cwd twice

            // Check if the file is absolute
            if(is_file($path))
            {
                Console::outDebug(sprintf('Acquired file "%s" from absolute path', $path));
                return IO::fread($path);
            }

            // Since $package is not null, let's try to acquire the file from the package
            if($package !== null && isset(self::$importedPackages[$package]))
            {
                $basePath = basename($path);

                if(self::$importedPackages[$package] instanceof PackageReader)
                {
                    $acquiredFile = self::$importedPackages[$package]->find($basePath);
                    Console::outDebug(sprintf('Acquired file "%s" from package "%s"', $path, $package));

                    return match (Resolver::componentType($acquiredFile))
                    {
                        PackageDirectory::RESOURCES->value => self::$importedPackages[$package]->getResource(Resolver::componentName($acquiredFile))->getData(),
                        PackageDirectory::COMPONENTS->value => self::$importedPackages[$package]->getComponent(Resolver::componentName($acquiredFile))->getData([ComponentDecodeOptions::AS_FILE->value]),
                        default => throw new IOException(sprintf('Unable to acquire file "%s" from package "%s" because it is not a resource or component', $path, $package)),
                    };
                }

                if(is_dir(self::$importedPackages[$package]))
                {
                    $basePath = basename($path);
                    foreach(IO::scan(self::$importedPackages[$package]) as $file)
                    {
                        if(str_ends_with($file, $basePath))
                        {
                            Console::outDebug(sprintf('Acquired file "%s" from package "%s"', $path, $package));
                            return IO::fread($file);
                        }
                    }
                }
            }

            // If not, let's try the include_path
            foreach(explode(PATH_SEPARATOR, get_include_path()) as $filePath)
            {
                if($filePath === '.' && !$CwdChecked)
                {
                    $CwdChecked = true;
                    $filePath = getcwd();
                }

                if(is_file($filePath . DIRECTORY_SEPARATOR . $path))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from include_path', $path));
                    return IO::fread($filePath . DIRECTORY_SEPARATOR . $path);
                }

                if(is_file($filePath . DIRECTORY_SEPARATOR . basename($path)))
                {
                    Console::outDebug(sprintf('Acquired file "%s" from include_path (using basename)', $path));
                    return IO::fread($filePath . DIRECTORY_SEPARATOR . basename($path));
                }
            }

            // Check the current working directory
            if(!$CwdChecked)
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
            $filePath = $called_script_directory . DIRECTORY_SEPARATOR . $path;
            if(is_file($filePath))
            {
                Console::outDebug(sprintf('Acquired file "%s" from calling script\'s directory', $path));
                return IO::fread($filePath);
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
                $acquiredFile = self::acquireFile($path, $package);
            }
            catch(Exception $e)
            {
                $package ?
                    Console::outWarning(sprintf('Failed to acquire file "%s" from package "%s" at runtime: %s', $path, $package, $e->getMessage())) :
                    Console::outWarning(sprintf('Failed to acquire file "%s" at runtime: %s', $path, $e->getMessage()));

                return;
            }

            $acquiredName = $path;

            if(!is_file($path))
            {
                $acquiredName = hash('crc32', $acquiredFile);
            }

            if(!in_array($acquiredName, self::$includedFiles, true))
            {
                self::$includedFiles[] = sprintf('virtual(%s)', $acquiredName);
            }

            self::extendedEvaluate($acquiredFile);
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
                $acquiredFile = self::acquireFile($path, $package);
            }
            catch(Exception $e)
            {
                $package ?
                    throw new RuntimeException(sprintf('Failed to acquire file "%s" from package "%s" at runtime: %s', $path, $package, $e->getMessage()), $e->getCode(), $e) :
                    throw new RuntimeException(sprintf('Failed to acquire file "%s" at runtime: %s', $path, $e->getMessage()), $e->getCode(), $e);
            }

            $requiredName = $path;

            if(!is_file($path))
            {
                $requiredName = hash('crc32', $acquiredFile);
            }

            if(!in_array($requiredName, self::$includedFiles, true))
            {
                self::$includedFiles[] = sprintf('virtual(%s)', $requiredName);
            }

            self::extendedEvaluate($acquiredFile);
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