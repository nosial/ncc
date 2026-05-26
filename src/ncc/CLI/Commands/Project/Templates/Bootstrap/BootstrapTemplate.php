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

    namespace ncc\CLI\Commands\Project\Templates\Bootstrap;

    use ncc\ArchiveExtractors\ZipArchive;
    use ncc\Classes\Console;
    use ncc\Classes\PathResolver;
    use ncc\Classes\ShutdownHandler;
    use ncc\CLI\Commands\Project\Templates\Web\WebTemplate;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Objects\Project;

    class BootstrapTemplate extends WebTemplate
    {
        /**
         * @var string The Bootstrap version to download.
         */
        private static string $version;

        /**
         * Sets the Bootstrap version to use when generating the template.
         *
         * @param string $version The Bootstrap version (e.g., "5.3.3", "4.1.3").
         * @return void
         */
        public static function setVersion(string $version): void
        {
            self::$version = $version;
        }

        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            // Run the base web template generation first
            parent::generate($projectDirectory, $projectConfiguration);

            $sourcePath = $projectConfiguration->getSourcePath() ?? 'src';
            $assembly = $projectConfiguration->getAssembly();
            $replacements = [
                '${PACKAGE_NAME}' => $assembly->getPackage(),
                '${ASSEMBLY_NAME}' => $assembly->getName(),
                '${ASSEMBLY_VERSION}' => $assembly->getVersion(),
                '${BOOTSTRAP_VERSION}' => self::$version,
            ];

            // Download and extract Bootstrap distribution
            $extractedPath = self::downloadBootstrap(self::$version);

            // Locate the distribution directory within the extracted archive
            $distDir = self::findDistDirectory($extractedPath, self::$version);

            // Copy Bootstrap css/ and js/ to WebResources
            $webResourcesDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebResources';
            self::copyBootstrapResources($distDir, $webResourcesDir);

            // Overwrite web application templates with Bootstrap-local versions
            $webAppDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebApplication';
            $webErrorsDir = $webAppDir . DIRECTORY_SEPARATOR . 'errors';
            $webSectionsDir = $webAppDir . DIRECTORY_SEPARATOR . 'sections';
            $webLocaleDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebLocale';

            IO::createDirectory($webSectionsDir, 0755, true);

            self::writeTemplate(
                $webAppDir . DIRECTORY_SEPARATOR . 'index.phtml',
                'index.phtml.tpl',
                $replacements,
                __DIR__
            );

            self::writeTemplate(
                $webErrorsDir . DIRECTORY_SEPARATOR . '404.phtml',
                '404.phtml.tpl',
                $replacements,
                __DIR__
            );

            self::writeTemplate(
                $webErrorsDir . DIRECTORY_SEPARATOR . '500.phtml',
                '500.phtml.tpl',
                $replacements,
                __DIR__
            );

            // Generate section templates
            self::writeTemplate(
                $webSectionsDir . DIRECTORY_SEPARATOR . 'navbar.phtml',
                'sections' . DIRECTORY_SEPARATOR . 'navbar.phtml.tpl',
                $replacements,
                __DIR__
            );

            self::writeTemplate(
                $webSectionsDir . DIRECTORY_SEPARATOR . 'footer.phtml',
                'sections' . DIRECTORY_SEPARATOR . 'footer.phtml.tpl',
                $replacements,
                __DIR__
            );

            // Overwrite locale file with Bootstrap-specific version (includes nav_home)
            self::writeTemplate(
                $webLocaleDir . DIRECTORY_SEPARATOR . 'en.yml',
                'en.yml.tpl',
                $replacements,
                __DIR__
            );

            // Add sections to the web_configuration
            $buildConfig = $projectConfiguration->getBuildConfiguration('web_release');
            if($buildConfig !== null)
            {
                $options = $buildConfig->getOptions();
                $options['web_configuration']['sections'] = [
                    'navbar' => [
                        'module' => 'sections/navbar.phtml',
                        'locale_id' => 'home',
                    ],
                    'footer' => [
                        'module' => 'sections/footer.phtml',
                        'locale_id' => 'home',
                    ],
                ];
                $buildConfig->setOptions($options);

                $projectConfiguration->save($projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
                Console::out('Modified File: ' . $projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
            }
        }

        /**
         * Downloads the Bootstrap distribution zip for the given version to a temporary location.
         *
         * @param string $version The Bootstrap version to download.
         * @return string The path to the extracted archive contents.
         * @throws OperationException If the download or extraction fails.
         */
        private static function downloadBootstrap(string $version): string
        {
            $url = sprintf('https://github.com/twbs/bootstrap/releases/download/v%s/bootstrap-%s-dist.zip', $version, $version);
            $tmpDir = PathResolver::getTmpLocation();

            if(!IO::isDirectory($tmpDir))
            {
                IO::createDirectory($tmpDir);
            }

            $zipPath = $tmpDir . DIRECTORY_SEPARATOR . sprintf('bootstrap-%s-dist.zip', $version);
            ShutdownHandler::flagTemporary($zipPath);

            Console::out(sprintf('Downloading Bootstrap v%s...', $version));

            $curl = curl_init($url);
            $fileHandle = fopen($zipPath, 'wb');

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_NOPROGRESS, false);
            curl_setopt($curl, CURLOPT_FILE, $fileHandle);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-Agent: ncc']);
            curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, static function ($resource, $totalBytes, $downloadedBytes) use ($version)
            {
                if($totalBytes > 0)
                {
                    $downloadedKB = round($downloadedBytes / 1024, 1);
                    $totalKB = round($totalBytes / 1024, 1);
                    $message = sprintf('Downloading Bootstrap v%s (%s KB / %s KB)', $version, $downloadedKB, $totalKB);
                    Console::inlineProgress((int)$downloadedBytes, (int)$totalBytes, $message);
                }
            });

            curl_exec($curl);
            fclose($fileHandle);

            if(curl_errno($curl))
            {
                throw new OperationException(sprintf('Failed to download Bootstrap v%s: %s', $version, curl_error($curl)));
            }

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            Console::completeProgress(sprintf('Downloaded Bootstrap v%s', $version));

            if($httpCode !== 200)
            {
                throw new OperationException(sprintf('Failed to download Bootstrap v%s: HTTP %d (check that the version exists)', $version, $httpCode));
            }

            // Extract the zip archive
            $extractPath = $tmpDir . DIRECTORY_SEPARATOR . 'bootstrap-' . $version . '-extract';
            ShutdownHandler::flagTemporary($extractPath);

            Console::out('Extracting Bootstrap archive...');
            ZipArchive::extract($zipPath, $extractPath);

            return $extractPath;
        }

        /**
         * Locates the distribution directory within the extracted archive.
         *
         * @param string $extractedPath The path to the extracted archive.
         * @param string $version The Bootstrap version.
         * @return string The path to the distribution directory containing css/ and js/.
         * @throws OperationException If the distribution directory cannot be found.
         */
        private static function findDistDirectory(string $extractedPath, string $version): string
        {
            // Bootstrap zips typically extract to bootstrap-<version>-dist/
            $expectedDir = $extractedPath . DIRECTORY_SEPARATOR . 'bootstrap-' . $version . '-dist';
            if(IO::isDirectory($expectedDir))
            {
                return $expectedDir;
            }

            // Fallback: scan for a directory containing both css/ and js/
            $entries = scandir($extractedPath);
            foreach($entries as $entry)
            {
                if($entry === '.' || $entry === '..')
                {
                    continue;
                }

                $entryPath = $extractedPath . DIRECTORY_SEPARATOR . $entry;
                if(IO::isDirectory($entryPath)
                    && IO::isDirectory($entryPath . DIRECTORY_SEPARATOR . 'css')
                    && IO::isDirectory($entryPath . DIRECTORY_SEPARATOR . 'js'))
                {
                    return $entryPath;
                }
            }

            // Last fallback: check if css/ and js/ are directly in the extracted path
            if(IO::isDirectory($extractedPath . DIRECTORY_SEPARATOR . 'css')
                && IO::isDirectory($extractedPath . DIRECTORY_SEPARATOR . 'js'))
            {
                return $extractedPath;
            }

            throw new OperationException(sprintf(
                'Could not locate Bootstrap distribution directories (css/ and js/) in the extracted archive for v%s',
                $version
            ));
        }

        /**
         * Copies Bootstrap css/ and js/ directories into the project's WebResources directory.
         *
         * @param string $distDir The Bootstrap distribution directory containing css/ and js/.
         * @param string $webResourcesDir The project's WebResources directory.
         * @return void
         */
        private static function copyBootstrapResources(string $distDir, string $webResourcesDir): void
        {
            $cssSource = $distDir . DIRECTORY_SEPARATOR . 'css';
            $jsSource = $distDir . DIRECTORY_SEPARATOR . 'js';
            $cssTarget = $webResourcesDir . DIRECTORY_SEPARATOR . 'css';
            $jsTarget = $webResourcesDir . DIRECTORY_SEPARATOR . 'js';

            IO::createDirectory($cssTarget, 0755, true);
            IO::createDirectory($jsTarget, 0755, true);

            // Copy all CSS files
            foreach(scandir($cssSource) as $file)
            {
                if($file === '.' || $file === '..')
                {
                    continue;
                }

                $source = $cssSource . DIRECTORY_SEPARATOR . $file;
                $destination = $cssTarget . DIRECTORY_SEPARATOR . $file;
                if(is_file($source))
                {
                    copy($source, $destination);
                }
            }
            Console::out('Copied Bootstrap CSS resources to: ' . $cssTarget);

            // Copy all JS files
            foreach(scandir($jsSource) as $file)
            {
                if($file === '.' || $file === '..')
                {
                    continue;
                }

                $source = $jsSource . DIRECTORY_SEPARATOR . $file;
                $destination = $jsTarget . DIRECTORY_SEPARATOR . $file;
                if(is_file($source))
                {
                    copy($source, $destination);
                }
            }
            Console::out('Copied Bootstrap JS resources to: ' . $jsTarget);
        }
    }
