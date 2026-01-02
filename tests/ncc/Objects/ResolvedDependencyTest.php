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

    use PHPUnit\Framework\TestCase;

    class ResolvedDependencyTest extends TestCase
    {
        /**
         * Test constructor with valid package source
         */
        public function testConstructorWithValidPackageSource(): void
        {
            $packageSource = PackageSource::fromArray([
                'organization' => 'test-org',
                'name' => 'test-package',
                'version' => '1.0.0',
                'repository' => 'test-repo'
            ]);

            $resolvedDep = new ResolvedDependency('test-org/test-package', $packageSource);

            $this->assertInstanceOf(ResolvedDependency::class, $resolvedDep);
            $this->assertEquals('test-org/test-package', $resolvedDep->getPackage());
            $this->assertInstanceOf(PackageSource::class, $resolvedDep->getPackageSource());
        }

        /**
         * Test getPackage returns correct package name
         */
        public function testGetPackage(): void
        {
            $packageSource = PackageSource::fromArray([
                'organization' => 'vendor',
                'name' => 'my-lib',
                'version' => '2.0.0',
                'repository' => 'main'
            ]);

            $resolvedDep = new ResolvedDependency('vendor/my-lib', $packageSource);

            $this->assertEquals('vendor/my-lib', $resolvedDep->getPackage());
        }

        /**
         * Test getPackageSource returns correct PackageSource instance
         */
        public function testGetPackageSource(): void
        {
            $packageSource = PackageSource::fromArray([
                'organization' => 'test',
                'name' => 'package',
                'version' => '3.0.0',
                'repository' => 'repo'
            ]);

            $resolvedDep = new ResolvedDependency('test/package', $packageSource);

            $retrievedSource = $resolvedDep->getPackageSource();
            $this->assertInstanceOf(PackageSource::class, $retrievedSource);
            $this->assertEquals('test', $retrievedSource->getOrganization());
            $this->assertEquals('package', $retrievedSource->getName());
            $this->assertEquals('3.0.0', $retrievedSource->getVersion());
        }

        /**
         * Test getPackageReader returns null when package is not installed
         */
        public function testGetPackageReaderReturnsNullForNonInstalledPackage(): void
        {
            $packageSource = PackageSource::fromArray([
                'organization' => 'non-existent',
                'name' => 'not-installed',
                'version' => '1.0.0',
                'repository' => 'test'
            ]);

            $resolvedDep = new ResolvedDependency('non-existent/not-installed', $packageSource);

            // Package reader should be null since the package is not installed
            $this->assertNull($resolvedDep->getPackageReader());
        }

        /**
         * Test with latest version
         */
        public function testConstructorWithLatestVersion(): void
        {
            $packageSource = PackageSource::fromArray([
                'organization' => 'org',
                'name' => 'pkg',
                'version' => 'latest',
                'repository' => 'repo'
            ]);

            $resolvedDep = new ResolvedDependency('org/pkg', $packageSource);

            $this->assertEquals('org/pkg', $resolvedDep->getPackage());
            $this->assertEquals('latest', $resolvedDep->getPackageSource()->getVersion());
        }

        /**
         * Test with different package name formats
         */
        public function testWithDifferentPackageNameFormats(): void
        {
            $testCases = [
                'simple/package',
                'vendor/my-package',
                'org/package_name',
                'company/package.name'
            ];

            foreach ($testCases as $packageName) {
                $parts = explode('/', $packageName);
                $packageSource = PackageSource::fromArray([
                    'organization' => $parts[0],
                    'name' => $parts[1],
                    'version' => '1.0.0',
                    'repository' => 'test'
                ]);

                $resolvedDep = new ResolvedDependency($packageName, $packageSource);

                $this->assertEquals($packageName, $resolvedDep->getPackage());
            }
        }

        /**
         * Test with various version formats
         */
        public function testWithVariousVersionFormats(): void
        {
            $versions = [
                '1.0.0',
                '2.0.0-alpha',
                '3.0.0-beta.1',
                'v4.5.6',
                'latest',
                '0.0.1-dev'
            ];

            foreach ($versions as $version) {
                $packageSource = PackageSource::fromArray([
                    'organization' => 'test',
                    'name' => 'pkg',
                    'version' => $version,
                    'repository' => 'repo'
                ]);

                $resolvedDep = new ResolvedDependency('test/pkg', $packageSource);

                $this->assertEquals($version, $resolvedDep->getPackageSource()->getVersion());
            }
        }

        /**
         * Test package source is correctly stored and retrievable
         */
        public function testPackageSourceIntegrity(): void
        {
            $originalData = [
                'organization' => 'integrity-test',
                'name' => 'test-package',
                'version' => '5.0.0',
                'repository' => 'integrity-repo'
            ];

            $packageSource = PackageSource::fromArray($originalData);
            $resolvedDep = new ResolvedDependency('integrity-test/test-package', $packageSource);

            $retrieved = $resolvedDep->getPackageSource();
            
            $this->assertEquals($originalData['organization'], $retrieved->getOrganization());
            $this->assertEquals($originalData['name'], $retrieved->getName());
            $this->assertEquals($originalData['version'], $retrieved->getVersion());
            $this->assertEquals($originalData['repository'], $retrieved->getRepository());
        }
    }
