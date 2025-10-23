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

    class FileCollectorTest extends TestCase
    {
        private string $testDir;

        protected function setUp(): void
        {
            // Create a temporary test directory structure
            $this->testDir = sys_get_temp_dir() . '/file_collector_test_' . uniqid();
            mkdir($this->testDir);
            mkdir($this->testDir . '/src');
            mkdir($this->testDir . '/tests');
            mkdir($this->testDir . '/vendor');

            // Create test files
            file_put_contents($this->testDir . '/src/Main.php', '<?php class Main {}');
            file_put_contents($this->testDir . '/src/Helper.php', '<?php class Helper {}');
            file_put_contents($this->testDir . '/src/index.phtml', '<html lang="en"></html>');
            file_put_contents($this->testDir . '/tests/MainTest.php', '<?php class MainTest {}');
            file_put_contents($this->testDir . '/vendor/Package.php', '<?php class Package {}');
            file_put_contents($this->testDir . '/README.md', '# Test');
            file_put_contents($this->testDir . '/composer.json', '{}');
        }

        protected function tearDown(): void
        {
            // Clean up test directory
            if (is_dir($this->testDir))
            {
                $this->removeDirectory($this->testDir);
            }
        }

        private function removeDirectory(string $dir): void
        {
            if (!is_dir($dir))
            {
                return;
            }

            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file)
            {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }

        public function testCollectAllFiles(): void
        {
            $files = FileCollector::collectFiles($this->testDir);

            $this->assertIsArray($files);
            $this->assertCount(7, $files); // All 7 files should be collected
        }

        public function testCollectWithIncludePattern(): void
        {
            $files = FileCollector::collectFiles($this->testDir, ['*.php']);

            $this->assertIsArray($files);
            $this->assertCount(4, $files); // Only .php files

            // Verify all returned files end with .php
            foreach ($files as $file)
            {
                $this->assertStringEndsWith('.php', $file);
            }
        }

        public function testCollectWithMultipleIncludePatterns(): void
        {
            $files = FileCollector::collectFiles($this->testDir, ['*.php', '*.phtml']);

            $this->assertIsArray($files);
            $this->assertCount(5, $files); // .php and .phtml files
        }

        public function testCollectWithExcludePattern(): void
        {
            $files = FileCollector::collectFiles($this->testDir, ['*.php'], ['/tests/*']);

            $this->assertIsArray($files);
            $this->assertCount(3, $files); // .php files excluding tests directory

            // Verify no files from tests directory
            foreach ($files as $file)
            {
                $this->assertStringNotContainsString('/tests/', $file);
            }
        }

        public function testCollectWithMultipleExcludePatterns(): void
        {
            $files = FileCollector::collectFiles($this->testDir, ['*.php'], ['/tests/*', '/vendor/*']);

            $this->assertIsArray($files);
            $this->assertCount(2, $files); // Only src/*.php files

            // Verify files are only from src directory
            foreach ($files as $file)
            {
                $this->assertStringContainsString('/src/', $file);
            }
        }

        public function testCollectWithFileExtensionExclusion(): void
        {
            $files = FileCollector::collectFiles($this->testDir, [], ['*.md', '*.json']);

            $this->assertIsArray($files);
            $this->assertCount(5, $files); // All files except .md and .json

            // Verify no .md or .json files
            foreach ($files as $file)
            {
                $this->assertStringEndsNotWith('.md', $file);
                $this->assertStringEndsNotWith('.json', $file);
            }
        }

        public function testInvalidDirectory(): void
        {
            $this->expectException(InvalidArgumentException::class);
            FileCollector::collectFiles('/nonexistent/directory');
        }

        public function testEmptyIncludeReturnsAllFiles(): void
        {
            $files = FileCollector::collectFiles($this->testDir, []);

            $this->assertIsArray($files);
            $this->assertGreaterThan(0, count($files));
        }

        public function testRealpathReturn(): void
        {
            $files = FileCollector::collectFiles($this->testDir, ['*.php']);

            // All returned paths should be absolute paths
            foreach ($files as $file)
            {
                $this->assertNotEmpty($file);
                $this->assertTrue(is_file($file));
                $this->assertEquals($file, realpath($file));
            }
        }
    }
