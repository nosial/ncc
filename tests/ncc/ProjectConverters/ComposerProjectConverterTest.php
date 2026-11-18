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

    namespace ncc\ProjectConverters;

    use FilesystemIterator;
    use ncc\Objects\Project;
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    /**
     * The fixtures only use exact dependency versions, so Packagist is never queried
     */
    class ComposerProjectConverterTest extends TestCase
    {
        private string $workDirectory;

        protected function setUp(): void
        {
            $this->workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_composer_converter_test_' . uniqid();
            mkdir($this->workDirectory, 0777, true);
        }

        protected function tearDown(): void
        {
            if(!is_dir($this->workDirectory))
            {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->workDirectory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach($iterator as $file)
            {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->workDirectory);
        }

        /**
         * Writes a composer.json (and the given empty files/directories) and converts it
         */
        private function convert(array $composerData, array $directories = [], array $files = []): Project
        {
            foreach($directories as $directory)
            {
                mkdir($this->workDirectory . DIRECTORY_SEPARATOR . $directory, 0777, true);
            }

            foreach($files as $file)
            {
                $path = $this->workDirectory . DIRECTORY_SEPARATOR . $file;
                if(!is_dir(dirname($path)))
                {
                    mkdir(dirname($path), 0777, true);
                }

                file_put_contents($path, '<?php');
            }

            $composerFile = $this->workDirectory . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($composerFile, json_encode($composerData));
            return (new ComposerProjectConverter())->convert($composerFile);
        }

        public function testAssemblyNameIsThePackageNotTheVendor(): void
        {
            $project = $this->convert(['name' => 'sebastian/diff', 'version' => '1.2.3'], ['src']);

            $this->assertSame('diff', $project->getAssembly()->getName());
            $this->assertSame('com.sebastian.diff', $project->getAssembly()->getPackage());
            $this->assertSame('1.2.3', $project->getAssembly()->getVersion());
        }

        public function testUpdateSourceIsThePackagistPackage(): void
        {
            $project = $this->convert(['name' => 'symfony/yaml', 'version' => '8.1.11'], ['src']);

            $this->assertNotNull($project->getUpdateSource());
            $this->assertSame('symfony/yaml@packagist', (string)$project->getUpdateSource());
        }

        public function testNameHelpers(): void
        {
            $this->assertSame('com.symfony.polyfill_mbstring', ComposerProjectConverter::generatePackageName('symfony/polyfill-mbstring'));
            $this->assertSame('polyfill-mbstring', ComposerProjectConverter::getAssemblyName('symfony/polyfill-mbstring'));
            $this->assertSame('php-cs-fixer', ComposerProjectConverter::getBinName('bin/php-cs-fixer'));
            $this->assertSame(['phpunit'], ComposerProjectConverter::getBins(['bin' => 'phpunit']));
            $this->assertSame([], ComposerProjectConverter::getBins(['bin' => [null, '']]));
        }

        public function testEveryBinGetsAnExecutionUnit(): void
        {
            $project = $this->convert(
                ['name' => 'squizlabs/php_codesniffer', 'version' => '3.0.0', 'bin' => ['bin/phpcs', 'bin/phpcbf']],
                ['src'],
                ['bin/phpcs', 'bin/phpcbf']
            );

            $this->assertSame('main', $project->getEntryPoint());

            $main = $project->getExecutionUnit('main');
            $this->assertNotNull($main);
            $this->assertSame('bin/phpcs', $main->getEntryPoint());
            $this->assertTrue($main->isComposerBin());

            foreach(['phpcs' => 'bin/phpcs', 'phpcbf' => 'bin/phpcbf'] as $name => $entry)
            {
                $unit = $project->getExecutionUnit($name);
                $this->assertNotNull($unit, $name);
                $this->assertSame($entry, $unit->getEntryPoint());
                $this->assertTrue($unit->isComposerBin());
            }
        }

        public function testDuplicateBinNamesAreMadeUnique(): void
        {
            $project = $this->convert(
                ['name' => 'acme/tools', 'version' => '1.0.0', 'bin' => ['bin/tool', 'scripts/tool']],
                ['src'],
                ['bin/tool', 'scripts/tool']
            );

            $this->assertSame('bin/tool', $project->getExecutionUnit('tool')->getEntryPoint());
            $this->assertSame('scripts/tool', $project->getExecutionUnit('tool-2')->getEntryPoint());
        }

        public function testAutoloadFilesRunOnImportInEveryBuildConfiguration(): void
        {
            $project = $this->convert(
                ['name' => 'phpunit/phpunit', 'version' => '14.0.0', 'autoload' => ['classmap' => ['src/'], 'files' => ['src/Framework/Assert/Functions.php']]],
                ['src'],
                ['src/Framework/Assert/Functions.php']
            );

            foreach(['release', 'debug'] as $configuration)
            {
                $buildConfiguration = $project->getBuildConfiguration($configuration);
                $this->assertSame(['src/Framework/Assert/Functions.php'], $buildConfiguration->getAutoloadFiles(), $configuration);
                $this->assertSame('phpunit/phpunit', $buildConfiguration->getOptions()[ComposerProjectConverter::COMPOSER_NAME_OPTION], $configuration);
            }
        }

        public function testPackageRootIsTheSourcePath(): void
        {
            // Code like PHPUnit's SchemaFinder reaches files next to src/ through __DIR__, the layout has to stay intact
            $project = $this->convert(
                ['name' => 'acme/mixed', 'version' => '1.0.0', 'autoload' => ['psr-4' => ['Acme\\' => 'src/'], 'psr-0' => ['Legacy_' => 'lib/'], 'classmap' => ['classes/']]],
                ['src', 'lib', 'classes', 'schema']
            );

            $this->assertSame('.', $project->getSourcePath());
        }

        public function testVendorBuildOutputAndArchiveExclusionsAreExcluded(): void
        {
            file_put_contents($this->workDirectory . DIRECTORY_SEPARATOR . '.gitattributes', implode("\n", [
                '/tests         export-ignore',
                '/phpunit.xml   export-ignore',
                '*.php diff=php',
                '# /commented export-ignore',
            ]));

            $project = $this->convert(
                ['name' => 'acme/excludes', 'version' => '1.0.0', 'archive' => ['exclude' => ['/docs/', '!/docs/keep.md']], 'autoload' => ['exclude-from-classmap' => ['/Tests/']]],
                ['src']
            );

            foreach(['release', 'debug'] as $configuration)
            {
                $buildConfiguration = $project->getBuildConfiguration($configuration);
                foreach(['vendor', 'vendor/*', 'target/' . $configuration, 'target/' . $configuration . '/*', 'tests', 'tests/*', 'phpunit.xml', 'docs', 'docs/*'] as $pattern)
                {
                    $this->assertContains($pattern, $buildConfiguration->getExcludedComponents(), $configuration . ': ' . $pattern);
                    $this->assertContains($pattern, $buildConfiguration->getExcludedResources(), $configuration . ': ' . $pattern);
                }

                $this->assertContains('Tests', $buildConfiguration->getExcludedComponents());
                $this->assertContains('Tests/*', $buildConfiguration->getExcludedComponents());
                $this->assertNotContains('docs/keep.md', $buildConfiguration->getExcludedComponents());
                $this->assertNotContains('commented', $buildConfiguration->getExcludedComponents());
            }
        }

        public function testGetExcludedPathsNormalizesPatterns(): void
        {
            $this->assertSame(['vendor', 'build', '*.md'], ComposerProjectConverter::getExcludedPaths($this->workDirectory, ['archive' => ['exclude' => ['/build/', '\\build', '*.md', '../escape', '/']]]));
        }

        public function testExactDependencyVersionsAreResolvedOffline(): void
        {
            $project = $this->convert(
                [
                    'name' => 'acme/app',
                    'version' => '1.0.0',
                    'require' => ['php' => '>=8.3', 'ext-json' => '*', 'composer-runtime-api' => '^2.2', 'acme/dep-one' => '1.2.3'],
                    'require-dev' => ['acme/dev-tool' => '2.0.0'],
                ],
                ['src']
            );

            $dependencies = $project->getDependencies();
            $this->assertCount(1, $dependencies);
            $this->assertArrayHasKey('com.acme.dep_one', $dependencies);
            $this->assertSame(['json'], $project->getExtensions());

            $debugDependencies = $project->getBuildConfiguration('debug')->getDependencies();
            $this->assertCount(1, $debugDependencies);
            $this->assertArrayHasKey('com.acme.dev_tool', $debugDependencies);
        }
    }
