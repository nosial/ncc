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

    use ncc\Enums\RepositoryType;
    use PHPUnit\Framework\TestCase;

    class RepositoryTypeTest extends TestCase
    {
        /**
         * Test that all enum cases have expected string values
         */
        public function testEnumValues(): void
        {
            $this->assertEquals('gitlab', RepositoryType::GITLAB->value);
            $this->assertEquals('github', RepositoryType::GITHUB->value);
            $this->assertEquals('gitea', RepositoryType::GITEA->value);
            $this->assertEquals('packagist', RepositoryType::PACKAGIST->value);
        }

        /**
         * Test that enum can be constructed from string values
         */
        public function testFromString(): void
        {
            $this->assertSame(RepositoryType::GITLAB, RepositoryType::from('gitlab'));
            $this->assertSame(RepositoryType::GITHUB, RepositoryType::from('github'));
            $this->assertSame(RepositoryType::GITEA, RepositoryType::from('gitea'));
            $this->assertSame(RepositoryType::PACKAGIST, RepositoryType::from('packagist'));
        }

        /**
         * Test tryFrom with valid values
         */
        public function testTryFromValid(): void
        {
            $this->assertSame(RepositoryType::GITLAB, RepositoryType::tryFrom('gitlab'));
            $this->assertSame(RepositoryType::GITHUB, RepositoryType::tryFrom('github'));
            $this->assertSame(RepositoryType::GITEA, RepositoryType::tryFrom('gitea'));
            $this->assertSame(RepositoryType::PACKAGIST, RepositoryType::tryFrom('packagist'));
        }

        /**
         * Test tryFrom with invalid value returns null
         */
        public function testTryFromInvalid(): void
        {
            $this->assertNull(RepositoryType::tryFrom('bitbucket'));
            $this->assertNull(RepositoryType::tryFrom(''));
            $this->assertNull(RepositoryType::tryFrom('GITHUB'));
            $this->assertNull(RepositoryType::tryFrom('svn'));
        }

        /**
         * Test that all cases are present
         */
        public function testAllCases(): void
        {
            $cases = RepositoryType::cases();
            
            $this->assertCount(4, $cases);
            $this->assertContains(RepositoryType::GITLAB, $cases);
            $this->assertContains(RepositoryType::GITHUB, $cases);
            $this->assertContains(RepositoryType::GITEA, $cases);
            $this->assertContains(RepositoryType::PACKAGIST, $cases);
        }

        /**
         * Test enum comparison
         */
        public function testEnumComparison(): void
        {
            $type1 = RepositoryType::GITHUB;
            $type2 = RepositoryType::GITHUB;
            $type3 = RepositoryType::GITLAB;

            $this->assertSame($type1, $type2);
            $this->assertNotSame($type1, $type3);
        }

        /**
         * Test enum name property
         */
        public function testEnumName(): void
        {
            $this->assertEquals('GITLAB', RepositoryType::GITLAB->name);
            $this->assertEquals('GITHUB', RepositoryType::GITHUB->name);
            $this->assertEquals('GITEA', RepositoryType::GITEA->name);
            $this->assertEquals('PACKAGIST', RepositoryType::PACKAGIST->name);
        }
    }
