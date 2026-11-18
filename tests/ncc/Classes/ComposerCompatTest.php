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
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    class ComposerCompatTest extends TestCase
    {
        private const PACKAGE = 'com.example.composercompat';

        private string $workDirectory;
        private string $packagePath;

        protected function setUp(): void
        {
            $this->workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_composer_compat_test_' . uniqid();
            mkdir($this->workDirectory, 0777, true);
            $this->workDirectory = realpath($this->workDirectory);

            // The shim only references the package path, the file itself is never read here
            $this->packagePath = $this->workDirectory . DIRECTORY_SEPARATOR . 'package.ncc';
            file_put_contents($this->packagePath, 'not a real package');
        }

        protected function tearDown(): void
        {
            @chmod($this->workDirectory, 0777);
            @unlink($this->getTemporaryShimPath());
            self::recursiveDelete($this->workDirectory);
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

        private function getTemporaryShimPath(): string
        {
            $user = function_exists('posix_geteuid') ? (string)posix_geteuid() : preg_replace('/[^A-Za-z0-9_.-]/', '_', get_current_user());
            return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc-composer-' . $user . DIRECTORY_SEPARATOR . sha1($this->packagePath) . '.php';
        }

        public function testShimLoadsNccAndImportsThePackage(): void
        {
            $content = ComposerCompat::generateAutoloadShim($this->packagePath, self::PACKAGE);

            $this->assertNotNull($content);
            $this->assertStringContainsString(ComposerCompat::AUTOLOAD_SHIM_MARKER, $content);
            $this->assertStringContainsString(var_export(__NCC_DIR__ . DIRECTORY_SEPARATOR . 'ncc.php', true), $content);
            $this->assertStringContainsString('\ncc\Runtime::isImported(' . var_export(self::PACKAGE, true) . ')', $content);
            $this->assertStringContainsString('\ncc\Runtime::import(' . var_export($this->packagePath, true) . ')', $content);
            $this->assertStringContainsString('return true;', $content);
        }

        public function testShimIsWrittenNextToThePackage(): void
        {
            $shimPath = ComposerCompat::getAutoloadShim($this->packagePath, self::PACKAGE);

            $this->assertSame($this->packagePath . ComposerCompat::AUTOLOAD_SHIM_SUFFIX, $shimPath);
            $this->assertSame(ComposerCompat::generateAutoloadShim($this->packagePath, self::PACKAGE), file_get_contents($shimPath));
        }

        public function testStaleShimIsRewritten(): void
        {
            $shimPath = ComposerCompat::getInstalledShimPath($this->packagePath);
            file_put_contents($shimPath, "<?php\n" . ComposerCompat::AUTOLOAD_SHIM_MARKER . "\n// outdated\n");

            $this->assertSame($shimPath, ComposerCompat::getAutoloadShim($this->packagePath, self::PACKAGE));
            $this->assertSame(ComposerCompat::generateAutoloadShim($this->packagePath, self::PACKAGE), file_get_contents($shimPath));
        }

        public function testForeignFileIsNeverOverwritten(): void
        {
            $shimPath = ComposerCompat::getInstalledShimPath($this->packagePath);
            file_put_contents($shimPath, '<?php // somebody else\'s file');

            $result = ComposerCompat::getAutoloadShim($this->packagePath, self::PACKAGE);

            $this->assertSame('<?php // somebody else\'s file', file_get_contents($shimPath));
            $this->assertSame($this->getTemporaryShimPath(), $result);
        }

        public function testFallsBackToTemporaryDirectoryWhenPackageDirectoryIsReadOnly(): void
        {
            if(function_exists('posix_geteuid') && posix_geteuid() === 0)
            {
                $this->markTestSkipped('Directory permissions are not enforced for root');
            }

            chmod($this->workDirectory, 0555);
            $shimPath = ComposerCompat::getAutoloadShim($this->packagePath, self::PACKAGE);

            $this->assertSame($this->getTemporaryShimPath(), $shimPath);
            $this->assertFileExists($shimPath);
            $this->assertFileDoesNotExist(ComposerCompat::getInstalledShimPath($this->packagePath));
        }

        public function testApplyAndRestoreGlobals(): void
        {
            $originalServerArgv = $_SERVER['argv'] ?? null;
            $hadComposerGlobal = array_key_exists('_composer_autoload_path', $GLOBALS);

            $previous = ComposerCompat::applyGlobals('ncc://pkg/tool/bin/tool', ['--version', 'x y'], '/tmp/shim.php');

            $this->assertSame(['ncc://pkg/tool/bin/tool', '--version', 'x y'], $GLOBALS['argv']);
            $this->assertSame(3, $GLOBALS['argc']);
            $this->assertSame($GLOBALS['argv'], $_SERVER['argv']);
            $this->assertSame(3, $_SERVER['argc']);
            $this->assertSame('/tmp/shim.php', $GLOBALS['_composer_autoload_path']);

            ComposerCompat::restoreGlobals($previous);

            $this->assertSame($originalServerArgv, $_SERVER['argv'] ?? null);
            $this->assertSame($hadComposerGlobal, array_key_exists('_composer_autoload_path', $GLOBALS));
        }

        public function testApplyGlobalsWithoutShimLeavesComposerGlobalUntouched(): void
        {
            $hadComposerGlobal = array_key_exists('_composer_autoload_path', $GLOBALS);
            $previous = ComposerCompat::applyGlobals('script.php', [], null);

            $this->assertSame($hadComposerGlobal, array_key_exists('_composer_autoload_path', $GLOBALS));
            ComposerCompat::restoreGlobals($previous);
        }
    }
