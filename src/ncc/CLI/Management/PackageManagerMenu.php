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

    namespace ncc\CLI\Management;

    use Exception;
    use ncc\Classes\PackageReader;
    use ncc\Enums\ConsoleColors;
    use ncc\Enums\Options\InstallPackageOptions;
    use ncc\Enums\RegexPatterns;
    use ncc\Enums\Scopes;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Managers\CredentialManager;
    use ncc\Managers\PackageManager;
    use ncc\Managers\RepositoryManager;
    use ncc\Objects\CliHelpSection;
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
         * @return int
         */
        public static function start($args): int
        {
            if(isset($args['install']))
            {
                try
                {
                    return self::installPackage($args);
                }
                catch (Exception $e)
                {
                    Console::outException(sprintf('Unable to install package: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['list']))
            {
                try
                {
                    return self::listPackages();
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Unable to list packages: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['uninstall']))
            {
                try
                {
                    return self::uninstallPackage($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Unable to uninstall package: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['uninstall-all']))
            {
                try
                {
                    return self::uninstallAllPackages($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Unable to uninstall packages: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            if(isset($args['fix-broken']))
            {
                try
                {
                    return self::fixBrokenPackages($args);
                }
                catch(Exception $e)
                {
                    Console::outException(sprintf('Unable to fix broken packages: %s', $e->getMessage()), $e, 1);
                    return 1;
                }
            }

            return self::displayOptions();
        }

        /**
         * Installs a package from a local file or from a remote repository
         *
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         * @throws Exception
         */
        private static function installPackage(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You cannot install packages in a user scope, please run this command as root', true, 1);
                return 1;
            }

            $package = $args['package'] ?? $args['p'] ?? null;
            $authentication = $args['authentication'] ?? $args['a'] ?? null;
            $authentication_entry = null;
            $auto_yes = isset($args['y']);
            $repository_manager = new RepositoryManager();
            $package_manager = new PackageManager();

            if($package === null)
            {
                Console::outError('No package specified', true, 1);
                return 1;
            }

            $options = [];

            if(isset($args['reinstall']))
            {
                $options[InstallPackageOptions::REINSTALL] = true;
            }

            if(isset($args['prefer-static']) || isset($args['static']))
            {
                $options[InstallPackageOptions::PREFER_STATIC] = true;
            }

            if(isset($args['skip-dependencies']))
            {
                $options[InstallPackageOptions::SKIP_DEPENDENCIES] = true;
            }

            if($authentication !== null)
            {
                $entry = (new CredentialManager())->getVault()?->getEntry($authentication);

                if($entry->isEncrypted())
                {
                    $tries = 0;
                    while(true)
                    {
                        if (!$entry->unlock(Console::passwordInput('Password/Secret: ')))
                        {
                            $tries++;
                            if ($tries >= 3)
                            {
                                Console::outError('Too many failed attempts.', true, 1);
                                return 1;
                            }

                            Console::outError(sprintf('Incorrect password/secret, %d attempts remaining', 3 - $tries));
                        }
                        else
                        {
                            Console::out('Authentication successful.');
                            return 1;
                        }
                    }
                }

                $authentication_entry = $entry->getPassword();
            }

            if(preg_match(RegexPatterns::REMOTE_PACKAGE->value, $package) === 1)
            {
                $package_input = RemotePackageInput::fromString($package);

                if(!$repository_manager->repositoryExists($package_input->getRepository()))
                {
                    Console::outError(sprintf("Unable to find repository '%s'", $package_input->getRepository()), true, 1);
                    return 1;
                }

                Console::out(sprintf('You are about to install a remote package from %s, this will require ncc to fetch and or build the package', $package_input->getRepository()));

                if(!$auto_yes && !Console::getBooleanInput('Do you want to continue?'))
                {
                    Console::out('Installation aborted');
                    return 0;
                }

                $results = $package_manager->install($package_input, $authentication_entry, $options);
                Console::out(sprintf('Installed %d packages', count($results)));
                return 0;
            }

            if($package === '')
            {
                Console::outError('No package specified, use --package or -p to specify a package', true, 1);
                return 1;
            }

            if(!is_file($package))
            {
                Console::outError(sprintf("Unable to find package '%s'", $package), true, 1);
                return 1;
            }

            try
            {
                $package_reader = new PackageReader($package);
            }
            catch(Exception $e)
            {
                Console::outException(sprintf("Unable to read package '%s'", $package), $e, 1);
                return 1;
            }

            if(!isset($args['reinstall']) && $package_manager->getPackageLock()->entryExists($package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()))
            {
                Console::outError(sprintf("Package '%s=%s' is already installed",
                    $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion()), true, 1
                );
                return 1;
            }

            $required_dependencies = $package_manager->checkRequiredDependencies($package_reader);

            foreach($required_dependencies as $dependency)
            {
                if($dependency->getSource() === null)
                {
                    Console::outError(sprintf('The package %s=%s requires the package %s=%s to be installed, but it is not installed and no source was specified',
                        $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion(),
                        $dependency->getName(), $dependency->getVersion()
                    ), true, 1);
                    return 1;
                }

                if(!$repository_manager->repositoryExists(RemotePackageInput::fromString($dependency->getSource())->getRepository()))
                {
                    Console::outError(sprintf('The package %s=%s requires the package %s=%s to be installed, but it is not installed and the repository %s does not exist on your system',
                        $package_reader->getAssembly()->getPackage(), $package_reader->getAssembly()->getVersion(),
                        $dependency->getName(), $dependency->getVersion(),
                        $dependency->getSource()
                    ), true, 1);
                    return 1;
                }
            }

            Console::out('Package installation information:');
            Console::out('   UUID: ' . $package_reader->getAssembly()->getUuid());
            Console::out('   Name: ' . $package_reader->getAssembly()->getName());
            Console::out('   Package: ' . $package_reader->getAssembly()->getPackage());
            Console::out('   Version: ' . $package_reader->getAssembly()->getVersion());

            if($package_reader->getAssembly()->getCompany() !== null)
            {
                Console::out('   Company: ' . $package_reader->getAssembly()->getCompany());
            }

            if($package_reader->getAssembly()->getProduct() !== null)
            {
                Console::out('   Product: ' . $package_reader->getAssembly()->getProduct());
            }

            if($package_reader->getAssembly()->getCopyright() !== null)
            {
                Console::out('   Company: ' . $package_reader->getAssembly()->getCompany());
            }

            if($package_reader->getAssembly()->getTrademark() !== null)
            {
                Console::out('   Trademark: ' . $package_reader->getAssembly()->getTrademark());
            }

            if($package_reader->getAssembly()->getDescription() !== null)
            {
                Console::out('   Description: ' . $package_reader->getAssembly()->getDescription());
            }

            if(count($required_dependencies) > 0)
            {
                Console::out(PHP_EOL . 'This will also install the following dependencies:');
                Console::out('Note: some dependencies may require additional dependencies which will be installed automatically');

                foreach($required_dependencies as $dependency)
                {
                    Console::out(sprintf('   %s=%s from %s',
                        $dependency->getName(),
                        $dependency->getVersion(),
                        RemotePackageInput::fromString($dependency->getSource())->getRepository()
                    ));
                }
            }

            Console::out((string)null);
            if(!$auto_yes && !Console::getBooleanInput('Do you want to continue?'))
            {
                return 0;
            }

            Console::out(sprintf('Installed %d packages', count($package_manager->install($package_reader, $authentication_entry, $options))));
            return 0;
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
                'corner' => Console::formatColor(' └─', ConsoleColors::LIGHT_RED),
                'line' => Console::formatColor(' │ ', ConsoleColors::LIGHT_RED),
                'cross' => Console::formatColor(' ├─', ConsoleColors::LIGHT_RED),
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
         * Prints a list of all installed packages
         *
         * @return int
         */
        private static function listPackages(): int
        {
            $packages = (new PackageManager())->getInstalledPackages();

            foreach($packages as $package)
            {
                Console::out(sprintf('   %s', $package));
            }

            Console::out(sprintf('Total: %d packages', count($packages)));
            return 0;
        }

        /**
         * Uninstalls a package from the system
         *
         * @param $args
         * @return int
         * @throws IOException
         * @throws OperationException
         */
        private static function uninstallPackage($args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You cannot uninstall packages in a user scope, please run this command as root', true, 1);
                return 1;
            }

            $package = $args['package'] ?? $args['p'] ?? null;
            $version = $args['version'] ?? $args['v'] ?? null;

            if($package === null)
            {
                Console::outError('No package specified', true, 1);
                return 1;
            }

            $results = (new PackageManager())->uninstall($package, $version);
            Console::out(sprintf('Uninstalled %d packages', count($results)));

            return 0;
        }

        /**
         * Uninstall all packages from the system
         *
         * @param array $args
         * @return int
         * @throws IOException
         * @throws OperationException
         */
        private static function uninstallAllPackages(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You cannot uninstall all packages in a user scope, please run this command as root', true, 1);
                return 1;
            }

            $auto_yes = isset($args['y']);
            $package_manager = new PackageManager();

            if(count($package_manager->getInstalledPackages()) === 0)
            {
                Console::out('No packages installed');
                return 0;
            }

            if(!$auto_yes && !Console::getBooleanInput('Do you want to continue?'))
            {
                return 0;
            }

            Console::out(sprintf('Uninstalled %d packages', count($package_manager->uninstallAll())));
            return 0;
        }

        /**
         * Attempts to fix broken packages by installing missing dependencies
         *
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        private static function fixBrokenPackages(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You cannot fix broken packages in a user scope, please run this command as root', true, 1);
                return 1;
            }

            $package_manager = new PackageManager();
            $missing_dependencies = $package_manager->getMissingPackages();
            $broken_packages = $package_manager->getBrokenPackages();
            $auto_yes = isset($args['y']);

            if(count($missing_dependencies) === 0 && count($broken_packages) === 0)
            {
                Console::out('No broken packages found');
                return 0;
            }

            if(count($missing_dependencies) > 0)
            {
                Console::out('The following packages that are required by other packages are missing:');
                $unfixable_count = 0;
                foreach($missing_dependencies as $package => $source)
                {
                    if($source === null)
                    {
                        ++$unfixable_count;
                        continue;
                    }

                    Console::out(sprintf('   %s', $package));
                }

                if($unfixable_count > 0)
                {
                    Console::out('The following packages packages cannot be fixed because they are missing and no source was specified:');
                    foreach($missing_dependencies as $package => $source)
                    {
                        if($source !== null)
                        {
                            continue;
                        }

                        Console::out(sprintf('   %s', $package));
                    }
                }
            }

            if(count($broken_packages) > 0)
            {
                Console::out('The following packages are broken and should be removed:');
                foreach($broken_packages as $package)
                {
                    Console::out(sprintf('   %s', $package));
                }
            }

            if(!$auto_yes && !Console::getBooleanInput('Do you want attempt to fix these packages?'))
            {
                return 0;
            }

            foreach($broken_packages as $package)
            {
                Console::out(sprintf('Removing broken package %s', $package));
                $parsed = explode('=', $package, 2);

                if(count($parsed) === 1)
                {
                    Console::out(sprintf('Uninstalling all versions of %s, removed %s packages', $package, count($package_manager->uninstall($parsed[0]))));
                }
                else
                {
                    Console::out(sprintf('Uninstalling %s, removed %s packages', $package, count($package_manager->uninstall($parsed[0], $parsed[1]))));
                }
            }

            foreach($missing_dependencies as $package => $source)
            {
                if($source === null)
                {
                    continue;
                }

                Console::out(sprintf('Fixing missing dependency %s', $package));
                Console::out(sprintf('Installed %d packages', count($package_manager->install($source))));
            }

            return 0;
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['list'], 'Lists all installed packages on the system'),
                new CliHelpSection(['install', '--package', '-p'], 'Installs a specified ncc package'),
                new CliHelpSection(['install', '--package', '-p', '--version', '-v'], 'Installs a specified ncc package version'),
                new CliHelpSection(['install', '-p', '--skip-dependencies'], 'Installs a specified ncc package but skips the installation of dependencies'),
                new CliHelpSection(['install', '-p', '--reinstall'], 'Installs a specified ncc package, reinstall if already installed'),
                new CliHelpSection(['install', '--prefer-static', '--static'], 'Installs a static version of the package from the remote repository if available'),
                new CliHelpSection(['uninstall', '--package', '-p'], 'Uninstalls a specified ncc package'),
                new CliHelpSection(['uninstall', '--package', '-p', '--version', '-v'], 'Uninstalls a specified ncc package version'),
                new CliHelpSection(['uninstall-all'], 'Uninstalls all packages'),
                new CliHelpSection(['fix-broken'], 'Attempts to fix broken packages by installing missing dependencies'),
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
            Console::out('   ncc install --package=symfony/console=5.2.0@packagist');
            Console::out('   ncc install --package=symfony/console@packagist -v=5.2.0');

            return 0;
        }
    }