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

    use ncc\Enums\WritingMode;
    use PHPUnit\Framework\TestCase;

    class WritingModeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('header', WritingMode::HEADER->value);
            $this->assertEquals('assembly', WritingMode::ASSEMBLY->value);
            $this->assertEquals('execution_units', WritingMode::EXECUTION_UNITS->value);
            $this->assertEquals('components', WritingMode::COMPONENTS->value);
            $this->assertEquals('resources', WritingMode::RESOURCES->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(WritingMode::HEADER, WritingMode::from('header'));
            $this->assertSame(WritingMode::ASSEMBLY, WritingMode::from('assembly'));
            $this->assertSame(WritingMode::EXECUTION_UNITS, WritingMode::from('execution_units'));
            $this->assertSame(WritingMode::COMPONENTS, WritingMode::from('components'));
            $this->assertSame(WritingMode::RESOURCES, WritingMode::from('resources'));
        }

        /**
         * Test that invalid string values throw ValueError
         */
        public function testFromStringInvalid(): void
        {
            $this->expectException(\ValueError::class);
            WritingMode::from('invalid');
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(WritingMode::HEADER, WritingMode::tryFrom('header'));
            $this->assertSame(WritingMode::ASSEMBLY, WritingMode::tryFrom('assembly'));
            $this->assertSame(WritingMode::EXECUTION_UNITS, WritingMode::tryFrom('execution_units'));
            $this->assertSame(WritingMode::COMPONENTS, WritingMode::tryFrom('components'));
            $this->assertSame(WritingMode::RESOURCES, WritingMode::tryFrom('resources'));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(WritingMode::tryFrom('invalid'));
            $this->assertNull(WritingMode::tryFrom(''));
            $this->assertNull(WritingMode::tryFrom('HEADER'));
            $this->assertNull(WritingMode::tryFrom('data'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = WritingMode::cases();
            
            $this->assertCount(5, $cases);
            $this->assertContains(WritingMode::HEADER, $cases);
            $this->assertContains(WritingMode::ASSEMBLY, $cases);
            $this->assertContains(WritingMode::EXECUTION_UNITS, $cases);
            $this->assertContains(WritingMode::COMPONENTS, $cases);
            $this->assertContains(WritingMode::RESOURCES, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $mode1 = WritingMode::HEADER;
            $mode2 = WritingMode::HEADER;
            $mode3 = WritingMode::ASSEMBLY;

            $this->assertSame($mode1, $mode2);
            $this->assertNotSame($mode1, $mode3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('HEADER', WritingMode::HEADER->name);
            $this->assertEquals('ASSEMBLY', WritingMode::ASSEMBLY->name);
            $this->assertEquals('EXECUTION_UNITS', WritingMode::EXECUTION_UNITS->name);
            $this->assertEquals('COMPONENTS', WritingMode::COMPONENTS->name);
            $this->assertEquals('RESOURCES', WritingMode::RESOURCES->name);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (WritingMode::HEADER) {
                WritingMode::HEADER => 'writing_header',
                WritingMode::ASSEMBLY => 'writing_assembly',
                WritingMode::EXECUTION_UNITS => 'writing_units',
                WritingMode::COMPONENTS => 'writing_components',
                WritingMode::RESOURCES => 'writing_resources',
            };

            $this->assertEquals('writing_header', $result);
        }
    }
