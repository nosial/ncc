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

use ncc\Objects\PackageLockEntry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackageMaintenanceTest extends TestCase
{
    /**
     * Creates a lock entry, dependencies are given as [package, version constraint] pairs
     *
     * @param array<int, array{0: string, 1: string}> $dependencies
     */
    private static function entry(string $package, string $version, array $dependencies = []): PackageLockEntry
    {
        return new PackageLockEntry([
            'package' => $package,
            'version' => $version,
            'dependencies' => array_map(static fn(array $dependency) => ['package' => $dependency[0], 'version' => $dependency[1]], $dependencies),
        ]);
    }

    /**
     * @param PackageLockEntry[] $entries
     * @return string[]
     */
    private static function names(array $entries): array
    {
        return array_map('strval', array_values($entries));
    }

    #[DataProvider('versionProvider')]
    public function testIsNewerVersion(string $installed, string $candidate, bool $expected): void
    {
        $this->assertSame($expected, PackageMaintenance::isNewerVersion($installed, $candidate));
    }

    public static function versionProvider(): array
    {
        return [
            'newer patch' => ['1.0.0', '1.0.1', true],
            'newer major' => ['1.9.9', '2.0.0', true],
            'equal' => ['1.2.0', '1.2.0', false],
            'older' => ['2.0.0', '1.9.0', false],
            'v prefix candidate' => ['1.0.0', 'v1.2.0', true],
            'v prefix installed' => ['v1.2.0', '1.2.0', false],
            'short form is equal' => ['1.0', '1.0.0', false],
            'stable is newer than prerelease' => ['1.2.0-beta', '1.2.0', true],
            'prerelease is older than stable' => ['1.2.0', '1.2.0-beta', false],
            'unparsable falls back to version_compare' => ['release-7', 'release-8', true],
            'unparsable older falls back to version_compare' => ['release-8', 'release-7', false],
        ];
    }

    public function testLatestEntriesKeepsHighestVersionOfEachPackage(): void
    {
        $latest = PackageMaintenance::latestEntries([
            self::entry('lib.b', '1.0.0'),
            self::entry('lib.a', '1.10.0'),
            self::entry('lib.a', '1.9.0'),
            self::entry('lib.a', '1.2.0'),
        ]);

        $this->assertSame(['lib.a', 'lib.b'], array_keys($latest));
        $this->assertSame('1.10.0', $latest['lib.a']->getVersion());
        $this->assertSame('1.0.0', $latest['lib.b']->getVersion());
    }

    public function testAutoremoveSingleVersionIsNoop(): void
    {
        $entries = [self::entry('lib.a', '1.0.0'), self::entry('lib.b', '2.0.0')];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame([], $plan['remove']);
        $this->assertSame([], $plan['keep']);
    }

    public function testAutoremoveRemovesOldVersionsAndKeepsLatest(): void
    {
        $entries = [self::entry('lib.a', '1.0.0'), self::entry('lib.a', '1.1.0'), self::entry('lib.a', '2.0.0')];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame(['lib.a=1.0.0', 'lib.a=1.1.0'], self::names($plan['remove']));
        $this->assertSame([], $plan['keep']);
    }

    public function testAutoremoveKeepsVersionRequiredByDependent(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '1.5.0'),
            self::entry('lib.a', '2.0.0'),
            self::entry('app', '1.0.0', [['lib.a', '^1.0']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        // The highest version satisfying ^1.0 is kept, the lower one goes
        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
        $this->assertSame(['lib.a=1.5.0' => 'required by app=1.0.0 (^1.0)'], $plan['keep']);
    }

    public function testAutoremoveRemovesOldVersionWhenLatestSatisfiesDependent(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '1.5.0'),
            self::entry('app', '1.0.0', [['lib.a', '^1.0']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
        $this->assertSame([], $plan['keep']);
    }

    public function testAutoremoveTreatsLatestConstraintAsSatisfiedByLatest(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '2.0.0'),
            self::entry('app', '1.0.0', [['lib.a', 'latest']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
    }

    public function testAutoremoveKeepsDependenciesOfKeptVersions(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '2.0.0'),
            self::entry('lib.b', '1.0.0', [['lib.a', '^1.0']]),
            self::entry('lib.b', '2.0.0', [['lib.a', '^2.0']]),
            self::entry('app', '1.0.0', [['lib.b', '1.0.0']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame([], $plan['remove']);
        $this->assertSame([
            'lib.a=1.0.0' => 'required by lib.b=1.0.0 (^1.0)',
            'lib.b=1.0.0' => 'required by app=1.0.0 (1.0.0)',
        ], $plan['keep']);
    }

    public function testAutoremoveIgnoresDependenciesOfRemovedVersions(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '2.0.0'),
            self::entry('app', '1.0.0', [['lib.a', '^1.0']]),
            self::entry('app', '2.0.0', [['lib.a', '^2.0']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        // Only the old app version needed lib.a 1.x, both go together
        $this->assertSame(['app=1.0.0', 'lib.a=1.0.0'], self::names($plan['remove']));
        $this->assertSame([], $plan['keep']);
    }

    public function testAutoremoveNeverRemovesEntriesOutsideRemovable(): void
    {
        $user = [self::entry('lib.a', '1.0.0'), self::entry('lib.a', '1.1.0')];
        $system = [self::entry('lib.b', '1.0.0'), self::entry('lib.b', '2.0.0')];
        $plan = PackageMaintenance::planAutoremove($user, array_merge($system, $user));

        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
    }

    public function testAutoremoveKeepsLatestRemovableVersionWhenOtherManagerIsNewer(): void
    {
        $user = [self::entry('lib.a', '1.0.0'), self::entry('lib.a', '1.1.0')];
        $system = [self::entry('lib.a', '2.0.0')];
        $plan = PackageMaintenance::planAutoremove($user, array_merge($system, $user));

        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
    }

    public function testAutoremoveKeepsVersionRequiredByEntryOutsideRemovable(): void
    {
        $user = [self::entry('lib.a', '1.0.0'), self::entry('lib.a', '2.0.0')];
        $system = [self::entry('app', '1.0.0', [['lib.a', '1.0.0']])];
        $plan = PackageMaintenance::planAutoremove($user, array_merge($system, $user));

        $this->assertSame([], $plan['remove']);
        $this->assertSame(['lib.a=1.0.0' => 'required by app=1.0.0 (1.0.0)'], $plan['keep']);
    }

    public function testAutoremoveIgnoresUnsatisfiableDependency(): void
    {
        $entries = [
            self::entry('lib.a', '1.0.0'),
            self::entry('lib.a', '2.0.0'),
            self::entry('app', '1.0.0', [['lib.a', '^3.0']]),
        ];
        $plan = PackageMaintenance::planAutoremove($entries, $entries);

        $this->assertSame(['lib.a=1.0.0'], self::names($plan['remove']));
    }
}
