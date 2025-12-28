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

    class ExecutionUnitValidationTest extends TestCase
    {
        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidData(): void
        {
            $data = [
                'name' => 'run-main',
                'type' => 'php',
                'entry' => 'main.php',
                'mode' => 'auto',
                'environment' => ['ENV' => 'prod'],
                'required_files' => ['config.json'],
                'arguments' => ['--verbose', '--output=file'],
                'timeout' => 3600,
                'silent' => false,
                'tty' => true
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with minimal required data
         */
        public function testValidateArrayWithMinimalData(): void
        {
            $data = [
                'name' => 'simple',
                'type' => 'php',
                'entry' => 'script.php',
                'mode' => 'auto'
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with all execution unit types
         */
        public function testValidateArrayWithAllTypes(): void
        {
            $types = ['php', 'web', 'system'];

            foreach ($types as $type) {
                $data = [
                    'name' => 'test',
                    'type' => $type,
                    'entry' => 'run.php',
                    'mode' => 'auto'
                ];

                ExecutionUnit::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with all execution modes
         */
        public function testValidateArrayWithAllModes(): void
        {
            $modes = ['auto', 'tty', 'pty'];

            foreach ($modes as $mode) {
                $data = [
                    'name' => 'test',
                    'type' => 'php',
                    'entry' => 'run.php',
                    'mode' => $mode
                ];

                ExecutionUnit::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validate() method on instance
         */
        public function testValidateInstanceMethod(): void
        {
            $data = [
                'name' => 'worker',
                'type' => 'php',
                'entry' => 'worker.php',
                'mode' => 'tty'
            ];

            $unit = new ExecutionUnit($data);
            $unit->validate();
            $this->assertTrue(true);
        }

        /**
         * Test validate with modified instance
         */
        public function testValidateModifiedInstance(): void
        {
            $data = [
                'name' => 'task',
                'type' => 'php',
                'entry' => 'task.php',
                'mode' => 'auto'
            ];

            $unit = new ExecutionUnit($data);
            $unit->setArguments(['--config=prod.json', '--verbose']);
            $unit->setTimeout(7200);
            $unit->validate();

            $this->assertTrue(true);
        }

        /**
         * Test toArray and validate roundtrip
         */
        public function testToArrayAndValidateRoundtrip(): void
        {
            $data = [
                'name' => 'job',
                'type' => 'php',
                'entry' => 'job.php',
                'mode' => 'tty',
                'timeout' => 1800
            ];

            $unit = new ExecutionUnit($data);
            $array = $unit->toArray();

            ExecutionUnit::validateArray($array);

            $restored = ExecutionUnit::fromArray($array);
            $restored->validate();

            $this->assertEquals($unit->getName(), $restored->getName());
            $this->assertEquals($unit->getType(), $restored->getType());
            $this->assertEquals($unit->getEntryPoint(), $restored->getEntryPoint());
        }

        /**
         * Test validateArray with empty arrays for optional fields
         */
        public function testValidateArrayWithEmptyArrays(): void
        {
            $data = [
                'name' => 'test',
                'type' => 'php',
                'entry' => 'test.php',
                'mode' => 'auto',
                'environment' => [],
                'required_files' => [],
                'arguments' => []
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with multiple arguments
         */
        public function testValidateArrayWithMultipleArguments(): void
        {
            $data = [
                'name' => 'test',
                'type' => 'php',
                'entry' => 'test.php',
                'mode' => 'auto',
                'arguments' => [
                    '--input=file.txt',
                    '--output=result.txt',
                    '--format=json',
                    '--verbose'
                ]
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with complex environment
         */
        public function testValidateArrayWithComplexEnvironment(): void
        {
            $data = [
                'name' => 'test',
                'type' => 'php',
                'entry' => 'test.php',
                'mode' => 'auto',
                'environment' => [
                    'APP_ENV' => 'production',
                    'DATABASE_URL' => 'mysql://localhost/db',
                    'API_KEY' => 'secret-key-123',
                    'MAX_WORKERS' => '10',
                    'ENABLE_CACHE' => 'true'
                ]
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with multiple required files
         */
        public function testValidateArrayWithMultipleRequiredFiles(): void
        {
            $data = [
                'name' => 'test',
                'type' => 'php',
                'entry' => 'test.php',
                'mode' => 'auto',
                'required_files' => [
                    'config/app.json',
                    'config/database.json',
                    'keys/secret.key',
                    'data/init.sql'
                ]
            ];

            ExecutionUnit::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with different timeout values
         */
        public function testValidateArrayWithDifferentTimeouts(): void
        {
            $timeouts = [1, 60, 300, 3600, 7200, 86400];

            foreach ($timeouts as $timeout) {
                $data = [
                    'name' => 'test',
                    'type' => 'php',
                    'entry' => 'test.php',
                    'mode' => 'auto',
                    'timeout' => $timeout
                ];

                ExecutionUnit::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with TTY and silent combinations
         */
        public function testValidateArrayWithTtyAndSilentCombinations(): void
        {
            $combinations = [
                ['tty' => true, 'silent' => false],
                ['tty' => false, 'silent' => true],
                ['tty' => true, 'silent' => true],
                ['tty' => false, 'silent' => false]
            ];

            foreach ($combinations as $combo) {
                $data = array_merge([
                    'name' => 'test',
                    'type' => 'php',
                    'entry' => 'test.php',
                    'mode' => 'auto'
                ], $combo);

                ExecutionUnit::validateArray($data);
            }

            $this->assertTrue(true);
        }
    }
