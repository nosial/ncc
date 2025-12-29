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
    use ncc\Objects\Package\DependencyReference;
    use PHPUnit\Framework\TestCase;

    class PackageLockEntryTest extends TestCase
    {
        /**
         * Test constructor with valid data including dependencies
         */
        public function testConstructorWithValidData(): void
        {
            $data = [
                'package' => 'org/test-package',
                'version' => '1.2.3',
                'dependencies' => [
                    ['package' => 'org/dep1', 'version' => '1.0.0'],
                    ['package' => 'org/dep2', 'version' => '2.0.0']
                ]
            ];

            $entry = new PackageLockEntry($data);

            $this->assertEquals('org/test-package', $entry->getPackage());
            $this->assertEquals('1.2.3', $entry->getVersion());
            $this->assertIsArray($entry->getDependencies());
            $this->assertCount(2, $entry->getDependencies());
            $this->assertInstanceOf(DependencyReference::class, $entry->getDependencies()[0]);
            $this->assertInstanceOf(DependencyReference::class, $entry->getDependencies()[1]);
        }

        /**
         * Test constructor with minimal data (no dependencies)
         */
        public function testConstructorWithMinimalData(): void
        {
            $data = [
                'package' => 'org/simple-package',
                'version' => '0.1.0'
            ];

            $entry = new PackageLockEntry($data);

            $this->assertEquals('org/simple-package', $entry->getPackage());
            $this->assertEquals('0.1.0', $entry->getVersion());
            $this->assertIsArray($entry->getDependencies());
            $this->assertCount(0, $entry->getDependencies());
        }

        /**
         * Test constructor throws exception when package name is missing
         */
        public function testConstructorThrowsExceptionWithoutPackage(): void
        {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Package name is required');

            new PackageLockEntry(['version' => '1.0.0']);
        }

        /**
         * Test constructor throws exception when version is missing
         */
        public function testConstructorThrowsExceptionWithoutVersion(): void
        {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Package version is required');

            new PackageLockEntry(['package' => 'org/test']);
        }

        /**
         * Test getPackage returns correct value
         */
        public function testGetPackage(): void
        {
            $entry = new PackageLockEntry([
                'package' => 'vendor/my-package',
                'version' => '2.0.0'
            ]);

            $this->assertEquals('vendor/my-package', $entry->getPackage());
        }

        /**
         * Test getVersion returns correct value
         */
        public function testGetVersion(): void
        {
            $entry = new PackageLockEntry([
                'package' => 'vendor/package',
                'version' => '3.14.159'
            ]);

            $this->assertEquals('3.14.159', $entry->getVersion());
        }

        /**
         * Test toArray returns correct structure
         */
        public function testToArray(): void
        {
            $data = [
                'package' => 'org/test',
                'version' => '1.0.0',
                'dependencies' => [
                    ['package' => 'org/dep1', 'version' => '1.0.0']
                ]
            ];

            $entry = new PackageLockEntry($data);
            $result = $entry->toArray();

            $this->assertIsArray($result);
            $this->assertEquals('org/test', $result['package']);
            $this->assertEquals('1.0.0', $result['version']);
            $this->assertIsArray($result['dependencies']);
            $this->assertCount(1, $result['dependencies']);
        }

        /**
         * Test fromArray static constructor
         */
        public function testFromArray(): void
        {
            $data = [
                'package' => 'org/from-array-test',
                'version' => '5.0.0',
                'dependencies' => []
            ];

            $entry = PackageLockEntry::fromArray($data);

            $this->assertInstanceOf(PackageLockEntry::class, $entry);
            $this->assertEquals('org/from-array-test', $entry->getPackage());
            $this->assertEquals('5.0.0', $entry->getVersion());
        }

        /**
         * Test __toString returns correct format
         */
        public function testToString(): void
        {
            $entry = new PackageLockEntry([
                'package' => 'vendor/package',
                'version' => '1.2.3'
            ]);

            $this->assertEquals('vendor/package=1.2.3', (string)$entry);
        }

        /**
         * Test toArray and fromArray round-trip conversion
         */
        public function testRoundTripConversion(): void
        {
            $originalData = [
                'package' => 'org/roundtrip',
                'version' => '10.5.2',
                'dependencies' => [
                    ['package' => 'org/dep1', 'version' => '1.0.0'],
                    ['package' => 'org/dep2', 'version' => '2.0.0']
                ]
            ];

            $entry = new PackageLockEntry($originalData);
            $arrayData = $entry->toArray();
            $newEntry = PackageLockEntry::fromArray($arrayData);

            $this->assertEquals($entry->getPackage(), $newEntry->getPackage());
            $this->assertEquals($entry->getVersion(), $newEntry->getVersion());
            $this->assertCount(count($entry->getDependencies()), $newEntry->getDependencies());
        }

        /**
         * Test with complex version strings
         */
        public function testComplexVersionStrings(): void
        {
            $versions = [
                '1.0.0-alpha',
                '2.0.0-beta.1',
                '3.0.0-rc.2',
                'v4.5.6',
                '0.0.1-dev+20240101'
            ];

            foreach ($versions as $version) {
                $entry = new PackageLockEntry([
                    'package' => 'test/package',
                    'version' => $version
                ]);

                $this->assertEquals($version, $entry->getVersion());
                $this->assertStringContainsString($version, (string)$entry);
            }
        }

        /**
         * Test with various package name formats
         */
        public function testVariousPackageNameFormats(): void
        {
            $packages = [
                'vendor/package',
                'org/my-package',
                'company/package-name',
                'user/package_name',
                'org/package.name',
                'vendor/UPPERCASE'
            ];

            foreach ($packages as $package) {
                $entry = new PackageLockEntry([
                    'package' => $package,
                    'version' => '1.0.0'
                ]);

                $this->assertEquals($package, $entry->getPackage());
                $this->assertStringContainsString($package, (string)$entry);
            }
        }
    }
