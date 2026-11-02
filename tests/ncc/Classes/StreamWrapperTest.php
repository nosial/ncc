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
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    class StreamWrapperTest extends TestCase
    {
        private const PACKAGE = 'com.example.stream_wrapper_test';
        private const BASE = 'ncc://' . self::PACKAGE . '/';

        private static string $workDirectory;

        /**
         * Builds a small package with nested directories and imports it once for all tests
         */
        public static function setUpBeforeClass(): void
        {
            self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_stream_wrapper_test_' . uniqid();
            $sourcePath = self::$workDirectory . DIRECTORY_SEPARATOR . 'src';
            mkdir($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'Deep', 0777, true);

            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Root.php', '<?php class StreamWrapperTestRoot {}');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'Child.php', '<?php class StreamWrapperTestChild {}');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'Deep' . DIRECTORY_SEPARATOR . 'Leaf.php', '<?php class StreamWrapperTestLeaf {}');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'Sub' . DIRECTORY_SEPARATOR . 'Deep' . DIRECTORY_SEPARATOR . 'data.txt', 'hello');

            $projectFile = self::$workDirectory . DIRECTORY_SEPARATOR . 'project.yml';
            Project::createNew('StreamWrapperTest', self::PACKAGE)->save($projectFile);
            $outputPath = Project::compilerFromFile($projectFile, 'release')->build();

            Runtime::import($outputPath);
        }

        public static function tearDownAfterClass(): void
        {
            if(!isset(self::$workDirectory) || !is_dir(self::$workDirectory))
            {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(self::$workDirectory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach($iterator as $file)
            {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir(self::$workDirectory);
        }

        /**
         * Returns the directory the package contents are stored under (the assembly name)
         */
        private function root(): string
        {
            return self::BASE . 'StreamWrapperTest/';
        }

        public function testFilesAreFiles(): void
        {
            $this->assertTrue(is_file($this->root() . 'Root.php'));
            $this->assertTrue(file_exists($this->root() . 'Sub/Deep/data.txt'));
            $this->assertFalse(is_dir($this->root() . 'Root.php'));
            $this->assertSame('hello', file_get_contents($this->root() . 'Sub/Deep/data.txt'));
        }

        public function testDirectoriesAreDirectories(): void
        {
            $this->assertTrue(is_dir(self::BASE));
            $this->assertFalse(is_file(self::BASE));
            $this->assertTrue(is_dir($this->root()));
            $this->assertTrue(is_dir($this->root() . 'Sub'));
            $this->assertTrue(is_dir($this->root() . 'Sub/Deep/'));
            $this->assertTrue(file_exists($this->root() . 'Sub'));
            $this->assertFalse(is_file($this->root() . 'Sub'));
        }

        public function testMissingPathsDoNotExist(): void
        {
            $this->assertFalse(file_exists($this->root() . 'Missing'));
            $this->assertFalse(is_dir($this->root() . 'Su'));
            $this->assertFalse(@opendir($this->root() . 'Missing'));
            $this->assertFalse(@opendir($this->root() . 'Root.php'));
        }

        public function testReaddirReturnsPlainNames(): void
        {
            $entries = scandir($this->root() . 'Sub');
            $this->assertIsArray($entries);
            sort($entries);
            $this->assertSame(['Child.php', 'Deep'], $entries);
        }

        public function testRecursiveIterationFindsAllFiles(): void
        {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root(), FilesystemIterator::SKIP_DOTS));

            $files = [];
            foreach($iterator as $file)
            {
                $files[] = substr($file->getPathname(), strlen($this->root()));
            }

            sort($files);
            $this->assertSame(['Root.php', 'Sub/Child.php', 'Sub/Deep/Leaf.php', 'Sub/Deep/data.txt'], $files);
        }

        public function testPackageOnlyRequireStillWorks(): void
        {
            $this->assertSame(1, require self::BASE);
        }
    }
