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

    use ncc\Enums\RemotePackageType;
    use ncc\Objects\RemotePackage;
    use PHPUnit\Framework\TestCase;

    class RemotePackageTest extends TestCase
    {
        /**
         * Test creating a RemotePackage with all parameters
         */
        public function testCreateWithAllParameters(): void
        {
            $downloadUrl = 'https://example.com/package.ncc';
            $type = RemotePackageType::NCC;
            $group = 'com.example';
            $project = 'test_project';
            $version = '1.0.0';

            $package = new RemotePackage($downloadUrl, $type, $group, $project, $version);

            $this->assertEquals($downloadUrl, $package->getDownloadUrl());
            $this->assertSame($type, $package->getType());
            $this->assertEquals($group, $package->getGroup());
            $this->assertEquals($project, $package->getProject());
            $this->assertEquals($version, $package->getVersion());
        }

        /**
         * Test creating a RemotePackage without version
         */
        public function testCreateWithoutVersion(): void
        {
            $downloadUrl = 'https://example.com/package.zip';
            $type = RemotePackageType::SOURCE_ZIP;
            $group = 'com.example';
            $project = 'test_project';

            $package = new RemotePackage($downloadUrl, $type, $group, $project);

            $this->assertEquals($downloadUrl, $package->getDownloadUrl());
            $this->assertSame($type, $package->getType());
            $this->assertEquals($group, $package->getGroup());
            $this->assertEquals($project, $package->getProject());
            $this->assertNull($package->getVersion());
        }

        /**
         * Test getDownloadUrl returns correct URL
         */
        public function testGetDownloadUrl(): void
        {
            $url = 'https://github.com/user/repo/archive/v1.0.0.tar.gz';
            $package = new RemotePackage($url, RemotePackageType::SOURCE_TAR, 'com.github', 'repo', '1.0.0');

            $this->assertEquals($url, $package->getDownloadUrl());
        }

        /**
         * Test getType returns correct RemotePackageType
         */
        public function testGetType(): void
        {
            $package = new RemotePackage(
                'https://example.com/repo.git',
                RemotePackageType::SOURCE_GIT,
                'com.example',
                'project'
            );

            $this->assertSame(RemotePackageType::SOURCE_GIT, $package->getType());
        }

        /**
         * Test getGroup returns correct group
         */
        public function testGetGroup(): void
        {
            $group = 'com.mycompany.tools';
            $package = new RemotePackage(
                'https://example.com/package.ncc',
                RemotePackageType::NCC,
                $group,
                'utility'
            );

            $this->assertEquals($group, $package->getGroup());
        }

        /**
         * Test getProject returns correct project name
         */
        public function testGetProject(): void
        {
            $project = 'awesome_library';
            $package = new RemotePackage(
                'https://example.com/package.zip',
                RemotePackageType::SOURCE_ZIP,
                'com.example',
                $project
            );

            $this->assertEquals($project, $package->getProject());
        }

        /**
         * Test getVersion returns correct version
         */
        public function testGetVersion(): void
        {
            $version = '2.3.4-beta';
            $package = new RemotePackage(
                'https://example.com/package.ncc',
                RemotePackageType::NCC,
                'com.example',
                'project',
                $version
            );

            $this->assertEquals($version, $package->getVersion());
        }

        /**
         * Test creating packages with different RemotePackageType values
         */
        public function testDifferentPackageTypes(): void
        {
            $types = [
                RemotePackageType::NCC,
                RemotePackageType::SOURCE_ZIP,
                RemotePackageType::SOURCE_TAR,
                RemotePackageType::SOURCE_GIT
            ];

            foreach ($types as $type) {
                $package = new RemotePackage(
                    'https://example.com/package',
                    $type,
                    'com.test',
                    'project'
                );

                $this->assertSame($type, $package->getType());
            }
        }

        /**
         * Test package with special characters in URL
         */
        public function testSpecialCharactersInUrl(): void
        {
            $url = 'https://example.com/path/to/package?version=1.0&format=ncc';
            $package = new RemotePackage(
                $url,
                RemotePackageType::NCC,
                'com.example',
                'project'
            );

            $this->assertEquals($url, $package->getDownloadUrl());
        }

        /**
         * Test package with version containing special characters
         */
        public function testVersionWithSpecialCharacters(): void
        {
            $version = '1.0.0-alpha+build.123';
            $package = new RemotePackage(
                'https://example.com/package.ncc',
                RemotePackageType::NCC,
                'com.example',
                'project',
                $version
            );

            $this->assertEquals($version, $package->getVersion());
        }
    }
