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
            // Test date components using format specifiers
            $result = MacroVariable::fromInput('Today is ${Y}-${m}-${d}');
            $this->assertEquals('Today is ' . date('Y-m-d'), $result);

            // Test TIME macro using format specifiers
            $result = MacroVariable::fromInput('Current time: ${H}:${i}:${s}');
            $this->assertEquals('Current time: ' . date('H:i:s'), $result);

            // Test YEAR macro
            $result = MacroVariable::fromInput('Year: ${Y}');
            $this->assertEquals('Year: ' . date('Y'), $result);

            // Test MONTH macro
            $result = MacroVariable::fromInput('Month: ${m}');
            $this->assertEquals('Month: ' . date('m'), $result);

            // Test DAY macro
            $result = MacroVariable::fromInput('Day: ${d}');
            $this->assertEquals('Day: ' . date('d'), $result);
        }

        /**
         * Test built-in macros with long strings containing multiple macros
         */
        public function testBuiltinMacrosLongStrings()
        {
            $input = 'This is a long string generated on ${Y}-${m}-${d} at ${H}:${i}:${s}. ' .
                     'The year is ${Y}, month is ${m}, and day is ${d}. ' .
                     'Current working directory: ${CWD}. ' .
                     'Full datetime: ${c}. ' .
                     'Hour: ${H}, Minute: ${i}, Second: ${s}.';

            $result = MacroVariable::fromInput($input);

            // Verify no macros remain
            $this->assertStringNotContainsString('${Y}', $result);
            $this->assertStringNotContainsString('${m}', $result);
            $this->assertStringNotContainsString('${d}', $result);
            $this->assertStringNotContainsString('${c}', $result);
            $this->assertStringNotContainsString('${H}', $result);
            $this->assertStringNotContainsString('${i}', $result);
            $this->assertStringNotContainsString('${s}', $result);

            // Verify expected values are present
            $this->assertStringContainsString(date('Y-m-d'), $result);
            $this->assertStringContainsString(date('Y'), $result);
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
                     'Generated on: ${Y}-${m}-${d} at ${H}:${i}:${s}';

            $result = MacroVariable::fromInput($input, false, $handler);

            // Verify all macros are replaced
            $this->assertStringContainsString('Name: MyAwesomeProject', $result);
            $this->assertStringContainsString('Version: 2.5.3', $result);
            $this->assertStringContainsString('Author: Jane Smith', $result);
            $this->assertStringContainsString('Organization: Acme Corp', $result);
            $this->assertStringContainsString('License: MIT', $result);
            $this->assertStringContainsString('Description: An awesome project that does amazing things', $result);
            $this->assertStringContainsString('Copyright (c) 2025 Acme Corp', $result);
            $this->assertStringContainsString(date('Y-m-d'), $result);
        }

        /**
         * Test that built-in macros take precedence over custom handlers
         */
        public function testBuiltinMacrosPrecedence()
        {
            $handler = function($macro) {
                // Try to override a built-in macro
                return match($macro) {
                    '${Y}' => 'CUSTOM_YEAR',
                    '${ASSEMBLY.NAME}' => 'MyProject',
                    default => null
                };
            };

            // Built-in Y (year) should NOT be overridden
            $result = MacroVariable::fromInput('Date: ${Y}', false, $handler);
            $this->assertEquals('Date: ' . date('Y'), $result);
            $this->assertStringNotContainsString('CUSTOM_YEAR', $result);

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
                'date' => 'Today is ${Y}-${m}-${d}',
                'year' => 'Year: ${Y}',
                'number' => 42,
                'boolean' => true
            ];

            $result = MacroVariable::fromArray($input);

            $this->assertEquals('Today is ' . date('Y-m-d'), $result['date']);
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
                        'date' => '${Y}-${m}-${d}',
                        'cwd' => '${CWD}'
                    ]
                ],
                'simple' => 'Year: ${Y}'
            ];

            $result = MacroVariable::fromArray($input, false, $handler);

            $this->assertEquals('TestProject', $result['project']['name']);
            $this->assertEquals('1.0.0', $result['project']['version']);
            $this->assertEquals(date('Y-m-d'), $result['project']['metadata']['date']);
            $this->assertEquals('Year: ' . date('Y'), $result['simple']);
        }

        /**
         * Test translateMacrosArray in strict mode with unresolved macros
         */
        public function testTranslateMacrosArrayStrictMode()
        {
            $input = [
                'valid' => 'Today is ${Y}-${m}-${d}',
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
            $result = MacroVariable::fromInput('${Y}-${m}-${d}');
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

            $input = 'Generated on ${Y}-${m}-${d} with ${CUSTOM.VALUE}';
            $result = MacroVariable::fromInput($input, false, $handler);

            $this->assertEquals('Generated on ' . date('Y-m-d') . ' with CustomData', $result);
            $this->assertStringNotContainsString('${', $result);
        }

        /**
         * Test DATETIME macro format using c (ISO 8601 format)
         */
        public function testDateTimeMacro()
        {
            $result = MacroVariable::fromInput('${c}');
            // Should match ISO 8601 format (e.g., 2004-02-12T15:19:21+00:00)
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result);
        }
    }
