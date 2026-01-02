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

namespace ncc\Classes;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class PathResolverTest extends TestCase
{
    /**
     * Test that getUserHome returns a non-empty string
     */
    public function testGetUserHome(): void
    {
        $home = PathResolver::getUserHome();
        
        $this->assertNotEmpty($home, 'User home directory should not be empty');
        $this->assertIsString($home, 'User home directory should be a string');
        $this->assertDirectoryExists($home, 'User home directory should exist');
        $this->assertStringNotContainsString(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, $home, 'Path should not contain double separators');
    }

    /**
     * Test that getUserHome throws exception when home cannot be resolved
     */
    public function testGetUserHomeThrowsExceptionWhenNotResolvable(): void
    {
        // Save original environment variables
        $originalHome = getenv('HOME');
        $originalHomePath = getenv('HOMEPATH');
        $originalHomeDrive = getenv('HOMEDRIVE');

        try {
            // Clear all home-related environment variables
            putenv('HOME=');
            putenv('HOMEPATH=');
            putenv('HOMEDRIVE=');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Could not resolve user home directory');
            
            PathResolver::getUserHome();
        } finally {
            // Restore original environment variables
            if ($originalHome !== false) {
                putenv('HOME=' . $originalHome);
            }
            if ($originalHomePath !== false) {
                putenv('HOMEPATH=' . $originalHomePath);
            }
            if ($originalHomeDrive !== false) {
                putenv('HOMEDRIVE=' . $originalHomeDrive);
            }
        }
    }

    /**
     * Test that getUserPackageManagerLocation returns user path for non-root users
     */
    public function testGetUserPackageManagerLocationAsUser(): void
    {
        // Skip if running as root
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('This test should not run as root');
        }

        $location = PathResolver::getUserLocation();
        $home = PathResolver::getUserHome();
        
        $this->assertNotNull($location, 'Non-root user should have a user-level location');
        $this->assertStringStartsWith($home, $location, 'Non-root user should use user-level path');
        $this->assertStringEndsWith('ncc', $location, 'User location should end with ncc');
    }

    /**
     * Test that getAllPackageLocations returns an array of valid paths
     */
    public function testGetAllPackageLocations(): void
    {
        $locations = PathResolver::getAllLocations();
        
        $this->assertIsArray($locations, 'Should return an array');
        $this->assertNotEmpty($locations, 'Should return at least one location');
        
        // Count depends on whether running as root (1 location) or user (2 locations)
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->assertCount(1, $locations, 'Root should have 1 location');
        } else {
            $this->assertCount(2, $locations, 'Non-root should have 2 locations');
        }
        
        foreach ($locations as $location) {
            $this->assertIsString($location, 'Each location should be a string');
            $this->assertNotEmpty($location, 'Each location should not be empty');
            $this->assertStringEndsWith('ncc', $location, 'Each path should end with ncc');
            $this->assertStringNotContainsString(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, $location, 'Path should not contain double separators');
        }
    }

    /**
     * Test that getAllPackageLocations returns user location first
     */
    public function testGetAllPackageLocationsOrderPriority(): void
    {
        // Skip if running as root since root won't have user location
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('This test should not run as root');
        }
        
        $locations = PathResolver::getAllLocations();
        $home = PathResolver::getUserHome();
        
        $this->assertStringStartsWith($home, $locations[0], 'First location should be user-level path');
        
        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'ncc', $locations[1], 'Second location should be system-level path');
            $this->assertStringNotStartsWith($home, $locations[1], 'Second location should not be under user home');
        } else {
            $expectedSystemPath = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ncc';
            $this->assertEquals($expectedSystemPath, $locations[1], 'Second location should be Unix system path');
        }
    }
}
