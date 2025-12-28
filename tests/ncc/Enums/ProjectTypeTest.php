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

    namespace ncc\Tests\Enums;

    use ncc\Classes\IO;
    use ncc\Enums\ProjectType;
    use PHPUnit\Framework\TestCase;

    class ProjectTypeTest extends TestCase
    {
        private string $tempDir;

        protected function setUp(): void
        {
            parent::setUp();
            // Create a temporary directory for testing
            $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_test_' . uniqid();
            mkdir($this->tempDir, 0777, true);
        }

        protected function tearDown(): void
        {
            parent::tearDown();
            // Clean up temporary directory
            $this->recursiveRemoveDirectory($this->tempDir);
        }

        /**
         * Recursively remove a directory and its contents
         */
        private function recursiveRemoveDirectory(string $directory): void
        {
            if (!is_dir($directory))
            {
                return;
            }

            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $file)
            {
                $path = $directory . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
            }
            rmdir($directory);
        }

        /**
         * Test that root-level composer.json is prioritized over build/composer.json
         * This prevents the bug where build artifacts are incorrectly detected as the project root
         */
        public function testPrioritizesRootComposerJsonOverBuildDirectory(): void
        {
            // Create root composer.json
            $rootComposerPath = $this->tempDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($rootComposerPath, json_encode([
                'name' => 'test/root-package',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]));

            // Create build/composer.json (should be ignored)
            $buildDir = $this->tempDir . DIRECTORY_SEPARATOR . 'build';
            mkdir($buildDir);
            $buildComposerPath = $buildDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($buildComposerPath, json_encode([
                'name' => 'test/build-package',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]));

            // Detect the composer.json file
            $detectedPath = ProjectType::COMPOSER->getFilePath($this->tempDir);

            // Assert that root-level composer.json is detected, not build/composer.json
            $this->assertNotNull($detectedPath, 'Should detect composer.json');
            $this->assertEquals($rootComposerPath, $detectedPath, 'Should prioritize root composer.json over build/composer.json');
            $this->assertStringNotContainsString('build', $detectedPath, 'Should not detect composer.json in build directory');
        }

        /**
         * Test that build directory is skipped during recursive search
         */
        public function testSkipsBuildDirectoryInRecursiveSearch(): void
        {
            // Don't create root composer.json, only in build directory
            $buildDir = $this->tempDir . DIRECTORY_SEPARATOR . 'build';
            mkdir($buildDir);
            $buildComposerPath = $buildDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($buildComposerPath, json_encode([
                'name' => 'test/build-package',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]));

            // Create a valid composer.json in a non-skipped subdirectory
            $libDir = $this->tempDir . DIRECTORY_SEPARATOR . 'lib';
            mkdir($libDir);
            $libComposerPath = $libDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($libComposerPath, json_encode([
                'name' => 'test/lib-package',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]));

            // Detect the composer.json file
            $detectedPath = ProjectType::COMPOSER->getFilePath($this->tempDir);

            // Assert that build directory is skipped and lib directory is found
            $this->assertNotNull($detectedPath, 'Should detect composer.json');
            $this->assertEquals($libComposerPath, $detectedPath, 'Should find lib/composer.json and skip build/composer.json');
        }

        /**
         * Test that other common build directories are also skipped
         */
        public function testSkipsCommonBuildDirectories(): void
        {
            $buildDirs = ['build', 'dist', 'target', 'out'];
            
            foreach ($buildDirs as $dirName)
            {
                $dir = $this->tempDir . DIRECTORY_SEPARATOR . $dirName;
                mkdir($dir);
                file_put_contents($dir . DIRECTORY_SEPARATOR . 'composer.json', '{}');
            }

            // Create valid composer.json in a non-skipped directory
            $srcDir = $this->tempDir . DIRECTORY_SEPARATOR . 'packages';
            mkdir($srcDir);
            $srcComposerPath = $srcDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($srcComposerPath, '{}');

            // Detect the composer.json file
            $detectedPath = ProjectType::COMPOSER->getFilePath($this->tempDir);

            // Assert that all build directories are skipped
            $this->assertNotNull($detectedPath, 'Should detect composer.json');
            $this->assertEquals($srcComposerPath, $detectedPath, 'Should skip all build directories');
            
            foreach ($buildDirs as $dirName)
            {
                $this->assertStringNotContainsString($dirName, $detectedPath, "Should not detect composer.json in {$dirName} directory");
            }
        }

        /**
         * Test that detectProjectType correctly identifies a Composer project
         */
        public function testDetectProjectTypeForComposer(): void
        {
            // Create root composer.json
            $rootComposerPath = $this->tempDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($rootComposerPath, json_encode([
                'name' => 'test/package',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]));

            // Detect project type
            $projectType = ProjectType::detectProjectType($this->tempDir);

            // Assert correct project type is detected
            $this->assertNotNull($projectType, 'Should detect project type');
            $this->assertEquals(ProjectType::COMPOSER, $projectType, 'Should detect Composer project type');
        }

        /**
         * Test enum values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('project.yml', ProjectType::NCC->value);
            $this->assertEquals('project.json', ProjectType::NCC_V2->value);
            $this->assertEquals('composer.json', ProjectType::COMPOSER->value);
        }

        /**
         * Test getFilePath returns null when file doesn't exist
         */
        public function testGetFilePathReturnsNullWhenNotFound(): void
        {
            $path = ProjectType::NCC->getFilePath($this->tempDir);
            $this->assertNull($path);
        }

        /**
         * Test getFilePath finds file in root directory
         */
        public function testGetFilePathFindsFileInRoot(): void
        {
            $expectedPath = $this->tempDir . DIRECTORY_SEPARATOR . 'project.yml';
            file_put_contents($expectedPath, 'test: content');
            
            $path = ProjectType::NCC->getFilePath($this->tempDir);
            $this->assertEquals($expectedPath, $path);
        }

        /**
         * Test getFilePath finds file in subdirectory
         */
        public function testGetFilePathFindsFileInSubdirectory(): void
        {
            $subDir = $this->tempDir . DIRECTORY_SEPARATOR . 'src';
            mkdir($subDir);
            $expectedPath = $subDir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($expectedPath, '{}');
            
            $path = ProjectType::COMPOSER->getFilePath($this->tempDir);
            $this->assertEquals($expectedPath, $path);
        }

        /**
         * Test getConverter returns correct converter for NCC_V2
         */
        public function testGetConverterForNccV2(): void
        {
            $converter = ProjectType::NCC_V2->getConverter();
            $this->assertNotNull($converter);
            $this->assertInstanceOf(\ncc\Abstracts\AbstractProjectConverter::class, $converter);
        }

        /**
         * Test getConverter returns correct converter for Composer
         */
        public function testGetConverterForComposer(): void
        {
            $converter = ProjectType::COMPOSER->getConverter();
            $this->assertNotNull($converter);
            $this->assertInstanceOf(\ncc\Abstracts\AbstractProjectConverter::class, $converter);
        }

        /**
         * Test getConverter returns null for NCC
         */
        public function testGetConverterForNccReturnsNull(): void
        {
            $converter = ProjectType::NCC->getConverter();
            $this->assertNull($converter);
        }

        /**
         * Test detectProjectPath finds project in root
         */
        public function testDetectProjectPathInRoot(): void
        {
            $expectedPath = $this->tempDir . DIRECTORY_SEPARATOR . 'project.yml';
            file_put_contents($expectedPath, 'test: value');
            
            $path = ProjectType::detectProjectPath($this->tempDir);
            $this->assertEquals($expectedPath, $path);
        }

        /**
         * Test detectProjectPath returns null when no project found
         */
        public function testDetectProjectPathReturnsNull(): void
        {
            $path = ProjectType::detectProjectPath($this->tempDir);
            $this->assertNull($path);
        }

        /**
         * Test detectProjectType returns null when no project found
         */
        public function testDetectProjectTypeReturnsNull(): void
        {
            $type = ProjectType::detectProjectType($this->tempDir);
            $this->assertNull($type);
        }

        /**
         * Test all cases are present
         */
        public function testAllCases(): void
        {
            $cases = ProjectType::cases();
            $this->assertCount(3, $cases);
            $this->assertContains(ProjectType::NCC, $cases);
            $this->assertContains(ProjectType::NCC_V2, $cases);
            $this->assertContains(ProjectType::COMPOSER, $cases);
        }
    }

