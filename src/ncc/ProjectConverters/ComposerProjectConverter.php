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

    namespace ncc\ProjectConverters;

    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\Classes\IO;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\IOException;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\RepositoryConfiguration;

    class ComposerProjectConverter extends AbstractProjectConverter
    {
        /**
         * @inheritDoc
         */
        public function convert(string $filePath): Project
        {
            $content = IO::readFile($filePath);
            $composerData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new IOException('Failed to parse JSON: ' . json_last_error_msg());
            }

            $project = new Project();

            // Apply assembly
            $project->setAssembly($this->generateAssembly($composerData));

            // Apply release configuration
            $releaseConfiguration = Project\BuildConfiguration::defaultRelease();
            if(isset($composerData['require']))
            {
                foreach($this->generateDependencies($composerData) as $dependencyName => $dependencySource)
                {
                    $project->addDependency($dependencyName, $dependencySource);
                }
            }

            // Apply debug configuration
            $debugConfiguration = Project\BuildConfiguration::defaultDebug();
            if(isset($composerData['require-dev']))
            {
                foreach($this->generateDebugDependencies($composerData) as $dependencyName => $dependencySource)
                {
                    $debugConfiguration->addDependency($dependencyName, $dependencySource);
                }
            }

            // Apply packagist repository configurations
            $project->setRepository(new RepositoryConfiguration(
                name: 'packagist',
                type: RepositoryType::PACKAGIST,
                host: 'packagist.org',
                ssl: true
            ));

            // Even if target-dir is deprecated, we will still support it for setting the source path if it exists
            if(isset($composerData['target-dir']))
            {
                $project->setSourcePath($composerData['target-dir']);
            }
            // PSR-4 autoloading
            elseif(isset($composerData['autoload']['psr-4']))
            {
                // Get first item, we consider this the main source path
                $psr4Paths = array_values($composerData['autoload']['psr-4']);
                $firstPath = $psr4Paths[0] ?? 'src';
                // Empty string in PSR-4 means the root directory, use '.' instead
                $project->setSourcePath($firstPath === '' ? '.' : $firstPath);

                // If there are more than one, add them as included components
                if(count($psr4Paths) > 1)
                {
                    foreach(array_slice($psr4Paths, 1) as $item)
                    {
                        // Empty string means root directory
                        $includePath = $item === '' ? '.' : $item;
                        $releaseConfiguration->addIncludedComponent($includePath);
                        $debugConfiguration->addIncludedComponent($includePath);
                    }
                }
            }
            // PSR-0 autoloading
            elseif(isset($composerData['autoload']['classmap']))
            {
                // Get first item, we consider this the main source path
                $firstPath = $composerData['autoload']['classmap'][0] ?? 'src';
                // Empty string in classmap means the root directory, use '.' instead
                $project->setSourcePath($firstPath === '' ? '.' : $firstPath);

                // If there are more than one, add them as included components
                if(count($composerData['autoload']['classmap']) > 1)
                {
                    foreach(array_slice($composerData['autoload']['classmap'], 1) as $item)
                    {
                        // Empty string means root directory
                        $includePath = $item === '' ? '.' : $item;
                        $releaseConfiguration->addIncludedComponent($includePath);
                        $debugConfiguration->addIncludedComponent($includePath);
                    }
                }
            }
            else
            {
                $project->setSourcePath('src');
            }

            // Add any files autoloaded via files to included components
            if(isset($composerData['autoload']['files']))
            {
                foreach($composerData['autoload']['files'] as $item)
                {
                    $releaseConfiguration->addIncludedComponent($item);
                    $debugConfiguration->addIncludedComponent($item);
                }
            }

            // If there are any excluded files from classmap, add them to excluded components
            if(isset($composerData['autoload']['exclude-from-classmap']))
            {
                $excludedComponents = is_array($composerData['autoload']['exclude-from-classmap']) 
                    ? $composerData['autoload']['exclude-from-classmap'] 
                    : [$composerData['autoload']['exclude-from-classmap']];
                    
                foreach($excludedComponents as $component)
                {
                    $releaseConfiguration->addExcludedComponent($component);
                    $debugConfiguration->addExcludedComponent($component);
                }
            }

            $project->addBuildConfiguration($releaseConfiguration);
            $project->addBuildConfiguration($debugConfiguration);

            if(isset($composerData['bin']) && is_array($composerData['bin']) && count($composerData['bin']) > 0)
            {
                $project->addExecutionUnit($this->generateExecutionUnit($composerData));
            }

            return $project;
        }

        /**
         * Generate an execution unit from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return Project\ExecutionUnit The generated execution unit.
         */
        private function generateExecutionUnit(array $composerData): Project\ExecutionUnit
        {
            return new Project\ExecutionUnit([
                'name' => 'main',
                'type' => 'php',
                'mode' => 'auto',
                'entry' => $composerData['bin'][0], // TODO: Ensure the entry is considered to be a required file
                'working_directory' => MacroVariable::CURRENT_WORKING_DIRECTORY->value,
            ]);
        }

        /**
         * Generate dependencies from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return array An associative array of dependency names to PackageSource objects.
         */
        private function generateDependencies(array $composerData): array
        {
            $dependencies = [];
            foreach ($composerData['require'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    continue;
                }

                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                //$dependencies[$this->generatePackageName($dependency)]->setVersion((new VersionParser)->normalize($version));
                $dependencies[$this->generatePackageName($dependency)]->setVersion('latest');
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate debug dependencies from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return array An associative array of dependency names to PackageSource objects for debug dependencies.
         */
        private function generateDebugDependencies(array $composerData): array
        {
            $dependencies = [];
            foreach ($composerData['require-dev'] as $dependency => $version)
            {
                if(str_starts_with($dependency, 'ext-') || $dependency === 'php')
                {
                    continue;
                }

                $dependencies[$this->generatePackageName($dependency)] = new PackageSource($dependency);
                //$dependencies[$this->generatePackageName($dependency)]->setVersion((new VersionParser)->normalize($version));
                $dependencies[$this->generatePackageName($dependency)]->setVersion('latest');
                $dependencies[$this->generatePackageName($dependency)]->setRepository('packagist');
            }

            return $dependencies;
        }

        /**
         * Generate a Project\Assembly object from Composer data.
         *
         * @param array $composerData The parsed Composer JSON data.
         * @return Project\Assembly The generated assembly object.
         */
        private function generateAssembly(array $composerData): Project\Assembly
        {
            $assembly = new Project\Assembly();

            // Description
            if(isset($composerData['description']))
            {
                $assembly->setDescription($composerData['description']);
            }

            // Homepage
            if(isset($composerData['homepage']))
            {
                $assembly->setUrl($composerData['homepage']);
            }

            // Authors
            if(isset($composerData['authors']) && count($composerData['authors']) > 0)
            {
                if(isset($composerData['authors']['name']))
                {
                    $assembly->setAuthor(sprintf("%s %s%s",
                        $composerData['authors']['name'],
                        ($composerData['authors']['email'] ?' <' . $composerData['authors']['email'] . '>' : ''),
                        ($composerData['authors']['homepage'] ? ' (' . $composerData['authors']['homepage'] . ')' : '')
                    ));
                }
                else
                {
                    $authorString = (string)null;
                    foreach($composerData['authors'] as $author)
                    {
                        if(isset($authorString[0]))
                        {
                            $authorString .= ', ';
                        }

                        $authorString .= sprintf("%s %s%s",
                            $author['name'],
                            (isset($author['email']) ? ' <' . $author['email'] . '>' : ''),
                            (isset($author['homepage']) ? ' (' . $author['homepage'] . ')' : '')
                        );
                    }

                    $assembly->setAuthor($authorString);
                }
            }

            // License
            if(isset($composerData['license']))
            {
                $assembly->setLicense($composerData['license']);
            }

            $assembly->setPackage($this->generatePackageName($composerData['name']));

            return $assembly;
        }

        /**
         * Generate a package name from a Composer package name.
         *
         * @param string $composerPackageName The Composer package name (e.g., "vendor/package-name").
         * @return string The generated package name (e.g., "com.vendor.packagename").
         */
        private function generatePackageName(string $composerPackageName): string
        {
            return sprintf("%s.%s.%s",
                'com',
                str_replace('-', '', explode('/', $composerPackageName)[0]),
                str_replace('-', '', explode('/', $composerPackageName)[1])
            );
        }
    }