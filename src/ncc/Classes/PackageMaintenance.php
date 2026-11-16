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

    namespace ncc\Classes;

    use ncc\Libraries\semver\Comparator;
    use ncc\Libraries\semver\Semver;
    use ncc\Libraries\semver\VersionParser;
    use ncc\Objects\Package\DependencyReference;
    use ncc\Objects\PackageLockEntry;
    use Throwable;

    /**
     * The planning logic behind "ncc update" and "ncc autoremove", free of any I/O so it can be tested on its own
     */
    class PackageMaintenance
    {
        /**
         * Returns whether the candidate version is newer than the installed version. Versions are compared as
         * semantic versions (a "v" prefix is accepted), falling back to version_compare() when either version cannot
         * be parsed.
         *
         * @param string $installed The installed version
         * @param string $candidate The version that may replace it
         * @return bool True if the candidate is newer than the installed version
         */
        public static function isNewerVersion(string $installed, string $candidate): bool
        {
            $normalizedInstalled = self::normalize($installed);
            $normalizedCandidate = self::normalize($candidate);

            if($normalizedInstalled !== null && $normalizedCandidate !== null)
            {
                return Comparator::greaterThan($normalizedCandidate, $normalizedInstalled);
            }

            return version_compare(ltrim($candidate, 'vV'), ltrim($installed, 'vV'), '>');
        }

        /**
         * Returns the highest installed version of every package
         *
         * @param PackageLockEntry[] $entries The installed package entries
         * @return array<string, PackageLockEntry> The latest entry of each package, keyed by package name
         */
        public static function latestEntries(array $entries): array
        {
            $latest = [];
            foreach($entries as $entry)
            {
                $current = $latest[$entry->getPackage()] ?? null;
                if($current === null || self::isNewerVersion($current->getVersion(), $entry->getVersion()))
                {
                    $latest[$entry->getPackage()] = $entry;
                }
            }

            ksort($latest);
            return $latest;
        }

        /**
         * Plans which old package versions can be uninstalled. The latest removable version of every package is kept
         * (even when the other package manager has a newer one), older versions are removed unless a package that stays installed depends on a version the latest one does not
         * satisfy, in which case the highest satisfying version is kept (and its own dependencies are honoured too).
         *
         * @param PackageLockEntry[] $removable The entries that may be uninstalled (the writable package manager)
         * @param PackageLockEntry[] $all Every installed entry (system and user), used to find the dependents
         * @return array{remove: PackageLockEntry[], keep: array<string, string>} The entries to uninstall and the old
         *                                                                      versions that are kept with the reason
         */
        public static function planAutoremove(array $removable, array $all): array
        {
            // Every entry, keyed by name=version, the removable ones override the ones from $all
            $entries = [];
            foreach(array_merge($all, $removable) as $entry)
            {
                $entries[(string)$entry] = $entry;
            }

            $removableKeys = [];
            foreach($removable as $entry)
            {
                $removableKeys[(string)$entry] = true;
            }

            $latest = self::latestEntries(array_values($entries));
            // The latest removable version of a package is kept even if the other package manager has a newer one
            $latestRemovable = [];
            foreach(self::latestEntries($removable) as $entry)
            {
                $latestRemovable[(string)$entry] = true;
            }

            // Everything that cannot be removed stays installed, so its dependencies count from the start
            $kept = [];
            foreach($entries as $key => $entry)
            {
                if(!isset($removableKeys[$key]) || isset($latestRemovable[$key]))
                {
                    $kept[$key] = $entry;
                }
            }

            $reasons = [];
            $pending = array_values($kept);
            while(count($pending) > 0)
            {
                $dependent = array_shift($pending);

                /** @var DependencyReference $dependency */
                foreach($dependent->getDependencies() as $dependency)
                {
                    $latestDependency = $latest[$dependency->getPackage()] ?? null;
                    if($latestDependency === null || self::satisfies($latestDependency->getVersion(), $dependency->getVersion()))
                    {
                        // Not installed at all, or the latest version satisfies it
                        continue;
                    }

                    $required = self::highestSatisfying($entries, $dependency);
                    if($required === null)
                    {
                        // No installed version satisfies it, keeping old versions won't help
                        continue;
                    }

                    $requiredKey = (string)$required;
                    if(!isset($kept[$requiredKey]))
                    {
                        $kept[$requiredKey] = $required;
                        $pending[] = $required;
                    }

                    if(isset($removableKeys[$requiredKey]) && !isset($reasons[$requiredKey]))
                    {
                        $reasons[$requiredKey] = sprintf('required by %s (%s)', $dependent, $dependency->getVersion());
                    }
                }
            }

            $remove = [];
            foreach($removable as $entry)
            {
                if(!isset($kept[(string)$entry]))
                {
                    $remove[] = $entry;
                }
            }

            usort($remove, static fn(PackageLockEntry $a, PackageLockEntry $b) => strcmp((string)$a, (string)$b));
            ksort($reasons);

            return ['remove' => $remove, 'keep' => $reasons];
        }

        /**
         * Returns the highest installed version of the dependency's package that satisfies its version constraint
         *
         * @param array<string, PackageLockEntry> $entries Every installed entry
         * @param DependencyReference $dependency The dependency
         * @return PackageLockEntry|null The highest satisfying entry, or null if none satisfies it
         */
        private static function highestSatisfying(array $entries, DependencyReference $dependency): ?PackageLockEntry
        {
            $highest = null;
            foreach($entries as $entry)
            {
                if($entry->getPackage() !== $dependency->getPackage() || !self::satisfies($entry->getVersion(), $dependency->getVersion()))
                {
                    continue;
                }

                if($highest === null || self::isNewerVersion($highest->getVersion(), $entry->getVersion()))
                {
                    $highest = $entry;
                }
            }

            return $highest;
        }

        /**
         * Returns whether a version satisfies a dependency constraint, "latest" and empty constraints are satisfied
         * by any version. A constraint that cannot be parsed only matches the same version.
         *
         * @param string $version The installed version
         * @param string $constraint The version constraint
         * @return bool True if the version satisfies the constraint
         */
        private static function satisfies(string $version, string $constraint): bool
        {
            $constraint = trim($constraint);
            if($constraint === '' || $constraint === '*' || strtolower($constraint) === 'latest')
            {
                return true;
            }

            $normalizedVersion = self::normalize($version);
            if($normalizedVersion !== null)
            {
                try
                {
                    return Semver::satisfies($normalizedVersion, $constraint);
                }
                catch(Throwable)
                {
                    // Fall through to the exact comparison
                }
            }

            $normalizedConstraint = self::normalize($constraint);
            if($normalizedVersion !== null && $normalizedConstraint !== null)
            {
                return $normalizedVersion === $normalizedConstraint;
            }

            return ltrim($version, 'vV') === ltrim($constraint, 'vV');
        }

        /**
         * Normalizes a version, null if it is not a valid version
         *
         * @param string $version The version to normalize
         * @return string|null The normalized version, or null if it cannot be parsed
         */
        private static function normalize(string $version): ?string
        {
            try
            {
                return (new VersionParser())->normalize(trim($version));
            }
            catch(Throwable)
            {
                return null;
            }
        }
    }
