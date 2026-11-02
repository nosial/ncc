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
    use ncc\CLI\Commands\InstallCommand;
    use ncc\Objects\Package\PackageCacheData;
    use ncc\Objects\Project;
    use ncc\Runtime;
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use ReflectionClass;
    use ReflectionMethod;

    class OptimizedInstallTest extends TestCase
    {
        private const PACKAGE = 'com.example.optimized_install_test';
        private const ASSEMBLY = 'OptimizedInstallTest';

        private static string $workDirectory;
        private static string $builtPackage;
        private string $dataDirectory;

        /**
         * Builds a small package once for all tests
         */
        public static function setUpBeforeClass(): void
        {
            self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_optimized_install_test_' . uniqid();
            $sourcePath = self::$workDirectory . DIRECTORY_SEPARATOR . 'src';
            mkdir($sourcePath . DIRECTORY_SEPARATOR . 'Sub', 0777, true);

            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'OptimizedInstallTestRoot.php', '<?php class OptimizedInstallTestRoot { public static function data(): string { return file_get_contents(__DIR__ . "/Sub/data.txt"); } }');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'OptimizedInstallTestChild.php', '<?php class OptimizedInstallTestChild {}');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'data.txt', 'hello');

            $projectFile = self::$workDirectory . DIRECTORY_SEPARATOR . 'project.yml';
            Project::createNew(self::ASSEMBLY, self::PACKAGE)->save($projectFile);
            self::$builtPackage = Project::compilerFromFile($projectFile, 'release')->build();
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
         * Installs the test package into the temporary data directory and returns the installed package path
         */
        private function install(PackageManager $packageManager, bool $optimized): string
        {
            $packageManager->install(new PackageReader(self::$builtPackage), [
                'reinstall' => true,
                'no-symlink' => true,
                'optimized' => $optimized
            ]);

            $installedPath = $packageManager->getPackagePath(self::PACKAGE);
            $this->assertNotNull($installedPath);
            return $installedPath;
        }

        public function testOptimizedInstallExtractsPackage(): void
        {
            $installedPath = $this->install(new PackageManager($this->dataDirectory), true);
            $optimizedPath = PackageManager::getOptimizedInstallationPath($installedPath);

            $this->assertDirectoryExists($optimizedPath);
            $this->assertFileExists($optimizedPath . '/' . self::ASSEMBLY . '/OptimizedInstallTestRoot.php');
            $this->assertFileExists($optimizedPath . '/' . self::ASSEMBLY . '/Sub/data.txt');
            $this->assertSame(hash_file('sha1', $installedPath), file_get_contents($optimizedPath . '/' . PackageReader::OPTIMIZED_MARKER));
            $this->assertSame($optimizedPath, (new PackageReader($installedPath, true))->getOptimizedPath());
        }

        public function testRegularInstallHasNoOptimizedPath(): void
        {
            $installedPath = $this->install(new PackageManager($this->dataDirectory), false);

            $this->assertDirectoryDoesNotExist(PackageManager::getOptimizedInstallationPath($installedPath));
            $this->assertNull((new PackageReader($installedPath, true))->getOptimizedPath());
        }

        public function testRegularReinstallRemovesOptimizedInstallation(): void
        {
            $packageManager = new PackageManager($this->dataDirectory);
            $installedPath = $this->install($packageManager, true);
            $this->install($packageManager, false);

            $this->assertDirectoryDoesNotExist(PackageManager::getOptimizedInstallationPath($installedPath));
            $this->assertNull((new PackageReader($installedPath, true))->getOptimizedPath());
        }

        public function testUninstallRemovesCacheAndOptimizedInstallation(): void
        {
            $packageManager = new PackageManager($this->dataDirectory);
            $installedPath = $this->install($packageManager, true);
            $packageManager->uninstall(self::PACKAGE);

            $this->assertFileDoesNotExist($installedPath);
            $this->assertFileDoesNotExist($installedPath . '.cache');
            $this->assertDirectoryDoesNotExist(PackageManager::getOptimizedInstallationPath($installedPath));
        }

        public function testOutdatedMarkerFallsBackToStreamWrapper(): void
        {
            $installedPath = $this->install(new PackageManager($this->dataDirectory), true);
            file_put_contents(PackageManager::getOptimizedInstallationPath($installedPath) . '/' . PackageReader::OPTIMIZED_MARKER, 'outdated');

            $this->assertNull((new PackageReader($installedPath, true))->getOptimizedPath());
        }

        public function testImportLoadsClassesFromOptimizedInstallation(): void
        {
            $installedPath = $this->install(new PackageManager($this->dataDirectory), true);
            $optimizedPath = PackageManager::getOptimizedInstallationPath($installedPath);

            Runtime::import($installedPath);

            $this->assertTrue(class_exists('OptimizedInstallTestRoot'));
            $this->assertTrue(class_exists('OptimizedInstallTestChild'));
            $this->assertStringStartsWith($optimizedPath . DIRECTORY_SEPARATOR, (new ReflectionClass('OptimizedInstallTestRoot'))->getFileName());
            $this->assertSame('hello', \OptimizedInstallTestRoot::data());

            // ncc:// access keeps working for optimized packages
            $this->assertSame('hello', file_get_contents('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/Sub/data.txt'));
            $this->assertTrue(is_dir('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/Sub'));
        }

        public function testCacheDataWithoutOptimizedPathStillUnserializes(): void
        {
            $reader = new PackageReader(self::$builtPackage);
            $cacheFile = $this->dataDirectory . DIRECTORY_SEPARATOR . 'test.cache';
            $reader->exportCache($cacheFile);

            // Simulate a cache file written before the optimizedPath property existed
            $serialized = file_get_contents($cacheFile);
            $property = "\0" . PackageCacheData::class . "\0optimizedPath";
            $serialized = str_replace(sprintf('s:%d:"%s";N;', strlen($property), $property), '', $serialized, $count);
            $this->assertSame(1, $count);
            $serialized = preg_replace_callback('/^(O:\d+:"[^"]+":)(\d+)/', fn($m) => $m[1] . ((int)$m[2] - 1), $serialized);

            $cacheData = unserialize($serialized);
            $this->assertInstanceOf(PackageCacheData::class, $cacheData);
            $this->assertNull($cacheData->getOptimizedPath());
        }

        public function testInstallOptionParsing(): void
        {
            $parseOptions = new ReflectionMethod(InstallCommand::class, 'parseOptions');

            $parsed = $parseOptions->invoke(null, ['optimized', 'force-symlink']);
            $this->assertTrue($parsed['optimized']);
            $this->assertTrue($parsed['force-symlink']);
            $this->assertFalse($parsed['skip-repositories']);

            // Parsing already parsed options must keep them unchanged
            $reparsed = $parseOptions->invoke(null, ['reinstall' => true] + $parsed);
            $this->assertTrue($reparsed['reinstall']);
            $this->assertTrue($reparsed['optimized']);
            $this->assertFalse($reparsed['skip-repositories']);
            $this->assertSame($reparsed, $parseOptions->invoke(null, $reparsed));
        }
    }
