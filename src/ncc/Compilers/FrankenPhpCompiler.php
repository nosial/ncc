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

    namespace ncc\Compilers;

    use ncc\Classes\Logger;
    use ncc\Classes\PathResolver;
    use ncc\Classes\ShutdownHandler;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Libraries\Process\ExecutableFinder;
    use ncc\Libraries\Process\Process;
    use Phar;

    class FrankenPhpCompiler extends PackageCompiler
    {
        private const array REQUIRED_EXTENSIONS = ['ctype', 'msgpack', 'phar', 'zlib'];
        private const array DEFAULT_EXTENSIONS = [
            'apcu', 'bcmath', 'curl', 'dom', 'fileinfo', 'filter', 'iconv', 'mbstring', 'opcache', 'openssl', 'pcntl',
            'pdo', 'pdo_sqlite', 'posix', 'session', 'simplexml', 'sockets', 'sodium', 'tokenizer', 'xml', 'xmlreader',
            'xmlwriter', 'zip'
        ];

        private const string DEFAULT_PHP_VERSION = '8.4';
        private const string SPC_DOWNLOAD_URL = 'https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-%s-%s';
        private const string FRANKENPHP_TAG_URL = 'https://github.com/php/frankenphp/archive/refs/tags/v%s.tar.gz';

        private string $phpVersion;

        /**
         * @var string[]
         */
        private array $extensions;
        /**
         * @var string[]
         */
        private array $libraries;
        private ?string $libc;
        private ?string $spcPath;
        private string $workingDirectory;
        private ?string $frankenPhpSource;
        private ?string $frankenPhpVersion;
        private ?string $xcaddyModules;
        private ?array $phpIni;
        private ?string $caddyfile;
        private bool $autoFix;
        private bool $upx;
        private bool $debugSymbols;

        /**
         * @inheritDoc
         */
        public function __construct(string $projectFilePath, string $buildConfiguration)
        {
            Logger::getLogger()?->debug('Initializing FrankenPhpCompiler');
            parent::__construct($projectFilePath, $buildConfiguration);

            if(PHP_OS_FAMILY !== 'Linux' && PHP_OS_FAMILY !== 'Darwin')
            {
                throw new OperationException(sprintf('The frankenphp build type is only supported on Linux and macOS, %s is not supported', PHP_OS_FAMILY));
            }

            $options = $this->getBuildConfiguration()->getOptions() ?? [];

            $this->phpVersion = self::stringOption($options, 'php_version') ?? self::DEFAULT_PHP_VERSION;
            $this->extensions = array_values(array_unique(array_merge(
                self::listOption($options, 'extensions') ?? self::DEFAULT_EXTENSIONS,
                self::REQUIRED_EXTENSIONS
            )));
            $this->libraries = self::listOption($options, 'libraries') ?? [];
            $this->libc = self::stringOption($options, 'libc');
            $this->spcPath = self::stringOption($options, 'spc');
            $workingDirectory = self::stringOption($options, 'work_dir');
            $this->workingDirectory = $workingDirectory !== null
                ? $this->resolveProjectPath($workingDirectory)
                : (PathResolver::getUserLocation() ?? PathResolver::getTmpLocation()) . DIRECTORY_SEPARATOR . 'frankenphp';
            $this->frankenPhpSource = self::stringOption($options, 'frankenphp_source');
            $this->frankenPhpVersion = self::stringOption($options, 'frankenphp_version');
            $this->xcaddyModules = self::stringOption($options, 'xcaddy_modules');
            $this->phpIni = isset($options['php_ini']) && is_array($options['php_ini']) ? $options['php_ini'] : null;
            $this->caddyfile = self::stringOption($options, 'caddyfile');
            $this->autoFix = isset($options['auto_fix']) && $options['auto_fix'] === true;
            $this->upx = isset($options['upx']) && $options['upx'] === true;
            $this->debugSymbols = isset($options['debug_symbols']) && $options['debug_symbols'] === true;

            if($this->libc !== null && !in_array($this->libc, ['musl', 'glibc'], true))
            {
                throw new OperationException(sprintf('Invalid libc option "%s", must be either "musl" or "glibc"', $this->libc));
            }

            if($this->frankenPhpSource !== null)
            {
                $this->frankenPhpSource = $this->resolveProjectPath($this->frankenPhpSource);
                if(!IO::isDirectory($this->frankenPhpSource))
                {
                    throw new OperationException(sprintf('The frankenphp_source directory %s does not exist', $this->frankenPhpSource));
                }
            }

            if($this->caddyfile !== null)
            {
                $this->caddyfile = $this->resolveProjectPath($this->caddyfile);
                if(!IO::isFile($this->caddyfile))
                {
                    throw new OperationException(sprintf('The caddyfile %s does not exist', $this->caddyfile));
                }
            }

            Logger::getLogger()?->verbose(sprintf('PHP version: %s', $this->phpVersion));
            Logger::getLogger()?->verbose(sprintf('Extensions: %s', implode(',', $this->extensions)));
            Logger::getLogger()?->verbose(sprintf('Working directory: %s', $this->workingDirectory));
        }

        /**
         * @inheritDoc
         */
        public function compile(?callable $progressCallback=null, bool $overwrite=true): string
        {
            $outputPath = $this->getOutputPath();
            if(!$overwrite && IO::exists($outputPath))
            {
                throw new OperationException('Output file already exists and overwrite is disabled');
            }

            // The binary has no access to installed packages, so all dependencies must be embedded into the package.
            if(!$this->isStaticallyLinked() && count($this->getPackageDependencies() ?? []) > 0)
            {
                throw new OperationException('The frankenphp build type requires the "static" option to be enabled when the project has dependencies');
            }

            $entryPoint = $this->getProjectConfiguration()->getEntryPoint();
            $webEntryPoint = $this->getProjectConfiguration()->getWebEntryPoint();
            if($entryPoint === null && $webEntryPoint === null)
            {
                throw new OperationException('The frankenphp build type requires the project to define an entry_point or a web_entry_point');
            }

            // Compile the project into a standard ncc package first, it's moved into the staging directory afterward
            Logger::getLogger()?->verbose('FrankenPhpCompiler: Compiling base ncc package');
            $packagePath = parent::compile($progressCallback, true);

            $totalStages = 5;
            $stagingDirectory = PathResolver::getTmpLocation() . DIRECTORY_SEPARATOR . uniqid('frankenphp_');
            ShutdownHandler::flagTemporary($stagingDirectory);

            try
            {
                $this->reportProgress($progressCallback, 1, $totalStages, 'Preparing embedded application');
                $this->prepareStagingDirectory($stagingDirectory, $packagePath);

                $this->reportProgress($progressCallback, 2, $totalStages, 'Preparing static-php-cli');
                $spc = $this->resolveSpc();

                $this->reportProgress($progressCallback, 3, $totalStages, 'Checking build environment');
                $this->runSpc($spc, $this->autoFix ? ['doctor', '--auto-fix'] : ['doctor']);
                $this->runSpc($spc, ['install-pkg', 'go-xcaddy']);
                if($this->upx && PHP_OS_FAMILY === 'Linux')
                {
                    $this->runSpc($spc, ['install-pkg', 'upx']);
                }

                $this->reportProgress($progressCallback, 4, $totalStages, 'Downloading PHP & FrankenPHP sources');
                $this->runSpc($spc, $this->getDownloadArguments());

                // Remove the binary of a previous build so that a stale binary is never mistaken for the new one
                $builtBinary = $this->workingDirectory . DIRECTORY_SEPARATOR . 'buildroot' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'frankenphp';
                if(IO::exists($builtBinary))
                {
                    IO::delete($builtBinary);
                }

                $this->reportProgress($progressCallback, 5, $totalStages, 'Building FrankenPHP (this may take a while)');
                $this->runSpc($spc, $this->getBuildArguments($stagingDirectory));

                if(!IO::isFile($builtBinary))
                {
                    throw new OperationException(sprintf('static-php-cli finished but the FrankenPHP binary was not found at %s', $builtBinary));
                }

                if(IO::exists($outputPath))
                {
                    IO::delete($outputPath);
                }

                IO::copy($builtBinary, $outputPath);
                chmod($outputPath, 0755);

                // Stripping and compressing are done on the copy rather than by spc: spc does it in place right after
                // xcaddy runs the freshly built binary as a smoke test, which can fail with "Text file busy"
                $this->postProcessBinary($outputPath);
            }
            catch(IOException $e)
            {
                throw new OperationException(sprintf('Failed to build the FrankenPHP binary: %s', $e->getMessage()), $e->getCode(), $e);
            }

            Logger::getLogger()?->verbose(sprintf('FrankenPhpCompiler: Binary written to %s', $outputPath));
            return $outputPath;
        }

        /**
         * Creates the directory that gets embedded into the FrankenPHP binary, the layout is as follows:
         *
         * @param string $stagingDirectory The directory to create
         * @param string $packagePath The path of the compiled ncc package
         * @throws IOException Thrown if there was an IO error
         * @throws OperationException Thrown if a required file could not be resolved
         */
        private function prepareStagingDirectory(string $stagingDirectory, string $packagePath): void
        {
            $packageName = $this->getProjectConfiguration()->getAssembly()->getPackage();
            $appDirectory = $stagingDirectory . DIRECTORY_SEPARATOR . 'app';
            $runtimeDirectory = $appDirectory . DIRECTORY_SEPARATOR . 'runtime';
            $publicDirectory = $stagingDirectory . DIRECTORY_SEPARATOR . 'public';

            Logger::getLogger()?->verbose(sprintf('Preparing staging directory: %s', $stagingDirectory));
            IO::createDirectory($runtimeDirectory, 0755, true);
            IO::createDirectory($publicDirectory, 0755, true);

            // Move the compiled package into the staging directory, the output path is reserved for the binary
            IO::copy($packagePath, $appDirectory . DIRECTORY_SEPARATOR . $packageName . '.ncc');
            IO::delete($packagePath);

            $this->copyRuntime($runtimeDirectory);

            $webEntryFile = null;
            $webEntryPoint = $this->getProjectConfiguration()->getWebEntryPoint();
            if($webEntryPoint !== null)
            {
                IO::copy($this->resolveExecutionUnitFile($webEntryPoint), $appDirectory . DIRECTORY_SEPARATOR . 'web_entry.php');
                $webEntryFile = 'web_entry.php';
            }

            IO::writeFile($appDirectory . DIRECTORY_SEPARATOR . 'bootstrap.php', $this->generateBootstrap($packageName));
            IO::writeFile($stagingDirectory . DIRECTORY_SEPARATOR . 'main.php', $this->generateCliController());
            IO::writeFile($publicDirectory . DIRECTORY_SEPARATOR . 'index.php', $this->generateWebController($webEntryFile));

            if($this->phpIni !== null)
            {
                IO::writeFile($stagingDirectory . DIRECTORY_SEPARATOR . 'php.ini', $this->generatePhpIni($this->phpIni));
            }

            if($this->caddyfile !== null)
            {
                IO::copy($this->caddyfile, $stagingDirectory . DIRECTORY_SEPARATOR . 'Caddyfile');
            }
        }

        /**
         * Copies the currently running ncc runtime into the given directory so that `require 'ncc';` resolves to it
         * once the directory is added to the include path.
         *
         * @param string $runtimeDirectory The directory to copy the runtime into
         * @throws IOException Thrown if there was an IO error
         * @throws OperationException Thrown if the ncc runtime could not be located
         */
        private function copyRuntime(string $runtimeDirectory): void
        {
            // ncc is normally installed as a phar named "ncc" in the include path
            $runningPhar = Phar::running(false);
            if($runningPhar !== '' && IO::isFile($runningPhar))
            {
                Logger::getLogger()?->verbose(sprintf('Embedding ncc runtime from phar: %s', $runningPhar));
                IO::copy($runningPhar, $runtimeDirectory . DIRECTORY_SEPARATOR . 'ncc.phar');
                $this->writeRuntimeLoader($runtimeDirectory, 'ncc.phar');
                return;
            }

            // Otherwise ncc is running from a build directory, copy it as-is
            if(!defined('__NCC_DIR__') || !IO::isFile(__NCC_DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php'))
            {
                throw new OperationException('Unable to locate the ncc runtime to embed, ncc must be built or installed to use the frankenphp build type');
            }

            Logger::getLogger()?->verbose(sprintf('Embedding ncc runtime from directory: %s', __NCC_DIR__));
            IO::copy(__NCC_DIR__, $runtimeDirectory . DIRECTORY_SEPARATOR . 'ncc_src');
            $this->writeRuntimeLoader($runtimeDirectory, 'ncc_src' . DIRECTORY_SEPARATOR . 'ncc.php');
        }

        /**
         * Writes the loader named "ncc" that `require 'ncc';` resolves to.
         *
         * Packages (eg; a web entry point) may `require 'ncc';` after the bootstrap already loaded the runtime, loading
         * the ncc phar a second time is fatal when opcache is enabled ("Cannot redeclare class Extract_Phar"), so the
         * loader includes the runtime with require_once which makes every require after the first one a no-op.
         *
         * @param string $runtimeDirectory The runtime directory
         * @param string $runtimeFile The path of the runtime's entry file, relative to the runtime directory
         * @throws IOException Thrown if there was an IO error
         */
        private function writeRuntimeLoader(string $runtimeDirectory, string $runtimeFile): void
        {
            IO::writeFile($runtimeDirectory . DIRECTORY_SEPARATOR . 'ncc', sprintf(
                "<?php\n    require_once __DIR__ . DIRECTORY_SEPARATOR . %s;\n",
                var_export($runtimeFile, true)
            ));
        }

        /**
         * Resolves the file of a PHP/Web execution unit, relative to the project path first and the source path second
         *
         * @param string $unitName The name of the execution unit
         * @return string The absolute path of the execution unit's entry file
         * @throws OperationException Thrown if the execution unit or its file could not be found
         */
        private function resolveExecutionUnitFile(string $unitName): string
        {
            $executionUnit = $this->getProjectConfiguration()->getExecutionUnit($unitName);
            if($executionUnit === null)
            {
                throw new OperationException(sprintf('The execution unit %s was not found in the project configuration', $unitName));
            }

            foreach([$this->getProjectPath(), $this->getSourcePath()] as $basePath)
            {
                $path = $basePath . DIRECTORY_SEPARATOR . $executionUnit->getEntryPoint();
                if(IO::isFile($path))
                {
                    return $path;
                }
            }

            throw new OperationException(sprintf('The entrypoint %s was not found for the execution unit %s', $executionUnit->getEntryPoint(), $unitName));
        }

        /**
         * Generates the bootstrap script which loads the embedded ncc runtime, imports the embedded package and
         * returns its PackageReader.
         *
         * @param string $packageName The package name of the project
         * @return string The generated PHP code
         */
        private function generateBootstrap(string $packageName): string
        {
            $exportedPackageName = var_export($packageName, true);
            $exportedPackageFile = var_export($packageName . '.ncc', true);

            return <<<PHP
<?php
    /**
     * Auto-generated by ncc, loads the embedded ncc runtime and imports the embedded package
     */

    set_include_path(__DIR__ . DIRECTORY_SEPARATOR . 'runtime' . PATH_SEPARATOR . get_include_path());
    require_once 'ncc';

    if(!\\ncc\\Runtime::isImported({$exportedPackageName}))
    {
        import(__DIR__ . DIRECTORY_SEPARATOR . {$exportedPackageFile});
    }

    return \\ncc\\Runtime::getImportedPackage({$exportedPackageName});

PHP;
        }

        /**
         * Generates the CLI front controller which executes the package's main entry point
         *
         * @return string The generated PHP code
         */
        private function generateCliController(): string
        {
            return <<<'PHP'
<?php
    /**
     * Auto-generated by ncc, CLI front controller for the embedded package
     * Usage: ./<binary> php-cli main.php [arguments...]
     */

    try
    {
        $packageReader = require __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

        if($packageReader->getHeader()->getEntryPoint() === null)
        {
            fwrite(STDERR, "Error: No CLI entry point defined in package.\n");
            exit(1);
        }

        $result = $packageReader->execute(null, array_slice($argv ?? [], 1));
        if(is_int($result))
        {
            exit($result);
        }
    }
    catch(Throwable $e)
    {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        if(getenv('NCC_DEBUG'))
        {
            fwrite(STDERR, $e->getTraceAsString() . "\n");
        }

        exit(1);
    }

PHP;
        }

        /**
         * Generates the web front controller, which either includes the copied web entry point file or executes the
         * package's main entry point if no web entry point is defined.
         *
         * @param string|null $webEntryFile The file name of the copied web entry point inside the app directory
         * @return string The generated PHP code
         */
        private function generateWebController(?string $webEntryFile): string
        {
            if($webEntryFile !== null)
            {
                // The web entry point is included at the top level so that it runs in the global scope, the same
                // way it would when deployed as the index.php of a web server.
                $exportedWebEntryFile = var_export($webEntryFile, true);
                $execute = "require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . {$exportedWebEntryFile};";
            }
            else
            {
                $execute = '$packageReader->execute(null, []);';
            }

            return <<<PHP
<?php
    /**
     * Auto-generated by ncc, web front controller for the embedded package
     */

    try
    {
        \$packageReader = require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
    }
    catch(Throwable \$e)
    {
        http_response_code(500);
        echo 'Import Error: ' . htmlspecialchars(\$e->getMessage());
        exit(1);
    }

    {$execute}

PHP;
        }

        /**
         * Generates a php.ini file from the given directives
         *
         * @param array $directives The ini directives as key-value pairs
         * @return string The generated php.ini contents
         */
        private function generatePhpIni(array $directives): string
        {
            $lines = ['; Auto-generated by ncc'];
            foreach($directives as $key => $value)
            {
                if(!is_scalar($value))
                {
                    Logger::getLogger()?->warning(sprintf('Skipping php_ini directive %s, only scalar values are supported', $key));
                    continue;
                }

                if(is_bool($value))
                {
                    $value = $value ? 'On' : 'Off';
                }
                elseif(is_string($value) && !is_numeric($value))
                {
                    $value = '"' . addcslashes($value, '"\\') . '"';
                }

                $lines[] = sprintf('%s = %s', $key, $value);
            }

            return implode(PHP_EOL, $lines) . PHP_EOL;
        }

        /**
         * Returns the path to the spc binary, downloading it into the working directory if necessary
         *
         * @return string The path to the spc binary
         * @throws OperationException Thrown if spc could not be found or downloaded
         * @throws IOException Thrown if there was an IO error
         */
        private function resolveSpc(): string
        {
            if(!IO::isDirectory($this->workingDirectory))
            {
                IO::createDirectory($this->workingDirectory, 0755, true);
            }

            if($this->spcPath !== null)
            {
                $spcPath = $this->resolveProjectPath($this->spcPath);
                if(!IO::isFile($spcPath))
                {
                    throw new OperationException(sprintf('The spc binary %s does not exist', $spcPath));
                }

                return $spcPath;
            }

            $spcPath = $this->workingDirectory . DIRECTORY_SEPARATOR . 'spc';
            if(IO::isFile($spcPath))
            {
                return $spcPath;
            }

            $architecture = php_uname('m');
            if($architecture === 'arm64')
            {
                $architecture = 'aarch64';
            }

            $url = sprintf(self::SPC_DOWNLOAD_URL, PHP_OS_FAMILY === 'Darwin' ? 'macos' : 'linux', $architecture);
            Logger::getLogger()?->verbose(sprintf('Downloading static-php-cli from %s', $url));
            $this->downloadFile($url, $spcPath);
            chmod($spcPath, 0755);

            return $spcPath;
        }

        /**
         * Downloads a file using curl
         *
         * @param string $url The URL to download
         * @param string $destination The destination file path
         * @throws OperationException Thrown if the download failed
         */
        private function downloadFile(string $url, string $destination): void
        {
            $temporaryPath = $destination . '.download';
            $fileHandle = fopen($temporaryPath, 'wb');
            if($fileHandle === false)
            {
                throw new OperationException(sprintf('Unable to open %s for writing', $temporaryPath));
            }

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_FILE, $fileHandle);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            $success = curl_exec($curl);
            $error = curl_error($curl);
            fclose($fileHandle);

            if($success === false)
            {
                @unlink($temporaryPath);
                throw new OperationException(sprintf('Failed to download %s: %s', $url, $error));
            }

            rename($temporaryPath, $destination);
        }

        /**
         * Returns the arguments for `spc download`
         *
         * @return string[]
         */
        private function getDownloadArguments(): array
        {
            $pinFrankenPhp = $this->frankenPhpVersion !== null && $this->frankenPhpSource === null;
            $arguments = [
                'download', 'frankenphp',
                '--with-php=' . $this->phpVersion,
                '--for-extensions=' . implode(',', $this->extensions),
                '--ignore-cache-sources=' . ($pinFrankenPhp ? 'php-src,frankenphp' : 'php-src'),
                '--retry=5',
            ];

            if(count($this->libraries) > 0)
            {
                $arguments[] = '--for-libs=' . implode(',', $this->libraries);
            }

            if($this->isMusl())
            {
                $arguments[] = '--prefer-pre-built';
            }

            if($pinFrankenPhp)
            {
                $arguments[] = '--custom-url=frankenphp:' . sprintf(self::FRANKENPHP_TAG_URL, ltrim($this->frankenPhpVersion, 'v'));
            }

            return $arguments;
        }

        /**
         * Returns the arguments for `spc build`
         *
         * @param string $stagingDirectory The directory to embed into the binary
         * @return string[]
         */
        private function getBuildArguments(string $stagingDirectory): array
        {
            $arguments = [
                'build', implode(',', $this->extensions),
                '--enable-zts',
                '--build-embed',
                '--build-frankenphp',
                '--with-frankenphp-app=' . $stagingDirectory,
            ];

            if(count($this->libraries) > 0)
            {
                $arguments[] = '--with-libs=' . implode(',', $this->libraries);
            }

            // The opcache JIT is not supported on musl, the same as FrankenPHP's own build script
            if($this->isMusl())
            {
                $arguments[] = '--disable-opcache-jit';
            }

            // The binary is stripped and compressed by postProcessBinary() instead
            $arguments[] = '--no-strip';

            return $arguments;
        }

        /**
         * Strips the debug symbols from the binary (unless `debug_symbols` is enabled) and compresses it with UPX if
         * the `upx` option is enabled.
         *
         * @param string $binaryPath The path of the binary to process
         * @throws OperationException Thrown if stripping or compressing the binary failed
         */
        private function postProcessBinary(string $binaryPath): void
        {
            if(!$this->debugSymbols)
            {
                $strip = (new ExecutableFinder())->find('strip');
                if($strip === null)
                {
                    Logger::getLogger()?->warning('strip was not found, the binary will keep its debug symbols');
                }
                else
                {
                    Logger::getLogger()?->verbose(sprintf('Stripping debug symbols from %s', $binaryPath));
                    $this->runCommand('strip', [$strip, PHP_OS_FAMILY === 'Darwin' ? '-S' : '--strip-unneeded', $binaryPath]);
                }
            }

            if($this->upx && PHP_OS_FAMILY === 'Linux')
            {
                $upx = $this->findUpx();
                if($upx === null)
                {
                    throw new OperationException('The upx option is enabled but upx could not be found');
                }

                Logger::getLogger()?->verbose(sprintf('Compressing %s with UPX', $binaryPath));
                $this->runCommand('upx', [$upx, '--best', $binaryPath]);
            }
        }

        /**
         * Returns the path to upx, preferring the copy installed by `spc install-pkg upx`
         *
         * @return string|null The path to upx or null if it could not be found
         */
        private function findUpx(): ?string
        {
            $candidates = glob($this->workingDirectory . DIRECTORY_SEPARATOR . 'pkgroot' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'upx') ?: [];
            $candidates[] = $this->workingDirectory . DIRECTORY_SEPARATOR . 'pkgroot' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'upx';

            foreach($candidates as $candidate)
            {
                if(is_file($candidate) && is_executable($candidate))
                {
                    return $candidate;
                }
            }

            return (new ExecutableFinder())->find('upx');
        }

        /**
         * Runs spc in the working directory, the output is logged and the last lines are included in the exception
         * if the command fails.
         *
         * @param string $spc The path to the spc binary
         * @param string[] $arguments The arguments to pass to spc
         * @throws OperationException Thrown if spc exits with a non-zero exit code
         */
        private function runSpc(string $spc, array $arguments): void
        {
            $this->runCommand('spc ' . $arguments[0], array_merge([$spc], $arguments, ['--no-interaction']), $this->getSpcEnvironment());
        }

        /**
         * Runs a command in the working directory, the output is logged and the last lines are included in the
         * exception if the command fails.
         *
         * @param string $name The name of the command used in log messages and exceptions
         * @param string[] $command The command and its arguments
         * @param array<string,string> $environment Environment variables merged with the current environment
         * @throws OperationException Thrown if the command exits with a non-zero exit code
         */
        private function runCommand(string $name, array $command, array $environment=[]): void
        {
            Logger::getLogger()?->verbose(sprintf('Running: %s', implode(' ', $command)));

            $process = new Process($command, $this->workingDirectory, $environment);
            $process->setTimeout(null);

            $outputTail = [];
            $process->run(function(string $type, string $buffer) use (&$outputTail, $name): void
            {
                foreach(preg_split('/\r?\n/', rtrim($buffer)) as $line)
                {
                    Logger::getLogger()?->verbose(sprintf('[%s] %s', $name, $line));
                    $outputTail[] = $line;
                }

                $outputTail = array_slice($outputTail, -20);
            });

            if(!$process->isSuccessful())
            {
                throw new OperationException(sprintf(
                    "%s failed with exit code %d:\n%s",
                    $name, $process->getExitCode(), implode("\n", $outputTail)
                ));
            }
        }

        /**
         * Returns the environment variables passed to spc, these are merged with the current environment
         *
         * @return array<string,string>
         */
        private function getSpcEnvironment(): array
        {
            $environment = [];

            if($this->libc !== null)
            {
                $environment['SPC_LIBC'] = $this->libc;
            }

            if($this->frankenPhpSource !== null)
            {
                $environment['FRANKENPHP_SOURCE_PATH'] = $this->frankenPhpSource;
            }

            if($this->xcaddyModules !== null)
            {
                $environment['SPC_CMD_VAR_FRANKENPHP_XCADDY_MODULES'] = $this->xcaddyModules;
            }

            return $environment;
        }

        /**
         * Returns True if the binary will be linked against musl, which is the default on Linux
         *
         * @return bool
         */
        private function isMusl(): bool
        {
            return PHP_OS_FAMILY === 'Linux' && $this->libc !== 'glibc';
        }

        /**
         * Resolves a path relative to the project path if it's not absolute
         *
         * @param string $path The path to resolve
         * @return string The resolved path
         */
        private function resolveProjectPath(string $path): string
        {
            if(IO::isAbsolutePath($path))
            {
                return $path;
            }

            return $this->getProjectPath() . DIRECTORY_SEPARATOR . $path;
        }

        /**
         * Reports progress if a progress callback was provided
         *
         * @param callable|null $progressCallback The progress callback
         * @param int $current The current stage
         * @param int $total The total amount of stages
         * @param string $message The message to report
         */
        private function reportProgress(?callable $progressCallback, int $current, int $total, string $message): void
        {
            Logger::getLogger()?->verbose(sprintf('FrankenPhpCompiler: %s', $message));
            if($progressCallback !== null)
            {
                $progressCallback($current, $total, $message);
            }
        }

        /**
         * Returns a non-empty string option, or null if the option is not set
         *
         * @param array $options The build options
         * @param string $name The name of the option
         * @return string|null
         */
        private static function stringOption(array $options, string $name): ?string
        {
            if(!isset($options[$name]) || !is_scalar($options[$name]) || trim((string)$options[$name]) === '')
            {
                return null;
            }

            return trim((string)$options[$name]);
        }

        /**
         * Returns a list option which may be defined as an array or as a comma separated string, or null if the
         * option is not set
         *
         * @param array $options The build options
         * @param string $name The name of the option
         * @return string[]|null
         */
        private static function listOption(array $options, string $name): ?array
        {
            if(!isset($options[$name]))
            {
                return null;
            }

            $values = is_array($options[$name]) ? $options[$name] : explode(',', (string)$options[$name]);
            return array_values(array_filter(array_map(fn($value) => trim((string)$value), $values), fn($value) => $value !== ''));
        }
    }
