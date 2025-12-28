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

    use ncc\Objects\Package\ResourceReference;
    use PHPUnit\Framework\TestCase;

    class ResourceReferenceTest extends TestCase
    {
        /**
         * Test creating a ResourceReference
         */
        public function testCreateResourceReference(): void
        {
            $name = 'config.json';
            $offset = 512;
            $size = 1024;

            $reference = new ResourceReference($name, $offset, $size);

            $this->assertEquals($name, $reference->getName());
            $this->assertEquals($offset, $reference->getOffset());
            $this->assertEquals($size, $reference->getSize());
        }

        /**
         * Test getName returns correct name
         */
        public function testGetName(): void
        {
            $reference = new ResourceReference('data.xml', 100, 200);
            
            $this->assertEquals('data.xml', $reference->getName());
        }

        /**
         * Test getOffset returns correct offset
         */
        public function testGetOffset(): void
        {
            $reference = new ResourceReference('resource', 16384, 2048);
            
            $this->assertEquals(16384, $reference->getOffset());
        }

        /**
         * Test getSize returns correct size
         */
        public function testGetSize(): void
        {
            $reference = new ResourceReference('resource', 1000, 7500);
            
            $this->assertEquals(7500, $reference->getSize());
        }

        /**
         * Test with zero offset and size
         */
        public function testWithZeroOffsetAndSize(): void
        {
            $reference = new ResourceReference('empty_resource', 0, 0);
            
            $this->assertEquals(0, $reference->getOffset());
            $this->assertEquals(0, $reference->getSize());
        }

        /**
         * Test with large offset and size
         */
        public function testWithLargeValues(): void
        {
            $largeOffset = 2147483647; // Max 32-bit int
            $largeSize = 1000000000;
            $reference = new ResourceReference('large_resource', $largeOffset, $largeSize);
            
            $this->assertEquals($largeOffset, $reference->getOffset());
            $this->assertEquals($largeSize, $reference->getSize());
        }

        /**
         * Test with file path as name
         */
        public function testWithFilePathAsName(): void
        {
            $name = 'resources/images/logo.png';
            $reference = new ResourceReference($name, 500, 1000);
            
            $this->assertEquals($name, $reference->getName());
        }

        /**
         * Test with empty name
         */
        public function testWithEmptyName(): void
        {
            $reference = new ResourceReference('', 100, 200);
            
            $this->assertEquals('', $reference->getName());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $ref1 = new ResourceReference('resource1.txt', 100, 200);
            $ref2 = new ResourceReference('resource2.dat', 300, 400);

            $this->assertEquals('resource1.txt', $ref1->getName());
            $this->assertEquals(100, $ref1->getOffset());
            $this->assertEquals(200, $ref1->getSize());

            $this->assertEquals('resource2.dat', $ref2->getName());
            $this->assertEquals(300, $ref2->getOffset());
            $this->assertEquals(400, $ref2->getSize());
        }

        /**
         * Test calculating end position
         */
        public function testCalculatingEndPosition(): void
        {
            $offset = 2000;
            $size = 800;
            $reference = new ResourceReference('resource', $offset, $size);
            
            $endPosition = $reference->getOffset() + $reference->getSize();
            $this->assertEquals(2800, $endPosition);
        }

        /**
         * Test with special characters in name
         */
        public function testWithSpecialCharactersInName(): void
        {
            $name = 'resource-file_v1.2.3@backup.json';
            $reference = new ResourceReference($name, 1000, 2000);
            
            $this->assertEquals($name, $reference->getName());
        }

        /**
         * Test with various file extensions
         */
        public function testWithVariousFileExtensions(): void
        {
            $extensions = ['txt', 'json', 'xml', 'png', 'jpg', 'pdf', 'zip', 'dat'];
            
            foreach ($extensions as $ext) {
                $name = "file.$ext";
                $reference = new ResourceReference($name, 100, 200);
                $this->assertEquals($name, $reference->getName());
            }
        }

        /**
         * Test with long file path
         */
        public function testWithLongFilePath(): void
        {
            $name = 'very/long/path/to/some/deeply/nested/resource/file.txt';
            $reference = new ResourceReference($name, 5000, 10000);
            
            $this->assertEquals($name, $reference->getName());
        }
    }
