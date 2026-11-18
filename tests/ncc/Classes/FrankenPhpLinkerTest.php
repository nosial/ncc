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

    class FrankenPhpLinkerTest extends TestCase
    {
        private string $workDirectory;

        private const LIBS = '-L/work/buildroot/lib -lphp -lcurl -lz -lpthread -lm -lc';
        private const INCLUDES = '-I/work/buildroot/include -I/work/buildroot/include/php';

        protected function setUp(): void
        {
            $this->workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_frankenphp_linker_test_' . uniqid();
            mkdir($this->workDirectory . '/buildroot/lib', 0777, true);
            mkdir($this->workDirectory . '/source/frankenphp/caddy/frankenphp', 0777, true);
        }

        protected function tearDown(): void
        {
            foreach(['buildroot/lib/libphp.a.dynsym', 'source/frankenphp/caddy/frankenphp/default.pgo'] as $file)
            {
                @unlink($this->workDirectory . '/' . $file);
            }

            foreach(['source/frankenphp/caddy/frankenphp', 'source/frankenphp/caddy', 'source/frankenphp', 'source', 'buildroot/lib', 'buildroot', ''] as $directory)
            {
                @rmdir($this->workDirectory . '/' . $directory);
            }
        }

        private function createLinker(string $osFamily='Linux', bool $static=true, string $libs=self::LIBS, array $variables=[]): FrankenPhpLinker
        {
            return new FrankenPhpLinker(
                $this->workDirectory,
                $this->workDirectory . '/source/frankenphp',
                '/pkgroot/x86_64-linux/go-xcaddy',
                '/pkgroot/x86_64-linux/go',
                $osFamily,
                $static,
                self::INCLUDES,
                $libs,
                '8.4.26',
                'v1.13.0',
                array_merge([
                    'CC' => 'x86_64-linux-musl-gcc',
                    'CXX' => 'x86_64-linux-musl-g++',
                    'AR' => null,
                    'LD' => null,
                    'PATH' => '/usr/bin',
                    'SPC_DEFAULT_C_FLAGS' => '-fPIC -Os',
                    'SPC_CMD_VAR_PHP_MAKE_EXTRA_CFLAGS' => '-g -fPIE',
                    'SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES' => '--with github.com/dunglas/mercure/caddy --with github.com/dunglas/caddy-cbrotli',
                ], $variables)
            );
        }

        public function testMuslStaticFlags(): void
        {
            touch($this->workDirectory . '/buildroot/lib/libphp.a.dynsym');
            $linker = $this->createLinker();

            $this->assertSame('-fPIC -Os ' . self::INCLUDES . ' -g -fPIE -DFRANKENPHP_VERSION=1.13.0', $linker->getCgoCFlags());
            $this->assertSame('-static-pie ' . self::LIBS, $linker->getCgoLdFlags());

            $buildFlags = $linker->getGoBuildFlags();
            $this->assertStringStartsWith('-buildmode=pie -ldflags "-linkmode=external -extldflags \'-static-pie -Wl,-z,stack-size=0x80000 -Wl,--dynamic-list=', $buildFlags);
            $this->assertStringContainsString('CustomVersion=FrankenPHP v1.13.0 PHP 8.4.26 Caddy', $buildFlags);
            $this->assertStringEndsWith('-tags=static_build,nobadger,nomysql,nopgx,nobrotli,nowatcher', $buildFlags);
        }

        public function testGlibcFlags(): void
        {
            $linker = $this->createLinker('Linux', false);

            $this->assertSame(self::LIBS . ' -lrt', $linker->getCgoLdFlags());
            $this->assertStringContainsString("-extldflags '-pie '", $linker->getGoBuildFlags());
            $this->assertNotContains('static_build', $linker->getTags());
        }

        public function testDarwinFlags(): void
        {
            touch($this->workDirectory . '/buildroot/lib/libphp.a.dynsym');
            $linker = $this->createLinker('Darwin', false);

            $this->assertSame(self::LIBS, $linker->getCgoLdFlags());
            $this->assertStringNotContainsString('--dynamic-list', $linker->getGoBuildFlags());
        }

        public function testBrotliAndWatcherTags(): void
        {
            $linker = $this->createLinker(libs: self::LIBS . ' -lbrotlienc -lbrotlidec -lbrotlicommon -lwatcher-c');

            $this->assertTrue($linker->hasBrotli());
            $this->assertTrue($linker->hasWatcher());
            $this->assertNotContains('nobrotli', $linker->getTags());
            $this->assertNotContains('nowatcher', $linker->getTags());
            $this->assertContains('github.com/dunglas/caddy-cbrotli', $linker->getModuleArguments());
        }

        public function testModulesUseLocalSourceAndDropCbrotliWithoutBrotli(): void
        {
            $source = $this->workDirectory . '/source/frankenphp';
            $this->assertSame([
                '--with', 'github.com/dunglas/frankenphp=' . $source,
                '--with', 'github.com/dunglas/frankenphp/caddy=' . $source . '/caddy',
                '--with', 'github.com/dunglas/mercure/caddy',
            ], $this->createLinker()->getModuleArguments());
        }

        public function testModulesReferencingFrankenPhpAreKept(): void
        {
            $linker = $this->createLinker(variables: ['SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES' => '--with github.com/dunglas/frankenphp=/custom --with github.com/dunglas/frankenphp/caddy=/custom/caddy']);
            $this->assertSame([
                '--with', 'github.com/dunglas/frankenphp=/custom',
                '--with', 'github.com/dunglas/frankenphp/caddy=/custom/caddy',
            ], $linker->getModuleArguments());
        }

        public function testCommandAndEnvironment(): void
        {
            touch($this->workDirectory . '/source/frankenphp/caddy/frankenphp/default.pgo');
            $linker = $this->createLinker();

            $command = $linker->getCommand();
            $this->assertSame(['/pkgroot/x86_64-linux/go-xcaddy/bin/xcaddy', 'build', '--output', 'frankenphp', '--pgo'], array_slice($command, 0, 5));
            $this->assertSame($this->workDirectory . '/buildroot/bin/frankenphp', $linker->getBinaryPath());

            $environment = $linker->getEnvironment();
            $this->assertSame('1', $environment['CGO_ENABLED']);
            $this->assertSame('/pkgroot/x86_64-linux/go-xcaddy', $environment['GOROOT']);
            $this->assertSame('/pkgroot/x86_64-linux/go', $environment['GOPATH']);
            $this->assertSame('/pkgroot/x86_64-linux/go-xcaddy/bin' . PATH_SEPARATOR . '/usr/bin', $environment['PATH']);
            $this->assertSame('x86_64-linux-musl-gcc', $environment['CC']);
            $this->assertArrayNotHasKey('AR', $environment);
        }

        public function testArchiveCommand(): void
        {
            $linker = $this->createLinker();
            $this->assertSame(
                ['tar', '-cf', $this->workDirectory . '/source/frankenphp/app.tar', '-C', '/staging', '.'],
                $linker->getArchiveCommand('/staging')
            );
            $this->assertSame($this->workDirectory . '/source/frankenphp/app_checksum.txt', $linker->getApplicationChecksumPath());
        }
    }
