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

    use ncc\Enums\RepositoryType;
    use PHPUnit\Framework\TestCase;

    class RepositoryConfigurationValidationTest extends TestCase
    {
        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidData(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];

            RepositoryConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with different repository types
         */
        public function testValidateArrayWithAllTypes(): void
        {
            $types = ['github', 'gitlab', 'gitea', 'packagist'];

            foreach ($types as $type) {
                $data = [
                    'name' => 'test',
                    'type' => $type,
                    'host' => 'example.com',
                    'ssl' => true
                ];

                RepositoryConfiguration::validateArray($data);
            }

            $this->assertTrue(true);
        }

        /**
         * Test validateArray with SSL false
         */
        public function testValidateArrayWithSslFalse(): void
        {
            $data = [
                'name' => 'local-repo',
                'type' => 'gitlab',
                'host' => '192.168.1.1',
                'ssl' => false
            ];

            RepositoryConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validate() method on instance
         */
        public function testValidateInstanceMethod(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'github.com', true);
            $config->validate();
            $this->assertTrue(true);
        }

        /**
         * Test validate() with different configurations
         */
        public function testValidateVariousConfigurations(): void
        {
            $configs = [
                ['test-1', RepositoryType::GITHUB, 'github.com', true],
                ['test-2', RepositoryType::GITLAB, 'gitlab.example.com', false],
                ['test-3', RepositoryType::GITEA, 'gitea.local:3000', true],
                ['test-4', RepositoryType::PACKAGIST, 'packagist.org', true],
            ];

            foreach ($configs as [$name, $type, $host, $ssl]) {
                $config = new RepositoryConfiguration($name, $type, $host, $ssl);
                $config->validate();
            }

            $this->assertTrue(true);
        }

        /**
         * Test toArray and validate roundtrip
         */
        public function testToArrayAndValidateRoundtrip(): void
        {
            $config = new RepositoryConfiguration('my-repo', RepositoryType::GITLAB, 'gitlab.example.com', false);
            $array = $config->toArray();
            
            RepositoryConfiguration::validateArray($array);
            
            $restored = RepositoryConfiguration::fromArray($array);
            $restored->validate();
            
            $this->assertEquals($config->getName(), $restored->getName());
            $this->assertEquals($config->getType(), $restored->getType());
            $this->assertEquals($config->getHost(), $restored->getHost());
            $this->assertEquals($config->isSslEnabled(), $restored->isSslEnabled());
        }

        /**
         * Test validateArray with host containing port
         */
        public function testValidateArrayWithPortInHost(): void
        {
            $data = [
                'name' => 'custom',
                'type' => 'gitlab',
                'host' => 'gitlab.local:8080',
                'ssl' => true
            ];

            RepositoryConfiguration::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test validateArray with complex host names
         */
        public function testValidateArrayWithComplexHosts(): void
        {
            $hosts = [
                'sub.domain.example.com',
                'git-hub.internal.company.net',
                '192.168.1.100',
                'localhost',
                'git.example.co.uk:9000'
            ];

            foreach ($hosts as $host) {
                $data = [
                    'name' => 'test',
                    'type' => 'github',
                    'host' => $host,
                    'ssl' => true
                ];

                RepositoryConfiguration::validateArray($data);
            }

            $this->assertTrue(true);
        }
    }
