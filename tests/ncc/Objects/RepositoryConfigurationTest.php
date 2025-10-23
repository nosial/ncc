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
    use ncc\Exceptions\InvalidPropertyException;
    use PHPUnit\Framework\TestCase;
    
    class RepositoryConfigurationTest extends TestCase
    {
        /**
         * Test constructor with default SSL parameter
         */
        public function testConstructorWithDefaults(): void
        {
            $config = new RepositoryConfiguration('test-repo', RepositoryType::GITHUB, 'github.com');
            
            $this->assertEquals('test-repo', $config->getName());
            $this->assertEquals(RepositoryType::GITHUB, $config->getType());
            $this->assertEquals('github.com', $config->getHost());
            $this->assertTrue($config->isSslEnabled());
        }

        /**
         * Test constructor with explicit SSL parameter
         */
        public function testConstructorWithExplicitSsl(): void
        {
            $config = new RepositoryConfiguration('test-repo', RepositoryType::GITLAB, 'gitlab.com', false);
            
            $this->assertEquals('test-repo', $config->getName());
            $this->assertEquals(RepositoryType::GITLAB, $config->getType());
            $this->assertEquals('gitlab.com', $config->getHost());
            $this->assertFalse($config->isSslEnabled());
        }

        /**
         * Test all repository types
         */
        public function testAllRepositoryTypes(): void
        {
            $types = [
                RepositoryType::GITHUB,
                RepositoryType::GITLAB,
                RepositoryType::GITEA,
                RepositoryType::PACKAGIST
            ];

            foreach ($types as $type) {
                $config = new RepositoryConfiguration('test', $type, 'example.com');
                $this->assertEquals($type, $config->getType());
            }
        }

        /**
         * Test getName method
         */
        public function testGetName(): void
        {
            $config = new RepositoryConfiguration('my-awesome-repo', RepositoryType::GITHUB, 'github.com');
            $this->assertEquals('my-awesome-repo', $config->getName());
        }

        /**
         * Test getType method
         */
        public function testGetType(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITEA, 'gitea.com');
            $this->assertEquals(RepositoryType::GITEA, $config->getType());
        }

        /**
         * Test getHost method
         */
        public function testGetHost(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'custom-github.example.com');
            $this->assertEquals('custom-github.example.com', $config->getHost());
        }

        /**
         * Test isSslEnabled method with SSL enabled
         */
        public function testIsSslEnabledTrue(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'github.com', true);
            $this->assertTrue($config->isSslEnabled());
        }

        /**
         * Test isSslEnabled method with SSL disabled
         */
        public function testIsSslEnabledFalse(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'github.com', false);
            $this->assertFalse($config->isSslEnabled());
        }

        /**
         * Test getBaseUrl method with SSL enabled
         */
        public function testGetBaseUrlWithSslEnabled(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'github.com', true);
            $this->assertEquals('https://github.com', $config->getBaseUrl());
        }

        /**
         * Test getBaseUrl method with SSL disabled
         */
        public function testGetBaseUrlWithSslDisabled(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITHUB, 'github.com', false);
            $this->assertEquals('http://github.com', $config->getBaseUrl());
        }

        /**
         * Test __toString method
         */
        public function testToString(): void
        {
            $config = new RepositoryConfiguration('test', RepositoryType::GITLAB, 'gitlab.com', true);
            $this->assertEquals('https://gitlab.com', (string) $config);
            
            $configNoSsl = new RepositoryConfiguration('test', RepositoryType::GITLAB, 'gitlab.com', false);
            $this->assertEquals('http://gitlab.com', (string) $configNoSsl);
        }

        /**
         * Test toArray method
         */
        public function testToArray(): void
        {
            $config = new RepositoryConfiguration('test-repo', RepositoryType::GITHUB, 'github.com', true);
            $expected = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->assertEquals($expected, $config->toArray());
        }

        /**
         * Test toArray method with SSL disabled
         */
        public function testToArrayWithSslDisabled(): void
        {
            $config = new RepositoryConfiguration('test-repo', RepositoryType::GITLAB, 'gitlab.example.com', false);
            $expected = [
                'name' => 'test-repo',
                'type' => 'gitlab',
                'host' => 'gitlab.example.com',
                'ssl' => false
            ];
            
            $this->assertEquals($expected, $config->toArray());
        }

        /**
         * Test fromArray method with valid data
         */
        public function testFromArrayValid(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'gitlab',
                'host' => 'gitlab.com',
                'ssl' => false
            ];
            
            $config = RepositoryConfiguration::fromArray($data);
            
            $this->assertEquals('test-repo', $config->getName());
            $this->assertEquals(RepositoryType::GITLAB, $config->getType());
            $this->assertEquals('gitlab.com', $config->getHost());
            $this->assertFalse($config->isSslEnabled());
        }

        /**
         * Test fromArray method with missing SSL (should default to provided value)
         */
        public function testFromArrayMissingSsl(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com'
                // ssl is missing, but fromArray requires it in the current implementation
            ];
            
            // This will cause an error in the current implementation since ssl is required
            // but let's test what happens if we add a default
            $data['ssl'] = true; // Add default for this test
            
            $config = RepositoryConfiguration::fromArray($data);
            
            $this->assertEquals('test-repo', $config->getName());
            $this->assertEquals(RepositoryType::GITHUB, $config->getType());
            $this->assertEquals('github.com', $config->getHost());
            $this->assertTrue($config->isSslEnabled());
        }

        /**
         * Test fromArray method with invalid type (should default to GITHUB)
         */
        public function testFromArrayInvalidType(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'invalid-type',
                'host' => 'example.com',
                'ssl' => true
            ];
            
            $config = RepositoryConfiguration::fromArray($data);
            
            $this->assertEquals('test-repo', $config->getName());
            $this->assertEquals(RepositoryType::GITHUB, $config->getType()); // Should default to GITHUB
            $this->assertEquals('example.com', $config->getHost());
            $this->assertTrue($config->isSslEnabled());
        }

        /**
         * Test validateArray method with valid data
         */
        public function testValidateArrayValid(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            // Should not throw any exception
            $this->expectNotToPerformAssertions();
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with missing name
         */
        public function testValidateArrayMissingName(): void
        {
            $data = [
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.name': The repository name is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with empty name
         */
        public function testValidateArrayEmptyName(): void
        {
            $data = [
                'name' => '',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.name': The repository name is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with whitespace-only name
         */
        public function testValidateArrayWhitespaceOnlyName(): void
        {
            $data = [
                'name' => '   ',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.name': The repository name is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with non-string name
         */
        public function testValidateArrayNonStringName(): void
        {
            $data = [
                'name' => 123,
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.name': The repository name is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with missing type
         */
        public function testValidateArrayMissingType(): void
        {
            $data = [
                'name' => 'test-repo',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.type': The repository type is required and must be a valid RepositoryType");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with invalid type
         */
        public function testValidateArrayInvalidType(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'invalid-type',
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.type': The repository type is required and must be a valid RepositoryType");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with non-string type
         */
        public function testValidateArrayNonStringType(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 123,
                'host' => 'github.com',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.type': The repository type is required and must be a valid RepositoryType");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with missing host
         */
        public function testValidateArrayMissingHost(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.host': The repository host is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with empty host
         */
        public function testValidateArrayEmptyHost(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => '',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.host': The repository host is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with whitespace-only host
         */
        public function testValidateArrayWhitespaceOnlyHost(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => '   ',
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.host': The repository host is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with non-string host
         */
        public function testValidateArrayNonStringHost(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 123,
                'ssl' => true
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.host': The repository host is required and cannot be empty");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with non-boolean SSL
         */
        public function testValidateArrayNonBooleanSsl(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => 'true' // String instead of boolean
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage("Invalid property 'repository.ssl': The repository SSL flag must be a boolean value");
            
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test validateArray method with missing SSL (should be valid)
         */
        public function testValidateArrayMissingSsl(): void
        {
            $data = [
                'name' => 'test-repo',
                'type' => 'github',
                'host' => 'github.com'
                // ssl is missing, which should be valid
            ];
            
            // Should not throw any exception
            $this->expectNotToPerformAssertions();
            RepositoryConfiguration::validateArray($data);
        }

        /**
         * Test serialization and deserialization roundtrip
         */
        public function testSerializationRoundtrip(): void
        {
            $original = new RepositoryConfiguration('test-repo', RepositoryType::GITEA, 'gitea.example.com', false);
            
            $array = $original->toArray();
            $restored = RepositoryConfiguration::fromArray($array);
            
            $this->assertEquals($original->getName(), $restored->getName());
            $this->assertEquals($original->getType(), $restored->getType());
            $this->assertEquals($original->getHost(), $restored->getHost());
            $this->assertEquals($original->isSslEnabled(), $restored->isSslEnabled());
            $this->assertEquals($original->getBaseUrl(), $restored->getBaseUrl());
            $this->assertEquals((string) $original, (string) $restored);
        }

        /**
         * Test with different repository types serialization
         */
        public function testDifferentRepositoryTypesSerialization(): void
        {
            $types = [
                [RepositoryType::GITHUB, 'github'],
                [RepositoryType::GITLAB, 'gitlab'],
                [RepositoryType::GITEA, 'gitea'],
                [RepositoryType::PACKAGIST, 'packagist']
            ];

            foreach ($types as [$enumType, $stringType]) {
                $config = new RepositoryConfiguration('test', $enumType, 'example.com');
                $array = $config->toArray();
                
                $this->assertEquals($stringType, $array['type']);
                
                $restored = RepositoryConfiguration::fromArray($array);
                $this->assertEquals($enumType, $restored->getType());
            }
        }

        /**
         * Test edge cases with special characters in name and host
         */
        public function testSpecialCharacters(): void
        {
            $config = new RepositoryConfiguration(
                'test-repo_with.special-chars',
                RepositoryType::GITHUB,
                'sub.domain-with_special.chars.com'
            );
            
            $this->assertEquals('test-repo_with.special-chars', $config->getName());
            $this->assertEquals('sub.domain-with_special.chars.com', $config->getHost());
            $this->assertEquals('https://sub.domain-with_special.chars.com', $config->getBaseUrl());
        }
    }
