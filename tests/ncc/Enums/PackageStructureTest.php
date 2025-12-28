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

    use ncc\Enums\PackageStructure;
    use PHPUnit\Framework\TestCase;

    class PackageStructureTest extends TestCase
    {
        /**
         * Test that all enum cases have expected byte values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals("\xA0", PackageStructure::START_PACKAGE->value);
            $this->assertEquals("\x4E\x43\x43\x50\x4B\x47", PackageStructure::MAGIC_BYTES->value);
            $this->assertEquals("\xA1", PackageStructure::PACKAGE_VERSION->value);
            $this->assertEquals("\xA2", PackageStructure::HEADER->value);
            $this->assertEquals("\xA3", PackageStructure::ASSEMBLY->value);
            $this->assertEquals("\xA4", PackageStructure::EXECUTION_UNITS->value);
            $this->assertEquals("\xA5", PackageStructure::COMPONENTS->value);
            $this->assertEquals("\xA6", PackageStructure::RESOURCES->value);
            $this->assertEquals("\xE0\xE0", PackageStructure::TERMINATE->value);
            $this->assertEquals("\xE1", PackageStructure::SOFT_TERMINATE->value);
        }

        /**
         * Test that MAGIC_BYTES represents "NCCPKG"
         */
        public function testMagicBytes(): void
        {
            $magicBytes = PackageStructure::MAGIC_BYTES->value;
            $this->assertEquals('NCCPKG', $magicBytes);
            $this->assertEquals(6, strlen($magicBytes));
        }

        /**
         * Test that enum can be constructed from byte values
         */
        public function testFromString(): void
        {
            $this->assertSame(PackageStructure::START_PACKAGE, PackageStructure::from("\xA0"));
            $this->assertSame(PackageStructure::MAGIC_BYTES, PackageStructure::from("\x4E\x43\x43\x50\x4B\x47"));
            $this->assertSame(PackageStructure::HEADER, PackageStructure::from("\xA2"));
            $this->assertSame(PackageStructure::TERMINATE, PackageStructure::from("\xE0\xE0"));
        }

        /**
         * Test that invalid byte values throw ValueError
         */
        public function testFromStringInvalid(): void
        {
            $this->expectException(\ValueError::class);
            PackageStructure::from("\xFF");
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(PackageStructure::START_PACKAGE, PackageStructure::tryFrom("\xA0"));
            $this->assertSame(PackageStructure::ASSEMBLY, PackageStructure::tryFrom("\xA3"));
            $this->assertSame(PackageStructure::SOFT_TERMINATE, PackageStructure::tryFrom("\xE1"));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(PackageStructure::tryFrom("\xFF"));
            $this->assertNull(PackageStructure::tryFrom(''));
            $this->assertNull(PackageStructure::tryFrom('invalid'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = PackageStructure::cases();
            
            $this->assertCount(10, $cases);
            $this->assertContains(PackageStructure::START_PACKAGE, $cases);
            $this->assertContains(PackageStructure::MAGIC_BYTES, $cases);
            $this->assertContains(PackageStructure::PACKAGE_VERSION, $cases);
            $this->assertContains(PackageStructure::HEADER, $cases);
            $this->assertContains(PackageStructure::ASSEMBLY, $cases);
            $this->assertContains(PackageStructure::EXECUTION_UNITS, $cases);
            $this->assertContains(PackageStructure::COMPONENTS, $cases);
            $this->assertContains(PackageStructure::RESOURCES, $cases);
            $this->assertContains(PackageStructure::TERMINATE, $cases);
            $this->assertContains(PackageStructure::SOFT_TERMINATE, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $struct1 = PackageStructure::HEADER;
            $struct2 = PackageStructure::HEADER;
            $struct3 = PackageStructure::ASSEMBLY;

            $this->assertSame($struct1, $struct2);
            $this->assertNotSame($struct1, $struct3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('START_PACKAGE', PackageStructure::START_PACKAGE->name);
            $this->assertEquals('MAGIC_BYTES', PackageStructure::MAGIC_BYTES->name);
            $this->assertEquals('HEADER', PackageStructure::HEADER->name);
            $this->assertEquals('TERMINATE', PackageStructure::TERMINATE->name);
        }

        /**
         * Test that byte values are distinct
         */
        public function testByteValuesAreUnique(): void
        {
            $cases = PackageStructure::cases();
            $values = array_map(fn($case) => $case->value, $cases);
            
            $this->assertCount(count($cases), array_unique($values));
        }
    }
