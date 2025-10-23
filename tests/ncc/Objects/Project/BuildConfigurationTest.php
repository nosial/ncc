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

    use InvalidArgumentException;
    use ncc\Enums\BuildType;
    use ncc\Exceptions\InvalidPropertyException;
    use PHPUnit\Framework\TestCase;

    class BuildConfigurationTest extends TestCase
    {
        public function testConstructorWithDefaults(): void
        {
            $config = new BuildConfiguration([]);
            
            $this->assertEquals('release', $config->getName());
            $this->assertEquals('out', $config->getOutput());
            $this->assertEquals(BuildType::NCC_PACKAGE, $config->getType());
            $this->assertNull($config->getOptions());
        }

        public function testConstructorWithFullData(): void
        {
            $data = [
                'name' => 'debug',
                'output' => 'build/debug',
                'type' => 'phar',
                'options' => ['compress' => true, 'strip_whitespace' => false]
            ];

            $config = new BuildConfiguration($data);
            
            $this->assertEquals('debug', $config->getName());
            $this->assertEquals('build/debug', $config->getOutput());
            $this->assertEquals(BuildType::PHAR_PACKAGE, $config->getType());
            $this->assertEquals(['compress' => true, 'strip_whitespace' => false], $config->getOptions());
        }

        public function testNameGetterSetter(): void
        {
            $config = new BuildConfiguration([]);
            
            $config->setName('custom-build');
            $this->assertEquals('custom-build', $config->getName());
        }

        public function testNameSetterEmpty(): void
        {
            $config = new BuildConfiguration([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Build configuration name cannot be empty');
            $config->setName('');
        }

        public function testOutputGetterSetter(): void
        {
            $config = new BuildConfiguration([]);
            
            $config->setOutput('dist/output');
            $this->assertEquals('dist/output', $config->getOutput());
        }

        public function testOutputSetterEmpty(): void
        {
            $config = new BuildConfiguration([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Build configuration output cannot be empty');
            $config->setOutput('');
        }

        public function testTypeGetterSetter(): void
        {
            $config = new BuildConfiguration([]);
            
            $config->setType(BuildType::PHAR_PACKAGE);
            $this->assertEquals(BuildType::PHAR_PACKAGE, $config->getType());
            
            $config->setType(BuildType::NATIVE);
            $this->assertEquals(BuildType::NATIVE, $config->getType());
        }

        public function testOptionsGetterSetter(): void
        {
            $config = new BuildConfiguration([]);
            
            $options = ['debug' => true, 'minify' => false];
            $config->setOptions($options);
            $this->assertEquals($options, $config->getOptions());
            
            $config->setOptions(null);
            $this->assertNull($config->getOptions());
        }

        public function testToArray(): void
        {
            $data = [
                'name' => 'debug',
                'output' => 'build/debug',
                'type' => 'phar',
                'options' => ['debug' => true]
            ];

            $config = new BuildConfiguration($data);
            $result = $config->toArray();
            
            $this->assertEquals('debug', $result['name']);
            $this->assertEquals('build/debug', $result['output']);
            $this->assertEquals('phar', $result['type']);
            $this->assertEquals(['debug' => true], $result['options']);
        }

        public function testToArrayWithNullOptions(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out',
                'type' => 'ncc'
            ];

            $config = new BuildConfiguration($data);
            $result = $config->toArray();
            
            $this->assertEquals('release', $result['name']);
            $this->assertEquals('out', $result['output']);
            $this->assertEquals('ncc', $result['type']);
            $this->assertNull($result['options']);
        }

        public function testFromArray(): void
        {
            $data = [
                'name' => 'test-build',
                'output' => 'test/output',
                'type' => 'native'
            ];

            $config = BuildConfiguration::fromArray($data);
            
            $this->assertInstanceOf(BuildConfiguration::class, $config);
            $this->assertEquals('test-build', $config->getName());
            $this->assertEquals('test/output', $config->getOutput());
            $this->assertEquals(BuildType::NATIVE, $config->getType());
        }

        public function testValidateArrayValid(): void
        {
            $data = [
                'name' => 'debug',
                'output' => 'build/debug',
                'type' => 'phar',
                'options' => ['compress' => true]
            ];

            // Should not throw any exception
            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayValidMinimal(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out',
                'type' => 'ncc'
            ];

            // Should not throw any exception
            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayMissingName(): void
        {
            $data = [
                'output' => 'out',
                'type' => 'ncc_package'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration name must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayEmptyName(): void
        {
            $data = [
                'name' => '',
                'output' => 'out',
                'type' => 'ncc_package'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration name must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayNameNotString(): void
        {
            $data = [
                'name' => 123,
                'output' => 'out',
                'type' => 'ncc_package'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration name must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayMissingOutput(): void
        {
            $data = [
                'name' => 'release',
                'type' => 'ncc'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration output must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayEmptyOutput(): void
        {
            $data = [
                'name' => 'release',
                'output' => '',
                'type' => 'ncc'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration output must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayOutputNotString(): void
        {
            $data = [
                'name' => 'release',
                'output' => 123,
                'type' => 'ncc'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration output must be a non-empty string');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayMissingType(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration type must be a valid build type');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayInvalidType(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out',
                'type' => 'invalid_type'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration type must be a valid build type');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayTypeNotString(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out',
                'type' => 123
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration type must be a valid build type');
            BuildConfiguration::validateArray($data);
        }

        public function testValidateArrayOptionsNotArray(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'out',
                'type' => 'ncc',
                'options' => 'not-array'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Build configuration options must be an array if set');
            BuildConfiguration::validateArray($data);
        }

        public function testAllBuildTypeValues(): void
        {
            $buildTypes = [
                'ncc' => BuildType::NCC_PACKAGE,
                'phar' => BuildType::PHAR_PACKAGE,
                'native' => BuildType::NATIVE
            ];

            foreach ($buildTypes as $typeString => $enumValue) {
                $data = [
                    'name' => 'test',
                    'output' => 'out',
                    'type' => $typeString
                ];

                $config = new BuildConfiguration($data);
                $this->assertEquals($enumValue, $config->getType());
                
                // Test validation doesn't throw
                BuildConfiguration::validateArray($data);
                $this->assertTrue(true);
            }
        }

        public function testInvalidBuildTypeDefaultsToNccPackage(): void
        {
            $data = [
                'name' => 'test',
                'output' => 'out',
                'type' => 'invalid_type_here'
            ];

            $config = new BuildConfiguration($data);
            $this->assertEquals(BuildType::NCC_PACKAGE, $config->getType());
        }
    }
