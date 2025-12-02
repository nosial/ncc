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

    namespace ncc\Enums;

    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    class MacroVariableTest extends TestCase
    {
        /**
         * Test built-in date/time macros with simple strings
         */
        public function testBuiltinMacrosSimpleStrings()
        {
            // Test DATE macro
            $result = MacroVariable::fromInput('Today is ${DATE}');
            $this->assertStringContainsString(date('Y-m-d'), $result);
            $this->assertStringStartsWith('Today is ', $result);

            // Test TIME macro
            $result = MacroVariable::fromInput('Current time: ${TIME}');
            $this->assertStringContainsString(':', $result);
            $this->assertStringStartsWith('Current time: ', $result);

            // Test YEAR macro
            $result = MacroVariable::fromInput('Year: ${YEAR}');
            $this->assertEquals('Year: ' . date('Y'), $result);

            // Test MONTH macro
            $result = MacroVariable::fromInput('Month: ${MONTH}');
            $this->assertEquals('Month: ' . date('m'), $result);

            // Test DAY macro
            $result = MacroVariable::fromInput('Day: ${DAY}');
            $this->assertEquals('Day: ' . date('d'), $result);
        }

        /**
         * Test built-in macros with long strings containing multiple macros
         */
        public function testBuiltinMacrosLongStrings()
        {
            $input = 'This is a long string generated on ${DATE} at ${TIME}. ' .
                     'The year is ${YEAR}, month is ${MONTH}, and day is ${DAY}. ' .
                     'Current working directory: ${CWD}. ' .
                     'Full datetime: ${DATETIME}. ' .
                     'Hour: ${HOUR}, Minute: ${MINUTE}, Second: ${SECOND}.';

            $result = MacroVariable::fromInput($input);

            // Verify no macros remain
            $this->assertStringNotContainsString('${DATE}', $result);
            $this->assertStringNotContainsString('${TIME}', $result);
            $this->assertStringNotContainsString('${YEAR}', $result);
            $this->assertStringNotContainsString('${MONTH}', $result);
            $this->assertStringNotContainsString('${DAY}', $result);
            $this->assertStringNotContainsString('${CWD}', $result);
            $this->assertStringNotContainsString('${DATETIME}', $result);
            $this->assertStringNotContainsString('${HOUR}', $result);
            $this->assertStringNotContainsString('${MINUTE}', $result);
            $this->assertStringNotContainsString('${SECOND}', $result);

            // Verify expected values are present
            $this->assertStringContainsString(date('Y-m-d'), $result);
            $this->assertStringContainsString(date('Y'), $result);
            $this->assertStringContainsString(getcwd(), $result);
        }

        /**
         * Test custom handler for non-built-in macros
         */
        public function testCustomHandlerSimple()
        {
            $handler = function($macro) {
                return match($macro) {
                    '${ASSEMBLY.NAME}' => 'MyProject',
                    '${ASSEMBLY.VERSION}' => '1.0.0',
                    '${ASSEMBLY.AUTHOR}' => 'John Doe',
                    default => null
                };
            };

            $result = MacroVariable::fromInput('Project: ${ASSEMBLY.NAME}', false, $handler);
            $this->assertEquals('Project: MyProject', $result);

            $result = MacroVariable::fromInput('Version: ${ASSEMBLY.VERSION}', false, $handler);
            $this->assertEquals('Version: 1.0.0', $result);

            $result = MacroVariable::fromInput('Author: ${ASSEMBLY.AUTHOR}', false, $handler);
            $this->assertEquals('Author: John Doe', $result);
        }

        /**
         * Test custom handler with long strings
         */
        public function testCustomHandlerLongStrings()
        {
            $handler = function($macro) {
                return match($macro) {
                    '${ASSEMBLY.NAME}' => 'MyAwesomeProject',
                    '${ASSEMBLY.VERSION}' => '2.5.3',
                    '${ASSEMBLY.AUTHOR}' => 'Jane Smith',
                    '${ASSEMBLY.ORGANIZATION}' => 'Acme Corp',
                    '${ASSEMBLY.LICENSE}' => 'MIT',
                    '${ASSEMBLY.DESCRIPTION}' => 'An awesome project that does amazing things',
                    '${ASSEMBLY.COPYRIGHT}' => 'Copyright (c) 2025 Acme Corp',
                    default => null
                };
            };

            $input = 'Project Information:\n' .
                     'Name: ${ASSEMBLY.NAME}\n' .
                     'Version: ${ASSEMBLY.VERSION}\n' .
                     'Author: ${ASSEMBLY.AUTHOR}\n' .
                     'Organization: ${ASSEMBLY.ORGANIZATION}\n' .
                     'License: ${ASSEMBLY.LICENSE}\n' .
                     'Description: ${ASSEMBLY.DESCRIPTION}\n' .
                     '${ASSEMBLY.COPYRIGHT}\n' .
                     'Generated on: ${DATE} at ${TIME}';

            $result = MacroVariable::fromInput($input, false, $handler);

            // Verify all macros are replaced
            $this->assertStringContainsString('Name: MyAwesomeProject', $result);
            $this->assertStringContainsString('Version: 2.5.3', $result);
            $this->assertStringContainsString('Author: Jane Smith', $result);
            $this->assertStringContainsString('Organization: Acme Corp', $result);
            $this->assertStringContainsString('License: MIT', $result);
            $this->assertStringContainsString('Description: An awesome project that does amazing things', $result);
            $this->assertStringContainsString('Copyright (c) 2025 Acme Corp', $result);
        }

        /**
         * Test that built-in macros take precedence over custom handlers
         */
        public function testBuiltinMacrosPrecedence()
        {
            $handler = function($macro) {
                // Try to override a built-in macro
                return match($macro) {
                    '${DATE}' => 'CUSTOM_DATE',
                    '${ASSEMBLY.NAME}' => 'MyProject',
                    default => null
                };
            };

            // Built-in DATE should NOT be overridden
            $result = MacroVariable::fromInput('Date: ${DATE}', false, $handler);
            $this->assertStringContainsString(date('Y-m-d'), $result);
            $this->assertStringNotContainsString('CUSTOM_DATE', $result);

            // Custom macro should work
            $result = MacroVariable::fromInput('Name: ${ASSEMBLY.NAME}', false, $handler);
            $this->assertEquals('Name: MyProject', $result);
        }

        /**
         * Test unresolved macros in non-strict mode
         */
        public function testUnresolvedMacrosNonStrict()
        {
            $result = MacroVariable::fromInput('Project: ${ASSEMBLY.NAME}', false);
            // Unresolved macros should remain in the string
            $this->assertEquals('Project: ${ASSEMBLY.NAME}', $result);

            $result = MacroVariable::fromInput('Version: ${UNKNOWN_MACRO}', false);
            $this->assertEquals('Version: ${UNKNOWN_MACRO}', $result);
        }

        /**
         * Test unresolved macros in strict mode
         */
        public function testUnresolvedMacrosStrictMode()
        {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage("Unable to translate one or more macro variables");

            MacroVariable::fromInput('Project: ${ASSEMBLY.NAME}', true);
        }


        /**
         * Test translateMacrosArray with simple array
         */
        public function testTranslateMacrosArraySimple()
        {
            $input = [
                'date' => 'Today is ${DATE}',
                'year' => 'Year: ${YEAR}',
                'number' => 42,
                'boolean' => true
            ];

            $result = MacroVariable::fromArray($input);

            $this->assertStringContainsString(date('Y-m-d'), $result['date']);
            $this->assertEquals('Year: ' . date('Y'), $result['year']);
            $this->assertEquals(42, $result['number']);
            $this->assertTrue($result['boolean']);
        }

        /**
         * Test translateMacrosArray with nested arrays
         */
        public function testTranslateMacrosArrayNested()
        {
            $handler = function($macro) {
                return match($macro) {
                    '${ASSEMBLY.NAME}' => 'TestProject',
                    '${ASSEMBLY.VERSION}' => '1.0.0',
                    default => null
                };
            };

            $input = [
                'project' => [
                    'name' => '${ASSEMBLY.NAME}',
                    'version' => '${ASSEMBLY.VERSION}',
                    'metadata' => [
                        'date' => '${DATE}',
                        'cwd' => '${CWD}'
                    ]
                ],
                'simple' => 'Year: ${YEAR}'
            ];

            $result = MacroVariable::fromArray($input, false, $handler);

            $this->assertEquals('TestProject', $result['project']['name']);
            $this->assertEquals('1.0.0', $result['project']['version']);
            $this->assertStringContainsString(date('Y-m-d'), $result['project']['metadata']['date']);
            $this->assertEquals(getcwd(), $result['project']['metadata']['cwd']);
            $this->assertEquals('Year: ' . date('Y'), $result['simple']);
        }

        /**
         * Test translateMacrosArray in strict mode with unresolved macros
         */
        public function testTranslateMacrosArrayStrictMode()
        {
            $input = [
                'valid' => 'Today is ${DATE}',
                'invalid' => 'Project: ${ASSEMBLY.NAME}'
            ];

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage("Unable to translate one or more macro variables");

            MacroVariable::fromArray($input, true);
        }

        /**
         * Test multiple macros in a single string
         */
        public function testMultipleMacrosInString()
        {
            $result = MacroVariable::fromInput('${YEAR}-${MONTH}-${DAY}');
            $this->assertEquals(date('Y') . '-' . date('m') . '-' . date('d'), $result);
        }

        /**
         * Test mixed built-in and custom macros
         */
        public function testMixedBuiltinAndCustomMacros()
        {
            $handler = function($macro) {
                return match($macro) {
                    '${CUSTOM.VALUE}' => 'CustomData',
                    default => null
                };
            };

            $input = 'Generated on ${DATE} with ${CUSTOM.VALUE}';
            $result = MacroVariable::fromInput($input, false, $handler);

            $this->assertStringContainsString(date('Y-m-d'), $result);
            $this->assertStringContainsString('CustomData', $result);
            $this->assertStringNotContainsString('${', $result);
        }

        /**
         * Test CWD macro
         */
        public function testCwdMacro()
        {
            $result = MacroVariable::fromInput('Working directory: ${CWD}');
            $this->assertEquals('Working directory: ' . getcwd(), $result);
        }

        /**
         * Test DATETIME macro format
         */
        public function testDateTimeMacro()
        {
            $result = MacroVariable::fromInput('${DATETIME}');
            // Should match pattern YYYY-MM-DD HH:MM:SS
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
        }
    }
