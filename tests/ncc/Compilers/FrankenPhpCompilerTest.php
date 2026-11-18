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

    namespace ncc\Compilers;

    use ncc\Enums\BuildType;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;
    use PHPUnit\Framework\TestCase;
    use ReflectionMethod;

    class FrankenPhpCompilerTest extends TestCase
    {
        public function testExtractVersion(): void
        {
            $this->assertSame('8.4.26', FrankenPhpCompiler::extractVersion('php-8.4.26.tar.xz'));
            $this->assertSame('1.13.0', FrankenPhpCompiler::extractVersion('php-frankenphp-caddy-v1.13.0-0-g233f793.tar.gz'));
            $this->assertSame('1.9.0', FrankenPhpCompiler::extractVersion('v1.9.0.tar.gz'));
            $this->assertNull(FrankenPhpCompiler::extractVersion('frankenphp-main.tar.gz'));
            $this->assertNull(FrankenPhpCompiler::extractVersion(null));
        }

        public function testMatchesLockedVersion(): void
        {
            $this->assertTrue(FrankenPhpCompiler::matchesLockedVersion('php-8.4.26.tar.xz', '8.4'));
            $this->assertFalse(FrankenPhpCompiler::matchesLockedVersion('php-8.4.26.tar.xz', '8.3'));
            $this->assertFalse(FrankenPhpCompiler::matchesLockedVersion('php-8.40.1.tar.xz', '8.4'));

            // A full version must match exactly
            $this->assertTrue(FrankenPhpCompiler::matchesLockedVersion('php-8.4.26.tar.xz', '8.4.26'));
            $this->assertFalse(FrankenPhpCompiler::matchesLockedVersion('php-8.4.26.tar.xz', '8.4.27'));
            $this->assertTrue(FrankenPhpCompiler::matchesLockedVersion('php-frankenphp-caddy-v1.13.0-0-g233f793.tar.gz', 'v1.13.0'));

            // Nothing cached (or an unknown format) always downloads
            $this->assertFalse(FrankenPhpCompiler::matchesLockedVersion(null, '8.4'));
            $this->assertFalse(FrankenPhpCompiler::matchesLockedVersion('php-src', '8.4'));
        }

        public function testConfigurationFilePaths(): void
        {
            $defaults = $this->createCompiler([]);
            $arguments = $this->invokePrivate($defaults, 'getBuildArguments', '/tmp/app');
            $this->assertContains('--with-config-file-path=/etc/frankenphp', $arguments);
            $this->assertContains('--with-config-file-scan-dir=/etc/frankenphp/php.d', $arguments);

            $custom = $this->createCompiler(['php_ini_path' => '/opt/app', 'php_ini_scan_dir' => '/opt/app/conf.d']);
            $arguments = $this->invokePrivate($custom, 'getBuildArguments', '/tmp/app');
            $this->assertContains('--with-config-file-path=/opt/app', $arguments);
            $this->assertContains('--with-config-file-scan-dir=/opt/app/conf.d', $arguments);

            // The paths are compiled into libphp, so changing them must not reuse an incremental build
            $spc = tempnam(sys_get_temp_dir(), 'ncc_spc_');
            try
            {
                $this->assertNotSame(
                    $this->invokePrivate($defaults, 'computeBaseFingerprint', $spc),
                    $this->invokePrivate($custom, 'computeBaseFingerprint', $spc)
                );
            }
            finally
            {
                unlink($spc);
            }
        }

        /**
         * Creates a FrankenPHP compiler for a new project with the given build options.
         *
         * @param array $options The options of the build configuration.
         * @return FrankenPhpCompiler The compiler.
         */
        private function createCompiler(array $options): FrankenPhpCompiler
        {
            $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_frankenphp_test_' . uniqid();
            mkdir($directory . DIRECTORY_SEPARATOR . 'src', 0777, true);
            $this->directories[] = $directory;

            $project = Project::createNew('FrankenPhpTest', 'com.example.frankenphp_test');
            $configuration = BuildConfiguration::defaultRelease();
            $configuration->setName('native');
            $configuration->setOutput('target/native/app');
            $configuration->setType(BuildType::FRANKENPHP);
            $configuration->setOptions(array_merge(['work_dir' => 'target/.frankenphp'], $options));
            $project->addBuildConfiguration($configuration);
            $project->save($directory . DIRECTORY_SEPARATOR . 'project.yml');

            return new FrankenPhpCompiler($directory . DIRECTORY_SEPARATOR . 'project.yml', 'native');
        }

        private function invokePrivate(object $object, string $method, mixed ...$arguments): mixed
        {
            return (new ReflectionMethod($object, $method))->invoke($object, ...$arguments);
        }

        /** @var string[] Project directories created by the test. */
        private array $directories = [];

        protected function tearDown(): void
        {
            foreach($this->directories as $directory)
            {
                exec('rm -rf ' . escapeshellarg($directory));
            }

            $this->directories = [];
        }
    }
