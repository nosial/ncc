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

    namespace ncc\CLI\Commands\Project\Templates\WebResource;

    use FilesystemIterator;
    use ncc\Abstracts\AbstractRepository;
    use ncc\Classes\Console;
    use ncc\Enums\RemotePackageType;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\OperationException;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\Yaml\Yaml;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\RemotePackage;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Objects\WebTemplateConfiguration;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    class WebResourceTemplate implements TemplateGeneratorInterface
    {
        private static PackageSource $packageSource;

        /**
         * Sets the package source configuration for the web resource template. This should be called before generate()
         * to specify where the template should be downloaded from. The package source should include the repository,
         * organization, project name, version, and variant (which determines the specific template asset to use).
         *
         * @param PackageSource $packageSource The package source configuration for the web resource template.
         * @return void
         */
        public static function setPackageSource(PackageSource $packageSource): void
        {
            self::$packageSource = $packageSource;
        }

        /**
         * Generates the web resource template in the specified project directory using the provided project configuration.
         * This involves downloading the template archive from the configured package source, extracting it, copying
         * resources and templates according to the template configuration, updating the project's build configuration,
         * and registering any sections found in the WebApplication/sections/ directory.
         *
         * @param string $projectDirectory The path to the project directory where the template should be generated.
         * @param Project $projectConfiguration The project configuration object to update with new build configurations and dependencies.
         * @throws OperationException If there are issues with downloading or processing the template.
         * @return void
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $sourcePath = $projectConfiguration->getSourcePath() ?? 'src';
            $assembly = $projectConfiguration->getAssembly();
            $assemblyName = $assembly->getName();

            $webResourcesDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebResources';
            $webApplicationDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebApplication';
            $webLocaleDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebLocale';

            $extractedPath = self::downloadTemplate();

            $templateConfig = self::loadTemplateConfiguration($extractedPath);

            Console::out(sprintf("Applying template '%s'...", $templateConfig->getName()));

            self::copyResources($extractedPath, $webResourcesDir, $templateConfig->getResources());

            $webApplicationMapping = $templateConfig->getWebApplication();
            if (!empty($webApplicationMapping))
            {
                self::copyFilesUsingMapping($extractedPath, $webApplicationDir, $webApplicationMapping, 'WebApplication');
            }

            $webLocaleMapping = $templateConfig->getWebLocale();
            if (!empty($webLocaleMapping))
            {
                self::copyFilesUsingMapping($extractedPath, $webLocaleDir, $webLocaleMapping, 'WebLocale');
            }

            self::updateBuildConfiguration($projectConfiguration, $assemblyName, $sourcePath);

            self::registerSections($projectConfiguration, $webApplicationDir);

            $projectConfiguration->save($projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
            Console::out('Modified File: ' . $projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
        }

        /**
         * Scans the WebApplication/sections/ directory for .phtml files and registers each
         * as a section in the web_configuration. Sections are reusable template partials
         * that can be included via Functions::insertSection().
         *
         * @param Project $projectConfiguration The project configuration to update.
         * @param string $webApplicationDir The WebApplication directory path.
         * @return void
         */
        private static function registerSections(Project $projectConfiguration, string $webApplicationDir): void
        {
            $sectionsDir = $webApplicationDir . DIRECTORY_SEPARATOR . 'sections';
            if (!IO::isDirectory($sectionsDir))
            {
                return;
            }

            $sections = [];
            $items = scandir($sectionsDir);
            foreach ($items as $item)
            {
                if ($item === '.' || $item === '..')
                {
                    continue;
                }

                $itemPath = $sectionsDir . DIRECTORY_SEPARATOR . $item;
                if (!IO::isFile($itemPath) || !str_ends_with($item, '.phtml'))
                {
                    continue;
                }

                $sectionName = basename($item, '.phtml');
                $sections[$sectionName] = [
                    'module' => 'sections/' . $item,
                    'locale_id' => 'home',
                ];
            }

            if (empty($sections))
            {
                return;
            }

            // Try to update the existing web_release config, or create a new one
            if ($projectConfiguration->buildConfigurationExists('web_release'))
            {
                $buildConfig = $projectConfiguration->getBuildConfiguration('web_release');
                $options = $buildConfig->getOptions();
                if (!isset($options['web_configuration']))
                {
                    $options['web_configuration'] = [];
                }
                $options['web_configuration']['sections'] = $sections;
                $buildConfig->setOptions($options);
            }

            Console::out(sprintf('Registered %d section(s) from: %s', count($sections), $sectionsDir));
        }

        /**
         * Downloads the template archive from the configured package source and extracts it to a temporary directory.
         * The package source should specify the repository, organization, project name, version, and variant to
         * determine the correct release asset to download.
         *
         * @return string The path to the extracted template directory.
         * @throws OperationException If there are issues with downloading or extracting the template.
         */
        private static function downloadTemplate(): string
        {
            $repoConfig = new RepositoryConfiguration(
                self::$packageSource->getRepository() ?? 'github',
                RepositoryType::tryFrom(self::$packageSource->getRepository() ?? 'github') ?? RepositoryType::GITHUB,
                self::getHostForRepository(self::$packageSource->getRepository() ?? 'github'),
                true
            );

            $client = AbstractRepository::fromConfiguration($repoConfig);

            $organization = self::$packageSource->getOrganization();
            $projectName = self::$packageSource->getName();
            $version = self::$packageSource->getVersion() ?? 'latest';
            $variant = self::$packageSource->getVariant();

            if($variant === null || $variant === '')
            {
                throw new OperationException("No variant specified in the package source");
            }

            $assetName = $variant . '.zip';
            Console::out(sprintf("Resolving asset '%s' in %s/%s release '%s'...", $assetName, $organization, $projectName, $version));

            $assetUrl = $client->getReleaseAssetUrl($organization, $projectName, $version, $assetName);
            if($assetUrl === null)
            {
                throw new OperationException(sprintf(
                    "Could not find release asset '%s' in %s/%s release '%s'. Check that the release exists and contains the expected asset.",
                    $assetName, $organization, $projectName, $version
                ));
            }

            $remotePackage = new RemotePackage($assetUrl, RemotePackageType::SOURCE_ZIP, $organization, $projectName, $version);
            return $client->download($remotePackage);
        }

        /**
         * Returns the API host for a given repository type. This is used to configure the repository client for downloading
         * the template asset. Supported repositories include GitHub, GitLab, and Gitea.
         *
         * @param string $repository The repository type (e.g., 'github', 'gitlab', 'gitea').
         * @return string The API host for the specified repository.
         * @throws OperationException If the repository type is unsupported.
         */
        private static function getHostForRepository(string $repository): string
        {
            return match(strtolower($repository))
            {
                'github' => 'api.github.com',
                'gitlab' => 'gitlab.com',
                'gitea' => 'gitea.com',
                default => throw new OperationException(sprintf("Unsupported repository type: %s", $repository)),
            };
        }

        /**
         * Loads and parses the template configuration from the extracted template directory. The template configuration is expected
         * to be defined in a 'template.yml' or 'template.yaml' file in the root of the extracted directory. This configuration
         * specifies how resources and templates should be copied into the project.
         *
         * @param string $extractedPath The path to the extracted template directory.
         * @return WebTemplateConfiguration The parsed template configuration object.
         * @throws OperationException If the template configuration file is missing or cannot be parsed.
         */
        private static function loadTemplateConfiguration(string $extractedPath): WebTemplateConfiguration
        {
            $templateYml = null;
            foreach (['template.yml', 'template.yaml'] as $filename)
            {
                $candidate = $extractedPath . DIRECTORY_SEPARATOR . $filename;
                if (IO::exists($candidate) && IO::isFile($candidate))
                {
                    $templateYml = $candidate;
                    break;
                }
            }

            if ($templateYml === null)
            {
                throw new OperationException("The template archive does not contain a template.yml or template.yaml file in its root directory");
            }

            $yamlContent = IO::readFile($templateYml);

            // Pre-process: quote bare * values used as catch-all glob patterns.
            // YAML interprets unquoted * as an alias reference, which fails with
            // "Reference "" does not exist" if no corresponding anchor is defined.
            // This handles template.yml entries like `/vendor: *` → `/vendor: "*"`
            $yamlContent = preg_replace('/^(\s*-\s+\S+:\s+)\*\s*$/m', '$1"*"', $yamlContent);

            return WebTemplateConfiguration::fromArray(Yaml::parse($yamlContent));
        }

        /**
         * Copies resources from the extracted template archive into the WebResources directory according to the specified mapping.
         * The mapping is an array of entries, where each entry is an associative array with a single key representing the
         * target subdirectory (relative to WebResources), and the value is either '*' to copy all files or an array of glob patterns.
         *
         * Example mapping entry:
         *   ['/*' => '*']
         *   Maps all files in the archive to the root of WebResources.
         *
         * @param string $extractedPath The extracted template archive root.
         * @param string $webResourcesDir The WebResources directory path in the project.
         * @param array $resources The resources mapping from the template configuration.
         * @return void
         */
        private static function copyResources(string $extractedPath, string $webResourcesDir, array $resources): void
        {
            foreach ($resources as $entry)
            {
                if (!is_array($entry))
                {
                    continue;
                }

                foreach ($entry as $targetSubdir => $patterns)
                {
                    $targetDir = $webResourcesDir . DIRECTORY_SEPARATOR . ltrim($targetSubdir, '/\\');
                    IO::createDirectory($targetDir, 0755, true);

                    if (is_string($patterns) && $patterns === '*')
                    {
                        self::copyAllFromArchive($extractedPath, $targetDir);
                    }
                    elseif (is_array($patterns))
                    {
                        self::copyMatchingFiles($extractedPath, $targetDir, $patterns);
                    }
                }
            }
        }

        /**
         * Copies files from the extracted template archive into a target directory using a mapping array.
         * Each entry in the mapping is an associative array with a single key representing the
         * target subdirectory (relative to the base target dir), and the value is an array of glob patterns.
         *
         * Example mapping entry:
         *   ['/*' => ['dynamical_web/*']]
         *   Maps files matching 'dynamical_web/*' in the archive to the root of the target directory.
         *
         * @param string $extractedPath The extracted template archive root.
         * @param string $targetBaseDir The base target directory (e.g., WebApplication or WebLocale).
         * @param array $mapping The mapping array (web_application or web_locale from template.yml).
         * @param string $label A human-readable label for log output.
         * @return void
         */
        private static function copyFilesUsingMapping(string $extractedPath, string $targetBaseDir, array $mapping, string $label): void
        {
            foreach ($mapping as $entry)
            {
                if (!is_array($entry))
                {
                    continue;
                }

                foreach ($entry as $targetSubdir => $patterns)
                {
                    $subdir = ltrim($targetSubdir, '/\\');
                    $targetDir = ($subdir === '*') ? $targetBaseDir : ($targetBaseDir . DIRECTORY_SEPARATOR . $subdir);
                    IO::createDirectory($targetDir, 0755, true);

                    if (is_string($patterns) && $patterns === '*')
                    {
                        self::copyAllFromArchive($extractedPath, $targetDir);
                    }
                    elseif (is_array($patterns))
                    {
                        self::copyMatchingFiles($extractedPath, $targetDir, $patterns);
                    }
                }
            }

            Console::out(sprintf('Copied %s templates to: %s', $label, $targetBaseDir));
        }

        /**
         * Copies all files from the extracted template archive to the target directory, preserving the directory structure.
         *
         * @param string $extractedPath The extracted template archive root.
         * @param string $targetDir The target directory to copy files into.
         * @return void
         */
        private static function copyAllFromArchive(string $extractedPath, string $targetDir): void
        {
            $items = IO::listDirectory($extractedPath, true, false);
            foreach ($items as $item)
            {
                $source = $extractedPath . DIRECTORY_SEPARATOR . $item;
                $dest = $targetDir . DIRECTORY_SEPARATOR . $item;
                IO::copy($source, $dest, true, false);
            }
            Console::out(sprintf('Copied resources to: %s', $targetDir));
        }

        /**
         * Copies files from the extracted template archive to the target directory based on an array of glob patterns.
         * The glob patterns are matched against the relative path of files in the archive, and matching files are copied
         * to the target directory while preserving their relative paths. Supports ** (globstar) syntax for matching
         * multiple directory levels.
         *
         * @param string $extractedPath The extracted template archive root.
         * @param string $targetDir The target directory to copy files into.
         * @param array $patterns An array of glob patterns to match files against (e.g., ['dynamical_web/*', 'assets/**']).
         * @return void
         */
        private static function copyMatchingFiles(string $extractedPath, string $targetDir, array $patterns): void
        {
            $allFiles = self::listAllFiles($extractedPath);

            foreach ($patterns as $pattern)
            {
                $baseDir = self::getPatternBaseDir($pattern);

                foreach ($allFiles as $file)
                {
                    $relativePath = substr($file, strlen($extractedPath) + 1);

                    if (!self::matchGlobPattern($pattern, $relativePath))
                    {
                        continue;
                    }

                    $subPath = $relativePath;
                    if (!empty($baseDir) && str_starts_with($relativePath, $baseDir))
                    {
                        $subPath = substr($relativePath, strlen($baseDir));
                        $subPath = ltrim($subPath, '/\\');
                    }

                    $destFile = $targetDir . DIRECTORY_SEPARATOR . $subPath;
                    $destDir = dirname($destFile);
                    if (!IO::exists($destDir))
                    {
                        IO::createDirectory($destDir, 0755, true);
                    }

                    if (IO::exists($destFile))
                    {
                        IO::delete($destFile);
                    }

                    IO::copy($file, $destFile, false, false);
                }
            }
            Console::out(sprintf('Copied resources to: %s', $targetDir));
        }

        /**
         * Matches a path against a glob pattern, with support for ** (globstar) syntax.
         * ** matches zero or more directory levels (like globstar in bash).
         *
         * @param string $pattern The glob pattern (may contain **)
         * @param string $path The relative file path to match
         * @return bool True if the path matches the pattern
         */
        private static function matchGlobPattern(string $pattern, string $path): bool
        {
            if (str_contains($pattern, '**'))
            {
                $parts = explode('**', $pattern, 2);
                $prefix = $parts[0];
                $suffix = ltrim($parts[1] ?? '', '/');

                if (!str_starts_with($path, $prefix))
                {
                    return false;
                }

                if ($suffix === '')
                {
                    return true;
                }

                $afterPrefix = substr($path, strlen($prefix));
                return fnmatch($suffix, $afterPrefix, FNM_PATHNAME);
            }

            return fnmatch($pattern, $path, FNM_PATHNAME);
        }

        /**
         * Recursively lists all files in a directory and its subdirectories, returning their full paths.
         *
         * @param string $directory The directory to list files from.
         * @return array An array of full file paths for all files found in the directory and its subdirectories.
         */
        private static function listAllFiles(string $directory): array
        {
            $files = [];
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $fileInfo)
            {
                if ($fileInfo->isFile())
                {
                    $files[] = $fileInfo->getPathname();
                }
            }
            return $files;
        }

        /**
         * Extracts the base directory from a glob pattern. For example, for the pattern 'dynamical_web/*', it returns 'dynamical_web/'.
         * This is used to determine the common prefix for patterns when copying files from the template archive.
         *
         * @param string $pattern The glob pattern (e.g., 'dynamical_web/*' or 'assets/**').
         * @return string The base directory extracted from the pattern (e.g., 'dynamical_web/' or 'assets/').
         */
        private static function getPatternBaseDir(string $pattern): string
        {
            $wildcardPos = strpos($pattern, '*');
            if ($wildcardPos === false)
            {
                $lastSlash = strrpos($pattern, '/');
                return $lastSlash !== false ? substr($pattern, 0, $lastSlash + 1) : '';
            }

            $substr = substr($pattern, 0, $wildcardPos);
            $lastSlash = strrpos($substr, '/');
            return $lastSlash !== false ? substr($pattern, 0, $lastSlash + 1) : '';
        }

        /**
         * Updates the project configuration by adding a new build configuration for the web release if it doesn't already exist.
         * The build configuration is set up to use the 'ncc' type and includes options for the web application, locales, router, and static resources.
         * It also adds a dependency on 'net.nosial.dynamicalweb' if it's not already present in the project configuration.
         *
         * @param Project $projectConfiguration The project configuration to update.
         * @param string $assemblyName The name of the assembly, used in the build configuration output path and web application config.
         * @param string $sourcePath The source path defined in the project configuration, used to determine where to place web resources and application files.
         * @return void
         */
        private static function updateBuildConfiguration(Project $projectConfiguration, string $assemblyName, string $sourcePath): void
        {
            if ($projectConfiguration->buildConfigurationExists('web_release'))
            {
                return;
            }

            $buildConfig = new Project\BuildConfiguration(['type' => 'ncc']);
            $buildConfig->setName('web_release');
            $buildConfig->setOutput('target/web_release/${ASSEMBLY.PACKAGE}.ncc');
            $buildConfig->setDefinitions(['NCC_DISABLE_LOGGING' => '1']);
            $buildConfig->setOptions([
                'web_configuration' => [
                    'application' => [
                        'name' => $assemblyName,
                        'root' => $assemblyName . '/WebApplication',
                        'resources' => $assemblyName . '/WebResources',
                        'default_locale' => 'en',
                        'report_errors' => true,
                        'xss_level' => 0,
                        'debug_panel' => true,
                    ],
                    'locales' => [
                        'en' => $assemblyName . '/WebLocale/en.yml',
                    ],
                    'router' => [
                        'base_path' => '/',
                        'routes' => [],
                    ],
                    'static' => true,
                ],
            ]);
            $projectConfiguration->addBuildConfiguration($buildConfig);

            if (!$projectConfiguration->dependencyExists('net.nosial.dynamicalweb'))
            {
                $projectConfiguration->addDependency('net.nosial.dynamicalweb', 'nosial/dynamicalweb@github');
            }
        }
    }