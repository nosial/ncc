<?php
/*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

namespace ncc\Abstracts;

use ncc\Enums\RemotePackageType;
use ncc\Enums\RepositoryType;
use ncc\Exceptions\OperationException;
use ncc\Objects\RemotePackage;
use ncc\Objects\RepositoryConfiguration;
use PHPUnit\Framework\TestCase;

class AbstractRepositoryTest extends TestCase
{
    /**
     * Creates a repository whose API calls all fail with the given message (or succeed when it is null), counting how
     * often the latest release is resolved
     */
    private function createRepository(?string $failure): AbstractRepository
    {
        $configuration = new RepositoryConfiguration('test', RepositoryType::GITEA, 'git.example.com', true);

        return new class($configuration, $failure) extends AbstractRepository
        {
            public int $latestReleaseCalls = 0;

            public function __construct(RepositoryConfiguration $configuration, private readonly ?string $failure)
            {
                parent::__construct($configuration);
            }

            private function fail(): void
            {
                if($this->failure !== null)
                {
                    throw new OperationException($this->failure);
                }
            }

            public function getTags(string $group, string $project): array { $this->fail(); return ['1.0.0']; }
            public function getLatestTag(string $group, string $project): string { $this->fail(); return '1.0.0'; }
            public function getReleases(string $group, string $project): array { $this->fail(); return ['1.0.0']; }

            public function getLatestRelease(string $group, string $project): string
            {
                $this->latestReleaseCalls++;
                $this->fail();
                return '1.0.0';
            }

            public function getTagArchive(string $group, string $project, string $tag): ?RemotePackage
            {
                $this->fail();
                return new RemotePackage('https://git.example.com/tag.zip', RemotePackageType::SOURCE_ZIP, $group, $project, $tag);
            }

            public function getReleaseArchive(string $group, string $project, string $release): ?RemotePackage
            {
                $this->fail();
                return new RemotePackage('https://git.example.com/release.zip', RemotePackageType::SOURCE_ZIP, $group, $project, $release);
            }

            public function getReleasePackage(string $group, string $project, string $release): ?RemotePackage
            {
                $this->fail();
                return new RemotePackage('https://git.example.com/release.ncc', RemotePackageType::NCC, $group, $project, $release);
            }

            public function getReleaseAssetUrl(string $group, string $project, string $release, string $assetName): ?string
            {
                $this->fail();
                return null;
            }

            protected function fetchGit(string $group, string $project): ?RemotePackage
            {
                $this->fail();
                return new RemotePackage('https://git.example.com/group/project.git', RemotePackageType::SOURCE_GIT, $group, $project);
            }
        };
    }

    public function testGetAllCollectsUniqueErrorsWhenRepositoryIsUnavailable(): void
    {
        $repository = $this->createRepository('Resource not found for group/project, server returned 404 Not Found');

        $errors = null;
        $results = $repository->getAll('group', 'project', null, $errors);

        // Only the assumed git URL remains, it has no version
        $this->assertCount(1, $results);
        $this->assertSame(RemotePackageType::SOURCE_GIT, $results[0]->getType());
        $this->assertNull($results[0]->getVersion());

        // The same failure of every source is reported once
        $this->assertSame(['Resource not found for group/project, server returned 404 Not Found'], $errors);
    }

    public function testGetAllResolvesTheLatestReleaseOnlyOnce(): void
    {
        $repository = $this->createRepository('Resource not found');
        $repository->getAll('group', 'project');
        $this->assertSame(1, $repository->latestReleaseCalls);

        $repository = $this->createRepository(null);
        $repository->getAll('group', 'project');
        $this->assertSame(1, $repository->latestReleaseCalls);
    }

    public function testGetAllDoesNotResolveTheLatestReleaseForASpecificVersion(): void
    {
        $repository = $this->createRepository(null);
        $errors = null;
        $results = $repository->getAll('group', 'project', '1.0.0', $errors);

        $this->assertSame(0, $repository->latestReleaseCalls);
        $this->assertSame([], $errors);
        $this->assertCount(4, $results);
    }

    public function testGetAllTreatsLatestAsNoVersion(): void
    {
        $repository = $this->createRepository(null);
        $repository->getAll('group', 'project', 'latest');
        $this->assertSame(1, $repository->latestReleaseCalls);
    }

    public function testGetAllWorksWithoutErrorsArgument(): void
    {
        $repository = $this->createRepository('Resource not found');
        $this->assertCount(1, $repository->getAll('group', 'project'));
    }
}
