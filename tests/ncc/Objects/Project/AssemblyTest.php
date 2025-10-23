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
    use ncc\Exceptions\InvalidPropertyException;
    use PHPUnit\Framework\TestCase;

    class AssemblyTest extends TestCase
    {
        public function testConstructorWithDefaults(): void
        {
            $assembly = new Assembly([]);
            
            $this->assertEquals('Project', $assembly->getName());
            $this->assertEquals('com.example.project', $assembly->getPackage());
            $this->assertEquals('0.0.0', $assembly->getVersion());
            $this->assertNull($assembly->getUrl());
            $this->assertNull($assembly->getLicense());
            $this->assertNull($assembly->getDescription());
            $this->assertNull($assembly->getAuthor());
            $this->assertNull($assembly->getOrganization());
            $this->assertNull($assembly->getProduct());
            $this->assertNull($assembly->getCopyright());
            $this->assertNull($assembly->getTrademark());
        }

        public function testConstructorWithFullData(): void
        {
            $data = [
                'name' => 'Test Project',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'url' => 'https://example.com',
                'license' => 'MIT',
                'description' => 'A test project',
                'author' => 'Test Author',
                'organization' => 'Test Org',
                'product' => 'Test Product',
                'copyright' => '2025 Test Org',
                'trademark' => 'Test TM'
            ];

            $assembly = new Assembly($data);
            
            $this->assertEquals('Test Project', $assembly->getName());
            $this->assertEquals('com.test.project', $assembly->getPackage());
            $this->assertEquals('1.0.0', $assembly->getVersion());
            $this->assertEquals('https://example.com', $assembly->getUrl());
            $this->assertEquals('MIT', $assembly->getLicense());
            $this->assertEquals('A test project', $assembly->getDescription());
            $this->assertEquals('Test Author', $assembly->getAuthor());
            $this->assertEquals('Test Org', $assembly->getOrganization());
            $this->assertEquals('Test Product', $assembly->getProduct());
            $this->assertEquals('2025 Test Org', $assembly->getCopyright());
            $this->assertEquals('Test TM', $assembly->getTrademark());
        }

        public function testNameGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setName('New Name');
            $this->assertEquals('New Name', $assembly->getName());
        }

        public function testNameSetterEmpty(): void
        {
            $assembly = new Assembly([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The project name cannot be empty');
            $assembly->setName('');
        }

        public function testPackageGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setPackage('com.new.package');
            $this->assertEquals('com.new.package', $assembly->getPackage());
        }

        public function testPackageSetterInvalid(): void
        {
            $assembly = new Assembly([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The package name is not valid');
            $assembly->setPackage('invalid package name');
        }

        public function testVersionGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setVersion('2.1.0');
            $this->assertEquals('2.1.0', $assembly->getVersion());
        }

        public function testVersionSetterInvalid(): void
        {
            $assembly = new Assembly([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The version is not valid');
            $assembly->setVersion('invalid-version');
        }

        public function testUrlGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setUrl('https://example.com');
            $this->assertEquals('https://example.com', $assembly->getUrl());
            
            $assembly->setUrl(null);
            $this->assertNull($assembly->getUrl());
        }

        public function testUrlSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setUrl('   ');
            $this->assertNull($assembly->getUrl());
        }

        public function testLicenseGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setLicense('MIT');
            $this->assertEquals('MIT', $assembly->getLicense());
            
            $assembly->setLicense(null);
            $this->assertNull($assembly->getLicense());
        }

        public function testDescriptionGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setDescription('Test description');
            $this->assertEquals('Test description', $assembly->getDescription());
            
            $assembly->setDescription(null);
            $this->assertNull($assembly->getDescription());
        }

        public function testDescriptionSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setDescription('   ');
            $this->assertNull($assembly->getDescription());
        }

        public function testAuthorGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setAuthor('Test Author');
            $this->assertEquals('Test Author', $assembly->getAuthor());
            
            $assembly->setAuthor(null);
            $this->assertNull($assembly->getAuthor());
        }

        public function testAuthorSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setAuthor('   ');
            $this->assertNull($assembly->getAuthor());
        }

        public function testOrganizationGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setOrganization('Test Org');
            $this->assertEquals('Test Org', $assembly->getOrganization());
            
            $assembly->setOrganization(null);
            $this->assertNull($assembly->getOrganization());
        }

        public function testOrganizationSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setOrganization('   ');
            $this->assertNull($assembly->getOrganization());
        }

        public function testProductGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setProduct('Test Product');
            $this->assertEquals('Test Product', $assembly->getProduct());
            
            $assembly->setProduct(null);
            $this->assertNull($assembly->getProduct());
        }

        public function testProductSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setProduct('   ');
            $this->assertNull($assembly->getProduct());
        }

        public function testCopyrightGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setCopyright('2025 Test');
            $this->assertEquals('2025 Test', $assembly->getCopyright());
            
            $assembly->setCopyright(null);
            $this->assertNull($assembly->getCopyright());
        }

        public function testCopyrightSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setCopyright('   ');
            $this->assertNull($assembly->getCopyright());
        }

        public function testTrademarkGetterSetter(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setTrademark('Test TM');
            $this->assertEquals('Test TM', $assembly->getTrademark());
            
            $assembly->setTrademark(null);
            $this->assertNull($assembly->getTrademark());
        }

        public function testTrademarkSetterEmptyString(): void
        {
            $assembly = new Assembly([]);
            
            $assembly->setTrademark('   ');
            $this->assertNull($assembly->getTrademark());
        }

        public function testToArray(): void
        {
            $data = [
                'name' => 'Test Project',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'description' => 'A test project',
                'author' => 'Test Author',
                'organization' => 'Test Org',
                'product' => 'Test Product',
                'copyright' => '2025 Test Org',
                'trademark' => 'Test TM'
            ];

            $assembly = new Assembly($data);
            $result = $assembly->toArray();
            
            $this->assertEquals('Test Project', $result['name']);
            $this->assertEquals('com.test.project', $result['package']);
            $this->assertEquals('1.0.0', $result['version']);
            $this->assertEquals('A test project', $result['description']);
            $this->assertEquals('Test Author', $result['author']);
            $this->assertEquals('Test Org', $result['organization']);
            $this->assertEquals('Test Product', $result['product']);
            $this->assertEquals('2025 Test Org', $result['copyright']);
            $this->assertEquals('Test TM', $result['trademark']);
        }

        public function testFromArray(): void
        {
            $data = [
                'name' => 'Test Project',
                'package' => 'com.test.project',
                'version' => '1.0.0'
            ];

            $assembly = Assembly::fromArray($data);
            
            $this->assertInstanceOf(Assembly::class, $assembly);
            $this->assertEquals('Test Project', $assembly->getName());
            $this->assertEquals('com.test.project', $assembly->getPackage());
            $this->assertEquals('1.0.0', $assembly->getVersion());
        }

        public function testValidateArrayValid(): void
        {
            $data = [
                'name' => 'Test Project',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'url' => 'https://example.com',
                'description' => 'A test project',
                'author' => 'Test Author',
                'organization' => 'Test Org',
                'product' => 'Test Product',
                'copyright' => '2025 Test Org',
                'trademark' => 'Test TM'
            ];

            // Should not throw any exception
            Assembly::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayMissingName(): void
        {
            $data = [
                'package' => 'com.test.project',
                'version' => '1.0.0'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly name is required and cannot be empty');
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyName(): void
        {
            $data = [
                'name' => '',
                'package' => 'com.test.project',
                'version' => '1.0.0'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly name is required and cannot be empty');
            Assembly::validateArray($data);
        }

        public function testValidateArrayMissingPackage(): void
        {
            $data = [
                'name' => 'Test',
                'version' => '1.0.0'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly package is required and must be a valid package name');
            Assembly::validateArray($data);
        }

        public function testValidateArrayInvalidPackage(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'invalid package',
                'version' => '1.0.0'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly package is required and must be a valid package name');
            Assembly::validateArray($data);
        }

        public function testValidateArrayMissingVersion(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly version is required and must be a valid version');
            Assembly::validateArray($data);
        }

        public function testValidateArrayInvalidVersion(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => 'invalid'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly version is required and must be a valid version');
            Assembly::validateArray($data);
        }

        public function testValidateArrayInvalidUrl(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'url' => 'invalid-url'
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly URL must be a non-empty string or null');
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyDescription(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'description' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyAuthor(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'author' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyOrganization(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'organization' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyProduct(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'product' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyCopyright(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'copyright' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }

        public function testValidateArrayEmptyTrademark(): void
        {
            $data = [
                'name' => 'Test',
                'package' => 'com.test.project',
                'version' => '1.0.0',
                'trademark' => ''
            ];
            
            $this->expectException(InvalidPropertyException::class);
            Assembly::validateArray($data);
        }
    }
