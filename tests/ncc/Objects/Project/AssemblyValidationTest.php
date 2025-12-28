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

    namespace ncc\Objects\Project;

    use PHPUnit\Framework\TestCase;

    class AssemblyValidationTest extends TestCase
    {
        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidData(): void
        {
            $data = [
                'name' => 'my-app',
                'package' => 'com.example.myapp',
                'version' => '1.0.0',
                'uid' => '12345678-1234-1234-1234-123456789012'
            ];

            Assembly::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with minimal required data
         */
        public function testValidateArrayWithMinimalData(): void
        {
            $data = [
                'name' => 'app',
                'package' => 'com.test.app',
                'version' => '0.1.0'
            ];

            Assembly::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validate() method on instance
         */
        public function testValidateInstanceMethod(): void
        {
            $assembly = new Assembly([
                'name' => 'test-app',
                'package' => 'org.test.app',
                'version' => '2.0.0'
            ]);

            $assembly->validate();
            $this->assertTrue(true);
        }

        /**
         * Test validate with modified instance
         */
        public function testValidateModifiedInstance(): void
        {
            $assembly = new Assembly([
                'name' => 'app',
                'package' => 'com.test.app',
                'version' => '1.0.0'
            ]);

            $assembly->setVersion('1.0.1');
            $assembly->validate();

            $this->assertTrue(true);
        }

        /**
         * Test toArray and validate roundtrip
         */
        public function testToArrayAndValidateRoundtrip(): void
        {
            $data = [
                'name' => 'my-package',
                'package' => 'com.example.package',
                'version' => '3.2.1',
                'uid' => 'abcdef12-3456-7890-abcd-ef1234567890'
            ];

            $assembly = new Assembly($data);
            $array = $assembly->toArray();

            Assembly::validateArray($array);

            $restored = Assembly::fromArray($array);
            $restored->validate();

            $this->assertEquals($assembly->getName(), $restored->getName());
            $this->assertEquals($assembly->getPackage(), $restored->getPackage());
            $this->assertEquals($assembly->getVersion(), $restored->getVersion());
        }

        /**
         * Test validateArray with various version formats
         */
        public function testValidateArrayWithVariousVersions(): void
        {
            $versions = [
                '0.0.1',
                '1.0.0',
                '2.5.3',
                '10.20.30',
                '1.0.0-alpha',
                '1.0.0-beta.1',
                '2.0.0-rc.2',
                '1.2.3-SNAPSHOT'
            ];

            foreach ($versions as $version) {
                $data = [
                    'name' => 'test',
                    'package' => 'com.test',
                    'version' => $version
                ];

                Assembly::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with various package naming conventions
         */
        public function testValidateArrayWithVariousPackageNames(): void
        {
            $packages = [
                'com.example.app',
                'org.apache.commons',
                'io.github.user.project',
                'net.company.app',
                'test.package.name'
            ];

            foreach ($packages as $package) {
                $data = [
                    'name' => 'test',
                    'package' => $package,
                    'version' => '1.0.0'
                ];

                Assembly::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with different name formats
         */
        public function testValidateArrayWithDifferentNames(): void
        {
            $names = [
                'app',
                'my-app',
                'my_app',
                'MyApp',
                'my.app',
                'app123'
            ];

            foreach ($names as $name) {
                $data = [
                    'name' => $name,
                    'package' => 'com.test.app',
                    'version' => '1.0.0'
                ];

                Assembly::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with UUID formats
         */
        public function testValidateArrayWithUuidFormats(): void
        {
            $uids = [
                '12345678-1234-1234-1234-123456789012',
                'abcdef01-2345-6789-abcd-ef0123456789',
                '00000000-0000-0000-0000-000000000000',
                'ffffffff-ffff-ffff-ffff-ffffffffffff'
            ];

            foreach ($uids as $uid) {
                $data = [
                    'name' => 'test',
                    'package' => 'com.test.app',
                    'version' => '1.0.0',
                    'uid' => $uid
                ];

                Assembly::validateArray($data);
            }

            $this->assertTrue(true);
        }
    }
