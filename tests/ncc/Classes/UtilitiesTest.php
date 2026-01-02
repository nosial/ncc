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

    class UtilitiesTest extends TestCase
    {
        /**
         * Test parsePackageSource with valid input containing all components
         */
        public function testParsePackageSourceWithAllComponents(): void
        {
            $result = Utilities::parsePackageSource('myorg/mypackage=1.2.3@myrepo');

            $this->assertIsArray($result);
            $this->assertEquals('myorg', $result['organization']);
            $this->assertEquals('mypackage', $result['package_name']);
            $this->assertEquals('1.2.3', $result['version']);
            $this->assertEquals('myrepo', $result['repository']);
        }

        /**
         * Test parsePackageSource without version (should default to 'latest')
         */
        public function testParsePackageSourceWithoutVersion(): void
        {
            $result = Utilities::parsePackageSource('myorg/mypackage@myrepo');

            $this->assertIsArray($result);
            $this->assertEquals('myorg', $result['organization']);
            $this->assertEquals('mypackage', $result['package_name']);
            $this->assertEquals('latest', $result['version']);
            $this->assertEquals('myrepo', $result['repository']);
        }

        /**
         * Test parsePackageSource with complex version string
         */
        public function testParsePackageSourceWithComplexVersion(): void
        {
            $result = Utilities::parsePackageSource('org/pkg=v2.0.0-beta.1@repo');

            $this->assertIsArray($result);
            $this->assertEquals('org', $result['organization']);
            $this->assertEquals('pkg', $result['package_name']);
            $this->assertEquals('v2.0.0-beta.1', $result['version']);
            $this->assertEquals('repo', $result['repository']);
        }

        /**
         * Test parsePackageSource with names containing allowed special characters
         */
        public function testParsePackageSourceWithSpecialCharacters(): void
        {
            $result = Utilities::parsePackageSource('my-org.test/my-pkg_v2.0=1.0@my-repo.name');

            $this->assertIsArray($result);
            $this->assertEquals('my-org.test', $result['organization']);
            $this->assertEquals('my-pkg_v2.0', $result['package_name']);
            $this->assertEquals('1.0', $result['version']);
            $this->assertEquals('my-repo.name', $result['repository']);
        }

        /**
         * Test parsePackageSource with empty string
         */
        public function testParsePackageSourceWithEmptyString(): void
        {
            $result = Utilities::parsePackageSource('');
            $this->assertNull($result);
        }

        /**
         * Test parsePackageSource with whitespace only
         */
        public function testParsePackageSourceWithWhitespace(): void
        {
            $result = Utilities::parsePackageSource('   ');
            $this->assertNull($result);
        }

        /**
         * Test parsePackageSource with missing repository
         */
        public function testParsePackageSourceWithMissingRepository(): void
        {
            $result = Utilities::parsePackageSource('myorg/mypackage=1.0');
            $this->assertIsArray($result);
            $this->assertEquals('myorg', $result['organization']);
            $this->assertEquals('mypackage', $result['package_name']);
            $this->assertEquals('1.0', $result['version']);
            $this->assertNull($result['repository']);
        }

        /**
         * Test parsePackageSource with missing organization
         */
        public function testParsePackageSourceWithMissingOrganization(): void
        {
            $result = Utilities::parsePackageSource('mypackage=1.0@myrepo');
            $this->assertNull($result);
        }

        /**
         * Test parsePackageSource with invalid format
         */
        public function testParsePackageSourceWithInvalidFormat(): void
        {
            $result = Utilities::parsePackageSource('invalid format here');
            $this->assertNull($result);
        }

        /**
         * Test cleanArray with simple array containing nulls
         */
        public function testCleanArrayWithNulls(): void
        {
            $input = [
                'key1' => 'value1',
                'key2' => null,
                'key3' => 'value3',
                'key4' => null
            ];

            $result = Utilities::cleanArray($input);

            $this->assertCount(2, $result);
            $this->assertEquals('value1', $result['key1']);
            $this->assertEquals('value3', $result['key3']);
            $this->assertArrayNotHasKey('key2', $result);
            $this->assertArrayNotHasKey('key4', $result);
        }

        /**
         * Test cleanArray with nested arrays containing nulls
         */
        public function testCleanArrayWithNestedNulls(): void
        {
            $input = [
                'key1' => 'value1',
                'nested' => [
                    'inner1' => 'value2',
                    'inner2' => null,
                    'inner3' => 'value3'
                ],
                'key2' => null
            ];

            $result = Utilities::cleanArray($input);

            $this->assertCount(2, $result);
            $this->assertEquals('value1', $result['key1']);
            $this->assertIsArray($result['nested']);
            $this->assertCount(2, $result['nested']);
            $this->assertEquals('value2', $result['nested']['inner1']);
            $this->assertEquals('value3', $result['nested']['inner3']);
            $this->assertArrayNotHasKey('inner2', $result['nested']);
        }

        /**
         * Test cleanArray with empty nested arrays
         */
        public function testCleanArrayWithEmptyNestedArrays(): void
        {
            $input = [
                'key1' => 'value1',
                'empty' => [],
                'nested_with_nulls' => [
                    'inner1' => null,
                    'inner2' => null
                ],
                'key2' => 'value2'
            ];

            $result = Utilities::cleanArray($input);

            $this->assertCount(2, $result);
            $this->assertEquals('value1', $result['key1']);
            $this->assertEquals('value2', $result['key2']);
            $this->assertArrayNotHasKey('empty', $result);
            $this->assertArrayNotHasKey('nested_with_nulls', $result);
        }

        /**
         * Test cleanArray with deeply nested structure
         */
        public function testCleanArrayWithDeeplyNestedStructure(): void
        {
            $input = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'value' => 'deep',
                            'null' => null
                        ],
                        'null' => null
                    ],
                    'empty' => []
                ],
                'keep' => 'this'
            ];

            $result = Utilities::cleanArray($input);

            $this->assertCount(2, $result);
            $this->assertEquals('this', $result['keep']);
            $this->assertArrayHasKey('level1', $result);
            $this->assertArrayHasKey('level2', $result['level1']);
            $this->assertArrayHasKey('level3', $result['level1']['level2']);
            $this->assertEquals('deep', $result['level1']['level2']['level3']['value']);
            $this->assertArrayNotHasKey('null', $result['level1']['level2']['level3']);
        }

        /**
         * Test cleanArray with completely empty array
         */
        public function testCleanArrayWithCompletelyEmptyArray(): void
        {
            $input = [];
            $result = Utilities::cleanArray($input);

            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }

        /**
         * Test cleanArray with array of all nulls
         */
        public function testCleanArrayWithAllNulls(): void
        {
            $input = [
                'key1' => null,
                'key2' => null,
                'key3' => null
            ];

            $result = Utilities::cleanArray($input);

            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }

        /**
         * Test cleanArray preserves non-null values of different types
         */
        public function testCleanArrayPreservesVariousTypes(): void
        {
            $input = [
                'string' => 'text',
                'int' => 42,
                'float' => 3.14,
                'bool_true' => true,
                'bool_false' => false,
                'zero' => 0,
                'empty_string' => '',
                'null' => null
            ];

            $result = Utilities::cleanArray($input);

            $this->assertCount(7, $result);
            $this->assertEquals('text', $result['string']);
            $this->assertEquals(42, $result['int']);
            $this->assertEquals(3.14, $result['float']);
            $this->assertTrue($result['bool_true']);
            $this->assertFalse($result['bool_false']);
            $this->assertEquals(0, $result['zero']);
            $this->assertEquals('', $result['empty_string']);
            $this->assertArrayNotHasKey('null', $result);
        }

        /**
         * Test getProjectConfiguration with existing project.yml file
         */
        public function testGetProjectConfigurationWithYmlFile(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $ymlFile = $testDir . '/project.yml';
            file_put_contents($ymlFile, 'test: content');

            $result = Utilities::getProjectConfiguration($ymlFile);

            $this->assertNotNull($result);
            $this->assertEquals(realpath($ymlFile), $result);

            // Cleanup
            unlink($ymlFile);
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration with existing project.yaml file
         */
        public function testGetProjectConfigurationWithYamlFile(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $yamlFile = $testDir . '/project.yaml';
            file_put_contents($yamlFile, 'test: content');

            $result = Utilities::getProjectConfiguration($yamlFile);

            $this->assertNotNull($result);
            $this->assertEquals(realpath($yamlFile), $result);

            // Cleanup
            unlink($yamlFile);
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration with directory containing project.yml
         */
        public function testGetProjectConfigurationWithDirectoryContainingYml(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $ymlFile = $testDir . '/project.yml';
            file_put_contents($ymlFile, 'test: content');

            $result = Utilities::getProjectConfiguration($testDir);

            $this->assertNotNull($result);
            $this->assertEquals(realpath($ymlFile), $result);

            // Cleanup
            unlink($ymlFile);
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration with directory containing project.yaml
         */
        public function testGetProjectConfigurationWithDirectoryContainingYaml(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $yamlFile = $testDir . '/project.yaml';
            file_put_contents($yamlFile, 'test: content');

            $result = Utilities::getProjectConfiguration($testDir);

            $this->assertNotNull($result);
            $this->assertEquals(realpath($yamlFile), $result);

            // Cleanup
            unlink($yamlFile);
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration prefers project.yml over project.yaml in directory
         */
        public function testGetProjectConfigurationPrefersYmlOverYaml(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $ymlFile = $testDir . '/project.yml';
            $yamlFile = $testDir . '/project.yaml';
            file_put_contents($ymlFile, 'yml: content');
            file_put_contents($yamlFile, 'yaml: content');

            $result = Utilities::getProjectConfiguration($testDir);

            $this->assertNotNull($result);
            $this->assertEquals(realpath($ymlFile), $result);

            // Cleanup
            unlink($ymlFile);
            unlink($yamlFile);
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration with non-existent path
         */
        public function testGetProjectConfigurationWithNonExistentPath(): void
        {
            $result = Utilities::getProjectConfiguration('/path/that/does/not/exist');
            $this->assertNull($result);
        }

        /**
         * Test getProjectConfiguration with directory without project files
         */
        public function testGetProjectConfigurationWithDirectoryWithoutProjectFiles(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);

            $result = Utilities::getProjectConfiguration($testDir);

            $this->assertNull($result);

            // Cleanup
            rmdir($testDir);
        }

        /**
         * Test getProjectConfiguration with file that's not named project.yml/yaml
         */
        public function testGetProjectConfigurationWithWrongFilename(): void
        {
            $testDir = sys_get_temp_dir() . '/ncc_test_' . uniqid();
            mkdir($testDir);
            $wrongFile = $testDir . '/config.yml';
            file_put_contents($wrongFile, 'test: content');

            $result = Utilities::getProjectConfiguration($wrongFile);

            $this->assertNull($result);

            // Cleanup
            unlink($wrongFile);
            rmdir($testDir);
        }

        /**
         * Test replaceString with single replacement
         */
        public function testReplaceStringWithSingleReplacement(): void
        {
            $input = 'Hello World';
            $replace = ['World' => 'Universe'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello Universe', $result);
        }

        /**
         * Test replaceString with multiple replacements
         */
        public function testReplaceStringWithMultipleReplacements(): void
        {
            $input = 'build/debug/%ASSEMBLY.PACKAGE%.ncc';
            $replace = [
                '%ASSEMBLY.PACKAGE%' => '${ASSEMBLY_PACKAGE}'
            ];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('build/debug/${ASSEMBLY_PACKAGE}.ncc', $result);
        }

        /**
         * Test replaceString with multiple occurrences of same string
         */
        public function testReplaceStringWithMultipleOccurrences(): void
        {
            $input = 'foo bar foo baz foo';
            $replace = ['foo' => 'test'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('test bar test baz test', $result);
        }

        /**
         * Test replaceString with empty replace array
         */
        public function testReplaceStringWithEmptyReplaceArray(): void
        {
            $input = 'Hello World';
            $replace = [];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello World', $result);
        }

        /**
         * Test replaceString when search string is not found
         */
        public function testReplaceStringWhenSearchNotFound(): void
        {
            $input = 'Hello World';
            $replace = ['Universe' => 'Galaxy'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello World', $result);
        }

        /**
         * Test replaceString with empty search string (should skip)
         */
        public function testReplaceStringWithEmptySearchString(): void
        {
            $input = 'Hello World';
            $replace = ['' => 'test'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello World', $result);
        }

        /**
         * Test replaceString with non-string values (should skip)
         */
        public function testReplaceStringWithNonStringValues(): void
        {
            $input = 'Hello World';
            $replace = [
                'World' => 'Universe',
                123 => 'number',
                'test' => 456
            ];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello Universe', $result);
        }

        /**
         * Test replaceString with overlapping replacements
         */
        public function testReplaceStringWithOverlappingReplacements(): void
        {
            $input = 'aaabbbccc';
            $replace = [
                'aaa' => 'A',
                'bbb' => 'B',
                'ccc' => 'C'
            ];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('ABC', $result);
        }

        /**
         * Test replaceString with sequential replacements affecting subsequent searches
         */
        public function testReplaceStringWithSequentialReplacements(): void
        {
            $input = 'foo';
            $replace = [
                'foo' => 'bar',
                'bar' => 'baz'
            ];

            $result = Utilities::replaceString($input, $replace);

            // First replacement: foo -> bar
            // Second replacement: bar -> baz
            $this->assertEquals('baz', $result);
        }

        /**
         * Test replaceString with special characters
         */
        public function testReplaceStringWithSpecialCharacters(): void
        {
            $input = 'Hello @user! How are you?';
            $replace = [
                '@user' => '@admin',
                '?' => '!'
            ];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello @admin! How are you!', $result);
        }

        /**
         * Test replaceString with longer replacement than search
         */
        public function testReplaceStringWithLongerReplacement(): void
        {
            $input = 'Hi';
            $replace = ['Hi' => 'Hello World'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello World', $result);
        }

        /**
         * Test replaceString with shorter replacement than search
         */
        public function testReplaceStringWithShorterReplacement(): void
        {
            $input = 'Hello World';
            $replace = ['Hello World' => 'Hi'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hi', $result);
        }

        /**
         * Test replaceString with empty replacement (deletion)
         */
        public function testReplaceStringWithEmptyReplacement(): void
        {
            $input = 'Hello World!';
            $replace = [' World' => ''];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello!', $result);
        }

        /**
         * Test replaceString with unicode characters
         */
        public function testReplaceStringWithUnicodeCharacters(): void
        {
            $input = 'Hello 世界';
            $replace = ['世界' => 'World'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello World', $result);
        }

        /**
         * Test replaceString with case-sensitive matching
         */
        public function testReplaceStringIsCaseSensitive(): void
        {
            $input = 'Hello WORLD hello world';
            $replace = ['hello' => 'hi'];

            $result = Utilities::replaceString($input, $replace);

            $this->assertEquals('Hello WORLD hi world', $result);
        }

    }
