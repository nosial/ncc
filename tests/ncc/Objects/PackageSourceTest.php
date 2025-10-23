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

    namespace ncc\Objects;

    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class PackageSourceTest extends TestCase
    {
        /**
         * Test successful construction with valid package strings
         */
        public function testConstructWithValidPackageStrings(): void
        {
            // Test with version specified
            $packageSource = new PackageSource('nosial/loglib=1.0.0@default');
            $this->assertEquals('nosial', $packageSource->getOrganization());
            $this->assertEquals('loglib', $packageSource->getName());
            $this->assertEquals('1.0.0', $packageSource->getVersion());
            $this->assertEquals('default', $packageSource->getRepository());

            // Test without version (should default to 'latest')
            $packageSource2 = new PackageSource('nosial/utilities@main');
            $this->assertEquals('nosial', $packageSource2->getOrganization());
            $this->assertEquals('utilities', $packageSource2->getName());
            $this->assertEquals('latest', $packageSource2->getVersion());
            $this->assertEquals('main', $packageSource2->getRepository());

            // Test with complex names and versions
            $packageSource3 = new PackageSource('my-org/my-package.name=v2.1.0-alpha@my-repo');
            $this->assertEquals('my-org', $packageSource3->getOrganization());
            $this->assertEquals('my-package.name', $packageSource3->getName());
            $this->assertEquals('v2.1.0-alpha', $packageSource3->getVersion());
            $this->assertEquals('my-repo', $packageSource3->getRepository());
        }

        /**
         * Test construction fails with invalid package strings
         */
        public function testConstructWithInvalidPackageStrings(): void
        {
            $invalidStrings = [
                '', // Empty string
                'invalid', // Missing organization/name separator
                'org/', // Missing name
                '/name', // Missing organization
                'org/name', // Missing repository
                'org/name@', // Empty repository
                '@repo', // Missing org/name
                'org name@repo', // Space in organization
                'org/na me@repo', // Space in name
                'org/name@re po', // Space in repository
                'org//name@repo', // Double slash
                'org/name@@repo', // Double @
                '123org/name@repo', // Organization starts with number (invalid pattern)
            ];

            foreach ($invalidStrings as $invalidString) {
                try {
                    new PackageSource($invalidString);
                    $this->fail("Expected InvalidArgumentException for string: '$invalidString'");
                } catch (InvalidArgumentException $e) {
                    $this->assertEquals('Invalid package string', $e->getMessage());
                }
            }
        }

        /**
         * Test edge cases for valid package strings
         */
        public function testConstructWithEdgeCases(): void
        {
            // Test with numbers in names
            $packageSource = new PackageSource('org123/package123=1.2.3@repo456');
            $this->assertEquals('org123', $packageSource->getOrganization());
            $this->assertEquals('package123', $packageSource->getName());
            $this->assertEquals('1.2.3', $packageSource->getVersion());
            $this->assertEquals('repo456', $packageSource->getRepository());

            // Test with hyphens and underscores
            $packageSource2 = new PackageSource('my_org/my-package_name=1.0.0@my-repo_name');
            $this->assertEquals('my_org', $packageSource2->getOrganization());
            $this->assertEquals('my-package_name', $packageSource2->getName());
            $this->assertEquals('1.0.0', $packageSource2->getVersion());
            $this->assertEquals('my-repo_name', $packageSource2->getRepository());

            // Test with dots
            $packageSource3 = new PackageSource('org.example/package.name=2.0.0@repo.example');
            $this->assertEquals('org.example', $packageSource3->getOrganization());
            $this->assertEquals('package.name', $packageSource3->getName());
            $this->assertEquals('2.0.0', $packageSource3->getVersion());
            $this->assertEquals('repo.example', $packageSource3->getRepository());

            // Test with empty version (should default to 'latest')
            $packageSource4 = new PackageSource('org/name=@repo');
            $this->assertEquals('org', $packageSource4->getOrganization());
            $this->assertEquals('name', $packageSource4->getName());
            $this->assertEquals('latest', $packageSource4->getVersion());
            $this->assertEquals('repo', $packageSource4->getRepository());
        }

        /**
         * Test getOrganization method
         */
        public function testGetOrganization(): void
        {
            $packageSource = new PackageSource('test-org/test-package=1.0.0@test-repo');
            $this->assertEquals('test-org', $packageSource->getOrganization());
            $this->assertIsString($packageSource->getOrganization());
        }

        /**
         * Test getName method
         */
        public function testGetName(): void
        {
            $packageSource = new PackageSource('test-org/test-package=1.0.0@test-repo');
            $this->assertEquals('test-package', $packageSource->getName());
            $this->assertIsString($packageSource->getName());
        }

        /**
         * Test getVersion method
         */
        public function testGetVersion(): void
        {
            $packageSource = new PackageSource('test-org/test-package=1.0.0@test-repo');
            $this->assertEquals('1.0.0', $packageSource->getVersion());
            $this->assertIsString($packageSource->getVersion());

            // Test default version when not specified
            $packageSource2 = new PackageSource('test-org/test-package@test-repo');
            $this->assertEquals('latest', $packageSource2->getVersion());
        }

        /**
         * Test getRepository method
         */
        public function testGetRepository(): void
        {
            $packageSource = new PackageSource('test-org/test-package=1.0.0@test-repo');
            $this->assertEquals('test-repo', $packageSource->getRepository());
            $this->assertIsString($packageSource->getRepository());
        }

        /**
         * Test __toString method
         */
        public function testToString(): void
        {
            // Test with explicit version
            $packageSource = new PackageSource('nosial/loglib=1.0.0@default');
            $this->assertEquals('nosial/loglib=1.0.0@default', (string)$packageSource);

            // Test with latest version (should omit version in string representation)
            $packageSource2 = new PackageSource('nosial/utilities@main');
            $this->assertEquals('nosial/utilities@main', (string)$packageSource2);

            // Test with explicit 'latest' version (should omit version)
            $packageSource3 = new PackageSource('nosial/test=latest@repo');
            $this->assertEquals('nosial/test@repo', (string)$packageSource3);

            // Ensure __toString returns the same format that can be parsed back
            $originalString = 'my-org/my-package=2.1.0@my-repo';
            $packageSource4 = new PackageSource($originalString);
            $this->assertEquals($originalString, (string)$packageSource4);
        }

        /**
         * Test that parsing and string conversion are reversible
         */
        public function testParseAndStringConversionReversibility(): void
        {
            $testStrings = [
                'nosial/loglib=1.0.0@default',
                'my-org/my-package=v2.1.0-alpha@my-repo',
                'org.example/package.name=2.0.0@repo.example',
                'my_org/my-package_name=1.0.0@my-repo_name',
            ];

            foreach ($testStrings as $originalString) {
                $packageSource = new PackageSource($originalString);
                $reconstructedString = (string)$packageSource;
                $this->assertEquals($originalString, $reconstructedString);
            }
        }

        /**
         * Test that 'latest' version handling in string conversion
         */
        public function testLatestVersionStringConversion(): void
        {
            // When version is omitted, it defaults to 'latest' and should be omitted in string representation
            $packageSource = new PackageSource('nosial/package@repo');
            $this->assertEquals('latest', $packageSource->getVersion());
            $this->assertEquals('nosial/package@repo', (string)$packageSource);

            // When version is explicitly set to 'latest', it should also be omitted in string representation
            $packageSource2 = new PackageSource('nosial/package=latest@repo');
            $this->assertEquals('latest', $packageSource2->getVersion());
            $this->assertEquals('nosial/package@repo', (string)$packageSource2);
        }

        /**
         * Test boundary conditions for component lengths and characters
         */
        public function testBoundaryConditions(): void
        {
            // Test single character components
            $packageSource = new PackageSource('a/b=1@c');
            $this->assertEquals('a', $packageSource->getOrganization());
            $this->assertEquals('b', $packageSource->getName());
            $this->assertEquals('1', $packageSource->getVersion());
            $this->assertEquals('c', $packageSource->getRepository());

            // Test longer components
            $longOrg = str_repeat('a', 50);
            $longName = str_repeat('b', 50);
            $longVersion = str_repeat('1', 20);
            $longRepo = str_repeat('c', 50);
            
            $packageSource2 = new PackageSource("{$longOrg}/{$longName}={$longVersion}@{$longRepo}");
            $this->assertEquals($longOrg, $packageSource2->getOrganization());
            $this->assertEquals($longName, $packageSource2->getName());
            $this->assertEquals($longVersion, $packageSource2->getVersion());
            $this->assertEquals($longRepo, $packageSource2->getRepository());
        }

        /**
         * Test various version formats
         */
        public function testVersionFormats(): void
        {
            $versionFormats = [
                '1.0.0',
                'v1.0.0',
                '2.1.0-alpha',
                '3.0.0-beta.1',
                '1.0.0-rc.1',
                'main',
                'develop',
                'feature-branch',
                '20231201',
                'snapshot',
            ];

            foreach ($versionFormats as $version) {
                $packageSource = new PackageSource("org/package={$version}@repo");
                $this->assertEquals($version, $packageSource->getVersion());
                $this->assertEquals("org/package={$version}@repo", (string)$packageSource);
            }
        }
    }
