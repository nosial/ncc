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

    use ncc\Enums\AuthenticationType;
    use PHPUnit\Framework\TestCase;

    class AuthenticationTypeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected integer values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals(1, AuthenticationType::USERNAME_PASSWORD->value);
            $this->assertEquals(2, AuthenticationType::ACCESS_TOKEN->value);
        }

        /**
         * Test that enum can be constructed from integer values
         */
        public function testFromInt(): void
        {
            $this->assertSame(AuthenticationType::USERNAME_PASSWORD, AuthenticationType::from(1));
            $this->assertSame(AuthenticationType::ACCESS_TOKEN, AuthenticationType::from(2));
        }

        /**
         * Test that invalid integer values throw ValueError
         */
        public function testFromIntInvalid(): void
        {
            $this->expectException(\ValueError::class);
            AuthenticationType::from(999);
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(AuthenticationType::USERNAME_PASSWORD, AuthenticationType::tryFrom(1));
            $this->assertSame(AuthenticationType::ACCESS_TOKEN, AuthenticationType::tryFrom(2));
        }

        /**
         * Test tryFrom with invalid values returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(AuthenticationType::tryFrom(0));
            $this->assertNull(AuthenticationType::tryFrom(3));
            $this->assertNull(AuthenticationType::tryFrom(999));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = AuthenticationType::cases();
            
            $this->assertCount(2, $cases);
            $this->assertContains(AuthenticationType::USERNAME_PASSWORD, $cases);
            $this->assertContains(AuthenticationType::ACCESS_TOKEN, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $type1 = AuthenticationType::USERNAME_PASSWORD;
            $type2 = AuthenticationType::USERNAME_PASSWORD;
            $type3 = AuthenticationType::ACCESS_TOKEN;

            $this->assertSame($type1, $type2);
            $this->assertNotSame($type1, $type3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('USERNAME_PASSWORD', AuthenticationType::USERNAME_PASSWORD->name);
            $this->assertEquals('ACCESS_TOKEN', AuthenticationType::ACCESS_TOKEN->name);
        }

        /**
         * Test enum in switch statement
         */
        public function testEnumInSwitch(): void
        {
            $result = match (AuthenticationType::USERNAME_PASSWORD) {
                AuthenticationType::USERNAME_PASSWORD => 'credentials',
                AuthenticationType::ACCESS_TOKEN => 'token',
            };

            $this->assertEquals('credentials', $result);
        }
    }
