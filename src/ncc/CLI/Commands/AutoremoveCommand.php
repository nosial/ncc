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
    use ncc\Runtime;

    class AutoremoveCommand extends AbstractCommandHandler
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

            try
            {
                $packageManager = Runtime::getPackageManager();
                if(!Runtime::isSystemUser() && count(Runtime::getSystemPackageManager()->getEntries()) > 0)
                {
                    Console::warning('Only selecting user packages to remove, elevated permissions are required to remove system packages');
                }

                $removable = $packageManager->getEntries();
                $plan = PackageMaintenance::planAutoremove($removable, Runtime::getPackageEntries());
            }
            catch(Exception $e)
            {
                Console::error('Failed to read the installed packages: ' . $e->getMessage());
                return 1;
            }

            if(count($plan['keep']) > 0)
            {
                Console::out('The following old versions are kept because other packages depend on them:');
                foreach($plan['keep'] as $package => $reason)
                {
                    Console::out(sprintf(' - %s (%s)', $package, $reason));
                }
            }

            if(count($plan['remove']) === 0)
            {
                Console::out('No old package versions to remove.');
                return 0;
            }

            $latest = PackageMaintenance::latestEntries($removable);
            Console::out(sprintf('The following %d old package version(s) will be uninstalled:', count($plan['remove'])));
            foreach($plan['remove'] as $entry)
            {
                Console::out(sprintf(' - %s (%s is installed)', $entry, $latest[$entry->getPackage()]->getVersion()));
            }

            if(!$autoConfirm)
            {
                $confirmation = Console::prompt(sprintf('Proceed with uninstalling %d package version(s)? (y/N): ', count($plan['remove'])));
                if(!in_array(strtolower(trim($confirmation)), ['y', 'yes'], true))
                {
                    Console::out('Autoremove cancelled.');
                    return 0;
                }
            }

            $removed = 0;
            $failed = [];
            foreach($plan['remove'] as $entry)
            {
                try
                {
                    $removed += count($packageManager->uninstall($entry->getPackage(), $entry->getVersion()));
                }
                catch(Exception $e)
                {
                    Console::error(sprintf('Failed to uninstall %s: %s', $entry, $e->getMessage()));
                    $failed[] = (string)$entry;
                }
            }

            Console::out(sprintf('Uninstalled %d old package version(s).', $removed));
            if(count($failed) > 0)
            {
                Console::error(sprintf('Failed to uninstall %d package version(s): %s', count($failed), implode(', ', $failed)));
                return 1;
            }

            return 0;
        }

        /**
         * Prints out the help menu for the autoremove command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc autoremove [options]' . PHP_EOL);
            Console::out('Uninstalls the old versions of installed packages, keeping the latest version');
            Console::out('of each package. Old versions that other installed packages depend on are kept.' . PHP_EOL);
            Console::out('The versions to uninstall are listed and require confirmation unless --yes is');
            Console::out('specified.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --yes, -y         Automatically confirm all prompts');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc autoremove');
            Console::out('  ncc autoremove --yes');
        }
    }
