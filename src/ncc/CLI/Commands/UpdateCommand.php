<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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
    use ncc\Classes\PackageMaintenance;
    use ncc\Classes\PackageManager;
    use ncc\Classes\PackageReader;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\semver\VersionParser;
    use ncc\Objects\PackageLockEntry;
    use ncc\Objects\PackageSource;
    use ncc\Runtime;

    class UpdateCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $autoConfirm = isset($argv['yes']) || isset($argv['y']);
            $repositoryAuth = InstallCommand::parseRepositoryAuthentication($argv);

            $options = [];
            if($autoConfirm)
            {
                $options[] = 'yes';
            }
            if(isset($argv['trust']))
            {
                $options[] = 'trust';
            }
            if(isset($argv['skip-signature-check']))
            {
                $options[] = 'skip-signature-check';
            }
            if(isset($argv['build-source']) || isset($argv['bs']))
            {
                $options[] = 'build-source';
            }

            try
            {
                $packageManager = Runtime::getPackageManager();
                if(!Runtime::isSystemUser() && count(Runtime::getSystemPackageManager()->getEntries()) > 0)
                {
                    Console::warning('Only checking user packages for updates, elevated permissions are required to update system packages');
                }

                $entries = PackageMaintenance::latestEntries($packageManager->getEntries());
            }
            catch(Exception $e)
            {
                Console::error('Failed to read the installed packages: ' . $e->getMessage());
                return 1;
            }

            if(count($entries) === 0)
            {
                Console::out('No packages are installed.');
                return 0;
            }

            [$updates, $skipped] = Console::withProgressScope(0.0, 1.0, static fn() => self::findUpdates($packageManager, $entries, $repositoryAuth));

            if($skipped > 0)
            {
                Console::out(sprintf('%d package(s) have no update source and were not checked.', $skipped));
            }

            if(count($updates) === 0)
            {
                Console::out('All packages are up to date.');
                return 0;
            }

            Console::out(sprintf('The following %d package(s) will be updated:', count($updates)));
            foreach($updates as $update)
            {
                Console::out(sprintf(' - %s  %s -> %s  (%s)', $update['entry']->getPackage(), $update['entry']->getVersion(), $update['version'], $update['source']));
            }

            if(!$autoConfirm)
            {
                $confirmation = Console::prompt(sprintf('Proceed with updating %d package(s)? (y/N): ', count($updates)));
                if(!in_array(strtolower(trim($confirmation)), ['y', 'yes'], true))
                {
                    Console::out('Update cancelled.');
                    return 0;
                }
            }

            $updated = [];
            $failed = [];
            foreach($updates as $update)
            {
                /** @var PackageLockEntry $entry */
                $entry = $update['entry'];
                $packageOptions = $options;
                if($update['optimized'])
                {
                    $packageOptions[] = 'optimized';
                }

                try
                {
                    $source = clone $update['source'];
                    $source->setVersion($update['version']);
                    Console::withProgressScope(0.0, 1.0, static fn() => InstallCommand::installFromRemote($source, $packageOptions, [], $repositoryAuth));
                    $updated[] = sprintf('%s %s -> %s', $entry->getPackage(), $entry->getVersion(), $update['version']);
                }
                catch(Exception $e)
                {
                    Console::clearInlineProgress();
                    Console::error(sprintf('Failed to update %s: %s', $entry->getPackage(), $e->getMessage()));
                    $failed[] = $entry->getPackage();
                }
            }

            if(count($updated) > 0)
            {
                Console::out(sprintf('Updated %d package(s):', count($updated)));
                foreach($updated as $line)
                {
                    Console::out(' - ' . $line);
                }

                Console::out('The previous versions are still installed, run "ncc autoremove" to uninstall them.');
            }

            if(count($failed) > 0)
            {
                Console::error(sprintf('Failed to update %d package(s): %s', count($failed), implode(', ', $failed)));
                return 1;
            }

            return 0;
        }

        /**
         * Checks the update source of every package for a newer version
         *
         * @param PackageManager $packageManager The package manager the packages are installed in
         * @param array<string, PackageLockEntry> $entries The latest installed entry of each package
         * @param array<string, string> $repositoryAuth The authentication entry names keyed by repository name
         * @return array{0: array<int, array{entry: PackageLockEntry, source: PackageSource, version: string, optimized: bool}>, 1: int}
         *         The available updates and the number of packages without an update source
         */
        private static function findUpdates(PackageManager $packageManager, array $entries, array $repositoryAuth): array
        {
            $updates = [];
            $skipped = 0;
            $total = count($entries);
            $index = 0;

            foreach($entries as $entry)
            {
                Console::inlineProgress($index++, $total, sprintf('Checking for updates (%d/%d) %s', $index, $total, $entry->getPackage()));
                $packagePath = $packageManager->getPackagePathFromEntry($entry);

                try
                {
                    $source = (new PackageReader($packagePath))->getHeader()->getUpdateSource();
                    if($source === null || $source->getRepository() === null)
                    {
                        $skipped++;
                        continue;
                    }

                    if(!Runtime::repositoryExists($source->getRepository()))
                    {
                        Console::warning(sprintf('Cannot check %s for updates, unknown remote repository "%s"', $entry->getPackage(), $source->getRepository()));
                        continue;
                    }

                    $latestVersion = self::getLatestRemoteVersion($source, $repositoryAuth);
                    if($latestVersion === null)
                    {
                        Console::warning(sprintf('Cannot check %s for updates, no version was found in %s', $entry->getPackage(), $source));
                        continue;
                    }

                    if(!PackageMaintenance::isNewerVersion($entry->getVersion(), $latestVersion))
                    {
                        continue;
                    }

                    $updates[] = [
                        'entry' => $entry,
                        'source' => $source,
                        'version' => $latestVersion,
                        'optimized' => IO::exists(PackageManager::getOptimizedInstallationPath($packagePath))
                    ];
                }
                catch(Exception $e)
                {
                    Console::warning(sprintf('Cannot check %s for updates: %s', $entry->getPackage(), $e->getMessage()));
                }
            }

            Console::inlineProgress($total, $total, 'Checked for updates');
            return [$updates, $skipped];
        }

        /**
         * Returns the latest version available from a package's update source without downloading it
         *
         * @param PackageSource $source The update source
         * @param array<string, string> $repositoryAuth The authentication entry names keyed by repository name
         * @return string|null The latest version, or null if the repository reported none
         * @throws OperationException If the repository cannot be queried
         */
        private static function getLatestRemoteVersion(PackageSource $source, array $repositoryAuth): ?string
        {
            $authentication = InstallCommand::resolveAuthentication($source->getRepository(), $repositoryAuth);
            $repository = Runtime::getRepository($source->getRepository())?->createClient($authentication);
            if($repository === null)
            {
                throw new OperationException(sprintf('Unknown remote repository "%s"', $source->getRepository()));
            }

            // Tags that are not versions (eg; "nightly") cannot be installed through a package source
            $versionParser = new VersionParser();
            $latestVersion = null;
            foreach($repository->getAll($source->getOrganization(), $source->getName()) as $remotePackage)
            {
                $version = $remotePackage->getVersion();
                if($version !== null && $versionParser->isValid($version) && ($latestVersion === null || PackageMaintenance::isNewerVersion($latestVersion, $version)))
                {
                    $latestVersion = $version;
                }
            }

            return $latestVersion;
        }

        /**
         * Prints out the help menu for the update command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc update [options]' . PHP_EOL);
            Console::out('Checks the update source of every installed package and installs the latest');
            Console::out('version of the packages that have a newer version available.' . PHP_EOL);
            Console::out('The packages to update are listed and require confirmation unless --yes is');
            Console::out('specified. Previous versions stay installed, use "ncc autoremove" to remove them.');
            Console::out('Packages without an update source are not checked.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --yes, -y         Automatically confirm all prompts');
            Console::out('  --trust           Trust the signing keys of updated packages without prompting');
            Console::out('  --skip-signature-check');
            Console::out('                    Install updates even if their signature check fails');
            Console::out('  --build-source, --bs');
            Console::out('                    Build updates from source instead of using prebuilt packages');
            Console::out('  --<repository>-auth=<entry>');
            Console::out('                    Use an authentication entry for a repository');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc update');
            Console::out('  ncc update --yes');
        }
    }
