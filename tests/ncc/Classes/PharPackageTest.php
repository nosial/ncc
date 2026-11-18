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
    use ncc\Runtime;
    use Phar;
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    /**
     * Packages bundling a .phar archive (like Composer tools such as PHPStan) must be loaded from the filesystem because
     * PHP's Phar extension cannot open archives through the ncc:// stream wrapper
     */
    class PharPackageTest extends TestCase
    {
        private const PACKAGE = 'com.example.phar_package_test';
        private const ASSEMBLY = 'PharPackageTest';

        private static string $workDirectory;
        private static string $builtPackage;
        private string $dataDirectory;

        public static function setUpBeforeClass(): void
        {
            self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_phar_package_test_' . uniqid();
            $sourcePath = self::$workDirectory . DIRECTORY_SEPARATOR . 'src';
            mkdir($sourcePath, 0777, true);

            if(ini_get('phar.readonly'))
            {
                return;
            }

            // A bundled archive and a class loading it relative to __DIR__, the way PHPStan's bin and bootstrap do
            $phar = new Phar($sourcePath . DIRECTORY_SEPARATOR . 'tool.phar', 0, 'ncc_phar_package_test_tool.phar');
            $phar->addFromString('run.php', '<?php return "from-phar";');
            $phar->setStub('<?php __HALT_COMPILER();');
            unset($phar);

            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'PharPackageTestTool.php', '<?php class PharPackageTestTool { public static function run(): string { Phar::loadPhar(__DIR__ . "/tool.phar", "ncc_phar_package_test_tool.phar"); return require "phar://ncc_phar_package_test_tool.phar/run.php"; } }');

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
            if(!isset(self::$builtPackage))
            {
                $this->markTestSkipped('phar.readonly is enabled, the test archive cannot be created');
            }

            $this->dataDirectory = self::$workDirectory . DIRECTORY_SEPARATOR . 'data_' . uniqid();
            mkdir($this->dataDirectory, 0777, true);
        }

        protected function tearDown(): void
        {
            if(isset($this->dataDirectory))
            {
                self::recursiveDelete($this->dataDirectory);
            }
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
         * Returns the per-user directory packages requiring the filesystem are extracted to at runtime
         */
        private static function getRuntimeExtractionDirectory(): string
        {
            $user = function_exists('posix_geteuid') ? (string)posix_geteuid() : preg_replace('/[^A-Za-z0-9_.-]/', '_', get_current_user());
            return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc-extract-' . $user;
        }

        public function testPackageWithPharRequiresFilesystem(): void
        {
            $this->assertTrue((new PackageReader(self::$builtPackage))->requiresFilesystem());
        }

        public function testInstallWithoutOptimizedOptionCreatesOptimizedInstallation(): void
        {
            $packageManager = new PackageManager($this->dataDirectory);
            $packageManager->install(new PackageReader(self::$builtPackage), [
                'reinstall' => true,
                'no-symlink' => true
            ]);

            $installedPath = $packageManager->getPackagePath(self::PACKAGE);
            $this->assertNotNull($installedPath);

            $optimizedPath = PackageManager::getOptimizedInstallationPath($installedPath);
            $this->assertDirectoryExists($optimizedPath);
            $this->assertFileExists($optimizedPath . '/' . self::ASSEMBLY . '/tool.phar');
            $this->assertSame($optimizedPath, (new PackageReader($installedPath, true))->getOptimizedPath());
        }

        public function testUninstalledPackageIsExtractedAtRuntime(): void
        {
            $packagePath = $this->dataDirectory . DIRECTORY_SEPARATOR . 'package.ncc';
            copy(self::$builtPackage, $packagePath);
            $extractionPath = self::getRuntimeExtractionDirectory() . DIRECTORY_SEPARATOR . hash_file('sha1', $packagePath);

            try
            {
                $optimizedPath = (new PackageReader($packagePath))->getOptimizedPath();
                $this->assertSame($extractionPath, $optimizedPath);
                $this->assertFileExists($extractionPath . '/' . self::ASSEMBLY . '/tool.phar');
                $this->assertSame(hash_file('sha1', $packagePath), file_get_contents($extractionPath . '/' . PackageReader::OPTIMIZED_MARKER));

                // An up-to-date extraction is reused
                touch($extractionPath . '/reuse-check');
                $this->assertSame($extractionPath, (new PackageReader($packagePath))->getOptimizedPath());
                $this->assertFileExists($extractionPath . '/reuse-check');

                // The class loads the archive relative to its real __DIR__
                Runtime::import($packagePath);
                $this->assertSame('from-phar', \PharPackageTestTool::run());
            }
            finally
            {
                self::recursiveDelete($extractionPath);
            }
        }

        public function testDisableOptimizedSkipsRuntimeExtraction(): void
        {
            putenv('NCC_DISABLE_OPTIMIZED=1');

            try
            {
                $this->assertNull((new PackageReader(self::$builtPackage))->getOptimizedPath());
            }
            finally
            {
                putenv('NCC_DISABLE_OPTIMIZED');
            }
        }
    }
