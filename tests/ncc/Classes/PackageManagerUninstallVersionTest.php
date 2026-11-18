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

use FilesystemIterator;
use ncc\Objects\Project;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PackageManagerUninstallVersionTest extends TestCase
{
    private const PACKAGE = 'com.example.uninstall_version_test';
    private const ASSEMBLY = 'UninstallVersionTest';

    private static string $workDirectory;
    /** @var array<string, string> The built package files keyed by version */
    private static array $builtPackages = [];
    private string $dataDirectory;

    /**
     * Builds the test package once per version
     */
    public static function setUpBeforeClass(): void
    {
        self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_uninstall_version_test_' . uniqid();
        $sourcePath = self::$workDirectory . DIRECTORY_SEPARATOR . 'src';
        mkdir($sourcePath, 0777, true);
        file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'UninstallVersionTestClass.php', '<?php class UninstallVersionTestClass {}');

        foreach(['1.0.0', '2.0.0'] as $version)
        {
            $project = Project::createNew(self::ASSEMBLY, self::PACKAGE);
            $project->getAssembly()->setVersion($version);

            $projectFile = self::$workDirectory . DIRECTORY_SEPARATOR . 'project.yml';
            $project->save($projectFile);

            // Every build writes the same output path, keep a copy per version
            $builtPackage = Project::compilerFromFile($projectFile, 'release')->build();
            $copy = self::$workDirectory . DIRECTORY_SEPARATOR . self::PACKAGE . '-' . $version . '.ncc';
            copy($builtPackage, $copy);
            self::$builtPackages[$version] = $copy;
        }
    }

    public static function tearDownAfterClass(): void
    {
        if(isset(self::$workDirectory))
        {
            self::recursiveDelete(self::$workDirectory);
        }
    }

    protected function setUp(): void
    {
        $this->dataDirectory = self::$workDirectory . DIRECTORY_SEPARATOR . 'data_' . uniqid();
        mkdir($this->dataDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        self::recursiveDelete($this->dataDirectory);
    }

    private static function recursiveDelete(string $directory): void
    {
        if(!is_dir($directory))
        {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach($iterator as $file)
        {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }

    /**
     * Installs both versions of the test package into a temporary package manager
     */
    private function installBothVersions(): PackageManager
    {
        $packageManager = new PackageManager($this->dataDirectory);
        foreach(self::$builtPackages as $builtPackage)
        {
            $packageManager->install(new PackageReader($builtPackage), ['reinstall' => true, 'no-symlink' => true]);
        }

        $this->assertCount(2, $packageManager->getAllVersions(self::PACKAGE));
        return $packageManager;
    }

    public function testUninstallingOldVersionKeepsNewerVersion(): void
    {
        $packageManager = $this->installBothVersions();
        $newerPath = $packageManager->getPackagePath(self::PACKAGE, '2.0.0');
        $this->assertNotNull($newerPath);

        $removed = $packageManager->uninstall(self::PACKAGE, '1.0.0');

        $this->assertSame([self::PACKAGE . '=1.0.0'], array_map('strval', $removed));
        $this->assertFalse($packageManager->entryExists(self::PACKAGE, '1.0.0'));
        $this->assertTrue($packageManager->entryExists(self::PACKAGE, '2.0.0'));
        $this->assertSame('2.0.0', $packageManager->getLatestVersion(self::PACKAGE)?->getVersion());
        $this->assertFileExists($newerPath);
    }

    public function testAutoremovePlanUninstallsOnlyOldVersion(): void
    {
        $packageManager = $this->installBothVersions();
        $plan = PackageMaintenance::planAutoremove($packageManager->getEntries(), $packageManager->getEntries());

        $this->assertSame([self::PACKAGE . '=1.0.0'], array_map('strval', $plan['remove']));

        foreach($plan['remove'] as $entry)
        {
            $packageManager->uninstall($entry->getPackage(), $entry->getVersion());
        }

        $this->assertSame([self::PACKAGE . '=2.0.0'], array_map('strval', $packageManager->getEntries()));
    }
}
