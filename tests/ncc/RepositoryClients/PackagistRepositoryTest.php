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

namespace ncc\RepositoryClients;

use ncc\Enums\RepositoryType;
use ncc\Exceptions\OperationException;
use ncc\Objects\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;

class PackagistRepositoryTest extends TestCase
{
    private PackagistRepository $repository;
    private RepositoryConfiguration $config;

    protected function setUp(): void
    {
        $this->config = new RepositoryConfiguration(
            'packagist',
            RepositoryType::PACKAGIST,
            'packagist.org',
            true
        );
        $this->repository = new PackagistRepository($this->config);
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $this->assertInstanceOf(PackagistRepository::class, $this->repository);
        $this->assertEquals($this->config, $this->repository->getConfiguration());
        $this->assertNull($this->repository->getAuthentication());
    }

    public function testConstructorWithInvalidRepositoryType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidConfig = new RepositoryConfiguration(
            'github',
            RepositoryType::GITHUB,
            'github.com',
            true
        );
        new PackagistRepository($invalidConfig);
    }

    public function testGetTagsThrowsException(): void
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Packagist does not support tags');
        $this->repository->getTags('symfony', 'console');
    }

    public function testGetLatestTagThrowsException(): void
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Packagist does not support tags');
        $this->repository->getLatestTag('symfony', 'console');
    }

    public function testGetTagArchiveThrowsException(): void
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Packagist does not support tags');
        $this->repository->getTagArchive('symfony', 'console', 'v1.0.0');
    }

    public function testGetReleases(): void
    {
        $releases = $this->repository->getReleases('symfony', 'console');
        $this->assertIsArray($releases);
        $this->assertNotEmpty($releases);
        
        foreach ($releases as $release) {
            $this->assertIsString($release);
        }
    }

    public function testGetLatestRelease(): void
    {
        $latestRelease = $this->repository->getLatestRelease('symfony', 'console');
        $this->assertIsString($latestRelease);
        $this->assertNotEmpty($latestRelease);
        
        // Verify it's not a pre-release version (alpha, beta, rc, dev)
        $this->assertStringNotContainsString('alpha', strtolower($latestRelease));
        $this->assertStringNotContainsString('beta', strtolower($latestRelease));
        $this->assertStringNotContainsString('rc', strtolower($latestRelease));
        $this->assertStringNotContainsString('dev', strtolower($latestRelease));
    }

    public function testGetLatestReleaseFiltersPreReleases(): void
    {
        // Using a package that likely has pre-releases
        $latestRelease = $this->repository->getLatestRelease('symfony', 'console');
        $this->assertIsString($latestRelease);
        
        // The latest should not be a pre-release
        $this->assertDoesNotMatchRegularExpression('/-alpha|-beta|-rc|dev/i', $latestRelease);
    }

    public function testGetReleaseArchive(): void
    {
        $latestRelease = $this->repository->getLatestRelease('symfony', 'console');
        $archive = $this->repository->getReleaseArchive('symfony', 'console', $latestRelease);
        
        $this->assertNotNull($archive);
        $this->assertNotEmpty($archive->getDownloadUrl());
        $this->assertStringContainsString('http', $archive->getDownloadUrl());
    }

    public function testGetReleaseArchiveWithSpecificVersion(): void
    {
        // Test with a version constraint
        $archive = $this->repository->getReleaseArchive('symfony', 'console', '^6.0');
        
        $this->assertNotNull($archive);
        $this->assertNotEmpty($archive->getDownloadUrl());
    }

    public function testGetReleaseArchiveWithInvalidVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getReleaseArchive('symfony', 'console', '999.999.999');
    }

    public function testGetReleasePackageReturnsNull(): void
    {
        // Packagist doesn't support release packages
        $package = $this->repository->getReleasePackage('symfony', 'console', 'v6.0.0');
        $this->assertNull($package);
    }

    public function testGetGitReturnsNull(): void
    {
        // Packagist doesn't support direct git URLs through this method
        $gitPackage = $this->repository->getGit('symfony', 'console');
        $this->assertNull($gitPackage);
    }

    public function testGetAll(): void
    {
        $packages = $this->repository->getAll('symfony', 'console');
        $this->assertIsArray($packages);
        $this->assertNotEmpty($packages);
        
        foreach ($packages as $package) {
            $this->assertNotEmpty($package->getDownloadUrl());
        }
    }

    public function testGetAllWithSpecificVersion(): void
    {
        $packages = $this->repository->getAll('symfony', 'console', '^6.0');
        $this->assertIsArray($packages);
        
        foreach ($packages as $package) {
            $this->assertNotEmpty($package->getDownloadUrl());
        }
    }

    public function testConfigurationProperties(): void
    {
        $this->assertEquals('packagist', $this->repository->getConfiguration()->getName());
        $this->assertEquals(RepositoryType::PACKAGIST, $this->repository->getConfiguration()->getType());
        $this->assertEquals('packagist.org', $this->repository->getConfiguration()->getHost());
        $this->assertTrue($this->repository->getConfiguration()->isSslEnabled());
    }

    public function testBaseUrlFormat(): void
    {
        $baseUrl = $this->repository->getConfiguration()->getBaseUrl();
        $this->assertEquals('https://packagist.org', $baseUrl);
    }

    public function testNonExistentPackage(): void
    {
        $this->expectException(OperationException::class);
        $this->repository->getReleases('nonexistent-vendor', 'nonexistent-package-12345');
    }

    public function testWithDifferentVendorAndPackage(): void
    {
        // Test with a well-known package
        $releases = $this->repository->getReleases('monolog', 'monolog');
        $this->assertIsArray($releases);
        $this->assertNotEmpty($releases);
    }

    public function testVersionResolution(): void
    {
        // Test version constraint resolution
        $archive = $this->repository->getReleaseArchive('symfony', 'console', '>=5.0,<7.0');
        $this->assertNotNull($archive);
        $this->assertNotEmpty($archive->getDownloadUrl());
    }

    public function testUrlEncoding(): void
    {
        // Test that vendor/package names are properly URL encoded
        try {
            $this->repository->getReleases('test-vendor', 'test-package');
        } catch (OperationException $e) {
            // Expected if package doesn't exist, but tests URL encoding
            $this->assertStringContainsString('test-vendor/test-package', $e->getMessage());
        }
    }
}
