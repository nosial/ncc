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

    namespace ncc\Classes;

    use PHPUnit\Framework\TestCase;

    class UtilitiesExtendedTest extends TestCase
    {
        /**
         * Test parsePackageSource with mixed case
         */
        public function testParsePackageSourceMixedCase(): void
        {
            $result = Utilities::parsePackageSource('MyOrg/MyPackage=1.0.0@MyRepo');
            $this->assertIsArray($result);
            $this->assertEquals('MyOrg', $result['organization']);
            $this->assertEquals('MyPackage', $result['package_name']);
            $this->assertEquals('1.0.0', $result['version']);
            $this->assertEquals('MyRepo', $result['repository']);
        }

        /**
         * Test parsePackageSource with underscores and dots
         */
        public function testParsePackageSourceWithUnderscoresAndDots(): void
        {
            $result = Utilities::parsePackageSource('my_org.v2/my_package.core=2.0.0@my_repo.main');
            $this->assertIsArray($result);
            $this->assertEquals('my_org.v2', $result['organization']);
            $this->assertEquals('my_package.core', $result['package_name']);
            $this->assertEquals('2.0.0', $result['version']);
            $this->assertEquals('my_repo.main', $result['repository']);
        }

        /**
         * Test parsePackageSource with hyphens
         */
        public function testParsePackageSourceWithHyphens(): void
        {
            $result = Utilities::parsePackageSource('my-org/my-package=1.0-beta@my-repo');
            $this->assertIsArray($result);
            $this->assertEquals('my-org', $result['organization']);
            $this->assertEquals('my-package', $result['package_name']);
            $this->assertEquals('1.0-beta', $result['version']);
            $this->assertEquals('my-repo', $result['repository']);
        }

        /**
         * Test parsePackageSource with pre-release version
         */
        public function testParsePackageSourceWithPreReleaseVersion(): void
        {
            $result = Utilities::parsePackageSource('org/pkg=1.0.0-alpha.1+build.123@repo');
            $this->assertIsArray($result);
            $this->assertEquals('org', $result['organization']);
            $this->assertEquals('pkg', $result['package_name']);
            $this->assertEquals('1.0.0-alpha.1+build.123', $result['version']);
            $this->assertEquals('repo', $result['repository']);
        }

        /**
         * Test parsePackageSource with single character components
         */
        public function testParsePackageSourceSingleChar(): void
        {
            $result = Utilities::parsePackageSource('a/b=1@c');
            $this->assertIsArray($result);
            $this->assertEquals('a', $result['organization']);
            $this->assertEquals('b', $result['package_name']);
            $this->assertEquals('1', $result['version']);
            $this->assertEquals('c', $result['repository']);
        }

        /**
         * Test parsePackageSource without version and repository
         */
        public function testParsePackageSourceMinimal(): void
        {
            $result = Utilities::parsePackageSource('org/package');
            $this->assertIsArray($result);
            $this->assertEquals('org', $result['organization']);
            $this->assertEquals('package', $result['package_name']);
            $this->assertEquals('latest', $result['version']);
            $this->assertNull($result['repository']);
        }

        /**
         * Test parsePackageSource with only version, no repository
         */
        public function testParsePackageSourceWithVersionOnly(): void
        {
            $result = Utilities::parsePackageSource('org/package=2.5.0');
            $this->assertIsArray($result);
            $this->assertEquals('org', $result['organization']);
            $this->assertEquals('package', $result['package_name']);
            $this->assertEquals('2.5.0', $result['version']);
            $this->assertNull($result['repository']);
        }

        /**
         * Test cleanArray with boolean values
         */
        public function testCleanArrayWithBooleans(): void
        {
            $input = [
                'enabled' => true,
                'disabled' => false,
                'nullable' => null,
                'zero' => 0
            ];

            $result = Utilities::cleanArray($input);
            
            $this->assertArrayHasKey('enabled', $result);
            $this->assertArrayHasKey('disabled', $result);
            $this->assertArrayNotHasKey('nullable', $result);
            $this->assertArrayHasKey('zero', $result);
            $this->assertTrue($result['enabled']);
            $this->assertFalse($result['disabled']);
            $this->assertEquals(0, $result['zero']);
        }

        /**
         * Test cleanArray preserves empty strings
         */
        public function testCleanArrayPreservesEmptyStrings(): void
        {
            $input = [
                'name' => '',
                'value' => null,
                'count' => 0
            ];

            $result = Utilities::cleanArray($input);
            
            $this->assertArrayHasKey('name', $result);
            $this->assertArrayNotHasKey('value', $result);
            $this->assertArrayHasKey('count', $result);
            $this->assertEquals('', $result['name']);
        }

        /**
         * Test cleanArray with mixed nested structures
         */
        public function testCleanArrayMixedNested(): void
        {
            $input = [
                'level1' => [
                    'level2a' => [
                        'data' => 'value',
                        'empty' => null
                    ],
                    'level2b' => [
                        'null' => null
                    ],
                    'level2c' => []
                ],
                'simple' => 'text'
            ];

            $result = Utilities::cleanArray($input);
            
            $this->assertArrayHasKey('level1', $result);
            $this->assertArrayHasKey('level2a', $result['level1']);
            $this->assertArrayNotHasKey('level2b', $result['level1']);
            $this->assertArrayNotHasKey('level2c', $result['level1']);
            $this->assertEquals('value', $result['level1']['level2a']['data']);
            $this->assertArrayNotHasKey('empty', $result['level1']['level2a']);
        }

        /**
         * Test cleanArray with numeric keys
         */
        public function testCleanArrayWithNumericKeys(): void
        {
            $input = [
                0 => 'first',
                1 => null,
                2 => 'third',
                3 => null,
                4 => 'fifth'
            ];

            $result = Utilities::cleanArray($input);
            
            $this->assertCount(3, $result);
            $this->assertArrayHasKey(0, $result);
            $this->assertArrayNotHasKey(1, $result);
            $this->assertArrayHasKey(2, $result);
        }

        /**
         * Test cleanArray with objects
         */
        public function testCleanArrayWithObjects(): void
        {
            $obj = new \stdClass();
            $obj->property = 'value';

            $input = [
                'object' => $obj,
                'null' => null,
                'string' => 'text'
            ];

            $result = Utilities::cleanArray($input);
            
            $this->assertCount(2, $result);
            $this->assertArrayHasKey('object', $result);
            $this->assertArrayHasKey('string', $result);
            $this->assertSame($obj, $result['object']);
        }

        /**
         * Test cleanArray with large array
         */
        public function testCleanArrayLargeArray(): void
        {
            $input = [];
            for ($i = 0; $i < 1000; $i++) {
                $input["key_$i"] = ($i % 3 === 0) ? null : "value_$i";
            }

            $result = Utilities::cleanArray($input);
            
            // Should remove every third element (nulls)
            // Elements at positions 0, 3, 6, 9... are null (i % 3 === 0)
            // That's 334 null elements (0-999, every 3rd starting at 0)
            $expectedCount = 1000 - 334;
            $this->assertCount($expectedCount, $result);
        }
    }
