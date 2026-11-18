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
use ncc\Objects\Authentication\AccessToken;
use ncc\Objects\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;

class GithubRepositoryTest extends TestCase
{
    private GithubRepository $repository;
    private GithubRepository $apiRepository;
    private RepositoryConfiguration $config;

    protected function setUp(): void
    {
        $this->config = new RepositoryConfiguration(
            'github',
            RepositoryType::GITHUB,
            'api.github.com',
            true
        );
        $this->repository = new GithubRepository($this->config);

        // Live API calls are authenticated when a token is available (eg; GITHUB_TOKEN in CI)
        $token = getenv('GITHUB_TOKEN');
        $this->apiRepository = is_string($token) && $token !== '' ? new GithubRepository($this->config, new AccessToken($token)) : $this->repository;
    }

    public function testConstructorWithValidConfiguration(): void
    {
        $this->assertInstanceOf(GithubRepository::class, $this->repository);
        $this->assertEquals($this->config, $this->repository->getConfiguration());
        $this->assertNull($this->repository->getAuthentication());
    }

    public function testConstructorWithInvalidRepositoryType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidConfig = new RepositoryConfiguration(
            'gitlab',
            RepositoryType::GITLAB,
            'gitlab.com',
            true
        );
        new GithubRepository($invalidConfig);
    }

    /**
     * Calls the live GitHub API, skipping the test when the API rate limit is exceeded (unauthenticated requests
     * from shared CI runners easily hit it). Set GITHUB_TOKEN to authenticate the requests.
     */
    private function callApi(callable $call): mixed
    {
        try {
            return $call($this->apiRepository);
        } catch (OperationException $e) {
            if (str_contains($e->getMessage(), 'API rate limit')) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }

    public function testGetTags(): void
    {
        $tags = $this->callApi(fn(GithubRepository $repository) => $repository->getTags('nosial', 'configlib'));
        $this->assertIsArray($tags);
        $this->assertNotEmpty($tags);
        foreach ($tags as $tag) {
            $this->assertIsString($tag);
        }
    }

    public function testGetLatestTag(): void
    {
        $latestTag = $this->callApi(fn(GithubRepository $repository) => $repository->getLatestTag('nosial', 'configlib'));
        $this->assertIsString($latestTag);
        $this->assertNotEmpty($latestTag);
    }

    public function testGetTagArchive(): void
    {
        $archive = $this->callApi(fn(GithubRepository $repository) => $repository->getTagArchive('nosial', 'configlib', $repository->getLatestTag('nosial', 'configlib')));

        if ($archive !== null) {
            $this->assertNotEmpty($archive->getDownloadUrl());
        } else {
            $this->markTestSkipped('No tag archive available');
        }
    }

    public function testGetReleases(): void
    {
        $releases = $this->callApi(fn(GithubRepository $repository) => $repository->getReleases('nosial', 'configlib'));
        $this->assertIsArray($releases);
        // Releases might be empty for some projects
        foreach ($releases as $release) {
            $this->assertIsString($release);
        }
    }

    public function testGetLatestRelease(): void
    {
        try {
            $latestRelease = $this->callApi(fn(GithubRepository $repository) => $repository->getLatestRelease('nosial', 'configlib'));
            $this->assertIsString($latestRelease);
            $this->assertNotEmpty($latestRelease);
        } catch (OperationException $e) {
            // Some projects might not have releases
            $this->assertStringContainsString('No releases found', $e->getMessage());
        }
    }

    public function testGetReleaseArchive(): void
    {
        try {
            $archive = $this->callApi(fn(GithubRepository $repository) => $repository->getReleaseArchive('nosial', 'configlib', $repository->getLatestRelease('nosial', 'configlib')));

            if ($archive !== null) {
                $this->assertNotEmpty($archive->getDownloadUrl());
            }
        } catch (OperationException $e) {
            $this->markTestSkipped('No releases available for testing');
        }
    }

    public function testGetGit(): void
    {
        $gitPackage = $this->apiRepository->getGit('nosial', 'configlib');
        $this->assertNotNull($gitPackage);
        $this->assertNotEmpty($gitPackage->getDownloadUrl());
        $this->assertStringContainsString('git', strtolower($gitPackage->getDownloadUrl()));
    }

    public function testGetAll(): void
    {
        $packages = $this->apiRepository->getAll('nosial', 'configlib');
        $this->assertIsArray($packages);
        $this->assertNotEmpty($packages);
        
        foreach ($packages as $package) {
            $this->assertNotEmpty($package->getDownloadUrl());
        }
    }

    public function testWithN64Repository(): void
    {
        $n64Config = new RepositoryConfiguration(
            'n64',
            RepositoryType::GITHUB,
            'git.n64.cc',
            true
        );
        $n64Repo = new GithubRepository($n64Config);
        
        $this->assertEquals('n64', $n64Repo->getConfiguration()->getName());
        $this->assertEquals('git.n64.cc', $n64Repo->getConfiguration()->getHost());
        $this->assertTrue($n64Repo->getConfiguration()->isSslEnabled());
    }
}
