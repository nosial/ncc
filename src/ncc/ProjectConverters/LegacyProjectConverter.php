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
    use ncc\Classes\Utilities;
    use ncc\CLI\Logger;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\RepositoryType;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;
    use ncc\Objects\RepositoryConfiguration;
    use RuntimeException;

    /**
     * Compatibility layer for converting old project.json format (ncc_production)
     * to the new Project configuration format (ncc v3)
     */
    class LegacyProjectConverter extends AbstractProjectConverter
    {
        public function convert(string $filePath): Project
        {
            $content = IO::readFile($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new RuntimeException('Failed to parse JSON: ' . json_last_error_msg());
            }

            $project = new Project();

            if(isset($data['project']['update_source']))
            {
                $project = $this->applyUpdateSource($project, $data['project']['update_source']);
            }

            if(isset($data['build']['source_path']))
            {
                $project->setSourcePath($data['build']['source_path']);
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.build.source_path is not set, using default source path: %s', $project->getSourcePath()));
            }


            if(isset($data['assembly']))
            {
                $project = $this->applyAssemblyConfiguration($project, $data['assembly']);
            }

            if(isset($data['execution_policies']))
            {
                $convertedResults = $this->convertExecutionPolicies($project, $data['execution_policies']);
                foreach($convertedResults as $name => $policy)
                {
                    $project->addExecutionUnit($policy);

                    if(isset($data['build']['main']) && $data['build']['main'] === $name)
                    {
                        $project->setEntryPoint($name);
                    }
                }
            }

            if(isset($data['build']))
            {
                $project = $this->applyBuildConfiguration($project, $data['build']);
            }

            if(isset($data['build']['dependencies']))
            {
                $project = $this->applyDependencies($project, $data['build']['dependencies']);
            }

            return $project;
        }

        private function applyUpdateSource(Project $project, array $data): Project
        {
            if(isset($data['source']))
            {
                Logger::getLogger()->verbose(sprintf('Configuring project update source: %s', $data['source']));
                $project->setUpdateSource(new PackageSource($data['source']));
            }

            if(isset($data['repository']))
            {
                if(!isset($data['repository']['name']))
                {
                    Logger::getLogger()->warning('project.update_source.repository.name is not set, skipping repository configuration');
                }
                elseif(!isset($data['repository']['type']))
                {
                    Logger::getLogger()->warning('project.update_source.repository.type is not set, skipping repository configuration');
                }
                elseif(!isset($data['repository']['host']))
                {
                    Logger::getLogger()->warning('project.update_source.repository.host is not set, skipping repository configuration');
                }
                elseif(!isset($data['repository']['ssl']))
                {
                    Logger::getLogger()->warning('project.update_source.repository.ssl is not set, skipping repository configuration');
                }
                else
                {
                    $name = $data['repository']['name'];
                    $type = match(strtolower($data['repository']['type']))
                    {
                        'github' => RepositoryType::GITHUB,
                        'gitlab' => RepositoryType::GITLAB,
                        'gitea' => RepositoryType::GITEA,
                        'packagist' => RepositoryType::PACKAGIST,
                        default => null
                    };
                    $host = $data['repository']['host'];
                    $ssl = (bool)$data['repository']['ssl'];

                    if($type === null)
                    {
                        Logger::getLogger()->warning(sprintf('project.update_source.repository.type "%s" is not a valid repository type, skipping repository configuration', $data['repository']['type']));
                    }
                    else
                    {
                        Logger::getLogger()->verbose(sprintf('Configuring project update source repository: %s (%s)', $name, $type->value));
                        $project->setRepository(new RepositoryConfiguration($name, $type, $host, $ssl));
                    }
                }
            }

            return $project;
        }


        private function applyAssemblyConfiguration(Project $project, array $assembly): Project
        {
            if(isset($assembly['name']))
            {
                $project->getAssembly()->setName($this->convertMacros($assembly['name']));
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.assembly.name is not set, using default assembly name: %s', $project->getAssembly()->getName()));
            }

            if(isset($assembly['package']))
            {
                $project->getAssembly()->setPackage($this->convertMacros($assembly['package']));
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.assembly.package is not set, using default package name, using: %s', $project->getAssembly()->getPackage()));
            }

            if(isset($assembly['version']))
            {
                $project->getAssembly()->setVersion($assembly['version']);
            }
            else
            {
                Logger::getLogger()->warning('project.assembly.version is not set, using default version');
            }

            if(isset($assembly['description']))
            {
                $project->getAssembly()->setDescription($assembly['description']);
            }

            if(isset($assembly['product']))
            {
                $project->getAssembly()->setProduct($assembly['product']);
            }

            if(isset($assembly['copyright']))
            {
                $project->getAssembly()->setCopyright($assembly['copyright']);
            }

            if(isset($assembly['trademark']))
            {
                $project->getAssembly()->setTrademark($assembly['trademark']);
            }

            return $project;
        }

        private function applyBuildConfiguration(Project $project, array $build): Project
        {
            if(isset($build['source_path']))
            {
                $project->setSourcePath($this->convertMacros($build['source_path']));
            }

            if(isset($build['configurations']))
            {
                $buildConfigurations = $this->convertBuildConfigurations($build['configurations']);
                foreach($buildConfigurations as $buildName => $buildConfig)
                {
                    $project->addBuildConfiguration($buildConfig);

                    // This ensures we actually have a default configuration defined in the configuration
                    if(isset($build['default_configuration']) && $build['default_configuration'] === $buildName)
                    {
                        $project->setDefaultBuild($buildName);
                    }

                }
            }

            return $project;
        }

        private function convertBuildConfigurations(array $buildConfigurations): array
        {
            $results = [];

            foreach($buildConfigurations as $legacyConfiguration)
            {
                if(!isset($legacyConfiguration['name']))
                {
                    // We skip this since there's no defined name for the build configuration
                    // Shouldn't even be valid anyway.
                    continue;
                }

                $convertedArray = [];
                $convertedArray['name'] = $legacyConfiguration['name'];

                if(isset($legacyConfiguration['output']))
                {
                    $convertedArray['output'] = $this->convertMacros($legacyConfiguration['output']);
                }

                if(isset($legacyConfiguration['build_type']))
                {
                    switch($legacyConfiguration['build_type'])
                    {
                        default:
                        case 'ncc':
                            $convertedArray['type'] = 'ncc';
                            break;

                        case 'executable':
                            $convertedArray['type'] = 'native';
                            break;
                    }
                }

                if(isset($legacyConfiguration['define_constants']) && is_array($legacyConfiguration['define_constants']))
                {
                    $convertedArray['definitions'] = array_map(function ($constantValue)
                    {
                        return $this->convertMacros($constantValue);
                    }, $legacyConfiguration['define_constants']);;
                }

                // Note: Build configuration dependency configurations are no longer supported.

                $results[$convertedArray['name']] = new BuildConfiguration($convertedArray);
            }

            return $results;
        }


        private function convertExecutionPolicies(Project $project, array $executionPolicies): array
        {
            $results = [];

            foreach($executionPolicies as $policy)
            {
                $convertedResult = [];
                if(isset($policy['name']))
                {
                    $convertedResult['name'] = $policy['name'];
                }

                if(isset($policy['runner']))
                {
                    if($policy['runner'] === 'php')
                    {
                        $convertedResult['type'] = 'php';
                    }
                    else
                    {
                        $convertedResult['type'] = 'system';
                    }
                }

                if(isset($policy['execute']['working_directory']))
                {
                    // TODO: Implement a backwards compatibility layer for the macros that can be found in these properties
                    $convertedResult['working_directory'] = $policy['execute']['working_directory'];
                }

                // Only `tty` is an option, if it's set we must respect it
                // Otherwise, we need to assume for auto-mode
                if(isset($policy['execute']['tty']) && $policy['execute']['tty'] === true)
                {
                    $convertedResult['mode'] = 'tty';
                }
                else
                {
                    $convertedResult['mode'] = 'auto';
                }

                // 'options' are arguments.
                if(isset($policy['execute']['options']) && is_array($policy['execute']['options']))
                {
                    $convertedResult['arguments'] = $policy['execute']['options'];
                }

                if(isset($policy['execute']['environment_variables']) && is_array($policy['execute']['environment_variables']))
                {
                    $convertedResult['environment'] = $policy['execute']['environment_variables'];
                }

                if(isset($policy['execute']['target']))
                {
                    $convertedResult['entry'] = $policy['execute']['target'];
                }

                $results[$convertedResult['name']] = new Project\ExecutionUnit($convertedResult);
            }

            return $results;
        }

        private function applyDependencies(Project $project, array $dependencies): Project
        {
            foreach($dependencies as $legacyDependency)
            {
                if(!isset($legacyDependency['source']))
                {
                    // We skip dependencies where no source is defined.
                    continue;
                }

                // Construct the package source from the original source, should be compatible.
                $packageSource = new PackageSource($legacyDependency['source']);

                // Set te version if one is set.
                if(isset($legacyDependency['version']))
                {
                    $packageSource->setVersion($legacyDependency['version']);
                }

                $project->addDependency($packageSource);
            }

            return $project;
        }

        /**
         * Converts Macros from the legacy format to the currently supported format
         *
         * @param string $input The input string of the string to convert
         * @return string The output of the resulting string
         */
        private function convertMacros(string $input): string
        {
            return Utilities::replaceString($input, [
                '%ASSEMBLY.NAME%' => MacroVariable::ASSEMBLY_NAME,
                '%ASSEMBLY.PACKAGE%' => MacroVariable::ASSEMBLY_PACKAGE,
                '%ASSEMBLY.DESCRIPTION%' => MacroVariable::ASSEMBLY_DESCRIPTION,
                '%ASSEMBLY.COMPANY%' => MacroVariable::ASSEMBLY_ORGANIZATION,
                '%ASSEMBLY.PRODUCT%' => MacroVariable::ASSEMBLY_PRODUCT,
                '%ASSEMBLY.COPYRIGHT%' => MacroVariable::ASSEMBLY_COPYRIGHT,
                '%ASSEMBLY.TRADEMARK%' => MacroVariable::ASSEMBLY_TRADEMARK,
                '%ASSEMBLY.VERSION%' => MacroVariable::ASSEMBLY_VERSION,
                '%COMPILE_TIMESTAMP%' => MacroVariable::COMPILE_TIMESTAMP,
                '%d%' => MacroVariable::d,
                '%D%' => MacroVariable::D,
                '%j%' => MacroVariable::j,
                '%l%' => MacroVariable::l,
                '%N%' => MacroVariable::N,
                '%S%' => MacroVariable::S,
                '%w%' => MacroVariable::w,
                '%z%' => MacroVariable::z,
                '%W%' => MacroVariable::W,
                '%F%' => MacroVariable::F,
                '%m%' => MacroVariable::m,
                '%M%' => MacroVariable::M,
                '%n%' => MacroVariable::n,
                '%t%' => MacroVariable::t,
                '%L%' => MacroVariable::L,
                '%o%' => MacroVariable::o,
                '%Y%' => MacroVariable::Y,
                '%y%' => MacroVariable::y,
                '%a%' => MacroVariable::a,
                '%A%' => MacroVariable::A,
                '%B%' => MacroVariable::B,
                '%g%' => MacroVariable::g,
                '%G%' => MacroVariable::G,
                '%h%' => MacroVariable::h,
                '%H%' => MacroVariable::H,
                '%i%' => MacroVariable::i,
                '%s%' => MacroVariable::s,
                '%c%' => MacroVariable::c,
                '%r%' => MacroVariable::r,
                '%u%' => MacroVariable::u,
                '%DEFAULT_BUILD_CONFIGURATION%' => MacroVariable::DEFAULT_BUILD_CONFIGURATION,
                '%BUILD_OUTPUT_PATH%' => MacroVariable::BUILD_OUTPUT_PATH,
                '%CWD%' => MacroVariable::CURRENT_WORKING_DIRECTORY,
                '%PID%' => MacroVariable::PROCESS_ID,
            ]);
        }
    }