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
    use ncc\Enums\ExecutionMode;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Exceptions\InvalidPropertyException;
    use PHPUnit\Framework\TestCase;

    class ExecutionUnitTest extends TestCase
    {
        public function testConstructorWithRequiredFields(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];

            $unit = new ExecutionUnit($data);
            
            $this->assertEquals('test-unit', $unit->getName());
            $this->assertEquals(ExecutionUnitType::PHP, $unit->getType());
            $this->assertEquals(ExecutionMode::AUTO, $unit->getMode());
            $this->assertEquals('main.php', $unit->getEntryPoint());
            $this->assertEquals('${CWD}', $unit->getWorkingDirectory());
            $this->assertNull($unit->getArguments());
            $this->assertNull($unit->getEnvironment());
        }

        public function testConstructorWithFullData(): void
        {
            $data = [
                'name' => 'full-unit',
                'type' => 'system',
                'mode' => 'tty',
                'entry' => 'app.bin',
                'working_directory' => '/opt/app',
                'arguments' => ['--verbose', '--config=prod'],
                'environment' => ['ENV' => 'production', 'DEBUG' => 'false']
            ];

            $unit = new ExecutionUnit($data);
            
            $this->assertEquals('full-unit', $unit->getName());
            $this->assertEquals(ExecutionUnitType::SYSTEM, $unit->getType());
            $this->assertEquals(ExecutionMode::TTY, $unit->getMode());
            $this->assertEquals('app.bin', $unit->getEntryPoint());
            $this->assertEquals('/opt/app', $unit->getWorkingDirectory());
            $this->assertEquals(['--verbose', '--config=prod'], $unit->getArguments());
            $this->assertEquals(['ENV' => 'production', 'DEBUG' => 'false'], $unit->getEnvironment());
        }

        public function testConstructorMissingName(): void
        {
            $data = [
                'entry' => 'main.php'
            ];

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Property \'name\' is required');
            new ExecutionUnit($data);
        }

        public function testConstructorEmptyName(): void
        {
            $data = [
                'name' => '',
                'entry' => 'main.php'
            ];

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Property \'name\' is required');
            new ExecutionUnit($data);
        }

        public function testConstructorMissingEntry(): void
        {
            $data = [
                'name' => 'test-unit'
            ];

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Property \'entry\' is required');
            new ExecutionUnit($data);
        }

        public function testConstructorEmptyEntry(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => ''
            ];

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Property \'entry\' is required');
            new ExecutionUnit($data);
        }

        public function testNameGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $unit->setName('new-name');
            $this->assertEquals('new-name', $unit->getName());
        }

        public function testNameSetterEmpty(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Execution Unit name cannot be empty');
            $unit->setName('');
        }

        public function testTypeGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $unit->setType(ExecutionUnitType::SYSTEM);
            $this->assertEquals(ExecutionUnitType::SYSTEM, $unit->getType());
            
            $unit->setType(ExecutionUnitType::PHP);
            $this->assertEquals(ExecutionUnitType::PHP, $unit->getType());
        }

        public function testModeGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $unit->setMode(ExecutionMode::TTY);
            $this->assertEquals(ExecutionMode::TTY, $unit->getMode());
            
            $unit->setMode(ExecutionMode::PTY);
            $this->assertEquals(ExecutionMode::PTY, $unit->getMode());
        }

        public function testEntryPointGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $unit->setEntryPoint('index.php');
            $this->assertEquals('index.php', $unit->getEntryPoint());
        }

        public function testEntryPointSetterEmpty(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The Execution Unit\'s Entry Point cannot be empty!');
            $unit->setEntryPoint('');
        }

        public function testWorkingDirectoryGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $unit->setWorkingDirectory('/custom/path');
            $this->assertEquals('/custom/path', $unit->getWorkingDirectory());
        }

        public function testWorkingDirectorySetterEmpty(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The Execution Unit\'s Working Directory cannot be empty!');
            $unit->setWorkingDirectory('');
        }

        public function testArgumentsGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $arguments = ['--verbose', '--config=test'];
            $unit->setArguments($arguments);
            $this->assertEquals($arguments, $unit->getArguments());
            
            $unit->setArguments(null);
            $this->assertNull($unit->getArguments());
        }

        public function testArgumentsSetterInvalidArgument(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Arguments must be strings');
            $unit->setArguments(['--valid', 123, '--another']);
        }

        public function testEnvironmentGetterSetter(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $environment = ['ENV' => 'test', 'DEBUG' => 'true'];
            $unit->setEnvironment($environment);
            $this->assertEquals($environment, $unit->getEnvironment());
            
            $unit->setEnvironment(null);
            $this->assertNull($unit->getEnvironment());
        }

        public function testEnvironmentSetterInvalidKey(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Environment variable keys must be non-empty strings');
            $unit->setEnvironment(['' => 'value']);
        }

        public function testEnvironmentSetterInvalidKeyType(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Environment variable keys must be non-empty strings');
            $unit->setEnvironment([123 => 'value']);
        }

        public function testEnvironmentSetterInvalidValue(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php'
            ];
            $unit = new ExecutionUnit($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Environment variable values must be strings');
            $unit->setEnvironment(['KEY' => 123]);
        }

        public function testToArray(): void
        {
            $data = [
                'name' => 'full-unit',
                'type' => 'system',
                'mode' => 'tty',
                'entry' => 'app.bin',
                'working_directory' => '/opt/app',
                'arguments' => ['--verbose'],
                'environment' => ['ENV' => 'prod']
            ];

            $unit = new ExecutionUnit($data);
            $result = $unit->toArray();
            
            $this->assertEquals('full-unit', $result['name']);
            $this->assertEquals('system', $result['type']);
            $this->assertEquals('tty', $result['mode']);
            $this->assertEquals('app.bin', $result['entry']);
            $this->assertEquals('/opt/app', $result['working_directory']);
            $this->assertEquals(['--verbose'], $result['arguments']);
            $this->assertEquals(['ENV' => 'prod'], $result['environment']);
        }

        public function testToArrayWithDefaults(): void
        {
            $data = [
                'name' => 'simple-unit',
                'entry' => 'main.php'
            ];

            $unit = new ExecutionUnit($data);
            $result = $unit->toArray();
            
            $this->assertEquals('simple-unit', $result['name']);
            $this->assertEquals('php', $result['type']);
            $this->assertEquals('auto', $result['mode']);
            $this->assertEquals('main.php', $result['entry']);
            $this->assertEquals('${CWD}', $result['working_directory']);
            $this->assertNull($result['arguments']);
            $this->assertNull($result['environment']);
        }

        public function testFromArray(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'script.sh',
                'type' => 'system'
            ];

            $unit = ExecutionUnit::fromArray($data);
            
            $this->assertInstanceOf(ExecutionUnit::class, $unit);
            $this->assertEquals('test-unit', $unit->getName());
            $this->assertEquals('script.sh', $unit->getEntryPoint());
            $this->assertEquals(ExecutionUnitType::SYSTEM, $unit->getType());
        }

        public function testValidateArrayValid(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'type' => 'php',
                'mode' => 'auto',
                'working_directory' => '/app',
                'arguments' => ['--arg1', '--arg2'],
                'environment' => ['KEY' => 'value']
            ];

            // Should not throw any exception
            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayValidMinimal(): void
        {
            $data = [
                'name' => 'minimal-unit',
                'entry' => 'index.php'
            ];

            // Should not throw any exception
            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayMissingName(): void
        {
            $data = [
                'entry' => 'main.php'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'name\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayEmptyName(): void
        {
            $data = [
                'name' => '',
                'entry' => 'main.php'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'name\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayNameNotString(): void
        {
            $data = [
                'name' => 123,
                'entry' => 'main.php'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'name\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayMissingEntry(): void
        {
            $data = [
                'name' => 'test-unit'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'entry\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayEmptyEntry(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'entry\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayEntryNotString(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 123
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'entry\' is required and must be a non-empty string');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayInvalidType(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'type' => 'invalid_type'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayInvalidMode(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'mode' => 'invalid_mode'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayEmptyWorkingDirectory(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'working_directory' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'working_directory\' must be a non-empty string if set');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayWorkingDirectoryNotString(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'working_directory' => 123
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'working_directory\' must be a non-empty string if set');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayArgumentsNotArray(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'arguments' => 'not-array'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'arguments\' must be an array if set');
            ExecutionUnit::validateArray($data);
        }

        public function testValidateArrayEnvironmentNotArray(): void
        {
            $data = [
                'name' => 'test-unit',
                'entry' => 'main.php',
                'environment' => 'not-array'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Property \'environment\' must be an array if set');
            ExecutionUnit::validateArray($data);
        }

        public function testAllExecutionUnitTypes(): void
        {
            $types = [
                'php' => ExecutionUnitType::PHP,
                'system' => ExecutionUnitType::SYSTEM
            ];

            foreach ($types as $typeString => $enumValue) {
                $data = [
                    'name' => 'test',
                    'entry' => 'main.file',
                    'type' => $typeString
                ];

                $unit = new ExecutionUnit($data);
                $this->assertEquals($enumValue, $unit->getType());
                
                // Test validation doesn't throw
                ExecutionUnit::validateArray($data);
                $this->assertTrue(true);
            }
        }

        public function testAllExecutionModes(): void
        {
            $modes = [
                'auto' => ExecutionMode::AUTO,
                'tty' => ExecutionMode::TTY,
                'pty' => ExecutionMode::PTY
            ];

            foreach ($modes as $modeString => $enumValue) {
                $data = [
                    'name' => 'test',
                    'entry' => 'main.file',
                    'mode' => $modeString
                ];

                $unit = new ExecutionUnit($data);
                $this->assertEquals($enumValue, $unit->getMode());
                
                // Test validation doesn't throw
                ExecutionUnit::validateArray($data);
                $this->assertTrue(true);
            }
        }

        public function testInvalidTypeDefaultsToPhp(): void
        {
            $data = [
                'name' => 'test',
                'entry' => 'main.file',
                'type' => 'invalid_type_here'
            ];

            $unit = new ExecutionUnit($data);
            $this->assertEquals(ExecutionUnitType::PHP, $unit->getType());
        }

        public function testInvalidModeDefaultsToAuto(): void
        {
            $data = [
                'name' => 'test',
                'entry' => 'main.file',
                'mode' => 'invalid_mode_here'
            ];

            $unit = new ExecutionUnit($data);
            $this->assertEquals(ExecutionMode::AUTO, $unit->getMode());
        }
    }
