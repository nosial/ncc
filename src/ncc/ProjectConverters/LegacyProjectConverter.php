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

    namespace ncc\ProjectConverters;

    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\Classes\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\Utilities;
    use ncc\Enums\MacroVariable;
    use ncc\Enums\RepositoryType;
    use ncc\Exceptions\IOException;
    use ncc\Objects\PackageSource;
    use ncc\Objects\Project;
    use ncc\Objects\Project\BuildConfiguration;
    use ncc\Objects\RepositoryConfiguration;

    /**
     * Compatibility layer for converting old project.json format (ncc_production)
     * to the new Project configuration format (ncc v3)
     */
    class LegacyProjectConverter extends AbstractProjectConverter
    {
        /**
         * Converts a legacy project.json file to the new Project format
         *
         * @param string $filePath The path to the legacy project.json file
         * @param string|null $version Optional version parameter (not used in legacy converter)
         * @param callable|null $progressCallback Optional callback for progress updates
         * @return Project The converted Project object
         * @throws IOException If the file cannot be read
         */
        public function convert(string $filePath, ?string $version = null, ?callable $progressCallback = null): Project
        {
            Logger::getLogger()->verbose(sprintf('Converting legacy project from %s', $filePath));
            $content = IO::readFile($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                Logger::getLogger()->error(sprintf('Failed to parse legacy project.json: %s', json_last_error_msg()));
                throw new IOException('Failed to parse JSON: ' . json_last_error_msg());
            }

            Logger::getLogger()->debug('Successfully parsed legacy project.json');
            $project = new Project();

            if(isset($data['project']['update_source']))
            {
                Logger::getLogger()->debug('Processing update source configuration');
                $project = $this->applyUpdateSource($project, $data['project']['update_source']);
            }

            if(isset($data['build']['source_path']))
            {
                Logger::getLogger()->verbose(sprintf('Setting source path: %s', $data['build']['source_path']));
                $project->setSourcePath($data['build']['source_path']);
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.build.source_path is not set, using default source path: %s', $project->getSourcePath()));
            }


            if(isset($data['assembly']))
            {
                Logger::getLogger()->debug('Processing assembly configuration');
                $project = $this->applyAssemblyConfiguration($project, $data['assembly']);
            }

            if(isset($data['execution_policies']))
            {
                Logger::getLogger()->debug(sprintf('Converting %d execution policies', count($data['execution_policies'])));
                $convertedResults = $this->convertExecutionPolicies($data['execution_policies']);
                foreach($convertedResults as $name => $policy)
                {
                    Logger::getLogger()->verbose(sprintf('Adding execution unit: %s', $name));
                    $project->addExecutionUnit($policy);

                    if(isset($data['build']['main']) && $data['build']['main'] === $name)
                    {
                        Logger::getLogger()->verbose(sprintf('Setting entry point to: %s', $name));
                        $project->setEntryPoint($name);
                    }
                }
            }

            if(isset($data['build']))
            {
                Logger::getLogger()->debug('Processing build configuration');
                $project = $this->applyBuildConfiguration($project, $data['build']);
            }

            if(isset($data['build']['dependencies']))
            {
                Logger::getLogger()->debug(sprintf('Processing %d dependencies', count($data['build']['dependencies'])));
                $project = $this->applyDependencies($project, $data['build']['dependencies']);
            }

            Logger::getLogger()->verbose('Successfully converted legacy project');
            return $project;
        }

        /**
         * Applies update source configuration from the legacy format to the project
         *
         * @param Project $project The project to apply the update source configuration to
         * @param array $data The array of update source configuration in the legacy format
         * @return Project The project with the applied update source configuration
         */
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
                        $project->setRepositories(new RepositoryConfiguration($name, $type, $host, $ssl));
                    }
                }
            }

            return $project;
        }

        /**
         * Applies assembly configuration from the legacy format to the project
         *
         * @param Project $project The project to apply the assembly configuration to
         * @param array $assembly The array of assembly configuration in the legacy format
         * @return Project The project with the applied assembly configuration
         */
        private function applyAssemblyConfiguration(Project $project, array $assembly): Project
        {
            if(isset($assembly['name']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly name: %s', $assembly['name']));
                $project->getAssembly()->setName($this->convertMacros($assembly['name']));
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.assembly.name is not set, using default assembly name: %s', $project->getAssembly()->getName()));
            }

            if(isset($assembly['package']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly package: %s', $assembly['package']));
                $project->getAssembly()->setPackage($this->convertMacros($assembly['package']));
            }
            else
            {
                Logger::getLogger()->warning(sprintf('project.assembly.package is not set, using default package name, using: %s', $project->getAssembly()->getPackage()));
            }

            if(isset($assembly['version']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly version: %s', $assembly['version']));
                $project->getAssembly()->setVersion($assembly['version']);
            }
            else
            {
                Logger::getLogger()->warning('project.assembly.version is not set, using default version');
            }

            if(isset($assembly['description']))
            {
                Logger::getLogger()->verbose('Setting assembly description');
                $project->getAssembly()->setDescription($assembly['description']);
            }

            if(isset($assembly['product']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly product: %s', $assembly['product']));
                $project->getAssembly()->setProduct($assembly['product']);
            }

            if(isset($assembly['copyright']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly copyright: %s', $assembly['copyright']));
                $project->getAssembly()->setCopyright($assembly['copyright']);
            }

            if(isset($assembly['trademark']))
            {
                Logger::getLogger()->verbose(sprintf('Setting assembly trademark: %s', $assembly['trademark']));
                $project->getAssembly()->setTrademark($assembly['trademark']);
            }

            return $project;
        }

        /**
         * Applies build configuration from the legacy format to the project
         *
         * @param Project $project The project to apply the build configuration to
         * @param array $build The array of build configuration in the legacy format
         * @return Project The project with the applied build configuration
         */
        private function applyBuildConfiguration(Project $project, array $build): Project
        {
            if(isset($build['source_path']))
            {
                Logger::getLogger()->verbose(sprintf('Setting build source path: %s', $build['source_path']));
                $project->setSourcePath($this->convertMacros($build['source_path']));
            }

            if(isset($build['configurations']))
            {
                Logger::getLogger()->debug(sprintf('Converting %d build configurations', count($build['configurations'])));
                $buildConfigurations = $this->convertBuildConfigurations($build['configurations']);
                foreach($buildConfigurations as $buildName => $buildConfig)
                {
                    Logger::getLogger()->verbose(sprintf('Adding build configuration: %s', $buildName));
                    $project->addBuildConfiguration($buildConfig);

                    // This ensures we actually have a default configuration defined in the configuration
                    if(isset($build['default_configuration']) && $build['default_configuration'] === $buildName)
                    {
                        Logger::getLogger()->verbose(sprintf('Setting default build configuration: %s', $buildName));
                        $project->setDefaultBuild($buildName);
                    }

                }
            }

            return $project;
        }

        /**
         * Converts build configurations from the legacy format to the currently supported format
         *
         * @param array $buildConfigurations The array of build configurations in the legacy format
         * @return array The converted build configurations
         */
        private function convertBuildConfigurations(array $buildConfigurations): array
        {
            $results = [];

            foreach($buildConfigurations as $legacyConfiguration)
            {
                if(!isset($legacyConfiguration['name']))
                {
                    Logger::getLogger()->warning('Skipping build configuration without name');
                    // We skip this since there's no defined name for the build configuration
                    // Shouldn't even be valid anyway.
                    continue;
                }

                Logger::getLogger()->debug(sprintf('Converting build configuration: %s', $legacyConfiguration['name']));
                $convertedArray = [];
                $convertedArray['name'] = $legacyConfiguration['name'];

                if(isset($legacyConfiguration['output']))
                {
                    $convertedArray['output'] = $this->convertMacros($legacyConfiguration['output']);
                }

                if(isset($legacyConfiguration['build_type']))
                {
                    Logger::getLogger()->verbose(sprintf('Converting build type: %s', $legacyConfiguration['build_type']));
                    switch($legacyConfiguration['build_type'])
                    {
                        default:
                        case 'ncc':
                            $convertedArray['type'] = 'ncc';
                            break;

                        case 'executable':
                            $convertedArray['type'] = 'native';
                            Logger::getLogger()->debug('Converted executable build type to native');
                            break;
                    }
                }

                if(isset($legacyConfiguration['define_constants']) && is_array($legacyConfiguration['define_constants']))
                {
                    Logger::getLogger()->verbose(sprintf('Converting %d defined constants', count($legacyConfiguration['define_constants'])));
                    $convertedArray['definitions'] = array_map(function ($constantValue)
                    {
                        return $this->convertMacros($constantValue);
                    }, $legacyConfiguration['define_constants']);
                }

                // Note: Build configuration dependency configurations are no longer supported.

                $results[$convertedArray['name']] = new BuildConfiguration($convertedArray);
                Logger::getLogger()->debug(sprintf('Successfully converted build configuration: %s', $convertedArray['name']));
            }

            return $results;
        }


        /**
         * Converts execution policies from the legacy format to the currently supported format
         *
         * @param array $executionPolicies The array of execution policies in the legacy format
         * @return array The converted execution policies
         */
        private function convertExecutionPolicies(array $executionPolicies): array
        {
            $results = [];

            foreach($executionPolicies as $policy)
            {
                Logger::getLogger()->debug(sprintf('Converting execution policy: %s', $policy['name'] ?? 'unnamed'));
                $convertedResult = [];
                if(isset($policy['name']))
                {
                    $convertedResult['name'] = $policy['name'];
                }

                if(isset($policy['runner']))
                {
                    Logger::getLogger()->verbose(sprintf('Converting runner type: %s', $policy['runner']));
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
                    $convertedResult['working_directory'] = $this->convertMacros($policy['execute']['working_directory']);
                }

                // Only `tty` is an option, if it's set we must respect it
                // Otherwise, we need to assume for auto-mode
                if(isset($policy['execute']['tty']) && $policy['execute']['tty'] === true)
                {
                    Logger::getLogger()->verbose('Setting execution mode to TTY');
                    $convertedResult['mode'] = 'tty';
                }
                else
                {
                    $convertedResult['mode'] = 'auto';
                }

                // 'options' are arguments.
                if(isset($policy['execute']['options']) && is_array($policy['execute']['options']))
                {
                    Logger::getLogger()->verbose(sprintf('Setting %d execution arguments', count($policy['execute']['options'])));
                    $convertedResult['arguments'] = $policy['execute']['options'];
                }

                if(isset($policy['execute']['environment_variables']) && is_array($policy['execute']['environment_variables']))
                {
                    Logger::getLogger()->verbose(sprintf('Setting %d environment variables', count($policy['execute']['environment_variables'])));
                    $convertedResult['environment'] = $policy['execute']['environment_variables'];
                }

                if(isset($policy['execute']['target']))
                {
                    Logger::getLogger()->verbose(sprintf('Setting execution target: %s', $policy['execute']['target']));
                    $convertedResult['entry'] = $this->convertMacros($policy['execute']['target']);
                }

                $results[$convertedResult['name']] = new Project\ExecutionUnit($convertedResult);
                Logger::getLogger()->debug(sprintf('Successfully converted execution policy: %s', $convertedResult['name']));
            }

            return $results;
        }

        /**
         * Applies dependencies from the legacy format to the project
         *
         * @param Project $project The project to apply the dependencies to
         * @param array $dependencies The array of dependencies in the legacy format
         * @return Project The project with the applied dependencies
         */
        private function applyDependencies(Project $project, array $dependencies): Project
        {
            foreach($dependencies as $legacyDependency)
            {
                if(!isset($legacyDependency['source']))
                {
                    Logger::getLogger()->warning('Skipping dependency without source');
                    // We skip dependencies where no source is defined.
                    continue;
                }

                Logger::getLogger()->verbose(sprintf('Processing dependency: %s from %s', $legacyDependency['name'] ?? 'unnamed', $legacyDependency['source']));
                // Construct the package source from the original source, should be compatible.
                $packageSource = new PackageSource($legacyDependency['source']);

                // Set te version if one is set.
                if(isset($legacyDependency['version']))
                {
                    Logger::getLogger()->debug(sprintf('Setting dependency version: %s', $legacyDependency['version']));
                    $packageSource->setVersion($legacyDependency['version']);
                }

                $project->addDependency($legacyDependency['name'], $packageSource);
                Logger::getLogger()->debug(sprintf('Added dependency: %s', $legacyDependency['name']));
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
            Logger::getLogger()->debug(sprintf('Converting macros in: %s', substr($input, 0, 50) . (strlen($input) > 50 ? '...' : '')));
            return Utilities::replaceString($input, [
                '%ASSEMBLY.NAME%' => MacroVariable::ASSEMBLY_NAME->value,
                '%ASSEMBLY.PACKAGE%' => MacroVariable::ASSEMBLY_PACKAGE->value,
                '%ASSEMBLY.DESCRIPTION%' => MacroVariable::ASSEMBLY_DESCRIPTION->value,
                '%ASSEMBLY.COMPANY%' => MacroVariable::ASSEMBLY_ORGANIZATION->value,
                '%ASSEMBLY.PRODUCT%' => MacroVariable::ASSEMBLY_PRODUCT->value,
                '%ASSEMBLY.COPYRIGHT%' => MacroVariable::ASSEMBLY_COPYRIGHT->value,
                '%ASSEMBLY.TRADEMARK%' => MacroVariable::ASSEMBLY_TRADEMARK->value,
                '%ASSEMBLY.VERSION%' => MacroVariable::ASSEMBLY_VERSION->value,
                '%COMPILE_TIMESTAMP%' => MacroVariable::COMPILE_TIMESTAMP->value,
                '%d%' => MacroVariable::d->value,
                '%D%' => MacroVariable::D->value,
                '%j%' => MacroVariable::j->value,
                '%l%' => MacroVariable::l->value,
                '%N%' => MacroVariable::N->value,
                '%S%' => MacroVariable::S->value,
                '%w%' => MacroVariable::w->value,
                '%z%' => MacroVariable::z->value,
                '%W%' => MacroVariable::W->value,
                '%F%' => MacroVariable::F->value,
                '%m%' => MacroVariable::m->value,
                '%M%' => MacroVariable::M->value,
                '%n%' => MacroVariable::n->value,
                '%t%' => MacroVariable::t->value,
                '%L%' => MacroVariable::L->value,
                '%o%' => MacroVariable::o->value,
                '%Y%' => MacroVariable::Y->value,
                '%y%' => MacroVariable::y->value,
                '%a%' => MacroVariable::a->value,
                '%A%' => MacroVariable::A->value,
                '%B%' => MacroVariable::B->value,
                '%g%' => MacroVariable::g->value,
                '%G%' => MacroVariable::G->value,
                '%h%' => MacroVariable::h->value,
                '%H%' => MacroVariable::H->value,
                '%i%' => MacroVariable::i->value,
                '%s%' => MacroVariable::s->value,
                '%c%' => MacroVariable::c->value,
                '%r%' => MacroVariable::r->value,
                '%u%' => MacroVariable::u->value,
                '%DEFAULT_BUILD_CONFIGURATION%' => MacroVariable::DEFAULT_BUILD_CONFIGURATION->value,
                '%BUILD_OUTPUT_PATH%' => MacroVariable::BUILD_OUTPUT_PATH->value,
                '%CWD%' => MacroVariable::CURRENT_WORKING_DIRECTORY->value,
                '%PID%' => MacroVariable::PROCESS_ID->value,
            ]);
        }
    }