<?php

    namespace ncc\CLI;

    use Exception;
    use ncc\Abstracts\ConsoleColors;
    use ncc\Abstracts\RemoteSource;
    use ncc\Abstracts\Scopes;
    use ncc\Classes\ComposerExtension\ComposerSource;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\PackageLockException;
    use ncc\Exceptions\VersionNotFoundException;
    use ncc\Managers\PackageManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Objects\Package;
    use ncc\Objects\RemotePackageInput;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;

    class PackageManagerMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return void
         */
        public static function start($args): void
        {
            if(isset($args['install']))
            {
                try
                {
                    self::installPackage($args);
                    return;
                }
                catch (Exception $e)
                {
                    Console::outException('Installation Failed', $e, 1);
                    return;
                }
            }

            if(isset($args['uninstall']))
            {
                try
                {
                    self::uninstallPackage($args);
                    return;
                }
                catch (Exception $e)
                {
                    Console::outException('Uninstallation Failed', $e, 1);
                    return;
                }
            }

            if(isset($args['uninstall-all']))
            {
                try
                {
                    self::uninstallAllPackages($args);
                    return;
                }
                catch (Exception $e)
                {
                    Console::outException('Uninstallation Failed', $e, 1);
                    return;
                }
            }

            if(isset($args['list']))
            {
                try
                {
                    self::getInstallPackages($args);
                    return;
                }
                catch(Exception $e)
                {
                    Console::outException('List Failed', $e, 1);
                    return;
                }
            }

            self::displayOptions();
            exit(0);
        }

        /**
         * Prints an ascii tree of an array
         *
         * @param $data
         * @param string $prefix
         * @return void
         */
        private static function printTree($data, string $prefix=''): void
        {
            $symbols = [
                'corner' => Console::formatColor(' └─', ConsoleColors::LightRed),
                'line' => Console::formatColor(' │ ', ConsoleColors::LightRed),
                'cross' => Console::formatColor(' ├─', ConsoleColors::LightRed),
            ];

            $keys = array_keys($data);
            $lastKey = end($keys);
            foreach ($data as $key => $value)
            {
                $isLast = $key === $lastKey;
                Console::out($prefix . ($isLast ? $symbols['corner'] : $symbols['cross']) . $key);
                if (is_array($value))
                {
                    self::printTree($value, $prefix . ($isLast ? '   ' : $symbols['line']));
                }
            }
        }

        /**
         * Displays all installed packages
         *
         * @param $args
         * @return void
         */
        private static function getInstallPackages($args): void
        {
            $package_manager = new PackageManager();

            try
            {
                $installed_packages = $package_manager->getInstalledPackages();
            }
            catch (Exception $e)
            {
                Console::outException('Failed to get installed packages', $e, 1);
                return;
            }

            if(isset($args['tree']))
            {
                self::printTree($package_manager->getPackageTree());
            }
            else
            {
                foreach($installed_packages as $package => $versions)
                {
                    if(count($versions) == 0)
                    {
                        continue;
                    }

                    foreach($versions as $version)
                    {
                        try
                        {
                            $package_version = $package_manager->getPackageVersion($package, $version);
                            if($package_version == null)
                                continue;
                            Console::out(sprintf('%s=%s (%s)',
                                Console::formatColor($package, ConsoleColors::LightGreen),
                                Console::formatColor($version, ConsoleColors::LightMagenta),
                                $package_manager->getPackageVersion($package, $version)->Compiler->Extension
                            ));
                        }
                        catch(Exception $e)
                        {
                            unset($e);
                            Console::out(sprintf('%s=%s',
                                Console::formatColor($package, ConsoleColors::LightGreen),
                                Console::formatColor($version, ConsoleColors::LightMagenta)
                            ));
                        }
                    }
                }
            }
        }

        /**
         * @param $args
         * @return void
         * @throws FileNotFoundException
         */
        private static function installPackage($args): void
        {
            $package = ($args['package'] ?? $args['p']);
            $package_manager = new PackageManager();

            if(Resolver::resolveScope() !== Scopes::System)
            {
                Console::outError('Insufficient permissions to install packages', true, 1);
                return;
            }

            $path = $package;
            $parsed_source = new RemotePackageInput($path);
            if($parsed_source->Vendor !== null && $parsed_source->Package !== null)
            {
                if($parsed_source->Source == null)
                {
                    Console::outError('No source specified', true, 1);
                    return;
                }

                switch($parsed_source->Source)
                {
                    case RemoteSource::Composer:
                        try
                        {
                            $path = ComposerSource::fetch($parsed_source);
                            break;
                        }
                        catch(Exception $e)
                        {
                            Console::outException(sprintf('Failed to fetch package %s', $package), $e, 1);
                            return;
                        }

                    default:
                        Console::outError('Cannot install package from source: ' . $parsed_source->Source, true, 1);
                        return;
                }
            }

            if(!file_exists($path) || !is_file($path) || !is_readable($path))
                throw new FileNotFoundException('The specified file \'' . $path .' \' does not exist or is not readable.');

            $user_confirmation = false;
            if(isset($args['y']) || isset($args['Y']))
            {
                $user_confirmation = (bool)($args['y'] ?? $args['Y']);
            }

            try
            {
                $package = Package::load($path);
            }
            catch(Exception $e)
            {
                Console::outException('Error while loading package', $e, 1);
                return;
            }

            Console::out('Package installation details' . PHP_EOL);
            if(!is_null($package->Assembly->UUID))
                Console::out('  UUID: ' . Console::formatColor($package->Assembly->UUID, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Package))
                Console::out('  Package: ' . Console::formatColor($package->Assembly->Package, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Name))
                Console::out('  Name: ' . Console::formatColor($package->Assembly->Name, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Version))
                Console::out('  Version: ' . Console::formatColor($package->Assembly->Version, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Description))
                Console::out('  Description: ' . Console::formatColor($package->Assembly->Description, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Product))
                Console::out('  Product: ' . Console::formatColor($package->Assembly->Product, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Company))
                Console::out('  Company: ' . Console::formatColor($package->Assembly->Company, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Copyright))
                Console::out('  Copyright: ' . Console::formatColor($package->Assembly->Copyright, ConsoleColors::LightGreen));
            if(!is_null($package->Assembly->Trademark))
                Console::out('  Trademark: ' . Console::formatColor($package->Assembly->Trademark, ConsoleColors::LightGreen));
            Console::out((string)null);

            if(count($package->Dependencies) > 0)
            {
                $dependencies = [];
                foreach($package->Dependencies as $dependency)
                {
                    $require_dependency = true;

                    try
                    {
                        $dependency_package = $package_manager->getPackage($dependency->Name);
                    }
                    catch (PackageLockException $e)
                    {
                        unset($e);
                        $dependency_package = null;
                    }

                    if($dependency_package !== null)
                    {
                        try
                        {
                            $dependency_version = $dependency_package->getVersion($dependency->Version);
                        }
                        catch (VersionNotFoundException $e)
                        {
                            unset($e);
                            $dependency_version = null;
                        }

                        if($dependency_version !== null)
                        {
                            $require_dependency = false;
                        }
                    }

                    if($require_dependency)
                    {
                        $dependencies[] = sprintf('  %s %s',
                            Console::formatColor($dependency->Name, ConsoleColors::Green),
                            Console::formatColor($dependency->Version, ConsoleColors::LightMagenta)
                        );
                    }
                }

                Console::out('The following dependencies will be installed:');
                Console::out(sprintf('%s', implode(PHP_EOL, $dependencies)));
            }

            Console::out(sprintf('Extension: %s',
                Console::formatColor($package->Header->CompilerExtension->Extension, ConsoleColors::Green)
            ));

            if($package->Header->CompilerExtension->MaximumVersion !== null)
                Console::out(sprintf('Maximum Version: %s',
                    Console::formatColor($package->Header->CompilerExtension->MaximumVersion, ConsoleColors::LightMagenta)
                ));

            if($package->Header->CompilerExtension->MinimumVersion !== null)
                Console::out(sprintf('Minimum Version: %s',
                    Console::formatColor($package->Header->CompilerExtension->MinimumVersion, ConsoleColors::LightMagenta)
                ));

            if(!$user_confirmation)
                $user_confirmation = Console::getBooleanInput(sprintf('Do you want to install %s', $package->Assembly->Package));

            if($user_confirmation)
            {
                try
                {
                    $package_manager->install($path);
                    Console::out(sprintf('Package %s installed successfully', $package->Assembly->Package));
                }
                catch(Exception $e)
                {
                    Console::outException('Installation Failed', $e, 1);
                }

                return;
            }

            Console::outError('User cancelled installation', true, 1);
        }

        /**
         * Uninstalls a version of a package or all versions of a package
         *
         * @param $args
         * @return void
         * @throws VersionNotFoundException
         */
        private static function uninstallPackage($args): void
        {
            $selected_package = ($args['package'] ?? $args['p']);
            $selected_version = null;
            if(isset($args['v']))
                $selected_version = $args['v'];
            if(isset($args['version']))
                $selected_version = $args['version'];

            $user_confirmation = null;
            // For undefined array key warnings
            if(isset($args['y']) || isset($args['Y']))
                $user_confirmation = (bool)($args['y'] ?? $args['Y']);

            if($selected_package == null)
                Console::outError('Missing argument \'package\'', true, 1);

            $package_manager = new PackageManager();

            try
            {
                $package_entry = $package_manager->getPackage($selected_package);
            }
            catch (PackageLockException $e)
            {
                Console::outException('PackageLock error', $e, 1);
                return;
            }

            $version_entry = null;
            if($version_entry !== null && $package_entry !== null)
                /** @noinspection PhpUnhandledExceptionInspection */
                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $version_entry = $package_entry->getVersion($version_entry, false);

            if($package_entry == null)
            {
                Console::outError(sprintf('Package "%s" is not installed', $selected_package), true, 1);
                return;
            }

            if($version_entry == null && $selected_version !== null)
            {
                Console::outError(sprintf('Package "%s=%s" is not installed', $selected_package, $selected_version), true, 1);
                return;
            }

            if($user_confirmation == null)
            {
                if($selected_version !== null)
                {
                    if(!Console::getBooleanInput(sprintf('Do you want to uninstall %s=%s', $selected_package, $selected_version)))
                    {
                        Console::outError('User cancelled operation', true, 1);
                        return;
                    }
                }
                else
                {
                    if(!Console::getBooleanInput(sprintf('Do you want to uninstall all versions of %s', $selected_package)))
                    {
                        Console::outError('User cancelled operation', true, 1);
                        return;
                    }
                }
            }

            try
            {
                if($selected_version !== null)
                {
                    $package_manager->uninstallPackageVersion($selected_package, $selected_version);
                }
                else
                {
                    $package_manager->uninstallPackage($selected_package);
                }
            }
            catch(Exception $e)
            {
                Console::outException('Uninstallation failed', $e, 1);
                return;
            }
        }

        /**
         * Uninstalls all packages
         *
         * @param $args
         * @return void
         * @throws PackageLockException
         */
        private static function uninstallAllPackages($args): void
        {
            $user_confirmation = null;
            // For undefined array key warnings
            if(isset($args['y']) || isset($args['Y']))
                $user_confirmation = (bool)($args['y'] ?? $args['Y']);

            if($user_confirmation == null)
            {
                if(!Console::getBooleanInput('Do you want to uninstall all packages'))
                {
                    Console::outError('User cancelled operation', true, 1);
                    return;
                }
            }

            $package_manager = new PackageManager();

            foreach($package_manager->getInstalledPackages() as $package => $versions)
            {
                foreach($versions as $version)
                {
                    try
                    {
                        $package_manager->uninstallPackageVersion($package, $version);
                    }
                    catch(Exception $e)
                    {
                        Console::outException('Uninstallation failed', $e, 1);
                        return;
                    }
                }
            }
        }

        /**
         * Displays the main options section
         *
         * @return void
         */
        private static function displayOptions(): void
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['list'], 'Lists all installed packages on the system'),
                new CliHelpSection(['install', '--package', '-p'], 'Installs a specified NCC package'),
                new CliHelpSection(['install', '--package', '-p', '--version', '-v'], 'Installs a specified NCC package version'),
                new CliHelpSection(['uninstall', '--package', '-p'], 'Uninstalls a specified NCC package'),
                new CliHelpSection(['uninstall', '--package', '-p', '--version', '-v'], 'Uninstalls a specified NCC package version'),
                new CliHelpSection(['uninstall-all'], 'Uninstalls all packages'),
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc install {command} [options]');
            Console::out('Options:' . PHP_EOL);
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            Console::out('You can install a package from a local file or from a supported remote repository');
            Console::out('Note that installing from some repositories may require ncc to build the package');
            Console::out('Examples of usage:');
            Console::out('   ncc install --package=build/release/com.example.library.ncc');
            Console::out('   ncc install --package=symfony/console=5.2.0@composer');
            Console::out('   ncc install --package=symfony/console@composer -v=5.2.0');
            Console::out('   ncc install --package=foo/bar:master@gitlab');
            Console::out('   ncc install --package=foo/bar:dev@custom');
        }
    }