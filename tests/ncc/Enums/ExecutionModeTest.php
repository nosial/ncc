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

    use ncc\Enums\ExecutionMode;
    use PHPUnit\Framework\TestCase;

    class ExecutionModeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('auto', ExecutionMode::AUTO->value);
            $this->assertEquals('tty', ExecutionMode::TTY->value);
            $this->assertEquals('pty', ExecutionMode::PTY->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(ExecutionMode::AUTO, ExecutionMode::from('auto'));
            $this->assertSame(ExecutionMode::TTY, ExecutionMode::from('tty'));
            $this->assertSame(ExecutionMode::PTY, ExecutionMode::from('pty'));
        }

        /**
         * Test that invalid string values throw ValueError
         */
        public function testFromStringInvalid(): void
        {
            $this->expectException(\ValueError::class);
            ExecutionMode::from('invalid');
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(ExecutionMode::AUTO, ExecutionMode::tryFrom('auto'));
            $this->assertSame(ExecutionMode::TTY, ExecutionMode::tryFrom('tty'));
            $this->assertSame(ExecutionMode::PTY, ExecutionMode::tryFrom('pty'));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(ExecutionMode::tryFrom('invalid'));
            $this->assertNull(ExecutionMode::tryFrom(''));
            $this->assertNull(ExecutionMode::tryFrom('AUTO'));
            $this->assertNull(ExecutionMode::tryFrom('shell'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = ExecutionMode::cases();
            
            $this->assertCount(3, $cases);
            $this->assertContains(ExecutionMode::AUTO, $cases);
            $this->assertContains(ExecutionMode::TTY, $cases);
            $this->assertContains(ExecutionMode::PTY, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $mode1 = ExecutionMode::AUTO;
            $mode2 = ExecutionMode::AUTO;
            $mode3 = ExecutionMode::TTY;

            $this->assertSame($mode1, $mode2);
            $this->assertNotSame($mode1, $mode3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('AUTO', ExecutionMode::AUTO->name);
            $this->assertEquals('TTY', ExecutionMode::TTY->name);
            $this->assertEquals('PTY', ExecutionMode::PTY->name);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (ExecutionMode::AUTO) {
                ExecutionMode::AUTO => 'auto_mode',
                ExecutionMode::TTY => 'tty_mode',
                ExecutionMode::PTY => 'pty_mode',
            };

            $this->assertEquals('auto_mode', $result);
        }
    }
