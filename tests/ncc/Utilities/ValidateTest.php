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

namespace ncc\Utilities;

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    /**
     * This test validates various versions with the version method from
     * the Validate class.
     * The method checks the input against a set of regular expressions
     * for semantic versioning, composer format and python versioning.
     */
    public function testVersion()
    {
        // Semantic Versioning
        $this->assertTrue(Validate::version('1.2.0'));
        $this->assertTrue(Validate::version('0.1.0-beta'));
        $this->assertTrue(Validate::version('1.0.0-alpha.1'));
        $this->assertTrue(Validate::version('1.2'));

        // Composer Version Format
        $this->assertTrue(Validate::version('1.2.0-beta1'));
        $this->assertTrue(Validate::version('2.0.0-RC2'));
        $this->assertTrue(Validate::version('2.0'));

        // Python Version Format
        $this->assertTrue(Validate::version('2.7.14'));
        $this->assertTrue(Validate::version('3.6.3'));
        $this->assertTrue(Validate::version('2.7'));

        // Invalid Versions
        $this->assertFalse(Validate::version('bob'));
        $this->assertFalse(Validate::version('a.b.c'));
        $this->assertFalse(Validate::version('foo.bar'));
    }

    /**
     * This test validates the package name with the packageName method from
     * the Validate class.
     * The method checks the input against a set of regular expressions
     * for package name format.
     */
    public function testPackageName()
    {
        // Valid package names
        $this->assertTrue(Validate::packageName('com.user.project'));
        $this->assertTrue(Validate::packageName('org.opensource.projectname'));
        $this->assertTrue(Validate::packageName('net.domainname.product'));

        // Invalid package names
        $this->assertFalse(Validate::packageName('com'));
        $this->assertFalse(Validate::packageName('com..user'));
        $this->assertFalse(Validate::packageName('.user.project'));
    }

    /**
     * This test validates the constant name with the constantName method from
     * the Validate class.
     * The method checks the input against a set of regular expressions
     * for constant name format.
     */
    public function testConstantName()
    {
        // Valid constant names
        $this->assertTrue(Validate::constantName('CONSTANT_NAME'));
        $this->assertTrue(Validate::constantName('Another_Constant456'));
        $this->assertTrue(Validate::constantName('THIRD_CONSTANT'));

        // Invalid constant names
        $this->assertFalse(Validate::constantName('Invalid Constant Name'));
        $this->assertFalse(Validate::constantName('Another-Invalid-Name'));
    }

    /**
     * This test validates the path name with the pathName method from
     * the Validate class.
     * The method checks the input against a set of regular expressions
     * for path name format.
     */
    public function testNameFriendly()
    {
        // Valid name friendly formats
        $this->assertTrue(Validate::nameFriendly('ValidName'));
        $this->assertTrue(Validate::nameFriendly('Another_Valid_Name123'));
        $this->assertTrue(Validate::nameFriendly('A_VALID_NAME'));
        $this->assertTrue(Validate::nameFriendly('_StartingWithUnderscoreName'));

        // Invalid name friendly formats
        $this->assertFalse(Validate::nameFriendly('Invalid Name'));
        $this->assertFalse(Validate::nameFriendly('Another-Invalid-Name'));
        $this->assertFalse(Validate::nameFriendly('123InvalidStartingWithNumber'));
        $this->assertFalse(Validate::nameFriendly(''));
    }

    /**
     * This test validates the path name with the pathName method from
     * the Validate class.
     * The method checks the input against a set of regular expressions
     * for path name format.
     */
    public function testPathName()
    {
        // Valid path names
        $this->assertTrue(Validate::pathName('/path/to/some/file'));
        $this->assertTrue(Validate::pathName('another/valid/path'));
        $this->assertTrue(Validate::pathName('valid_path-name'));

        // Invalid path names
        $this->assertFalse(Validate::pathName('invalid path name'));
        $this->assertFalse(Validate::pathName('invalid/path\name'));
        $this->assertFalse(Validate::pathName('invalid:path:name'));
        $this->assertFalse(Validate::pathName(''));
    }
}
