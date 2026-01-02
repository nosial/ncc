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

    class BuildConfigurationValidationTest extends TestCase
    {
        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidData(): void
        {
            $data = [
                'name' => 'release',
                'output' => 'build/release',
                'type' => 'ncc',
                'definitions' => ['DEBUG' => false],
                'include_components' => ['*.php'],
                'exclude_components' => ['tests/*'],
                'include_resources' => ['assets/*'],
                'exclude_resources' => ['*.tmp'],
                'dependencies' => ['org/package@repo'],
                'options' => ['optimize' => true]
            ];

            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with minimal required data
         */
        public function testValidateArrayWithMinimalData(): void
        {
            $data = [
                'name' => 'debug',
                'output' => 'out',
                'type' => 'ncc'
            ];

            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with all build types
         */
        public function testValidateArrayWithAllBuildTypes(): void
        {
            $types = ['ncc', 'phar', 'native'];

            foreach ($types as $type) {
                $data = [
                    'name' => 'test',
                    'output' => 'build',
                    'type' => $type
                ];

                BuildConfiguration::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validate() method on instance
         */
        public function testValidateInstanceMethod(): void
        {
            $config = new BuildConfiguration([
                'name' => 'release',
                'output' => 'build/release',
                'type' => 'ncc'
            ]);

            $config->validate();
            $this->assertTrue(true);
        }

        /**
         * Test validate with modified instance
         */
        public function testValidateModifiedInstance(): void
        {
            $config = new BuildConfiguration([
                'name' => 'debug',
                'output' => 'out',
                'type' => 'ncc'
            ]);

            $config->setOutput('build/debug');
            $config->setDefinitions(['DEBUG' => true, 'TEST_MODE' => true]);
            $config->validate();

            $this->assertTrue(true);
        }

        /**
         * Test toArray and validate roundtrip
         */
        public function testToArrayAndValidateRoundtrip(): void
        {
            $data = [
                'name' => 'production',
                'output' => 'dist',
                'type' => 'ncc',
                'definitions' => ['PROD' => true],
                'options' => ['minify' => true]
            ];

            $config = new BuildConfiguration($data);
            $array = $config->toArray();

            BuildConfiguration::validateArray($array);

            $restored = BuildConfiguration::fromArray($array);
            $restored->validate();

            $this->assertEquals($config->getName(), $restored->getName());
            $this->assertEquals($config->getOutput(), $restored->getOutput());
            $this->assertEquals($config->getType(), $restored->getType());
        }

        /**
         * Test validateArray with empty arrays for optional fields
         */
        public function testValidateArrayWithEmptyArrays(): void
        {
            $data = [
                'name' => 'test',
                'output' => 'build',
                'type' => 'ncc',
                'definitions' => [],
                'include_components' => [],
                'exclude_components' => [],
                'include_resources' => [],
                'exclude_resources' => [],
                'options' => []
            ];

            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with multiple dependencies
         */
        public function testValidateArrayWithMultipleDependencies(): void
        {
            $data = [
                'name' => 'test',
                'output' => 'build',
                'type' => 'ncc',
                'dependencies' => [
                    'org/package1@repo',
                    'org/package2=1.0.0@repo',
                    'org/package3=latest@repo'
                ]
            ];

            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with complex definitions
         */
        public function testValidateArrayWithComplexDefinitions(): void
        {
            $data = [
                'name' => 'test',
                'output' => 'build',
                'type' => 'ncc',
                'definitions' => [
                    'VERSION' => '1.0.0',
                    'DEBUG' => false,
                    'MAX_TIMEOUT' => 3000,
                    'API_ENDPOINT' => 'https://api.example.com'
                ]
            ];

            BuildConfiguration::validateArray($data);
            $this->assertTrue(true);
        }
    }
