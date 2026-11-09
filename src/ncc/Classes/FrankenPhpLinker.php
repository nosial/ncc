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

    class FrankenPhpLinker
    {
        public const string FRANKENPHP_MODULE = 'github.com/dunglas/frankenphp';
        public const string CBROTLI_MODULE = 'github.com/dunglas/caddy-cbrotli';
        public const string APP_ARCHIVE = 'app.tar';
        public const string APP_CHECKSUM = 'app_checksum.txt';

        private string $workingDirectory;
        private string $frankenPhpSource;
        private string $goRoot;
        private string $goPath;
        private string $osFamily;
        private bool $static;
        private string $includes;
        private string $libs;
        private string $phpVersion;
        private string $frankenPhpVersion;
        /**
         * @var array<string,string|null>
         */
        private array $spcVariables;

        /**
         * FrankenPhpLinker constructor
         *
         * @param string $workingDirectory The spc working directory (containing buildroot, source & pkgroot)
         * @param string $frankenPhpSource The FrankenPHP source directory
         * @param string $goRoot The go-xcaddy package installed by `spc install-pkg go-xcaddy`
         * @param string $goPath The GOPATH used by spc, it contains the Go module cache of the previous builds
         * @param string $osFamily The OS family, eg; Linux or Darwin
         * @param bool $static True if the binary is fully static (musl on Linux)
         * @param string $includes The output of `spc spc-config <extensions> --includes`
         * @param string $libs The output of `spc spc-config <extensions> --libs`
         * @param string $phpVersion The version of the built PHP
         * @param string $frankenPhpVersion The version of FrankenPHP
         * @param array<string,string|null> $spcVariables The spc variables resolved by `spc dev:env`, see getSpcVariableNames()
         */
        public function __construct(string $workingDirectory, string $frankenPhpSource, string $goRoot, string $goPath, string $osFamily,
            bool $static, string $includes, string $libs, string $phpVersion, string $frankenPhpVersion, array $spcVariables)
        {
            $this->workingDirectory = rtrim($workingDirectory, DIRECTORY_SEPARATOR);
            $this->frankenPhpSource = rtrim($frankenPhpSource, DIRECTORY_SEPARATOR);
            $this->goRoot = rtrim($goRoot, DIRECTORY_SEPARATOR);
            $this->goPath = rtrim($goPath, DIRECTORY_SEPARATOR);
            $this->osFamily = $osFamily;
            $this->static = $static;
            $this->includes = trim($includes);
            $this->libs = trim($libs);
            $this->phpVersion = $phpVersion;
            $this->frankenPhpVersion = ltrim($frankenPhpVersion, 'v');
            $this->spcVariables = $spcVariables;
        }

        /**
         * Returns the names of the spc variables that need to be resolved with `spc dev:env` and passed to the
         * constructor.
         *
         * @return string[]
         */
        public static function getSpcVariableNames(): array
        {
            return [
                'CC', 'CXX', 'AR', 'LD', 'PATH',
                'SPC_DEFAULT_C_FLAGS',
                'SPC_CMD_VAR_PHP_MAKE_EXTRA_CFLAGS',
                'SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES',
            ];
        }

        /**
         * Returns the directory xcaddy is executed in, the binary is written to this directory
         *
         * @return string
         */
        public function getBuildDirectory(): string
        {
            return $this->workingDirectory . DIRECTORY_SEPARATOR . 'buildroot' . DIRECTORY_SEPARATOR . 'bin';
        }

        /**
         * Returns the path of the binary produced by the link
         *
         * @return string
         */
        public function getBinaryPath(): string
        {
            return $this->getBuildDirectory() . DIRECTORY_SEPARATOR . 'frankenphp';
        }

        /**
         * Returns the path of the application archive embedded by FrankenPHP (`//go:embed app.tar`)
         *
         * @return string
         */
        public function getApplicationArchivePath(): string
        {
            return $this->frankenPhpSource . DIRECTORY_SEPARATOR . self::APP_ARCHIVE;
        }

        /**
         * Returns the path of the checksum of the application archive (`//go:embed app_checksum.txt`), FrankenPHP
         * uses it to name the directory the application is extracted into.
         *
         * @return string
         */
        public function getApplicationChecksumPath(): string
        {
            return $this->frankenPhpSource . DIRECTORY_SEPARATOR . self::APP_CHECKSUM;
        }

        /**
         * Returns the command that archives the given directory as the embedded application, the same way spc does
         *
         * @param string $stagingDirectory The directory to embed
         * @return string[]
         */
        public function getArchiveCommand(string $stagingDirectory): array
        {
            return ['tar', '-cf', $this->getApplicationArchivePath(), '-C', $stagingDirectory, '.'];
        }

        /**
         * Returns the xcaddy command
         *
         * @return string[]
         */
        public function getCommand(): array
        {
            $command = [$this->goRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'xcaddy', 'build', '--output', 'frankenphp'];

            $pgo = $this->frankenPhpSource . DIRECTORY_SEPARATOR . 'caddy' . DIRECTORY_SEPARATOR . 'frankenphp' . DIRECTORY_SEPARATOR . 'default.pgo';
            if(is_file($pgo))
            {
                $command[] = '--pgo';
                $command[] = $pgo;
            }

            return array_merge($command, $this->getModuleArguments());
        }

        /**
         * Returns the environment variables of the xcaddy command, these are merged with the current environment
         *
         * @return array<string,string>
         */
        public function getEnvironment(): array
        {
            $goBin = $this->goRoot . DIRECTORY_SEPARATOR . 'bin';
            $path = $this->spcVariables['PATH'] ?? (getenv('PATH') ?: '');

            $environment = [
                'CGO_ENABLED' => '1',
                'CGO_CFLAGS' => $this->getCgoCFlags(),
                'CGO_LDFLAGS' => $this->getCgoLdFlags(),
                'XCADDY_GO_BUILD_FLAGS' => $this->getGoBuildFlags(),
                'LD_LIBRARY_PATH' => $this->getLibraryDirectory(),
                'GOROOT' => $this->goRoot,
                'GOBIN' => $goBin,
                'GOPATH' => $this->goPath,
                'PATH' => $path === '' ? $goBin : $goBin . PATH_SEPARATOR . $path,
            ];

            foreach(['CC', 'CXX', 'AR', 'LD'] as $name)
            {
                if(!empty($this->spcVariables[$name]))
                {
                    $environment[$name] = $this->spcVariables[$name];
                }
            }

            return $environment;
        }

        /**
         * Returns the CFLAGS used to compile FrankenPHP's C code
         *
         * @return string
         */
        public function getCgoCFlags(): string
        {
            return self::joinFlags([
                $this->spcVariables['SPC_DEFAULT_C_FLAGS'] ?? '-fPIC -Os',
                $this->includes,
                $this->spcVariables['SPC_CMD_VAR_PHP_MAKE_EXTRA_CFLAGS'] ?? '',
                '-DFRANKENPHP_VERSION=' . $this->frankenPhpVersion,
            ]);
        }

        /**
         * Returns the LDFLAGS used to link libphp.a and its libraries
         *
         * @return string
         */
        public function getCgoLdFlags(): string
        {
            return self::joinFlags([
                $this->static ? '-static-pie' : '',
                $this->libs,
                ($this->osFamily === 'Linux' && !$this->static) ? '-lrt' : '',
            ]);
        }

        /**
         * Returns the flags xcaddy passes to `go build`
         *
         * @return string
         */
        public function getGoBuildFlags(): string
        {
            $dynamicList = '';
            $dynamicSymbols = $this->getLibraryDirectory() . DIRECTORY_SEPARATOR . 'libphp.a.dynsym';
            if($this->osFamily === 'Linux' && is_file($dynamicSymbols))
            {
                $dynamicList = ' -Wl,--dynamic-list=' . $dynamicSymbols;
            }

            $extLdFlags = $this->static
                ? '-static-pie -Wl,-z,stack-size=0x80000' . $dynamicList . ' '
                : '-pie' . $dynamicList . ' ';

            return '-buildmode=pie '
                . '-ldflags "-linkmode=external -extldflags \'' . $extLdFlags . '\' '
                . '-X \'github.com/caddyserver/caddy/v2/modules/caddyhttp.ServerHeader=FrankenPHP Caddy\' '
                . '-X \'github.com/caddyserver/caddy/v2.CustomBinaryName=frankenphp\' '
                . '-X \'github.com/caddyserver/caddy/v2.CustomVersion=FrankenPHP v' . $this->frankenPhpVersion . ' PHP ' . $this->phpVersion . ' Caddy\'" '
                . '-tags=' . implode(',', $this->getTags());
        }

        /**
         * Returns the Go build tags, brotli and the file watcher are only enabled if spc linked their libraries
         *
         * @return string[]
         */
        public function getTags(): array
        {
            $tags = $this->static ? ['static_build'] : [];
            array_push($tags, 'nobadger', 'nomysql', 'nopgx');

            if(!$this->hasBrotli())
            {
                $tags[] = 'nobrotli';
            }

            if(!$this->hasWatcher())
            {
                $tags[] = 'nowatcher';
            }

            return $tags;
        }

        /**
         * Returns the xcaddy `--with` arguments, the local FrankenPHP source is used unless the modules already
         * reference FrankenPHP and the cbrotli module is dropped if brotli isn't linked (the same as spc)
         *
         * @return string[]
         */
        public function getModuleArguments(): array
        {
            $modules = trim((string)($this->spcVariables['SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES'] ?? ''));
            if(!str_contains($modules, '--with ' . self::FRANKENPHP_MODULE))
            {
                $modules = sprintf('--with %s=%s --with %s/caddy=%s/caddy %s',
                    self::FRANKENPHP_MODULE, $this->frankenPhpSource,
                    self::FRANKENPHP_MODULE, $this->frankenPhpSource,
                    $modules
                );
            }

            if(!$this->hasBrotli())
            {
                $modules = str_replace('--with ' . self::CBROTLI_MODULE, '', $modules);
            }

            return array_values(array_filter(preg_split('/\s+/', trim($modules)), fn($value) => $value !== ''));
        }

        /**
         * Returns True if brotli is linked into the binary
         *
         * @return bool
         */
        public function hasBrotli(): bool
        {
            return (bool)preg_match('/(^|\s)-lbrotli(enc|dec|common)(\s|$)/', $this->libs)
                || str_contains($this->libs, 'libbrotlienc.a');
        }

        /**
         * Returns True if the file watcher library is linked into the binary
         *
         * @return bool
         */
        public function hasWatcher(): bool
        {
            return (bool)preg_match('/(^|\s)-lwatcher(-c)?(\s|$)/', $this->libs)
                || str_contains($this->libs, 'libwatcher-c.a');
        }

        /**
         * Returns the directory containing libphp.a and the libraries built by spc
         *
         * @return string
         */
        private function getLibraryDirectory(): string
        {
            return $this->workingDirectory . DIRECTORY_SEPARATOR . 'buildroot' . DIRECTORY_SEPARATOR . 'lib';
        }

        /**
         * Joins flags with a single space, skipping empty values
         *
         * @param string[] $flags
         * @return string
         */
        private static function joinFlags(array $flags): string
        {
            return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter(array_map('trim', $flags), fn($flag) => $flag !== ''))));
        }
    }
