<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\Validate;
    use ncc\Exceptions\IOException;
    use ncc\Objects\PackageLockEntry;
    use ncc\Runtime;

    class UninstallCommand extends AbstractCommandHandler
    {
        public static function handle(array $argv): int
        {
            $allPackages = $argv['all'] ?? null;
            $package = $argv['package'] ?? $argv['p'] ?? null;
            $version = $argv['version'] ?? $argv['v'] ?? null;
            $autoPrompt = isset($argv['yes']) || isset($argv['y']);

            if($allPackages !== null)
            {
                $selectedEntries = [];
                if(Runtime::isSystemUser())
                {
                    $selectedEntries = Runtime::getPackageEntries();
                }
                else
                {
                    $selectedEntries = Runtime::getUserPackageManager()?->getEntries() ?? [];
                }

                if(count($selectedEntries) === 0)
                {
                    Console::out('No packages found to uninstall.');
                    return 0;
                }

                if(!$autoPrompt)
                {
                    if(!Runtime::isSystemUser())
                    {
                        Console::warning('Only selecting user packages to uninstall, elevated permissions are required to uninstall system packages');
                    }

                    $confirmation = Console::prompt(sprintf('You are about to uninstall %d package(s) that you currently have write-access to, do you want to proceed? (y/N): ', count($selectedEntries)));
                    if(strtolower($confirmation) !== 'y')
                    {
                        Console::out('Uninstallation cancelled.');
                        return 0;
                    }
                }

                /** @var PackageLockEntry $entry */
                foreach($selectedEntries as $entry)
                {
                    Console::out(sprintf(' Uninstalling %s', $entry->getPackage()));
                    try
                    {
                        Runtime::uninstallPackage($entry->getPackage());
                    }
                    catch(Exception $e)
                    {
                        Console::error(sprintf('Failed to unstainll %s: %s', $entry->getPackage(), $e->getMessage()), $e);
                    }
                }

                Console::out('All packages uninstalled successfully');
                return 0;
            }

            if(empty($package))
            {
                Console::error('Package name is required. Use --package or -p to specify the package name or use --all to uninstall all packages');
                return 1;
            }
            elseif(!Validate::packageName($package))
            {
                Console::error(sprintf('Invalid package name "%s". Package names must start with a letter and can contain letters, numbers, dots, underscores, and hyphens.', $package));
                return 1;
            }

            if($version === null)
            {
                Console::warning('Omitting the version will uninstall all versions of the package.');
                // Check if package exists at all (any version)
                if(!Runtime::packageInstalled($package, null))
                {
                    Console::error(sprintf('Package "%s" is not installed.', $package));
                    return 1;
                }
            }
            elseif(!Validate::version($version))
            {
                Console::error(sprintf('Invalid version "%s". Version must follow semantic versioning (e.g., 1.0.0, 2.1.3-beta).', $version));
                return 1;
            }
            else
            {
                // For non-null version, check if it exists (handles 'latest' resolution)
                if(!Runtime::packageInstalled($package, $version))
                {
                    Console::error(sprintf('Package "%s" version "%s" is not installed.', $package, $version));
                    return 1;
                }
            }

            if(!$autoPrompt)
            {
                $confirmation = Console::prompt(sprintf('Are you sure you want to uninstall package "%s" version "%s"? (y/N): ', $package, $version ?? 'all versions'));
                if(strtolower($confirmation) !== 'y')
                {
                    Console::out('Uninstallation cancelled.');
                    return 0;
                }
            }

            // Check if it's a system package - when version is null, check if any version is in system
            if(Runtime::isSystemPackage($package, $version) && !Runtime::isSystemUser())
            {
                Console::error(sprintf('Cannot uninstall system package "%s" version "%s" without elevated permissions. Please run as an administrator or root user.', $package, $version ?? 'all versions'));
                return 1;
            }

            try
            {
                Console::out(sprintf('Uninstalling package "%s" version "%s"...', $package, $version ?? 'all versions'));
                $selectedEntries = Runtime::uninstallPackage($package, $version);
            }
            catch (Exception $e)
            {
                Console::error('Failed to uninstall package: ' . $e->getMessage());
                return 1;
            }

            foreach($selectedEntries as $entry)
            {
                Console::out(sprintf(' Uninstalled %s=%s', $entry->getPackage(), $entry->getVersion()));
            }

            Console::out('Package uninstalled successfully.');
            return 0;
        }

        public static function help(): void
        {
            return;
        }
    }