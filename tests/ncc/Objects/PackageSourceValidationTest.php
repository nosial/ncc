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

    use ncc\Exceptions\InvalidPropertyException;
    use PHPUnit\Framework\TestCase;

    class PackageSourceValidationTest extends TestCase
    {
        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidData(): void
        {
            $data = [
                'organization' => 'test-org',
                'name' => 'test-package',
                'version' => '1.0.0',
                'repository' => 'test-repo'
            ];

            PackageSource::validateArray($data);
            $this->assertTrue(true); // If no exception thrown, validation passed
        }

        /**
         * Test validateArray with valid data and null version
         */
        public function testValidateArrayWithNullVersion(): void
        {
            $data = [
                'organization' => 'test-org',
                'name' => 'test-package',
                'version' => null,
                'repository' => 'test-repo'
            ];

            PackageSource::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with valid data and null repository
         */
        public function testValidateArrayWithNullRepository(): void
        {
            $data = [
                'organization' => 'test-org',
                'name' => 'test-package',
                'version' => '1.0.0',
                'repository' => null
            ];

            PackageSource::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with 'latest' version
         */
        public function testValidateArrayWithLatestVersion(): void
        {
            $data = [
                'organization' => 'test-org',
                'name' => 'test-package',
                'version' => 'latest',
                'repository' => 'test-repo'
            ];

            PackageSource::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validate() method on instance
         */
        public function testValidateInstanceMethod(): void
        {
            $package = new PackageSource('org/package=1.0.0@repo');
            $package->validate();
            $this->assertTrue(true);
        }

        /**
         * Test validate() with modified valid instance
         */
        public function testValidateModifiedInstance(): void
        {
            $package = new PackageSource('org/package@repo');
            $package->setVersion('2.5.0');
            $package->validate();
            $this->assertTrue(true);
        }

        /**
         * Test toArray and validate roundtrip
         */
        public function testToArrayAndValidateRoundtrip(): void
        {
            $package = new PackageSource('my-org/my-pkg=3.0.0-beta@my-repo');
            $array = $package->toArray();
            
            PackageSource::validateArray($array);
            
            $restored = PackageSource::fromArray($array);
            $restored->validate();
            
            $this->assertTrue(true);
        }
    }
