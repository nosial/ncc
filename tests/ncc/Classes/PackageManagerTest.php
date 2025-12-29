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

    namespace ncc\Classes;

    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class PackageManagerTest extends TestCase
    {
        private string $testDirectory;

        /**
         * Set up test environment before each test
         */
        protected function setUp(): void
        {
            parent::setUp();
            // Create a unique temporary directory for each test
            $this->testDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_test_' . uniqid();
            if (!is_dir($this->testDirectory)) {
                mkdir($this->testDirectory, 0777, true);
            }
        }

        /**
         * Clean up test environment after each test
         */
        protected function tearDown(): void
        {
            parent::tearDown();
            // Clean up test directory
            if (is_dir($this->testDirectory)) {
                $this->recursiveDelete($this->testDirectory);
            }
        }

        /**
         * Helper method to recursively delete a directory
         */
        private function recursiveDelete(string $dir): void
        {
            if (!is_dir($dir)) {
                return;
            }

            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
            }
            rmdir($dir);
        }

        /**
         * Test constructor with valid directory path
         */
        public function testConstructorWithValidDirectory(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertInstanceOf(PackageManager::class, $pm);
            $this->assertEquals($this->testDirectory, $pm->getDataDirectoryPath());
            $this->assertEmpty($pm->getEntries());
            $this->assertFalse($pm->isModified());
        }

        /**
         * Test constructor throws exception with empty directory path
         */
        public function testConstructorThrowsExceptionWithEmptyPath(): void
        {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Directory path cannot be empty');

            new PackageManager('');
        }

        /**
         * Test getPackageLockPath returns correct path
         */
        public function testGetPackageLockPath(): void
        {
            $pm = new PackageManager($this->testDirectory);
            $expectedPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'lock.json';

            $this->assertEquals($expectedPath, $pm->getPackageLockPath());
        }

        /**
         * Test getDataDirectoryPath returns correct path
         */
        public function testGetDataDirectoryPath(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertEquals($this->testDirectory, $pm->getDataDirectoryPath());
        }

        /**
         * Test getEntries returns empty array initially
         */
        public function testGetEntriesReturnsEmptyArrayInitially(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertIsArray($pm->getEntries());
            $this->assertEmpty($pm->getEntries());
        }

        /**
         * Test isModified returns false initially
         */
        public function testIsModifiedReturnsFalseInitially(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertFalse($pm->isModified());
        }

        /**
         * Test entryExists returns false for non-existent package
         */
        public function testEntryExistsReturnsFalseForNonExistentPackage(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertFalse($pm->entryExists('non-existent/package'));
        }

        /**
         * Test entryExists returns false with empty package name
         */
        public function testEntryExistsReturnsFalseWithEmptyPackageName(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertFalse($pm->entryExists(''));
        }

        /**
         * Test getEntry returns null for non-existent package
         */
        public function testGetEntryReturnsNullForNonExistentPackage(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getEntry('non-existent/package', '1.0.0'));
        }

        /**
         * Test getEntry returns null with empty package name
         */
        public function testGetEntryReturnsNullWithEmptyPackageName(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getEntry('', '1.0.0'));
        }

        /**
         * Test getEntry returns null with empty version
         */
        public function testGetEntryReturnsNullWithEmptyVersion(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getEntry('some/package', ''));
        }

        /**
         * Test getLatestVersion returns null for non-existent package
         */
        public function testGetLatestVersionReturnsNullForNonExistentPackage(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getLatestVersion('non-existent/package'));
        }

        /**
         * Test getLatestVersion returns null with empty package name
         */
        public function testGetLatestVersionReturnsNullWithEmptyPackageName(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getLatestVersion(''));
        }

        /**
         * Test getAllVersions returns empty array for non-existent package
         */
        public function testGetAllVersionsReturnsEmptyArrayForNonExistentPackage(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertIsArray($pm->getAllVersions('non-existent/package'));
            $this->assertEmpty($pm->getAllVersions('non-existent/package'));
        }

        /**
         * Test getAllVersions returns empty array with empty package name
         */
        public function testGetAllVersionsReturnsEmptyArrayWithEmptyPackageName(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertIsArray($pm->getAllVersions(''));
            $this->assertEmpty($pm->getAllVersions(''));
        }

        /**
         * Test getPackagePath returns null for non-existent package
         */
        public function testGetPackagePathReturnsNullForNonExistentPackage(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $this->assertNull($pm->getPackagePath('non-existent/package', '1.0.0'));
        }

        /**
         * Test constructor with autoSave disabled
         */
        public function testConstructorWithAutoSaveDisabled(): void
        {
            $pm = new PackageManager($this->testDirectory, false);

            $this->assertInstanceOf(PackageManager::class, $pm);
            $this->assertEmpty($pm->getEntries());
        }

        /**
         * Test loading existing lock file
         */
        public function testLoadingExistingLockFile(): void
        {
            // Create a lock file with test data
            $lockData = [
                [
                    'package' => 'test/package1',
                    'version' => '1.0.0',
                    'dependencies' => []
                ],
                [
                    'package' => 'test/package2',
                    'version' => '2.0.0',
                    'dependencies' => []
                ]
            ];

            $lockFilePath = $this->testDirectory . DIRECTORY_SEPARATOR . 'lock.json';
            file_put_contents($lockFilePath, json_encode($lockData, JSON_PRETTY_PRINT));

            // Create PackageManager and verify it loads the entries
            $pm = new PackageManager($this->testDirectory);

            $this->assertCount(2, $pm->getEntries());
            $this->assertTrue($pm->entryExists('test/package1', '1.0.0'));
            $this->assertTrue($pm->entryExists('test/package2', '2.0.0'));
        }

        /**
         * Test constructor trims trailing directory separator
         */
        public function testConstructorTrimsTrailingDirectorySeparator(): void
        {
            $pathWithTrailingSeparator = $this->testDirectory . DIRECTORY_SEPARATOR;
            $pm = new PackageManager($pathWithTrailingSeparator);

            $this->assertEquals($this->testDirectory, $pm->getDataDirectoryPath());
        }

        /**
         * Test uninstall returns empty array when package not found
         */
        public function testUninstallReturnsEmptyArrayWhenPackageNotFound(): void
        {
            $pm = new PackageManager($this->testDirectory);

            $result = $pm->uninstall('non-existent/package', '1.0.0');

            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }

        /**
         * Test getPackagePathFromEntry with null returns packages directory
         */
        public function testGetPackagePathFromEntryWithNullReturnsPackagesDirectory(): void
        {
            $pm = new PackageManager($this->testDirectory);
            $expected = $this->testDirectory . DIRECTORY_SEPARATOR . 'packages';

            $this->assertEquals($expected, $pm->getPackagePathFromEntry(null));
        }
    }
