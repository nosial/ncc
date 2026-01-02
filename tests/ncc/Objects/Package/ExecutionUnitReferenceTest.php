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

    use ncc\Objects\Package\ExecutionUnitReference;
    use PHPUnit\Framework\TestCase;

    class ExecutionUnitReferenceTest extends TestCase
    {
        /**
         * Test creating an ExecutionUnitReference
         */
        public function testCreateExecutionUnitReference(): void
        {
            $name = 'main_unit';
            $offset = 2048;
            $size = 4096;

            $reference = new ExecutionUnitReference($name, $offset, $size);

            $this->assertEquals($name, $reference->getName());
            $this->assertEquals($offset, $reference->getOffset());
            $this->assertEquals($size, $reference->getSize());
        }

        /**
         * Test getName returns correct name
         */
        public function testGetName(): void
        {
            $reference = new ExecutionUnitReference('test_unit', 100, 200);
            
            $this->assertEquals('test_unit', $reference->getName());
        }

        /**
         * Test getOffset returns correct offset
         */
        public function testGetOffset(): void
        {
            $reference = new ExecutionUnitReference('unit', 8192, 1024);
            
            $this->assertEquals(8192, $reference->getOffset());
        }

        /**
         * Test getSize returns correct size
         */
        public function testGetSize(): void
        {
            $reference = new ExecutionUnitReference('unit', 1000, 5000);
            
            $this->assertEquals(5000, $reference->getSize());
        }

        /**
         * Test with zero offset and size
         */
        public function testWithZeroOffsetAndSize(): void
        {
            $reference = new ExecutionUnitReference('zero_unit', 0, 0);
            
            $this->assertEquals(0, $reference->getOffset());
            $this->assertEquals(0, $reference->getSize());
        }

        /**
         * Test with large offset and size
         */
        public function testWithLargeValues(): void
        {
            $largeOffset = 999999999;
            $largeSize = 888888888;
            $reference = new ExecutionUnitReference('large_unit', $largeOffset, $largeSize);
            
            $this->assertEquals($largeOffset, $reference->getOffset());
            $this->assertEquals($largeSize, $reference->getSize());
        }

        /**
         * Test with special characters in name
         */
        public function testWithSpecialCharactersInName(): void
        {
            $name = 'unit_with-special.chars_123';
            $reference = new ExecutionUnitReference($name, 500, 1000);
            
            $this->assertEquals($name, $reference->getName());
        }

        /**
         * Test with empty name
         */
        public function testWithEmptyName(): void
        {
            $reference = new ExecutionUnitReference('', 100, 200);
            
            $this->assertEquals('', $reference->getName());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $ref1 = new ExecutionUnitReference('unit1', 100, 200);
            $ref2 = new ExecutionUnitReference('unit2', 300, 400);

            $this->assertEquals('unit1', $ref1->getName());
            $this->assertEquals(100, $ref1->getOffset());
            $this->assertEquals(200, $ref1->getSize());

            $this->assertEquals('unit2', $ref2->getName());
            $this->assertEquals(300, $ref2->getOffset());
            $this->assertEquals(400, $ref2->getSize());
        }

        /**
         * Test calculating end position
         */
        public function testCalculatingEndPosition(): void
        {
            $offset = 1000;
            $size = 500;
            $reference = new ExecutionUnitReference('unit', $offset, $size);
            
            $endPosition = $reference->getOffset() + $reference->getSize();
            $this->assertEquals(1500, $endPosition);
        }

        /**
         * Test with path-like name
         */
        public function testWithPathLikeName(): void
        {
            $name = 'modules/core/main.php';
            $reference = new ExecutionUnitReference($name, 2000, 3000);
            
            $this->assertEquals($name, $reference->getName());
        }
    }
