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

    use PHPUnit\Framework\TestCase;

    class ValidateTest extends TestCase
    {
        /**
         * Test valid package names
         */
        public function testValidPackageNames(): void
        {
            $this->assertTrue(Validate::packageName('com.example'));
            $this->assertTrue(Validate::packageName('com.example.package'));
            $this->assertTrue(Validate::packageName('org.mycompany.myproject'));
            $this->assertTrue(Validate::packageName('net.test_package'));
            $this->assertTrue(Validate::packageName('io.github.user_name.project_name'));
            $this->assertTrue(Validate::packageName('_underscore.test'));
            $this->assertTrue(Validate::packageName('a.b'));
        }

        /**
         * Test invalid package names
         */
        public function testInvalidPackageNames(): void
        {
            $this->assertFalse(Validate::packageName(''));
            $this->assertFalse(Validate::packageName('singlename'));
            $this->assertFalse(Validate::packageName('com'));
            $this->assertFalse(Validate::packageName('.com.example'));
            $this->assertFalse(Validate::packageName('com.example.'));
            $this->assertFalse(Validate::packageName('com..example'));
            $this->assertFalse(Validate::packageName('123.example'));
            $this->assertFalse(Validate::packageName('com.123example'));
            $this->assertFalse(Validate::packageName('com.example-package'));
            $this->assertFalse(Validate::packageName('com example'));
        }

        /**
         * Test valid semantic versions
         */
        public function testValidVersions(): void
        {
            $this->assertTrue(Validate::version('1.0.0'));
            $this->assertTrue(Validate::version('0.0.1'));
            $this->assertTrue(Validate::version('2.1.3'));
            $this->assertTrue(Validate::version('10.20.30'));
            $this->assertTrue(Validate::version('1.0.0-alpha'));
            $this->assertTrue(Validate::version('1.0.0-beta.1'));
            $this->assertTrue(Validate::version('1.0.0-rc.1'));
            $this->assertTrue(Validate::version('1.0.0+20130313144700'));
            $this->assertTrue(Validate::version('1.0.0-beta+exp.sha.5114f85'));
            $this->assertTrue(Validate::version('1.0.0+21AF26D3'));
        }

        /**
         * Test invalid semantic versions
         */
        public function testInvalidVersions(): void
        {
            $this->assertFalse(Validate::version(''));
            $this->assertFalse(Validate::version('1'));
            $this->assertFalse(Validate::version('1.0'));
            $this->assertFalse(Validate::version('1.0.0.0'));
            $this->assertFalse(Validate::version('v1.0.0'));
            $this->assertFalse(Validate::version('1.0.0-'));
            $this->assertFalse(Validate::version('1.0.0+'));
            $this->assertFalse(Validate::version('a.b.c'));
            $this->assertFalse(Validate::version('1.0.x'));
        }

        /**
         * Test valid URLs
         */
        public function testValidUrls(): void
        {
            $this->assertTrue(Validate::url('https://example.com'));
            $this->assertTrue(Validate::url('http://example.com'));
            $this->assertTrue(Validate::url('https://www.example.com'));
            $this->assertTrue(Validate::url('https://example.com/path'));
            $this->assertTrue(Validate::url('https://example.com/path/to/resource'));
            $this->assertTrue(Validate::url('https://example.com:8080'));
            $this->assertTrue(Validate::url('https://example.com?query=value'));
            $this->assertTrue(Validate::url('https://example.com#fragment'));
            $this->assertTrue(Validate::url('ftp://ftp.example.com/file.txt'));
            $this->assertTrue(Validate::url('https://user:pass@example.com'));
        }

        /**
         * Test invalid URLs
         */
        public function testInvalidUrls(): void
        {
            $this->assertFalse(Validate::url(''));
            $this->assertFalse(Validate::url('not a url'));
            $this->assertFalse(Validate::url('example.com'));
            $this->assertFalse(Validate::url('//example.com'));
            $this->assertFalse(Validate::url('http://'));
            $this->assertFalse(Validate::url('https://'));
        }

        /**
         * Test package name with numbers after first character
         */
        public function testPackageNameWithNumbers(): void
        {
            $this->assertTrue(Validate::packageName('com.example2'));
            $this->assertTrue(Validate::packageName('com.test123.package'));
            $this->assertTrue(Validate::packageName('com.abc123.def456'));
        }

        /**
         * Test version with complex pre-release
         */
        public function testComplexPreReleaseVersions(): void
        {
            $this->assertTrue(Validate::version('1.0.0-alpha.beta.1'));
            $this->assertTrue(Validate::version('1.0.0-0.3.7'));
            $this->assertTrue(Validate::version('1.0.0-x.7.z.92'));
        }

        /**
         * Test URL with international domain names
         */
        public function testUrlWithSpecialCharacters(): void
        {
            $this->assertTrue(Validate::url('https://example.com/path?param1=value1&param2=value2'));
            $this->assertTrue(Validate::url('https://sub.domain.example.com'));
            $this->assertTrue(Validate::url('https://example.com/path/to/resource.html'));
        }

        /**
         * Test edge cases for package name
         */
        public function testPackageNameEdgeCases(): void
        {
            // Underscore at the beginning is valid
            $this->assertTrue(Validate::packageName('_test.example'));
            
            // Multiple underscores
            $this->assertTrue(Validate::packageName('com.test__package'));
            
            // Long package name
            $this->assertTrue(Validate::packageName('com.verylongexamplepackagename.anotherverylongpart'));
        }

        /**
         * Test edge cases for version
         */
        public function testVersionEdgeCases(): void
        {
            // Zero versions
            $this->assertTrue(Validate::version('0.0.0'));
            
            // Large version numbers
            $this->assertTrue(Validate::version('999.999.999'));
            
            // Multiple pre-release identifiers
            $this->assertTrue(Validate::version('1.0.0-alpha.1.beta.2.rc.3'));
        }
    }
