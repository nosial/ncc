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


    namespace ncc\CLI\Commands;

    use PHPUnit\Framework\TestCase;

    class HelperTest extends TestCase
    {
        public function testResolvePathReturnsNullForEmptyInput(): void
        {
            $this->assertNull(Helper::resolvePath(null));
            $this->assertNull(Helper::resolvePath(''));
            $this->assertNull(Helper::resolvePath('   '));
        }

        public function testResolvePathKeepsAbsolutePaths(): void
        {
            $this->assertSame(DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'example.ncc', Helper::resolvePath('/tmp/example.ncc'));
        }

        public function testResolvePathResolvesRelativePathsAgainstWorkingDirectory(): void
        {
            $expected = getcwd() . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'example.ncc';
            $this->assertSame($expected, Helper::resolvePath('sub/example.ncc'));
        }

        public function testResolvePathResolvesPathsToMissingFiles(): void
        {
            $this->assertSame(getcwd() . DIRECTORY_SEPARATOR . 'does-not-exist-yet', Helper::resolvePath('does-not-exist-yet'));
        }
    }
