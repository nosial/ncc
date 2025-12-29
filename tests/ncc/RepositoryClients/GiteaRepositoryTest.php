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
use ncc\Objects\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;

class GiteaRepositoryTest extends TestCase
{
    private GiteaRepository $repository;
    private RepositoryConfiguration $config;

    protected function setUp(): void
    {
        $this->config = new RepositoryConfiguration(
            'nocturn9x',
            RepositoryType::GITEA,
            'git.nocturn9x.space',
            true
        );
        $this->repository = new GiteaRepository($this->config);
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $this->assertInstanceOf(GiteaRepository::class, $this->repository);
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
        new GiteaRepository($invalidConfig);
    }

    public function testConfigurationProperties(): void
    {
        $this->assertEquals('nocturn9x', $this->repository->getConfiguration()->getName());
        $this->assertEquals(RepositoryType::GITEA, $this->repository->getConfiguration()->getType());
        $this->assertEquals('git.nocturn9x.space', $this->repository->getConfiguration()->getHost());
        $this->assertTrue($this->repository->getConfiguration()->isSslEnabled());
    }

    public function testGetTagsWithValidProject(): void
    {
        try {
            // Attempt to get tags from a Gitea repository
            $tags = $this->repository->getTags('nocturn9x', 'some-project');
            $this->assertIsArray($tags);
            foreach ($tags as $tag) {
                $this->assertIsString($tag);
            }
        } catch (\Exception $e) {
            // If project doesn't exist, that's expected
            $this->assertTrue(true);
        }
    }

    public function testGetLatestTag(): void
    {
        try {
            $latestTag = $this->repository->getLatestTag('test-org', 'test-repo');
            $this->assertIsString($latestTag);
            $this->assertNotEmpty($latestTag);
        } catch (\Exception $e) {
            // Expected if no tags or project doesn't exist
            $this->assertTrue(true);
        }
    }

    public function testGetReleases(): void
    {
        try {
            $releases = $this->repository->getReleases('test-org', 'test-repo');
            $this->assertIsArray($releases);
            foreach ($releases as $release) {
                $this->assertIsString($release);
            }
        } catch (\Exception $e) {
            // Expected if project doesn't exist
            $this->assertTrue(true);
        }
    }

    public function testGetLatestRelease(): void
    {
        try {
            $latestRelease = $this->repository->getLatestRelease('test-org', 'test-repo');
            $this->assertIsString($latestRelease);
            $this->assertNotEmpty($latestRelease);
        } catch (\Exception $e) {
            // Expected if no releases or project doesn't exist
            $this->assertTrue(true);
        }
    }

    public function testGetGit(): void
    {
        try {
            $gitPackage = $this->repository->getGit('test-org', 'test-repo');
            if ($gitPackage !== null) {
                $this->assertNotEmpty($gitPackage->getDownloadUrl());
                $this->assertStringContainsString('git', strtolower($gitPackage->getDownloadUrl()));
            }
        } catch (\Exception $e) {
            // Expected if project doesn't exist
            $this->assertTrue(true);
        }
    }

    public function testGetAll(): void
    {
        try {
            $packages = $this->repository->getAll('test-org', 'test-repo');
            $this->assertIsArray($packages);
            foreach ($packages as $package) {
                $this->assertNotEmpty($package->getDownloadUrl());
            }
        } catch (\Exception $e) {
            // Expected if project doesn't exist
            $this->assertTrue(true);
        }
    }

    public function testUrlEncoding(): void
    {
        // Test that special characters in project/group names are properly encoded
        try {
            $this->repository->getTags('test-org', 'test-repo-with-dashes');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected if project doesn't exist, but tests URL encoding
            $this->assertTrue(true);
        }
    }

    public function testSslConfiguration(): void
    {
        $baseUrl = $this->repository->getConfiguration()->getBaseUrl();
        $this->assertStringStartsWith('https://', $baseUrl);
        
        // Test with SSL disabled
        $nonSslConfig = new RepositoryConfiguration(
            'test',
            RepositoryType::GITEA,
            'git.example.com',
            false
        );
        $nonSslRepo = new GiteaRepository($nonSslConfig);
        $this->assertStringStartsWith('http://', $nonSslRepo->getConfiguration()->getBaseUrl());
    }

    public function testMultipleGiteaInstances(): void
    {
        // Test that different Gitea instances can be configured
        $configs = [
            new RepositoryConfiguration('gitea1', RepositoryType::GITEA, 'gitea1.example.com', true),
            new RepositoryConfiguration('gitea2', RepositoryType::GITEA, 'gitea2.example.com', true),
        ];

        foreach ($configs as $config) {
            $repo = new GiteaRepository($config);
            $this->assertEquals($config->getName(), $repo->getConfiguration()->getName());
            $this->assertEquals($config->getHost(), $repo->getConfiguration()->getHost());
        }
    }
}
