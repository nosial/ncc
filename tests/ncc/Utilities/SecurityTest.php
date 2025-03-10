<?php
/*
 * Copyright (c) Nosial 2022-2024, all rights reserved.
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

namespace ncc\Utilities;

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test case for the Security class
 */
class SecurityTest extends TestCase
{
    /**
     * Test sanitizeFilename with beautify set to true
     */
    public function testSanitizeFilenameWithBeautify(): void
    {
        $filename = '[]unsafe..FILE//NAM*E_.php';
        $expected = 'unsafe.file-nam-e.php';
        $result = Security::sanitizeFilename($filename, true);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test sanitizeFilename with beautify set to false
     */
    public function testSanitizeFilenameWithoutBeautify(): void
    {
        $filename = '[]unsafe..FILE//NAM*E_.php';
        $expected = 'unsafe..FILE--NAM-E_.php';
        $result = Security::sanitizeFilename($filename, false);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test sanitizeFilename with unicode characters
     */
    public function testSanitizeFilenameWithUnicode(): void
    {
        $filename = 'оȕȵȴȁȕfȅ_-.php';
        $expected = 'оȕȵȴȁȕfȅ.php';
        $result = Security::sanitizeFilename($filename, true);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test sanitizeFilename with a very long file name
     */
    public function testSanitizeFilenameWithLongFileName(): void
    {
        $filename = str_repeat("a", 300) . '.php';
        $expected = str_repeat("a", 251) . '.php';
        $result = Security::sanitizeFilename($filename, true);

        $this->assertEquals(strlen($expected), strlen($result));
    }

    /**
     * Test beautifyFilename with multiple special characters
     */
    public function testBeautifyFilenameWithSpecialChars(): void
    {
        $filename = '..unnecessary//_underscores___and..dots//extra..stuff..';
        $expected = 'unnecessary//-underscores-and.dots//extra.stuff';
        $result = Security::beautifyFilename($filename);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test beautifyFilename with lowercase-leters and special charecters
     */
    public function testBeautifyFilenameWithLowerCaseAndSpecial(): void
    {
        $filename = '..__Some__TEXT__with....CAPITALS__and..dots//extra..stuff__..';
        $expected = 'some-text-with.capitals-and.dots//extra.stuff';
        $result = Security::beautifyFilename($filename);

        $this->assertEquals($expected, $result);
    }
}
