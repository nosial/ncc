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

    namespace ncc\Objects;

    use ncc\Objects\Package\PackageCacheData;
    use PHPUnit\Framework\TestCase;

    class FrankenPhpBuildStateTest extends TestCase
    {
        private string $directory;

        protected function setUp(): void
        {
            $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_frankenphp_state_test_' . uniqid();
            mkdir($this->directory, 0777, true);
        }

        protected function tearDown(): void
        {
            foreach(glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file)
            {
                unlink($file);
            }

            rmdir($this->directory);
        }

        public function testFingerprintIgnoresListOrder(): void
        {
            $this->assertSame(
                FrankenPhpBuildState::computeBaseFingerprint(['php_version' => '8.4', 'extensions' => ['zlib', 'ctype', 'phar']]),
                FrankenPhpBuildState::computeBaseFingerprint(['extensions' => ['phar', 'zlib', 'ctype', 'ctype'], 'php_version' => '8.4'])
            );
        }

        public function testFingerprintChangesWithInputs(): void
        {
            $base = ['php_version' => '8.4', 'extensions' => ['ctype'], 'libc' => 'musl', 'libraries' => []];
            $fingerprint = FrankenPhpBuildState::computeBaseFingerprint($base);

            $this->assertNotSame($fingerprint, FrankenPhpBuildState::computeBaseFingerprint(['php_version' => '8.3'] + $base));
            $this->assertNotSame($fingerprint, FrankenPhpBuildState::computeBaseFingerprint(['extensions' => ['ctype', 'curl']] + $base));
            $this->assertNotSame($fingerprint, FrankenPhpBuildState::computeBaseFingerprint(['libc' => 'glibc'] + $base));
            $this->assertNotSame($fingerprint, FrankenPhpBuildState::computeBaseFingerprint(['libraries' => ['brotli']] + $base));
        }

        public function testSaveAndLoad(): void
        {
            $libphp = $this->directory . DIRECTORY_SEPARATOR . 'libphp.a';
            file_put_contents($libphp, 'archive');
            $statePath = $this->directory . DIRECTORY_SEPARATOR . FrankenPhpBuildState::FILE_NAME;

            (new FrankenPhpBuildState('abc', PackageCacheData::computeFingerprint($libphp), '8.4.26', '1.13.0'))->save($statePath);
            $state = FrankenPhpBuildState::load($statePath);

            $this->assertInstanceOf(FrankenPhpBuildState::class, $state);
            $this->assertSame('8.4.26', $state->getPhpVersion());
            $this->assertSame('1.13.0', $state->getFrankenPhpVersion());
            $this->assertTrue($state->isValidFor('abc', $libphp));
            $this->assertFalse($state->isValidFor('def', $libphp));
        }

        public function testModifiedLibphpInvalidatesState(): void
        {
            $libphp = $this->directory . DIRECTORY_SEPARATOR . 'libphp.a';
            file_put_contents($libphp, 'archive');
            $state = new FrankenPhpBuildState('abc', PackageCacheData::computeFingerprint($libphp), '8.4.26', '1.13.0');

            // Simulates another project rebuilding libphp.a in the same working directory
            file_put_contents($libphp, 'rebuilt archive');
            $this->assertFalse($state->isValidFor('abc', $libphp));

            unlink($libphp);
            $this->assertFalse($state->isValidFor('abc', $libphp));
        }

        public function testLoadRejectsInvalidFiles(): void
        {
            $statePath = $this->directory . DIRECTORY_SEPARATOR . FrankenPhpBuildState::FILE_NAME;
            $this->assertNull(FrankenPhpBuildState::load($statePath));

            file_put_contents($statePath, 'garbage');
            $this->assertNull(FrankenPhpBuildState::load($statePath));

            file_put_contents($statePath, serialize(['not' => 'a state']));
            $this->assertNull(FrankenPhpBuildState::load($statePath));
        }
    }
