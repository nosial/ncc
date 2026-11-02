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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Libraries\fslib\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\PackageReader;
    use ncc\Classes\SignatureVerifier;
    use ncc\Enums\ProjectType;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\SignatureStatus;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\semver\Semver;
    use ncc\Objects\Package\DependencyReference;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Objects\Signing\TrustedKey;
    use ncc\Runtime;

    class InstallCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $package = $argv['package'] ?? $argv['p'] ?? null;

            // Attempt to extract the positional argument from the original argv
            if ($package === null)
            {
                $rawArgv = $_SERVER['argv'] ?? [];
                $foundInstall = false;
                foreach ($rawArgv as $arg)
                {
                    if ($arg === 'install')
                    {
                        $foundInstall = true;
                        continue;
                    }

                    if ($foundInstall && !str_starts_with($arg, '-'))
                    {
                        $package = $arg;
                        break;
                    }
                }
            }

            if ($package === null)
            {
                Console::error("No package specified for installation.");
                return 1;
            }

            $autoConfirm = $argv['yes'] ?? $argv['y'] ?? false;
            $skipDependencies = $argv['skip-dependencies'] ?? $argv['skip-deps'] ?? $argv['sd'] ?? false;
            $skipRepositories = $argv['skip-repositories'] ?? $argv['skip-repos'] ?? $argv['sr'] ?? false;
            $skipExtensions = $argv['skip-extensions'] ?? $argv['skip-exts'] ?? $argv['se'] ?? false;
            $reinstall = $argv['reinstall'] ?? $argv['r'] ?? false;
            $buildSource = $argv['build-source'] ?? $argv['bs'] ?? false;
            $noSymlink = $argv['no-symlink'] ?? false;
            $forceSymlink = $argv['force-symlink'] ?? false;
            $optimized = $argv['optimized'] ?? $argv['O'] ?? false;
            $trust = $argv['trust'] ?? false;
            $skipSignatureCheck = $argv['skip-signature-check'] ?? false;
            
            // Build options array
            $options = [];
            if($autoConfirm)
            {
                $options[] = 'yes';
            }
            if($trust)
            {
                $options[] = 'trust';
            }
            if($skipSignatureCheck)
            {
                $options[] = 'skip-signature-check';
            }
            if($skipDependencies)
            {
                $options[] = 'skip-dependencies';
            }
            if($skipRepositories)
            {
                $options[] = 'skip-repositories';
            }
            if($skipExtensions)
            {
                $options[] = 'skip-extensions';
            }
            if($reinstall)
            {
                $options[] = 'reinstall';
            }
            if($buildSource)
            {
                $options[] = 'build-source';
            }
            if($noSymlink)
            {
                $options[] = 'no-symlink';
            }
            if($forceSymlink)
            {
                $options[] = 'force-symlink';
            }
            if($optimized)
            {
                $options[] = 'optimized';
            }
            
            // Parse dynamic repository authentication arguments (e.g., --github-auth=foo)
            $repositoryAuth = [];
            foreach($argv as $key => $value)
            {
                if(preg_match('/^(.+)-auth$/', $key, $matches))
                {
                    $repositoryName = $matches[1];
                    $repositoryAuth[$repositoryName] = $value;
                }
            }

            if($skipDependencies)
            {
                Console::warning('The "skip-dependencies" option was used, the package may not meet the requirements');
            }

            $packageFile = Helper::resolvePath($package);
            if($packageFile !== null && IO::isFile($packageFile))
            {
                try
                {
                    $packageReader = new PackageReader($packageFile);
                }
                catch(Exception $e)
                {
                    Console::error($e->getMessage());
                    return 1;
                }

                Console::out("Package found: " . $packageReader->getAssembly()->getPackage() . "=" . $packageReader->getAssembly()->getVersion());
                if(!$packageReader->getHeader()->getUpdateSource())
                {
                    Console::warning("This package does not specify an update source. This package will not receive updates automatically.");
                }
                else
                {
                    Console::out("Update source: " . $packageReader->getHeader()->getUpdateSource());
                }

                if($packageReader->getHeader()->getRepositories() !== null)
                {
                    if($packageReader->getHeader()->getMainRepository() !== null && $packageReader->getHeader()->getMainRepository()->getName() !== $packageReader->getHeader()->getUpdateSource()?->getRepository())
                    {
                        Console::warning("The repository name specified in the package header does not match the repository in the update source, updates will not be found correctly.");
                    }
                    elseif($packageReader->getHeader()->getMainRepository() !== null)
                    {
                        Console::out("Repository: " . $packageReader->getHeader()->getMainRepository()->getHost());
                    }

                    if($skipRepositories)
                    {
                        /** @var RepositoryConfiguration $repositoryConfiguration */
                        foreach($packageReader->getHeader()->getRepositories() as $repositoryConfiguration)
                        {
                            if(!Runtime::repositoryExists($repositoryConfiguration->getName()))
                            {
                                Console::warning(sprintf('The package requires repository "%s" (%s) which is not available, updates will not be found correctly.',
                                    $repositoryConfiguration->getName(),
                                    $repositoryConfiguration->getHost()
                                ));
                            }
                        }
                    }
                }

                if(!$autoConfirm)
                {
                    $input = Console::prompt("Do you want to proceed with the installation? (y/n): ");
                    if(!(strtolower($input) === 'y' || strtolower($input) === 'yes'))
                    {
                        Console::out("Installation cancelled by user.");
                        return 0;
                    }
                }

                $installedPackages = self::installFromFile($packageReader, $options, []);
            }
            else
            {
                try
                {
                    $packageSource = new PackageSource($package);
                }
                catch(Exception $e)
                {
                    Console::error('Failed to parse package source: ' . $e->getMessage());
                    return 1;
                }

                if($packageSource->getRepository() === null)
                {
                    Console::error(sprintf('Cannot install "%s", a remote repository is required', $packageSource));
                    return 1;
                }

                if(!Runtime::repositoryExists($packageSource->getRepository()))
                {
                    Console::error(sprintf('Cannot install "%s", unknown remote repository "%s"', $packageSource, $packageSource->getRepository()));
                    return 1;
                }

                if(!$autoConfirm)
                {
                    Console::out(sprintf("You are about to download and install %s from a remote repository", $packageSource));
                    $input = Console::prompt("Do you want to proceed with the installation? (y/n): ");
                    if(!(strtolower($input) === 'y' || strtolower($input) === 'yes'))
                    {
                        Console::out("Installation cancelled by user.");
                        return 0;
                    }
                }

                $installedPackages = self::installFromRemote($packageSource, $options, [], $repositoryAuth);
            }

            if(count($installedPackages) === 0)
            {
                Console::out("No installation actions were necessary.");
                return 0;
            }

            // Remove duplicates from the installed packages list
            $installedPackages = array_unique($installedPackages);

            Console::out(sprintf("Completed installation of %d package(s).", count($installedPackages)));
            foreach($installedPackages as $installedPackage)
            {
                Console::out(" - " . $installedPackage);
            }

            return 0;
        }

        /**
         * Installs a package and its dependencies from a PackageReader
         *
         * @param PackageReader $reader The package to install
         * @param array $options The installation options
         * @param array $installed The packages installed so far in this session
         * @param bool $builtFromSource Whether the package was built from source during this installation
         * @return array The packages installed so far in this session
         * @throws OperationException If the installation fails
         */
        private static function installFromFile(PackageReader $reader, array $options=[], array $installed=[], bool $builtFromSource=false): array
        {
            $options = self::parseOptions($options);
            $packageReader = $reader;
            $packageIdentifier = sprintf("%s=%s", $packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());

            // Check if package is already in the installed list (to avoid duplicates in current session)
            if(in_array($packageIdentifier, $installed, true))
            {
                return $installed;
            }

            // Check if package is already installed on the system
            if(!$options['reinstall'])
            {
                $isInstalled = Runtime::packageInstalled($packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());
                
                // Also check if a satisfying version exists using semver matching
                if(!$isInstalled)
                {
                    $isInstalled = self::isPackageSatisfied($packageReader->getAssembly()->getPackage(), $packageReader->getAssembly()->getVersion());
                }
                
                if($isInstalled)
                {
                    Console::out(sprintf("Package %s is already installed, skipping installation.", $packageIdentifier));
                    return $installed;
                }
            }

            Console::out(sprintf("Installing package %s=%s", $packageReader->getPackageName(), $packageReader->getAssembly()->getVersion()));
            self::verifyPackageSignature($packageReader, $packageIdentifier, $options, $builtFromSource);
            $installed[] = $packageIdentifier;

            // Validate required PHP extensions
            if(!$options['skip-extensions'])
            {
                $requiredExtensions = $packageReader->getHeader()->getExtensions();
                if($requiredExtensions !== null && count($requiredExtensions) > 0)
                {
                    $missingExtensions = [];
                    foreach($requiredExtensions as $extension)
                    {
                        if(!extension_loaded($extension))
                        {
                            $missingExtensions[] = $extension;
                        }
                    }

                    if(count($missingExtensions) > 0)
                    {
                        Console::error(sprintf("Cannot install %s: Missing required PHP extensions: %s", 
                            $packageIdentifier, implode(', ', $missingExtensions)));
                        Console::out("Use --skip-extensions to bypass this check.");
                        throw new OperationException(sprintf('Missing required PHP extensions: %s', implode(', ', $missingExtensions)));
                    }

                    Logger::getLogger()?->debug(sprintf('Validated %d required extensions for %s', count($requiredExtensions), $packageIdentifier));
                }
            }

            // Add the repositories if required
            if(!$options['skip-repositories'] && count($packageReader->getHeader()->getRepositories() ?? []) > 0)
            {
                /** @var RepositoryConfiguration $repositoryConfiguration */
                foreach($packageReader->getHeader()->getRepositories() as $repositoryConfiguration)
                {
                    if(!Runtime::repositoryExists($repositoryConfiguration->getName()))
                    {
                        Console::out(sprintf('Adding repository "%s" (%s)', $repositoryConfiguration->getName(), $repositoryConfiguration->getHost()));
                        Runtime::getRepositoryManager()->addConfiguration($repositoryConfiguration);
                    }
                }
            }

            // Install all dependencies first if it's not statically linked
            if(!$options['skip-dependencies'] && !$packageReader->getHeader()->isStaticallyLinked())
            {
                /** @var DependencyReference $dependency */
                foreach($packageReader->getHeader()->getDependencyReferences() as $dependency)
                {
                    $depIdentifier = sprintf("%s=%s", $dependency->getPackage(), $dependency->getVersion());
                    
                    // Check if already in the installed list (current session)
                    if(in_array($depIdentifier, $installed, true))
                    {
                        continue;
                    }
                    
                    // Check if already installed on the system (exact match or semver match)
                    $isInstalled = Runtime::packageInstalled($dependency->getPackage(), $dependency->getVersion());
                    if(!$isInstalled)
                    {
                        $isInstalled = self::isPackageSatisfied($dependency->getPackage(), $dependency->getVersion());
                    }
                    
                    if($isInstalled)
                    {
                        continue;
                    }

                    Console::out(sprintf("Installing dependency %s for package %s", $dependency, $packageReader->getPackageName()));
                    $dependencySource = new PackageSource($dependency);
                    $installed = self::installFromRemote($dependencySource, $options, $installed, []);
                }
            }

            Runtime::getPackageManager()->install($packageReader, $options);

            return $installed;
        }

        public static function installFromRemote(PackageSource $source, array $options=[], array $installed=[], array $repositoryAuth=[]): array
        {
            $options = self::parseOptions($options);
            if($source->getRepository() === null)
            {
                throw new OperationException(sprintf('Cannot install "%s", a remote repository is required', $source));
            }

            if(!Runtime::repositoryExists($source->getRepository()))
            {
                throw new OperationException(sprintf('Cannot install "%s", unknown remote repository "%s"', $source, $source->getRepository()));
            }
            
            Console::out(sprintf("Resolving package %s from repository %s", $source, $source->getRepository()));
            
            // Handle authentication if specified for this repository
            $authentication = null;
            if(isset($repositoryAuth[$source->getRepository()]))
            {
                $authEntryName = $repositoryAuth[$source->getRepository()];
                Console::out(sprintf("Authenticating with entry '%s' for repository '%s'", $authEntryName, $source->getRepository()));
                
                try
                {
                    $authManager = Runtime::getAuthenticationManager();
                    
                    // Check if vault exists
                    if(!$authManager->vaultExists())
                    {
                        throw new OperationException('Authentication vault does not exist. Please create authentication entries first.');
                    }
                    
                    // Unlock the vault if not already unlocked
                    if(!$authManager->isUnlocked())
                    {
                        $masterPassword = Console::getPassword('Enter vault master password: ');
                        $authManager->unlock($masterPassword);
                        Console::out('Vault unlocked successfully.');
                    }
                    
                    // Retrieve the authentication entry
                    $authentication = $authManager->getEntry($authEntryName);
                    if($authentication === null)
                    {
                        throw new OperationException(sprintf('Authentication entry "%s" not found in vault', $authEntryName));
                    }
                    
                    Console::out(sprintf('Using authentication entry "%s"', $authEntryName));
                }
                catch(Exception $e)
                {
                    throw new OperationException('Failed to authenticate: ' . $e->getMessage(), $e->getCode(), $e);
                }
            }

            // Define stages with weights for dynamic progress calculation
            $stages = [
                'download' => ['weight' => 60, 'start' => 0],
                'detect' => ['weight' => 5, 'start' => 0],
                'convert' => ['weight' => 5, 'start' => 0],
                'resolve_deps' => ['weight' => 10, 'start' => 0],
                'generate_config' => ['weight' => 5, 'start' => 0],
                'compile' => ['weight' => 15, 'start' => 0],
            ];
            
            // Calculate stage start positions
            $currentPos = 0;
            foreach($stages as $key => &$stage)
            {
                $stage['start'] = $currentPos;
                $currentPos += $stage['weight'];
            }
            unset($stage);

            // Helper function to calculate progress within a stage
            $calculateProgress = function(string $stageName, float $stageProgress = 1.0) use ($stages): int {
                $stage = $stages[$stageName];
                return (int)($stage['start'] + ($stage['weight'] * $stageProgress));
            };

            $repository = Runtime::getRepository($source->getRepository())->createClient($authentication);
            $resolvedPackages = $repository->getAll($source->getOrganization(), $source->getName(), $source->getVersion());
            $selectedPackage = null;

            foreach($resolvedPackages as $package)
            {
                if($package->getType() === RemotePackageType::NCC && !$options['build-source'])
                {
                    $selectedPackage = $package;
                    break;
                }

                if($package->getType() === RemotePackageType::SOURCE_ZIP)
                {
                    $selectedPackage = $package;
                    break;
                }

                $selectedPackage = $package;
                break;
            }

            if($selectedPackage === null)
            {
                throw new OperationException(sprintf('No suitable package found for %s in repository %s', $source, $source->getRepository()));
            }

            // Stage: Download
            $downloadedPackage = $repository->download($selectedPackage, function(int $downloaded, int $total, string $message) use ($calculateProgress) {
                $downloadProgress = $total > 0 ? ($downloaded / $total) : 0;
                $progress = $calculateProgress('download', $downloadProgress);
                Console::inlineProgress($progress, 100, $message);
            });

            // If it's a file, we assume it's an NCC package
            if(IO::isFile($downloadedPackage))
            {
                Console::completeProgress();
                return self::installFromFile(new PackageReader($downloadedPackage), $options, $installed);
            }

            // Stage: Detect project type
            Console::inlineProgress($calculateProgress('detect'), 100, sprintf("Detecting project type %s", $source));

            // Otherwise, we assume its source code that needs to be built, so we try to figure out it's project type
            try
            {
                // Get the version from the RemotePackage (already resolved by the repository)
                $resolvedVersion = $selectedPackage->getVersion();
                if($resolvedVersion !== null)
                {
                    Logger::getLogger()?->debug(sprintf('Using version from repository: " %s. The downloaded source may not contain a recognized project configuration file (composer.json, package.json, etc.)', $source));
                }

                $projectPath = ProjectType::detectProjectPath($downloadedPackage);
                if($projectPath === null)
                {
                    Console::clearInlineProgress();
                    Logger::getLogger()?->error(sprintf('Unable to detect project path for %s', $source));
                    throw new OperationException(sprintf('Unable to detect project configuration file path for %s', $source));
                }

                Console::inlineProgress($calculateProgress('detect', 0.5), 100, sprintf("Analyzing project structure %s", $source));

                $projectType = ProjectType::detectProjectType($downloadedPackage);
                if($projectType === null)
                {
                    Console::clearInlineProgress();
                    Logger::getLogger()?->error(sprintf('Unable to detect project type for %s', $source));
                    throw new OperationException(sprintf('Unable to detect project type for %s', $source));
                }

                Logger::getLogger()?->debug(sprintf('Detected project type: %s at path: %s', $projectType->value, $projectPath));

                // Check if this is already an NCC project (project.yml)
                if($projectType === ProjectType::NCC)
                {
                    // No conversion needed, project is already in NCC format
                    Logger::getLogger()?->debug(sprintf('Project is already in NCC format, skipping conversion'));
                    Console::inlineProgress($calculateProgress('generate_config', 0.7), 100, sprintf("Loading compiler for %s", $source));
                    $compiler = Project::compilerFromFile($projectPath);
                }
                else
                {
                    // Stage: Converting project
                    Console::inlineProgress($calculateProgress('convert'), 100, sprintf("Converting %s project %s", $projectType->value, $source));

                    // Get the converter for the project type
                    $converter = $projectType->getConverter();
                    if($converter === null)
                    {
                        Console::clearInlineProgress();
                        Logger::getLogger()?->error(sprintf('No converter available for project type %s', $projectType->value));
                        throw new OperationException(sprintf('Cannot convert project type %s to ncc format. No converter is available for this project type.', $projectType->value));
                    }

                    // Stage: Resolving dependencies
                    Console::inlineProgress($calculateProgress('resolve_deps'), 100, sprintf("Resolving dependencies for %s", $source));

                    // Convert the project source to a ncc project configuration
                    // Pass the resolved version to the converter
                    // Track progress dynamically during dependency resolution
                    $depResolutionProgress = 0.0;
                    $depResolutionStep = 0.15; // Increment by 15% of the stage per callback
                    
                    $projectConfiguration = $converter->convert($projectPath, $resolvedVersion, function(string $message) use ($calculateProgress, &$depResolutionProgress, $depResolutionStep) {
                        $depResolutionProgress = min(0.95, $depResolutionProgress + $depResolutionStep);
                        Console::inlineProgress($calculateProgress('resolve_deps', $depResolutionProgress), 100, $message);
                    });
                    
                    // Stage: Generating project configuration
                    Console::inlineProgress($calculateProgress('generate_config'), 100, sprintf("Generating project configuration for %s", $source));
                    
                    $outputPath = dirname($projectPath) . DIRECTORY_SEPARATOR . 'project.yml';
                    $projectConfiguration->save($outputPath);
                    
                    Console::inlineProgress($calculateProgress('generate_config', 0.7), 100, sprintf("Loading compiler for %s", $source));
                    $compiler = Project::compilerFromFile($outputPath);
                }
            }
            catch(Exception $e)
            {
                Console::clearInlineProgress();
                throw new OperationException(sprintf('Failed to convert project source for %s: %s', $source, $e->getMessage()));
            }

            // Stage: Compile
            // Build & install the project
            try
            {
                $packageReader = new PackageReader($compiler->build(function(int $current, int $total, string $message) use ($calculateProgress) {
                    $compileProgress = $total > 0 ? ($current / $total) : 0;
                    $progress = $calculateProgress('compile', $compileProgress);
                    Console::inlineProgress($progress, 100, $message);
                }));
                Console::completeProgress();
                return self::installFromFile($packageReader, $options, $installed, true);
            }
            catch(Exception $e)
            {
                Console::clearInlineProgress();
                throw new OperationException(sprintf('Failed to build project source for %s: %s', $source, $e->getMessage()));
            }
        }

        /**
         * Verifies the signatures of a package before it is installed.
         *
         * Unsigned packages are installed with a deprecation warning. A package signed by a key that is not trusted yet
         * prompts the user to trust the key (or trusts it automatically with --trust or NCC_TRUST=1), non-interactive
         * sessions install it without trusting the key. A package whose signature is broken, or that is signed by a
         * different key than the one trusted for its signer, is refused unless --skip-signature-check is used.
         *
         * @param PackageReader $packageReader The package to verify
         * @param string $packageIdentifier The identifier of the package (name=version)
         * @param array $options The parsed installation options
         * @param bool $builtFromSource Whether the package was built from source during this installation
         * @return void
         * @throws OperationException If the package fails verification or the user aborted the installation
         */
        private static function verifyPackageSignature(PackageReader $packageReader, string $packageIdentifier, array $options, bool $builtFromSource): void
        {
            $result = SignatureVerifier::verify($packageReader, Runtime::getTrustStores());
            Logger::getLogger()?->debug(sprintf('Signature verification of %s: %s', $packageIdentifier, $result->getStatus()->value));

            switch($result->getStatus())
            {
                case SignatureStatus::UNSIGNED:
                    if($builtFromSource)
                    {
                        Console::warning(sprintf('%s was built from source and is not signed, future versions of ncc will require signed packages', $packageIdentifier));
                    }
                    else
                    {
                        Console::warning(sprintf('%s is not signed, unsigned packages are deprecated and future versions of ncc will refuse to install them', $packageIdentifier));
                    }
                    return;

                case SignatureStatus::VERIFIED:
                    Console::out($result->getSummary());
                    return;

                case SignatureStatus::UNSUPPORTED:
                    Console::warning(sprintf('%s: %s, installing without verification', $packageIdentifier, $result->getSummary()));
                    return;

                case SignatureStatus::INVALID:
                case SignatureStatus::KEY_MISMATCH:
                    if($options['skip-signature-check'])
                    {
                        Console::warning(sprintf('%s failed signature verification: %s', $packageIdentifier, $result->getSummary()));
                        Console::warning('Installing anyway because --skip-signature-check was used');
                        return;
                    }

                    Console::clearInlineProgress();
                    Console::error(sprintf('%s failed signature verification: %s', $packageIdentifier, $result->getSummary()));
                    Console::error('Use --skip-signature-check to install it anyway (not recommended)');
                    throw new OperationException(sprintf('Signature verification failed for %s', $packageIdentifier));

                case SignatureStatus::UNTRUSTED:
                    // Only reached when no signature is trusted, so every untrusted signer is handled
                    $interactive = !$options['yes'] && Console::isInteractive();
                    $trustedAny = false;

                    foreach($result->getEntriesWithStatus(SignatureStatus::UNTRUSTED) as $entry)
                    {
                        $signature = $entry['signature'];
                        $trust = $options['trust'];

                        if(!$trust && $interactive)
                        {
                            Console::clearInlineProgress();
                            Console::out(sprintf('%s is signed by a key that is not trusted yet', $packageIdentifier));
                            Console::out(sprintf('  Signer:      %s', $signature->describeSigner()));
                            Console::out(sprintf('  Fingerprint: %s', $signature->getFingerprint()));
                            Console::out('Only trust this key if you know it belongs to this signer, packages claiming this signer');
                            Console::out('will then only be accepted if they are signed with this key.');

                            while(true)
                            {
                                $answer = strtolower(Console::prompt(sprintf('Trust this key for %s? [y] trust and remember, [n] install without trusting, [a] abort (default: n): ', $signature->describeSigner())));
                                if(in_array($answer, ['', 'n', 'no', 'y', 'yes', 'a', 'abort'], true))
                                {
                                    break;
                                }
                            }

                            if($answer === 'a' || $answer === 'abort')
                            {
                                throw new OperationException(sprintf('Installation of %s aborted, the signing key was not trusted', $packageIdentifier));
                            }

                            $trust = ($answer === 'y' || $answer === 'yes');
                        }

                        if(!$trust)
                        {
                            Console::warning(sprintf('%s is signed by %s with the untrusted key %s, installing without trusting it (use --trust to trust it)',
                                $packageIdentifier, $signature->describeSigner(), $signature->getFingerprint()));
                            continue;
                        }

                        $trustStore = Runtime::getTrustStore();
                        $trustStore->add(new TrustedKey($signature->getSignerType(), $signature->getSigner(), $signature->getPublicKey(), TrustedKey::SOURCE_PROMPT));
                        if($trustStore->save())
                        {
                            Console::out(sprintf('Trusted the key %s for %s', $signature->getFingerprint(), $signature->describeSigner()));
                        }
                        else
                        {
                            Console::warning(sprintf('Could not save the trusted key %s for %s to %s', $signature->getFingerprint(), $signature->describeSigner(), $trustStore->getTrustStorePath()));
                        }

                        $trustedAny = true;
                    }

                    if($trustedAny)
                    {
                        Console::out(sprintf('Verified signature of %s', $packageIdentifier));
                    }
                    return;
            }
        }

        private static function parseOptions(array $options): array
        {
            $trustEnvironment = getenv('NCC_TRUST');
            $results = [
                'yes' => false,
                'trust' => $trustEnvironment !== false && in_array(strtolower(trim($trustEnvironment)), ['1', 'true', 'yes'], true),
                'skip-signature-check' => false,
                'skip-repositories' => false,
                'skip-dependencies' => false,
                'skip-extensions' => false,
                'build-source' => false,
                'reinstall' => false,
                'no-symlink' => false,
                'force-symlink' => false,
                'optimized' => false
            ];

            foreach($options as $key => $option)
            {
                if(is_string($key))
                {
                    if(array_key_exists($key, $results))
                    {
                        $results[$key] = (bool)$option;
                    }

                    continue;
                }

                if(!is_string($option))
                {
                    continue;
                }

                switch($option)
                {
                    case 'skip-repositories':
                    case 'skip-repos':
                    case 'sr':
                        $results['skip-repositories'] = true;
                        break;

                    case 'skip-dependencies':
                    case 'skip-deps':
                    case 'sd':
                        $results['skip-dependencies'] = true;
                        break;

                    case 'skip-extensions':
                    case 'skip-exts':
                    case 'se':
                        $results['skip-extensions'] = true;
                        break;

                    case 'build-source':
                    case 'bs':
                        $results['build-source'] = true;
                        break;

                    case 'reinstall':
                    case 'r':
                        $results['reinstall'] = true;
                        break;

                    case 'no-symlink':
                        $results['no-symlink'] = true;
                        break;

                    case 'force-symlink':
                    case 'c':
                        $results['force-symlink'] = true;
                        break;

                    case 'optimized':
                    case 'O':
                        $results['optimized'] = true;
                        break;

                    case 'yes':
                    case 'y':
                        $results['yes'] = true;
                        break;

                    case 'trust':
                        $results['trust'] = true;
                        break;

                    case 'skip-signature-check':
                        $results['skip-signature-check'] = true;
                        break;
                }
            }

            return $results;
        }

        /**
         * Checks if a package is already installed with a version that satisfies the requested version constraint
         *
         * @param string $packageName The name of the package (e.g., "organization/name")
         * @param string|null $requestedVersion The requested version or version constraint
         * @return bool True if a satisfying version is already installed, false otherwise
         */
        private static function isPackageSatisfied(string $packageName, ?string $requestedVersion): bool
        {
            // If no specific version is requested, check if any version is installed
            if($requestedVersion === null || $requestedVersion === 'latest')
            {
                return Runtime::packageInstalled($packageName, 'latest');
            }

            // First check for exact match
            if(Runtime::packageInstalled($packageName, $requestedVersion))
            {
                return true;
            }

            // Try to find a satisfying version using semver
            try
            {
                // Get all installed versions of this package
                $allVersions = [];
                
                // Get versions from system package manager
                $systemManager = Runtime::getSystemPackageManager();
                foreach($systemManager->getAllVersions($packageName) as $entry)
                {
                    $allVersions[] = $entry->getVersion();
                }
                
                // Get versions from user package manager
                $userManager = Runtime::getUserPackageManager();
                if($userManager !== null)
                {
                    foreach($userManager->getAllVersions($packageName) as $entry)
                    {
                        $allVersions[] = $entry->getVersion();
                    }
                }

                if(empty($allVersions))
                {
                    return false;
                }

                // Remove duplicates
                $allVersions = array_unique($allVersions);

                // Try to find a satisfying version
                $satisfying = Semver::satisfiedBy($allVersions, $requestedVersion);
                
                return !empty($satisfying);
            }
            catch(Exception $e)
            {
                // If semver matching fails, fall back to exact match only
                return false;
            }
        }

        /**
         * Prints out the help menu for the install command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc install <package> [options]' . PHP_EOL);
            Console::out('Installs an ncc package from a file or remote repository.' . PHP_EOL);
            Console::out('The install command can handle local .ncc files or fetch packages');
            Console::out('from configured repositories. Dependencies are resolved and installed');
            Console::out('automatically unless explicitly skipped.' . PHP_EOL);
            Console::out('Arguments:');
            Console::out('  <package>         Package name or path to .ncc file' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --yes, -y         Automatically confirm all prompts');
            Console::out('  --reinstall, -r   Force reinstall package even if already installed');
            Console::out('  --skip-dependencies, --skip-deps, --sd');
            Console::out('                    Skip installing package dependencies');
            Console::out('  --skip-repositories, --skip-repos, --sr');
            Console::out('                    Skip adding package repositories');
            Console::out('  --skip-extensions, --skip-exts, --se');
            Console::out('                    Skip validation of required PHP extensions');
            Console::out('  --no-symlink      Do not create a symlink for the package');
            Console::out('  --force-symlink   Force symlink creation even if one exists');
            Console::out('  --optimized, -O   Also extract the package to the filesystem so its classes are loaded from');
            Console::out('                    real files that OPcache can cache (recommended for php-fpm deployments)');
            Console::out('  --trust           Trust the signing keys of packages signed by a key that is not trusted yet,');
            Console::out('                    the same as answering "y" to the trust prompt (also set by NCC_TRUST=1)');
            Console::out('  --skip-signature-check');
            Console::out('                    Install packages that fail signature verification (not recommended)');
            Console::out('  --<repository>-auth=<entry>');
            Console::out('                    Authenticate using the specified vault entry for a repository');
            Console::out('                    Example: --github-auth=mytoken');
            Console::out(PHP_EOL . 'Signatures:');
            Console::out('  Signed packages are verified before they are installed. Unsigned packages are installed with a');
            Console::out('  warning, packages that were modified or are signed by a different key than the one trusted for');
            Console::out('  their signer are refused. When a package is signed by a key that is not trusted yet you are asked');
            Console::out('  whether to trust it; non-interactive sessions (no terminal, or --yes) install it without trusting');
            Console::out('  the key unless --trust is used. --yes never trusts keys by itself.');
            Console::out(PHP_EOL . 'Examples:');
            Console::out('  ncc install mypackage.ncc');
            Console::out('  ncc install symfony/console=5.0@packagist');
            Console::out('  ncc install com.example.package --yes');
            Console::out('  ncc install package.ncc --skip-deps');
            Console::out('  ncc install package.ncc --optimized');
            Console::out('  ncc install nosial/plugin@github --yes --trust');
            Console::out('  ncc install group/project=latest@repo --github-auth=mytoken');
        }
    }