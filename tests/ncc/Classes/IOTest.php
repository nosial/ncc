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

    use ncc\Exceptions\IOException;
    use PHPUnit\Framework\TestCase;

    class IOTest extends TestCase
    {
        private string $testDirectory;

        /**
         * Set up test environment before each test
         */
        protected function setUp(): void
        {
            parent::setUp();
            // Create a unique temporary directory for each test
            $this->testDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_io_test_' . uniqid();
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
         * Test mkdir creates directory successfully
         */
        public function testMkdirCreatesDirectory(): void
        {
            $newDir = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_dir';

            IO::mkdir($newDir);

            $this->assertTrue(is_dir($newDir));
        }

        /**
         * Test mkdir creates nested directories recursively
         */
        public function testMkdirCreatesNestedDirectories(): void
        {
            $nestedDir = $this->testDirectory . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'level3';

            IO::mkdir($nestedDir, true);

            $this->assertTrue(is_dir($nestedDir));
        }

        /**
         * Test touch creates a new file
         */
        public function testTouchCreatesNewFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_file.txt';

            IO::touch($testFile);

            $this->assertTrue(file_exists($testFile));
            $this->assertTrue(is_file($testFile));
        }

        /**
         * Test touch updates file timestamp
         */
        public function testTouchUpdatesFileTimestamp(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_file.txt';
            
            // Create file
            touch($testFile);
            $firstTime = filemtime($testFile);
            
            // Wait a moment to ensure timestamp difference
            sleep(1);
            
            // Touch file again
            IO::touch($testFile);
            clearstatcache(true, $testFile);
            $secondTime = filemtime($testFile);

            $this->assertGreaterThanOrEqual($firstTime, $secondTime);
        }

        /**
         * Test writeFile creates and writes content
         */
        public function testWriteFileCreatesAndWritesContent(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_write.txt';
            $content = 'Hello, World!';

            IO::writeFile($testFile, $content);

            $this->assertTrue(file_exists($testFile));
            $this->assertEquals($content, file_get_contents($testFile));
        }

        /**
         * Test writeFile overwrites existing content
         */
        public function testWriteFileOverwritesContent(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_overwrite.txt';
            
            IO::writeFile($testFile, 'First content');
            IO::writeFile($testFile, 'Second content');

            $this->assertEquals('Second content', file_get_contents($testFile));
        }

        /**
         * Test readFile reads content correctly
         */
        public function testReadFileReadsContent(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_read.txt';
            $content = 'Test content to read';
            
            file_put_contents($testFile, $content);

            $result = IO::readFile($testFile);

            $this->assertEquals($content, $result);
        }

        /**
         * Test readFile throws IOException for non-existent file
         */
        public function testReadFileThrowsExceptionForNonExistentFile(): void
        {
            $this->expectException(IOException::class);

            IO::readFile($this->testDirectory . DIRECTORY_SEPARATOR . 'non_existent.txt');
        }

        /**
         * Test rm removes file successfully
         */
        public function testRmRemovesFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test_remove.txt';
            
            file_put_contents($testFile, 'content');
            $this->assertTrue(file_exists($testFile));

            IO::rm($testFile, false);

            $this->assertFalse(file_exists($testFile));
        }

        /**
         * Test rm removes empty directory
         */
        public function testRmRemovesEmptyDirectory(): void
        {
            $testDir = $this->testDirectory . DIRECTORY_SEPARATOR . 'empty_dir';
            
            mkdir($testDir);
            $this->assertTrue(is_dir($testDir));

            IO::rm($testDir, false);

            $this->assertFalse(is_dir($testDir));
        }

        /**
         * Test rm removes directory recursively
         */
        public function testRmRemovesDirectoryRecursively(): void
        {
            $testDir = $this->testDirectory . DIRECTORY_SEPARATOR . 'recursive_dir';
            $subDir = $testDir . DIRECTORY_SEPARATOR . 'sub_dir';
            $testFile = $subDir . DIRECTORY_SEPARATOR . 'test.txt';
            
            mkdir($testDir);
            mkdir($subDir);
            file_put_contents($testFile, 'content');

            IO::rm($testDir, true);

            $this->assertFalse(is_dir($testDir));
        }

        /**
         * Test exists returns true for existing file
         */
        public function testExistsReturnsTrueForExistingFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'exists_test.txt';
            file_put_contents($testFile, 'content');

            $this->assertTrue(IO::exists($testFile));
        }

        /**
         * Test exists returns false for non-existent file
         */
        public function testExistsReturnsFalseForNonExistentFile(): void
        {
            $this->assertFalse(IO::exists($this->testDirectory . DIRECTORY_SEPARATOR . 'non_existent.txt'));
        }

        /**
         * Test isDir returns true for directory
         */
        public function testIsDirReturnsTrueForDirectory(): void
        {
            $this->assertTrue(IO::isDir($this->testDirectory));
        }

        /**
         * Test isDir returns false for file
         */
        public function testIsDirReturnsFalseForFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt';
            file_put_contents($testFile, 'content');

            $this->assertFalse(IO::isDir($testFile));
        }

        /**
         * Test isReadable returns true for readable file
         */
        public function testIsReadableReturnsTrueForReadableFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'readable.txt';
            file_put_contents($testFile, 'content');

            $this->assertTrue(IO::isReadable($testFile));
        }

        /**
         * Test isWritable returns true for writable file
         */
        public function testIsWritableReturnsTrueForWritableFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'writable.txt';
            file_put_contents($testFile, 'content');

            $this->assertTrue(IO::isWritable($testFile));
        }

        /**
         * Test writeFile with empty content
         */
        public function testWriteFileWithEmptyContent(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'empty.txt';

            IO::writeFile($testFile, '');

            $this->assertTrue(file_exists($testFile));
            $this->assertEquals('', file_get_contents($testFile));
        }

        /**
         * Test writeFile with large content
         */
        public function testWriteFileWithLargeContent(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'large.txt';
            $content = str_repeat('Large content line. ', 10000);

            IO::writeFile($testFile, $content);

            $this->assertEquals($content, file_get_contents($testFile));
        }

        /**
         * Test readFile with empty file
         */
        public function testReadFileWithEmptyFile(): void
        {
            $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'empty_read.txt';
            file_put_contents($testFile, '');

            $result = IO::readFile($testFile);

            $this->assertEquals('', $result);
        }
    }
