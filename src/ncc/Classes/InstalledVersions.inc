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

    use ncc\Libraries\semver\VersionParser as NccVersionParser;
    use ncc\ProjectConverters\ComposerProjectConverter;
    use ncc\Runtime;
    use OutOfBoundsException;
    use Throwable;

    class InstalledVersions
    {
        private const string ROOT_PACKAGE_NAME = '__root__';

        /**
         * Returns the Composer names of the packages known to ncc in this process: the imported packages that were
         * converted from Composer (their dependencies are imported along with them)
         *
         * @return string[] The Composer package names
         */
        public static function getInstalledPackages(): array
        {
            $names = [];
            foreach(Runtime::getImportedPackages() as $packageName => $packageReader)
            {
                $composerName = self::getComposerName($packageName, $packageReader);
                if($composerName !== null)
                {
                    $names[$composerName] = true;
                }
            }

            return array_keys($names);
        }

        /**
         * Returns the installed packages of a type, ncc only knows libraries
         *
         * @param string $type The Composer package type
         * @return string[] The Composer package names
         */
        public static function getInstalledPackagesByType(string $type): array
        {
            return $type === 'library' ? self::getInstalledPackages() : [];
        }

        /**
         * Checks whether a Composer package is available through ncc (imported, or installed in a package manager)
         *
         * @param string $packageName The Composer package name
         * @param bool $includeDevRequirements Ignored, ncc does not distinguish development installs at runtime
         * @return bool True if the package is available
         */
        public static function isInstalled(string $packageName, bool $includeDevRequirements = true): bool
        {
            return self::findVersion($packageName) !== null;
        }

        /**
         * Checks whether the available version of a package satisfies a constraint
         *
         * @param object|null $parser A Composer\Semver\VersionParser, ncc's bundled parser is used if none is usable
         * @param string $packageName The Composer package name
         * @param string|null $constraint The version constraint, null matches any version
         * @return bool True if the available version satisfies the constraint
         */
        public static function satisfies($parser, string $packageName, ?string $constraint): bool
        {
            if(!is_object($parser) || !method_exists($parser, 'parseConstraints'))
            {
                $parser = new NccVersionParser();
            }

            $constraint = $parser->parseConstraints((string)$constraint);
            $provided = $parser->parseConstraints(self::getVersionRanges($packageName));

            return $provided->matches($constraint);
        }

        /**
         * Returns the version ranges a package provides, which for ncc is its normalized version
         *
         * @param string $packageName The Composer package name
         * @return string The version ranges
         * @throws OutOfBoundsException If the package is not available
         */
        public static function getVersionRanges(string $packageName): string
        {
            return (string)self::getVersion($packageName);
        }

        /**
         * Returns the normalized version of a package (eg; 1.2.3.0)
         *
         * @param string $packageName The Composer package name
         * @return string|null The normalized version
         * @throws OutOfBoundsException If the package is not available
         */
        public static function getVersion(string $packageName): ?string
        {
            $version = self::requireVersion($packageName);

            try
            {
                return (new NccVersionParser())->normalize($version);
            }
            catch(Throwable)
            {
                return $version;
            }
        }

        /**
         * Returns the version of a package as ncc knows it (eg; 1.2.3)
         *
         * @param string $packageName The Composer package name
         * @return string|null The version
         * @throws OutOfBoundsException If the package is not available
         */
        public static function getPrettyVersion(string $packageName): ?string
        {
            return self::requireVersion($packageName);
        }

        /**
         * Returns the source reference of a package, which ncc does not track
         *
         * @param string $packageName The Composer package name
         * @return string|null Always null
         * @throws OutOfBoundsException If the package is not available
         */
        public static function getReference(string $packageName): ?string
        {
            self::requireVersion($packageName);
            return null;
        }

        /**
         * Returns where a package's files can be read from: its optimized installation if it has one, otherwise its
         * ncc:// root
         *
         * @param string $packageName The Composer package name
         * @return string|null The install path
         * @throws OutOfBoundsException If the package is not available
         */
        public static function getInstallPath(string $packageName): ?string
        {
            self::requireVersion($packageName);
            $nccPackageName = self::toNccPackageName($packageName);
            $packageReader = Runtime::getImportedPackage($nccPackageName);

            if($packageReader !== null && $packageReader->getAssembly()->getPackage() === $nccPackageName)
            {
                return $packageReader->getOptimizedPath() ?? 'ncc://' . $nccPackageName;
            }

            return 'ncc://' . ($packageReader?->getAssembly()->getPackage() ?? $nccPackageName);
        }

        /**
         * Returns the root package information, ncc has no Composer root package
         *
         * @return array The root package information
         */
        public static function getRootPackage(): array
        {
            return [
                'name' => self::ROOT_PACKAGE_NAME,
                'pretty_version' => '1.0.0+no-version-set',
                'version' => '1.0.0.0',
                'reference' => null,
                'type' => 'library',
                'install_path' => getcwd(),
                'aliases' => [],
                'dev' => false,
            ];
        }

        /**
         * Returns the raw installed data in Composer's format
         *
         * @return array The raw data
         * @deprecated Composer deprecated this method in favour of getAllRawData()
         */
        public static function getRawData(): array
        {
            return self::getAllRawData()[0];
        }

        /**
         * Returns the raw installed data in Composer's format, one dataset for ncc
         *
         * @return array[] The raw datasets
         */
        public static function getAllRawData(): array
        {
            $versions = [];
            foreach(self::getInstalledPackages() as $packageName)
            {
                $versions[$packageName] = [
                    'pretty_version' => self::getPrettyVersion($packageName),
                    'version' => self::getVersion($packageName),
                    'reference' => null,
                    'type' => 'library',
                    'install_path' => self::getInstallPath($packageName),
                    'aliases' => [],
                    'dev_requirement' => false,
                ];
            }

            return [['root' => self::getRootPackage(), 'versions' => $versions]];
        }

        /**
         * Composer uses this to replace the installed data, ncc always reflects its own package managers
         *
         * @param array $data Ignored
         * @return void
         */
        public static function reload($data): void
        {
        }

        /**
         * Returns the version of an available package or throws like Composer does
         *
         * @param string $packageName The Composer package name
         * @return string The version
         * @throws OutOfBoundsException If the package is not available
         */
        private static function requireVersion(string $packageName): string
        {
            return self::findVersion($packageName) ?? throw new OutOfBoundsException('Package "' . $packageName . '" is not installed');
        }

        /**
         * Finds the version of a Composer package: the imported version first (including packages statically linked
         * into an imported package), then the latest version installed in a package manager
         *
         * @param string $packageName The Composer package name
         * @return string|null The version, or null if the package is not available
         */
        private static function findVersion(string $packageName): ?string
        {
            $nccPackageName = self::toNccPackageName($packageName);
            if($nccPackageName === null)
            {
                return null;
            }

            $packageReader = Runtime::getImportedPackage($nccPackageName);
            if($packageReader !== null)
            {
                if($packageReader->getAssembly()->getPackage() === $nccPackageName)
                {
                    return $packageReader->getAssembly()->getVersion();
                }

                // Statically linked into another package, which records the version it embedded
                foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
                {
                    if($reference->getPackage() === $nccPackageName)
                    {
                        return $reference->getVersion();
                    }
                }
            }

            try
            {
                return Runtime::getPackageEntry($nccPackageName)?->getVersion();
            }
            catch(Throwable)
            {
                return null;
            }
        }

        /**
         * Maps a Composer package name to the ncc package name the Composer converter gives it
         *
         * @param string $packageName The Composer package name (eg; symfony/console)
         * @return string|null The ncc package name (eg; com.symfony.console), or null for names without a vendor
         */
        private static function toNccPackageName(string $packageName): ?string
        {
            if(!str_contains($packageName, '/'))
            {
                return null;
            }

            return ComposerProjectConverter::generatePackageName(strtolower($packageName));
        }

        /**
         * Returns the Composer name of an imported package, if it was converted from Composer
         *
         * @param string $packageName The ncc package name it was imported as
         * @param PackageReader $packageReader The reader it was imported with (the parent package if statically linked)
         * @return string|null The Composer name, or null if unknown
         */
        private static function getComposerName(string $packageName, PackageReader $packageReader): ?string
        {
            if($packageReader->getAssembly()->getPackage() === $packageName)
            {
                $composerName = Runtime::getImportedPackageOptions($packageName)[ComposerProjectConverter::COMPOSER_NAME_OPTION] ?? null;
                return is_string($composerName) ? $composerName : null;
            }

            // Statically linked packages only keep their source, which is the Composer name for Packagist dependencies
            foreach($packageReader->getHeader()->getDependencyReferences() as $reference)
            {
                if($reference->getPackage() === $packageName && $reference->getSource() !== null)
                {
                    $name = strtolower($reference->getSource()->getOrganization() . '/' . $reference->getSource()->getName());
                    return str_contains($name, '/') && ComposerProjectConverter::generatePackageName($name) === $packageName ? $name : null;
                }
            }

            return null;
        }
    }
