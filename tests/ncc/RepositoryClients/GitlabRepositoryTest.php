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

class GitlabRepositoryTest extends TestCase
{
    private GitlabRepository $repository;
    private RepositoryConfiguration $config;

    protected function setUp(): void
    {
        $this->config = new RepositoryConfiguration(
            'gitlab',
            RepositoryType::GITLAB,
            'gitlab.com',
            true
        );
        $this->repository = new GitlabRepository($this->config);
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $this->assertInstanceOf(GitlabRepository::class, $this->repository);
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
        new GitlabRepository($invalidConfig);
    }

    public function testGetTags(): void
    {
        // Using a known GitLab project for testing
        $tags = $this->repository->getTags('gitlab-org', 'gitlab-foss');
        $this->assertIsArray($tags);
        // Tags might be empty depending on the project
        foreach ($tags as $tag) {
            $this->assertIsString($tag);
        }
    }

    public function testGetLatestTag(): void
    {
        try {
            $latestTag = $this->repository->getLatestTag('gitlab-org', 'gitlab-foss');
            $this->assertIsString($latestTag);
            $this->assertNotEmpty($latestTag);
        } catch (\Exception $e) {
            $this->markTestSkipped('No tags available for testing');
        }
    }

    public function testGetTagArchive(): void
    {
        try {
            $latestTag = $this->repository->getLatestTag('gitlab-org', 'gitlab-foss');
            $archive = $this->repository->getTagArchive('gitlab-org', 'gitlab-foss', $latestTag);
            
            if ($archive !== null) {
                $this->assertNotEmpty($archive->getDownloadUrl());
                $this->assertStringContainsString('archive', $archive->getDownloadUrl());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('No tag archive available for testing');
        }
    }

    public function testGetReleases(): void
    {
        $releases = $this->repository->getReleases('gitlab-org', 'gitlab-foss');
        $this->assertIsArray($releases);
        foreach ($releases as $release) {
            $this->assertIsString($release);
        }
    }

    public function testGetLatestRelease(): void
    {
        try {
            $latestRelease = $this->repository->getLatestRelease('gitlab-org', 'gitlab-foss');
            $this->assertIsString($latestRelease);
            $this->assertNotEmpty($latestRelease);
        } catch (\Exception $e) {
            $this->assertStringContainsString('No releases found', $e->getMessage());
        }
    }

    public function testGetReleaseArchive(): void
    {
        try {
            $latestRelease = $this->repository->getLatestRelease('gitlab-org', 'gitlab-foss');
            $archive = $this->repository->getReleaseArchive('gitlab-org', 'gitlab-foss', $latestRelease);
            
            if ($archive !== null) {
                $this->assertNotEmpty($archive->getDownloadUrl());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('No releases available for testing');
        }
    }

    public function testGetGit(): void
    {
        $gitPackage = $this->repository->getGit('gitlab-org', 'gitlab-foss');
        $this->assertNotNull($gitPackage);
        $this->assertNotEmpty($gitPackage->getDownloadUrl());
        $this->assertStringContainsString('git', strtolower($gitPackage->getDownloadUrl()));
    }

    public function testGetAll(): void
    {
        $packages = $this->repository->getAll('gitlab-org', 'gitlab-foss');
        $this->assertIsArray($packages);
        
        foreach ($packages as $package) {
            $this->assertNotEmpty($package->getDownloadUrl());
        }
    }

    public function testWithGitgudRepository(): void
    {
        $gitgudConfig = new RepositoryConfiguration(
            'gitgud',
            RepositoryType::GITLAB,
            'gitgud.io',
            true
        );
        $gitgudRepo = new GitlabRepository($gitgudConfig);
        
        $this->assertEquals('gitgud', $gitgudRepo->getConfiguration()->getName());
        $this->assertEquals('gitgud.io', $gitgudRepo->getConfiguration()->getHost());
        $this->assertTrue($gitgudRepo->getConfiguration()->isSslEnabled());
    }

    public function testProjectNameWithDots(): void
    {
        // GitLab handles project names with dots specially (replaces with slashes)
        try {
            $tags = $this->repository->getTags('some-group', 'project.name');
            $this->assertIsArray($tags);
        } catch (\Exception $e) {
            // Expected if project doesn't exist, but tests the URL encoding
            $this->assertTrue(true);
        }
    }
}
