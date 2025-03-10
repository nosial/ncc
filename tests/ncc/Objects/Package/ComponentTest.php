<?php
/*
 * Copyright (c) Nosial 2022-2024, all rights reserved.
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

namespace ncc\Objects\Package;

use ncc\Enums\Types\ComponentDataType;
use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Test Case for the class Component and the __construct method
 */
class ComponentTest extends TestCase
{
    /**
     * Test creation of a new instance with plain data type
     */
    public function testNewInstanceWithPlainDataType(): void
    {
        $component = new Component('TestName', 'TestData', ComponentDataType::PLAIN);

        $this->assertSame('TestName', $component->getName());
        $this->assertSame([], $component->getFlags());
        $this->assertSame(ComponentDataType::PLAIN, $component->getDataType());
        $this->assertSame('TestData', $component->getData());
    }

    /**
     * Test creation of a new instance with binary data type
     */
    public function testNewInstanceWithBinaryDataType(): void
    {
        $component = new Component('TestName', 'TestData', ComponentDataType::BINARY);

        $this->assertSame('TestName', $component->getName());
        $this->assertSame([], $component->getFlags());
        $this->assertSame(ComponentDataType::BINARY, $component->getDataType());
        $this->assertSame('TestData', $component->getData());
    }

    /**
     * Test creation of a new instance with base64 encoded data type
     */
    public function testNewInstanceWithBase64EncodedDataType(): void
    {
        $component = new Component('TestName', base64_encode('TestData'), ComponentDataType::BASE64_ENCODED);

        $this->assertSame('TestName', $component->getName());
        $this->assertSame([], $component->getFlags());
        $this->assertSame(ComponentDataType::BASE64_ENCODED, $component->getDataType());
        $this->assertSame('TestData', $component->getData());
    }
}