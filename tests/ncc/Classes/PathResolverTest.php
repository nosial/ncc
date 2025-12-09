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
     * Test that getPackageManagerLocation returns a valid path
     */
    public function testGetPackageManagerLocation(): void
    {
        $location = PathResolver::getPackageManagerLocation();
        
        $this->assertNotEmpty($location, 'Package manager location should not be empty');
        $this->assertIsString($location, 'Package manager location should be a string');
        $this->assertStringEndsWith('ncc' . DIRECTORY_SEPARATOR . 'packages', $location, 'Path should end with ncc/packages');
        $this->assertStringNotContainsString(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, $location, 'Path should not contain double separators');
    }

    /**
     * Test that getPackageManagerLocation returns system path when running as root
     */
    public function testGetPackageManagerLocationAsRoot(): void
    {
        // This test only runs on Unix-like systems with posix extension
        if (!function_exists('posix_geteuid')) {
            $this->markTestSkipped('posix_geteuid function not available');
        }

        $location = PathResolver::getPackageManagerLocation();
        
        if (posix_geteuid() === 0) {
            // Running as root
            $expectedPath = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'packages';
            $this->assertEquals($expectedPath, $location, 'Root should use system-level path');
        } else {
            // Running as regular user
            $home = PathResolver::getUserHome();
            $expectedPath = $home . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'packages';
            $this->assertEquals($expectedPath, $location, 'Regular user should use user-level path');
        }
    }

    /**
     * Test that getPackageManagerLocation returns user path for non-root users
     */
    public function testGetPackageManagerLocationAsUser(): void
    {
        // Skip if running as root
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('This test should not run as root');
        }

        $location = PathResolver::getPackageManagerLocation();
        $home = PathResolver::getUserHome();
        
        $this->assertStringStartsWith($home, $location, 'Non-root user should use user-level path');
    }

    /**
     * Test that getAllPackageLocations returns an array of valid paths
     */
    public function testGetAllPackageLocations(): void
    {
        $locations = PathResolver::getAllPackageLocations();
        
        $this->assertIsArray($locations, 'Should return an array');
        $this->assertNotEmpty($locations, 'Should return at least one location');
        $this->assertCount(2, $locations, 'Should return exactly 2 locations');
        
        foreach ($locations as $location) {
            $this->assertIsString($location, 'Each location should be a string');
            $this->assertNotEmpty($location, 'Each location should not be empty');
            $this->assertStringEndsWith('ncc' . DIRECTORY_SEPARATOR . 'packages', $location, 'Each path should end with ncc/packages');
            $this->assertStringNotContainsString(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, $location, 'Path should not contain double separators');
        }
    }

    /**
     * Test that getAllPackageLocations returns user location first
     */
    public function testGetAllPackageLocationsOrderPriority(): void
    {
        $locations = PathResolver::getAllPackageLocations();
        $home = PathResolver::getUserHome();
        
        $this->assertStringStartsWith($home, $locations[0], 'First location should be user-level path');
        
        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'packages', $locations[1], 'Second location should be system-level path');
            $this->assertStringNotStartsWith($home, $locations[1], 'Second location should not be under user home');
        } else {
            $expectedSystemPath = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ncc' . DIRECTORY_SEPARATOR . 'packages';
            $this->assertEquals($expectedSystemPath, $locations[1], 'Second location should be Unix system path');
        }
    }

    /**
     * Test that getAllPackageLocations includes the current package manager location
     */
    public function testGetAllPackageLocationsIncludesCurrentLocation(): void
    {
        $currentLocation = PathResolver::getPackageManagerLocation();
        $allLocations = PathResolver::getAllPackageLocations();
        
        $this->assertContains($currentLocation, $allLocations, 'All locations should include the current package manager location');
    }

    /**
     * Test path consistency across methods
     */
    public function testPathConsistencyAcrossMethods(): void
    {
        $location = PathResolver::getPackageManagerLocation();
        $allLocations = PathResolver::getAllPackageLocations();
        $home = PathResolver::getUserHome();
        
        // Ensure no mixed directory separators
        $this->assertStringNotContainsString('\\/', $location, 'Should not mix forward and back slashes');
        $this->assertStringNotContainsString('/\\', $location, 'Should not mix forward and back slashes');
        
        foreach ($allLocations as $loc) {
            $this->assertStringNotContainsString('\\/', $loc, 'Should not mix forward and back slashes');
            $this->assertStringNotContainsString('/\\', $loc, 'Should not mix forward and back slashes');
        }
        
        $this->assertStringNotContainsString('\\/', $home, 'Should not mix forward and back slashes');
        $this->assertStringNotContainsString('/\\', $home, 'Should not mix forward and back slashes');
    }
}
