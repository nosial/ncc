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

    namespace ncc\Objects\Package;

    use ncc\Objects\Package\ComponentReference;
    use PHPUnit\Framework\TestCase;

    class ComponentReferenceTest extends TestCase
    {
        /**
         * Test creating a ComponentReference
         */
        public function testCreateComponentReference(): void
        {
            $name = 'test_component';
            $offset = 1024;
            $size = 2048;

            $reference = new ComponentReference($name, $offset, $size);

            $this->assertEquals($name, $reference->getName());
            $this->assertEquals($offset, $reference->getOffset());
            $this->assertEquals($size, $reference->getSize());
        }

        /**
         * Test getName returns correct name
         */
        public function testGetName(): void
        {
            $reference = new ComponentReference('my_component', 100, 200);
            
            $this->assertEquals('my_component', $reference->getName());
        }

        /**
         * Test getOffset returns correct offset
         */
        public function testGetOffset(): void
        {
            $reference = new ComponentReference('component', 5000, 1000);
            
            $this->assertEquals(5000, $reference->getOffset());
        }

        /**
         * Test getSize returns correct size
         */
        public function testGetSize(): void
        {
            $reference = new ComponentReference('component', 1000, 3500);
            
            $this->assertEquals(3500, $reference->getSize());
        }

        /**
         * Test with zero offset and size
         */
        public function testWithZeroOffsetAndSize(): void
        {
            $reference = new ComponentReference('zero_component', 0, 0);
            
            $this->assertEquals(0, $reference->getOffset());
            $this->assertEquals(0, $reference->getSize());
        }

        /**
         * Test with large offset and size
         */
        public function testWithLargeValues(): void
        {
            $largeOffset = 1000000000;
            $largeSize = 500000000;
            $reference = new ComponentReference('large_component', $largeOffset, $largeSize);
            
            $this->assertEquals($largeOffset, $reference->getOffset());
            $this->assertEquals($largeSize, $reference->getSize());
        }

        /**
         * Test with special characters in name
         */
        public function testWithSpecialCharactersInName(): void
        {
            $name = 'component_with-special.chars@123';
            $reference = new ComponentReference($name, 100, 200);
            
            $this->assertEquals($name, $reference->getName());
        }

        /**
         * Test with empty name
         */
        public function testWithEmptyName(): void
        {
            $reference = new ComponentReference('', 100, 200);
            
            $this->assertEquals('', $reference->getName());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $ref1 = new ComponentReference('component1', 100, 200);
            $ref2 = new ComponentReference('component2', 300, 400);

            $this->assertEquals('component1', $ref1->getName());
            $this->assertEquals(100, $ref1->getOffset());
            $this->assertEquals(200, $ref1->getSize());

            $this->assertEquals('component2', $ref2->getName());
            $this->assertEquals(300, $ref2->getOffset());
            $this->assertEquals(400, $ref2->getSize());
        }

        /**
         * Test with long component name
         */
        public function testWithLongComponentName(): void
        {
            $longName = str_repeat('component_', 100);
            $reference = new ComponentReference($longName, 1000, 2000);
            
            $this->assertEquals($longName, $reference->getName());
            $this->assertEquals(1000, strlen($reference->getName()));
        }

        /**
         * Test offset and size relationship
         */
        public function testOffsetAndSizeRelationship(): void
        {
            $offset = 1000;
            $size = 500;
            $reference = new ComponentReference('test', $offset, $size);
            
            // End position would be offset + size
            $endPosition = $reference->getOffset() + $reference->getSize();
            $this->assertEquals(1500, $endPosition);
        }
    }
