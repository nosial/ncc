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

    namespace ncc\Enums;

    use ncc\Enums\BuildType;
    use PHPUnit\Framework\TestCase;

    class BuildTypeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('ncc', BuildType::NCC_PACKAGE->value);
            $this->assertEquals('php', BuildType::PHP_PACKAGE->value);
            $this->assertEquals('phar', BuildType::PHAR_PACKAGE->value);
            $this->assertEquals('native', BuildType::NATIVE->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(BuildType::NCC_PACKAGE, BuildType::from('ncc'));
            $this->assertSame(BuildType::PHP_PACKAGE, BuildType::from('php'));
            $this->assertSame(BuildType::PHAR_PACKAGE, BuildType::from('phar'));
            $this->assertSame(BuildType::NATIVE, BuildType::from('native'));
        }

        /**
         * Test that invalid string values throw ValueError
         */
        public function testFromStringInvalid(): void
        {
            $this->expectException(\ValueError::class);
            BuildType::from('invalid');
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(BuildType::NCC_PACKAGE, BuildType::tryFrom('ncc'));
            $this->assertSame(BuildType::PHP_PACKAGE, BuildType::tryFrom('php'));
            $this->assertSame(BuildType::PHAR_PACKAGE, BuildType::tryFrom('phar'));
            $this->assertSame(BuildType::NATIVE, BuildType::tryFrom('native'));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(BuildType::tryFrom('invalid'));
            $this->assertNull(BuildType::tryFrom(''));
            $this->assertNull(BuildType::tryFrom('NCC'));
            $this->assertNull(BuildType::tryFrom('jar'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = BuildType::cases();
            
            $this->assertCount(4, $cases);
            $this->assertContains(BuildType::NCC_PACKAGE, $cases);
            $this->assertContains(BuildType::PHP_PACKAGE, $cases);
            $this->assertContains(BuildType::PHAR_PACKAGE, $cases);
            $this->assertContains(BuildType::NATIVE, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $type1 = BuildType::NCC_PACKAGE;
            $type2 = BuildType::NCC_PACKAGE;
            $type3 = BuildType::PHP_PACKAGE;

            $this->assertSame($type1, $type2);
            $this->assertNotSame($type1, $type3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('NCC_PACKAGE', BuildType::NCC_PACKAGE->name);
            $this->assertEquals('PHP_PACKAGE', BuildType::PHP_PACKAGE->name);
            $this->assertEquals('PHAR_PACKAGE', BuildType::PHAR_PACKAGE->name);
            $this->assertEquals('NATIVE', BuildType::NATIVE->name);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (BuildType::NCC_PACKAGE) {
                BuildType::NCC_PACKAGE => 'ncc_build',
                BuildType::PHP_PACKAGE => 'php_build',
                BuildType::PHAR_PACKAGE => 'phar_build',
                BuildType::NATIVE => 'native_build',
            };

            $this->assertEquals('ncc_build', $result);
        }
    }
