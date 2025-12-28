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

    namespace ncc\Objects\Package;

    use ncc\Objects\Package\DependencyReference;
    use ncc\Objects\PackageSource;
    use PHPUnit\Framework\TestCase;

    class DependencyReferenceTest extends TestCase
    {
        /**
         * Test creating a DependencyReference without source
         */
        public function testCreateWithoutSource(): void
        {
            $package = 'com.example.package';
            $version = '1.0.0';

            $dependency = new DependencyReference($package, $version);

            $this->assertEquals($package, $dependency->getPackage());
            $this->assertEquals($version, $dependency->getVersion());
            $this->assertNull($dependency->getSource());
        }

        /**
         * Test creating a DependencyReference with PackageSource
         */
        public function testCreateWithPackageSource(): void
        {
            $package = 'com.example.package';
            $version = '2.0.0';
            $source = new PackageSource('example/package=1.0.0@main');

            $dependency = new DependencyReference($package, $version, $source);

            $this->assertEquals($package, $dependency->getPackage());
            $this->assertEquals($version, $dependency->getVersion());
            $this->assertSame($source, $dependency->getSource());
        }

        /**
         * Test creating a DependencyReference with string source
         */
        public function testCreateWithStringSource(): void
        {
            $package = 'com.test.library';
            $version = '3.1.0';
            $sourceString = 'test/library=3.1.0@repo';

            $dependency = new DependencyReference($package, $version, $sourceString);

            $this->assertEquals($package, $dependency->getPackage());
            $this->assertEquals($version, $dependency->getVersion());
            $this->assertInstanceOf(PackageSource::class, $dependency->getSource());
        }

        /**
         * Test getPackage returns correct package name
         */
        public function testGetPackage(): void
        {
            $package = 'org.mycompany.tool';
            $dependency = new DependencyReference($package, '1.0.0');

            $this->assertEquals($package, $dependency->getPackage());
        }

        /**
         * Test getVersion returns correct version
         */
        public function testGetVersion(): void
        {
            $version = '1.2.3-beta';
            $dependency = new DependencyReference('com.example.app', $version);

            $this->assertEquals($version, $dependency->getVersion());
        }

        /**
         * Test getSource returns correct source
         */
        public function testGetSource(): void
        {
            $source = new PackageSource('example/package=1.0.0@repo');
            $dependency = new DependencyReference('com.example.pkg', '1.0.0', $source);

            $this->assertSame($source, $dependency->getSource());
        }

        /**
         * Test toArray method without source
         */
        public function testToArrayWithoutSource(): void
        {
            $package = 'com.example.package';
            $version = '1.0.0';
            $dependency = new DependencyReference($package, $version);

            $array = $dependency->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('package', $array);
            $this->assertArrayHasKey('version', $array);
            $this->assertArrayHasKey('source', $array);
            $this->assertEquals($package, $array['package']);
            $this->assertEquals($version, $array['version']);
            // When source is null, (string)null becomes empty string
            $this->assertEquals('', $array['source']);
        }

        /**
         * Test toArray method with source
         */
        public function testToArrayWithSource(): void
        {
            $package = 'com.example.package';
            $version = '2.0.0';
            $sourceString = 'example/package=2.0.0@repo';
            $source = new PackageSource($sourceString);
            $dependency = new DependencyReference($package, $version, $source);

            $array = $dependency->toArray();

            $this->assertArrayHasKey('source', $array);
            $this->assertIsString($array['source']);
        }

        /**
         * Test fromArray static method
         */
        public function testFromArray(): void
        {
            $data = [
                'package' => 'com.test.package',
                'version' => '1.5.0',
                'source' => 'test/package=1.5.0@repo'
            ];

            $dependency = DependencyReference::fromArray($data);

            $this->assertInstanceOf(DependencyReference::class, $dependency);
            $this->assertEquals($data['package'], $dependency->getPackage());
            $this->assertEquals($data['version'], $dependency->getVersion());
        }

        /**
         * Test serialization round trip
         */
        public function testSerializationRoundTrip(): void
        {
            $original = new DependencyReference(
                'com.example.original',
                '1.0.0',
                'example/original=1.0.0@repo'
            );

            $array = $original->toArray();
            $restored = DependencyReference::fromArray($array);

            $this->assertEquals($original->getPackage(), $restored->getPackage());
            $this->assertEquals($original->getVersion(), $restored->getVersion());
        }

        /**
         * Test with version range
         */
        public function testWithVersionRange(): void
        {
            $versionRange = '>=1.0.0 <2.0.0';
            $dependency = new DependencyReference('com.example.pkg', $versionRange);

            $this->assertEquals($versionRange, $dependency->getVersion());
        }

        /**
         * Test with wildcard version
         */
        public function testWithWildcardVersion(): void
        {
            $wildcardVersion = '1.*';
            $dependency = new DependencyReference('com.example.pkg', $wildcardVersion);

            $this->assertEquals($wildcardVersion, $dependency->getVersion());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $dep1 = new DependencyReference('com.example.pkg1', '1.0.0');
            $dep2 = new DependencyReference('com.example.pkg2', '2.0.0');

            $this->assertEquals('com.example.pkg1', $dep1->getPackage());
            $this->assertEquals('1.0.0', $dep1->getVersion());
            $this->assertEquals('com.example.pkg2', $dep2->getPackage());
            $this->assertEquals('2.0.0', $dep2->getVersion());
        }
    }
