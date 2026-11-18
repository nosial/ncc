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

    namespace ncc\Classes;

    use FilesystemIterator;
    use ncc\Objects\Project;
    use ncc\ProjectConverters\ComposerProjectConverter;
    use ncc\Runtime;
    use OutOfBoundsException;
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use ReflectionClass;
    use ReflectionMethod;

    /**
     * Converts, builds and runs a small Composer package (no dependencies, nothing is downloaded) the way ncc handles
     * Composer tools such as PHPUnit
     */
    class ComposerPackageBuildTest extends TestCase
    {
        private const COMPOSER_NAME = 'acme/e2etool';
        private const PACKAGE = 'com.acme.e2etool';
        private const ASSEMBLY = 'e2etool';

        private static string $workDirectory;
        private static string $builtPackage;

        public static function setUpBeforeClass(): void
        {
            self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_composer_package_test_' . uniqid();
            $files = [
                'src/Main.php' => '<?php namespace AcmeE2E; class Main { public static function name(): string { return "main"; } }',
                'src/functions.php' => '<?php if(!function_exists("acme_e2e_function")) { function acme_e2e_function(): string { return "from-src"; } }',
                'bootstrap.php' => '<?php if(!defined("ACME_E2E_BOOTSTRAPPED")) { define("ACME_E2E_BOOTSTRAPPED", true); }',
                'lib/Helper.php' => '<?php namespace AcmeE2E\Lib; class Helper { public static function name(): string { return "helper"; } }',
                // Like PHPUnit's SchemaFinder: files next to src/ are reached through __DIR__
                'src/SchemaFinder.php' => '<?php namespace AcmeE2E; class SchemaFinder { public static function available(): array { $r = []; foreach(new \DirectoryIterator(__DIR__ . "/../schema") as $f) { if(!$f->isDot()) { $r[] = $f->getBasename(".xsd"); } } sort($r); return $r; } public static function current(): string { return file_get_contents(__DIR__ . "/../e2etool.xsd"); } }',
                'schema/1.0.xsd' => '<schema version="1.0"/>',
                'schema/2.0.xsd' => '<schema version="2.0"/>',
                'e2etool.xsd' => '<schema version="current"/>',
                // Not part of what Composer installs, never packaged or autoloaded
                '.gitattributes' => "/tests export-ignore\n",
                'tests/ShouldNotExist.php' => '<?php namespace AcmeE2E\Tests; class ShouldNotExist {}',
                'vendor/other/Other.php' => '<?php namespace Other; class Other {}',
                'bin/e2etool' => "#!/usr/bin/env php\n<?php\n" .
                    '$GLOBALS["__acme_e2e_seen"] = ["argv" => $argv, "argc" => $argc, "global_argv" => $GLOBALS["argv"], ' .
                    '"autoload" => $GLOBALS["_composer_autoload_path"] ?? null, "helper" => \AcmeE2E\Lib\Helper::name(), ' .
                    '"function" => acme_e2e_function(), "bootstrapped" => defined("ACME_E2E_BOOTSTRAPPED")];' . "\n" .
                    'return 5;',
                'composer.json' => json_encode([
                    'name' => self::COMPOSER_NAME,
                    'version' => '1.0.0',
                    'autoload' => [
                        'psr-4' => ['AcmeE2E\\' => 'src/', 'AcmeE2E\\Lib\\' => 'lib/'],
                        'files' => ['src/functions.php', 'bootstrap.php'],
                    ],
                    'bin' => ['bin/e2etool'],
                ]),
            ];

            foreach($files as $file => $content)
            {
                $path = self::$workDirectory . DIRECTORY_SEPARATOR . $file;
                if(!is_dir(dirname($path)))
                {
                    mkdir(dirname($path), 0777, true);
                }

                file_put_contents($path, $content);
            }

            $project = (new ComposerProjectConverter())->convert(self::$workDirectory . DIRECTORY_SEPARATOR . 'composer.json');
            $projectFile = self::$workDirectory . DIRECTORY_SEPARATOR . 'project.yml';
            $project->save($projectFile);
            self::$builtPackage = Project::compilerFromFile($projectFile, 'release')->build();
        }

        public static function tearDownAfterClass(): void
        {
            if(!isset(self::$workDirectory) || !is_dir(self::$workDirectory))
            {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(self::$workDirectory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach($iterator as $file)
            {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir(self::$workDirectory);
        }

        public function testHeaderListsAutoloadFilesInComposerOrder(): void
        {
            $header = (new PackageReader(self::$builtPackage))->getHeader();

            $this->assertSame([
                'ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/src/functions.php',
                'ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/bootstrap.php',
            ], $header->getAutoloadFiles());
        }

        public function testEveryBinIsPackagedAsComposerBinUnit(): void
        {
            $reader = new PackageReader(self::$builtPackage);

            $unit = $reader->readExecutionUnit($reader->findExecutionUnit(self::ASSEMBLY));
            $this->assertTrue($unit->isComposerBin());
            $this->assertSame(self::ASSEMBLY . '/bin/e2etool', $unit->getEntryPoint());

            $main = $reader->readExecutionUnit($reader->findExecutionUnit('main'));
            $this->assertSame(self::ASSEMBLY . '/bin/e2etool', $main->getEntryPoint());
            $this->assertSame('main', $reader->getHeader()->getEntryPoint());
        }

        public function testAutoloaderMapsOnlyPackagedClasses(): void
        {
            $autoloader = (new PackageReader(self::$builtPackage))->getHeader()->getAutoloader();

            $this->assertSame('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/lib/Helper.php', $autoloader['AcmeE2E\Lib\Helper']);
            $this->assertSame('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/src/Main.php', $autoloader['AcmeE2E\Main']);
            $this->assertArrayNotHasKey('AcmeE2E\Tests\ShouldNotExist', $autoloader);
            $this->assertArrayNotHasKey('Other\Other', $autoloader);
        }

        public function testPackageKeepsComposerLayoutForRelativeLookups(): void
        {
            Runtime::import(self::$builtPackage);

            $this->assertSame(['1.0', '2.0'], \AcmeE2E\SchemaFinder::available());
            $this->assertSame('<schema version="current"/>', \AcmeE2E\SchemaFinder::current());
            $this->assertFileDoesNotExist('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/tests/ShouldNotExist.php');
            $this->assertFileDoesNotExist('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/vendor/other/Other.php');
        }

        public function testImportRunsAutoloadFilesAfterRegisteringClasses(): void
        {
            Runtime::import(self::$builtPackage);

            $this->assertTrue(function_exists('acme_e2e_function'));
            $this->assertSame('from-src', acme_e2e_function());
            $this->assertTrue(defined('ACME_E2E_BOOTSTRAPPED'));
            $this->assertSame('helper', \AcmeE2E\Lib\Helper::name());
            $this->assertSame('main', \AcmeE2E\Main::name());

            // The Composer compatibility stub is packaged where bin scripts look for it
            $this->assertFileExists('ncc://' . self::PACKAGE . '/' . self::ASSEMBLY . '/vendor/autoload.php');
        }

        public function testComposerBinGetsArgvAndAutoloadPathAndExitsZero(): void
        {
            $hadComposerGlobal = array_key_exists('_composer_autoload_path', $GLOBALS);
            unset($GLOBALS['__acme_e2e_seen']);

            $result = (new PackageReader(self::$builtPackage))->execute(self::ASSEMBLY, ['--version', '-h']);

            // The script returned 5, Composer bins ignore that and only exit non-zero through exit()
            $this->assertSame(0, $result);

            $seen = $GLOBALS['__acme_e2e_seen'];
            $this->assertSame(['--version', '-h'], array_slice($seen['argv'], 1));
            $this->assertSame(3, $seen['argc']);
            $this->assertSame($seen['argv'], $seen['global_argv']);
            $this->assertSame('helper', $seen['helper']);
            $this->assertSame('from-src', $seen['function']);
            $this->assertTrue($seen['bootstrapped']);
            $this->assertNotNull($seen['autoload']);
            $this->assertFileExists($seen['autoload']);
            $this->assertStringNotContainsString('ncc://', $seen['autoload']);

            $this->assertSame($hadComposerGlobal, array_key_exists('_composer_autoload_path', $GLOBALS));
            unset($GLOBALS['__acme_e2e_seen']);
        }

        public function testAutoloadShimWorksInAChildProcess(): void
        {
            $shimPath = ComposerCompat::getAutoloadShim(realpath(self::$builtPackage), self::PACKAGE);
            $this->assertNotNull($shimPath);

            // What PHPUnit's process isolation does: a fresh PHP process that only requires the autoload file
            $code = 'require $argv[1]; echo json_encode(["helper" => \AcmeE2E\Lib\Helper::name(), "function" => acme_e2e_function(), "bootstrapped" => defined("ACME_E2E_BOOTSTRAPPED")]);';
            $process = proc_open([PHP_BINARY, '-r', $code, $shimPath], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($process);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $this->assertSame(0, $exitCode, $stderr);
            $this->assertSame(['helper' => 'helper', 'function' => 'from-src', 'bootstrapped' => true], json_decode($stdout, true), $stderr);
        }

        public function testLaunchersAreNamedAfterBins(): void
        {
            $method = new ReflectionMethod(PackageManager::class, 'getLaunchers');
            $this->assertSame([self::ASSEMBLY => self::ASSEMBLY], $method->invoke(null, new PackageReader(self::$builtPackage)));

            $script = new ReflectionMethod(SymlinkManager::class, 'generateSymlinkScript');
            $this->assertStringContainsString('exec ncc exec --package="' . self::PACKAGE . '" --unit="' . self::ASSEMBLY . '" --args "$@"', $script->invoke(null, self::PACKAGE, self::ASSEMBLY));
            $this->assertStringContainsString('exec ncc exec --package="' . self::PACKAGE . '" --args "$@"', $script->invoke(null, self::PACKAGE, null));
        }

        public function testInstalledVersionsReportsImportedComposerPackages(): void
        {
            Runtime::import(self::$builtPackage);

            if(!str_ends_with((string)(new ReflectionClass(\ncc\Classes\InstalledVersions::class))->getFileName(), 'InstalledVersions.inc'))
            {
                $this->markTestSkipped('A real Composer\InstalledVersions is loaded in this process');
            }

            $this->assertTrue(\ncc\Classes\InstalledVersions::isInstalled(self::COMPOSER_NAME));
            $this->assertContains(self::COMPOSER_NAME, \ncc\Classes\InstalledVersions::getInstalledPackages());
            $this->assertSame('1.0.0', \ncc\Classes\InstalledVersions::getPrettyVersion(self::COMPOSER_NAME));
            $this->assertSame('1.0.0.0', \ncc\Classes\InstalledVersions::getVersion(self::COMPOSER_NAME));
            $this->assertNull(\ncc\Classes\InstalledVersions::getReference(self::COMPOSER_NAME));
            $this->assertSame('ncc://' . self::PACKAGE, \ncc\Classes\InstalledVersions::getInstallPath(self::COMPOSER_NAME));
            $this->assertTrue(\ncc\Classes\InstalledVersions::satisfies(null, self::COMPOSER_NAME, '^1.0'));
            $this->assertFalse(\ncc\Classes\InstalledVersions::satisfies(null, self::COMPOSER_NAME, '^2.0'));
            $this->assertFalse(\ncc\Classes\InstalledVersions::isInstalled('acme/e2etool-does-not-exist'));

            $this->expectException(OutOfBoundsException::class);
            \ncc\Classes\InstalledVersions::getVersion('acme/e2etool-does-not-exist');
        }
    }
