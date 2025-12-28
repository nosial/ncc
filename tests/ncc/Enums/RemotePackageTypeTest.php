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

    use ncc\Enums\RemotePackageType;
    use PHPUnit\Framework\TestCase;

    class RemotePackageTypeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('ncc', RemotePackageType::NCC->value);
            $this->assertEquals('zip', RemotePackageType::SOURCE_ZIP->value);
            $this->assertEquals('tar', RemotePackageType::SOURCE_TAR->value);
            $this->assertEquals('git', RemotePackageType::SOURCE_GIT->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(RemotePackageType::NCC, RemotePackageType::from('ncc'));
            $this->assertSame(RemotePackageType::SOURCE_ZIP, RemotePackageType::from('zip'));
            $this->assertSame(RemotePackageType::SOURCE_TAR, RemotePackageType::from('tar'));
            $this->assertSame(RemotePackageType::SOURCE_GIT, RemotePackageType::from('git'));
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(RemotePackageType::NCC, RemotePackageType::tryFrom('ncc'));
            $this->assertSame(RemotePackageType::SOURCE_ZIP, RemotePackageType::tryFrom('zip'));
            $this->assertSame(RemotePackageType::SOURCE_TAR, RemotePackageType::tryFrom('tar'));
            $this->assertSame(RemotePackageType::SOURCE_GIT, RemotePackageType::tryFrom('git'));
        }

        /**
         * Test tryFrom with invalid value returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(RemotePackageType::tryFrom('invalid'));
            $this->assertNull(RemotePackageType::tryFrom(''));
            $this->assertNull(RemotePackageType::tryFrom('NCC'));
            $this->assertNull(RemotePackageType::tryFrom('rar'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = RemotePackageType::cases();
            
            $this->assertCount(4, $cases);
            $this->assertContains(RemotePackageType::NCC, $cases);
            $this->assertContains(RemotePackageType::SOURCE_ZIP, $cases);
            $this->assertContains(RemotePackageType::SOURCE_TAR, $cases);
            $this->assertContains(RemotePackageType::SOURCE_GIT, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $type1 = RemotePackageType::NCC;
            $type2 = RemotePackageType::NCC;
            $type3 = RemotePackageType::SOURCE_ZIP;

            $this->assertSame($type1, $type2);
            $this->assertNotSame($type1, $type3);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (RemotePackageType::NCC) {
                RemotePackageType::NCC => 'ncc_package',
                RemotePackageType::SOURCE_ZIP => 'zip_archive',
                RemotePackageType::SOURCE_TAR => 'tar_archive',
                RemotePackageType::SOURCE_GIT => 'git_repository',
            };

            $this->assertEquals('ncc_package', $result);
        }
    }
