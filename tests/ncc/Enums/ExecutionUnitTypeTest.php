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

    use ncc\Enums\ExecutionUnitType;
    use PHPUnit\Framework\TestCase;

    class ExecutionUnitTypeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('php', ExecutionUnitType::PHP->value);
            $this->assertEquals('web', ExecutionUnitType::WEB->value);
            $this->assertEquals('system', ExecutionUnitType::SYSTEM->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(ExecutionUnitType::PHP, ExecutionUnitType::from('php'));
            $this->assertSame(ExecutionUnitType::WEB, ExecutionUnitType::from('web'));
            $this->assertSame(ExecutionUnitType::SYSTEM, ExecutionUnitType::from('system'));
        }

        /**
         * Test that invalid string values throw ValueError
         */
        public function testFromStringInvalid(): void
        {
            $this->expectException(\ValueError::class);
            ExecutionUnitType::from('invalid');
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(ExecutionUnitType::PHP, ExecutionUnitType::tryFrom('php'));
            $this->assertSame(ExecutionUnitType::WEB, ExecutionUnitType::tryFrom('web'));
            $this->assertSame(ExecutionUnitType::SYSTEM, ExecutionUnitType::tryFrom('system'));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(ExecutionUnitType::tryFrom('invalid'));
            $this->assertNull(ExecutionUnitType::tryFrom(''));
            $this->assertNull(ExecutionUnitType::tryFrom('PHP'));
            $this->assertNull(ExecutionUnitType::tryFrom('cli'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = ExecutionUnitType::cases();
            
            $this->assertCount(3, $cases);
            $this->assertContains(ExecutionUnitType::PHP, $cases);
            $this->assertContains(ExecutionUnitType::WEB, $cases);
            $this->assertContains(ExecutionUnitType::SYSTEM, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $type1 = ExecutionUnitType::PHP;
            $type2 = ExecutionUnitType::PHP;
            $type3 = ExecutionUnitType::WEB;

            $this->assertSame($type1, $type2);
            $this->assertNotSame($type1, $type3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('PHP', ExecutionUnitType::PHP->name);
            $this->assertEquals('WEB', ExecutionUnitType::WEB->name);
            $this->assertEquals('SYSTEM', ExecutionUnitType::SYSTEM->name);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (ExecutionUnitType::PHP) {
                ExecutionUnitType::PHP => 'php_unit',
                ExecutionUnitType::WEB => 'web_unit',
                ExecutionUnitType::SYSTEM => 'system_unit',
            };

            $this->assertEquals('php_unit', $result);
        }
    }
