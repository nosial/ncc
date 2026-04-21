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

    namespace ncc\CLI\Commands\Project\Templates\Web;

    use ncc\Classes\Console;
    use ncc\Libraries\fslib\IO;
    use ncc\Interfaces\TemplateGeneratorInterface;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;

    class WebTemplate implements TemplateGeneratorInterface
    {
        /**
         * @inheritDoc
         */
        public static function generate(string $projectDirectory, Project $projectConfiguration): void
        {
            $assembly = $projectConfiguration->getAssembly();
            $packageName = $assembly->getPackage();
            $assemblyName = $assembly->getName();
            $assemblyVersion = $assembly->getVersion();
            $sourcePath = $projectConfiguration->getSourcePath() ?? 'src';

            $replacements = [
                '${PACKAGE_NAME}' => $packageName,
                '${ASSEMBLY_NAME}' => $assemblyName,
                '${ASSEMBLY_VERSION}' => $assemblyVersion,
            ];

            // Generate web_entry
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'web_entry',
                'web_entry.tpl',
                $replacements
            );

            // Generate Dockerfile
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'Dockerfile',
                'Dockerfile.tpl',
                $replacements
            );

            // Generate docker-compose.yml
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'docker-compose.yml',
                'docker-compose.yml.tpl',
                $replacements
            );

            // Generate nginx.conf
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'nginx.conf',
                'nginx.conf.tpl',
                $replacements
            );

            // Generate supervisord.conf
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'supervisord.conf',
                'supervisord.conf.tpl',
                $replacements
            );

            // Generate docker-entrypoint.sh
            self::writeTemplate(
                $projectDirectory . DIRECTORY_SEPARATOR . 'docker-entrypoint.sh',
                'docker-entrypoint.sh.tpl',
                $replacements
            );

            // Generate .gitignore (only if it doesn't exist)
            $gitignorePath = $projectDirectory . DIRECTORY_SEPARATOR . '.gitignore';
            if(!IO::exists($gitignorePath))
            {
                self::writeTemplate($gitignorePath, 'gitignore.tpl', $replacements);
            }

            // Create web application directory structure
            $webAppDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebApplication';
            $webErrorsDir = $webAppDir . DIRECTORY_SEPARATOR . 'errors';
            $webResourcesDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebResources' . DIRECTORY_SEPARATOR . 'css';
            $webLocaleDir = $projectDirectory . DIRECTORY_SEPARATOR . $sourcePath . DIRECTORY_SEPARATOR . 'WebLocale';

            IO::createDirectory($webAppDir, 0755, true);
            IO::createDirectory($webErrorsDir, 0755, true);
            IO::createDirectory($webResourcesDir, 0755, true);
            IO::createDirectory($webLocaleDir, 0755, true);

            // Generate sample application files
            self::writeTemplate(
                $webAppDir . DIRECTORY_SEPARATOR . 'index.phtml',
                'index.phtml.tpl',
                $replacements
            );

            self::writeTemplate(
                $webErrorsDir . DIRECTORY_SEPARATOR . '404.phtml',
                '404.phtml.tpl',
                $replacements
            );

            self::writeTemplate(
                $webErrorsDir . DIRECTORY_SEPARATOR . '500.phtml',
                '500.phtml.tpl',
                $replacements
            );

            self::writeTemplate(
                $webResourcesDir . DIRECTORY_SEPARATOR . 'style.css',
                'style.css.tpl',
                $replacements
            );

            self::writeTemplate(
                $webLocaleDir . DIRECTORY_SEPARATOR . 'en.yml',
                'en.yml.tpl',
                $replacements
            );

            // Add DynamicalWeb dependency
            if(!$projectConfiguration->dependencyExists('net.nosial.dynamicalweb'))
            {
                $projectConfiguration->addDependency('net.nosial.dynamicalweb', 'nosial/dynamicalweb@github');
            }

            // Build configuration for web release with web_configuration
            if(!$projectConfiguration->buildConfigurationExists('web_release'))
            {
                $buildConfiguration = new BuildConfiguration(['type' => 'ncc']);
                $buildConfiguration->setName('web_release');
                $buildConfiguration->setOutput('target/web_release/${ASSEMBLY.PACKAGE}.ncc');
                $buildConfiguration->setDefinitions(['NCC_DISABLE_LOGGING' => '1']);
                $buildConfiguration->setOptions([
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
                            'response_handlers' => [
                                404 => 'errors/404.phtml',
                                500 => 'errors/500.phtml',
                            ],
                            'routes' => [
                                [
                                    'id' => 'home',
                                    'path' => '/',
                                    'module' => 'index.phtml',
                                    'locale_id' => 'home',
                                    'allowed_methods' => ['GET'],
                                ],
                            ],
                        ],
                        'static' => true,
                    ],
                ]);
                $projectConfiguration->addBuildConfiguration($buildConfiguration);
            }

            // Execution unit
            if(!$projectConfiguration->executionUnitExists('web_entry'))
            {
                $executionUnit = new Project\ExecutionUnit([
                    'name' => 'web_entry',
                    'entry' => 'web_entry',
                ]);
                $projectConfiguration->addExecutionUnit($executionUnit);
            }

            // Update the project configuration
            $projectConfiguration->setWebEntryPoint('web_entry');

            $projectConfiguration->save($projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
            Console::out('Modified File: ' . $projectDirectory . DIRECTORY_SEPARATOR . 'project.yml');
        }

        /**
         * Reads a template file, applies replacements, and writes it to the target path.
         *
         * @param string $targetPath The destination file path.
         * @param string $templateFile The template filename in the current directory.
         * @param array $replacements Key-value pairs of placeholders to replace.
         * @param string|null $templateDirectory The directory containing the template file. Defaults to the Web template directory.
         * @return void
         */
        protected static function writeTemplate(string $targetPath, string $templateFile, array $replacements, ?string $templateDirectory=null): void
        {
            if(IO::exists($targetPath))
            {
                IO::delete($targetPath);
            }

            $templateDirectory = $templateDirectory ?? __DIR__;
            $content = file_get_contents($templateDirectory . DIRECTORY_SEPARATOR . $templateFile);
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);

            IO::writeFile($targetPath, $content);
            Console::out('Generated File: ' . $targetPath);
        }
    }