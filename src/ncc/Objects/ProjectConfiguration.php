<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use Exception;
    use InvalidArgumentException;
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Interfaces\ValidatableObjectInterface;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Build;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Objects\ProjectConfiguration\Project;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class ProjectConfiguration implements BytecodeObjectInterface, ValidatableObjectInterface
    {
        /**
         * The project configuration
         *
         * @var Project
         */
        private $project;

        /**
         * Assembly information for the build output
         *
         * @var Assembly
         */
        private $assembly;

        /**
         * Build configuration for the project
         *
         * @var Build
         */
        private $build;

        /**
         * An array of execution policies
         *
         * @var ExecutionPolicy[]
         */
        private $execution_policies;

        /**
         * Execution Policies to execute by the NCC installer
         *
         * @var Installer|null
         */
        private $installer;

        /**
         * Public Constructor
         */
        public function __construct(Project $project, Assembly $assembly, Build $build)
        {
            $this->project = $project;
            $this->assembly = $assembly;
            $this->build = $build;
            $this->execution_policies = [];
        }

        /**
         * @return Project
         */
        public function getProject(): Project
        {
            return $this->project;
        }

        /**
         * @param Project $project
         */
        public function setProject(Project $project): void
        {
            $this->project = $project;
        }

        /**
         * @return Assembly
         */
        public function getAssembly(): Assembly
        {
            return $this->assembly;
        }

        /**
         * @param Assembly $assembly
         */
        public function setAssembly(Assembly $assembly): void
        {
            $this->assembly = $assembly;
        }

        /**
         * @return array|ExecutionPolicy[]
         */
        public function getExecutionPolicies(): array
        {
            return $this->execution_policies;
        }

        /**
         * @param array|ExecutionPolicy[] $execution_policies
         */
        public function setExecutionPolicies(array $execution_policies): void
        {
            $this->execution_policies = $execution_policies;
        }

        /**
         * @param string $name
         * @return ExecutionPolicy
         */
        public function getExecutionPolicy(string $name): ExecutionPolicy
        {
            foreach($this->execution_policies as $executionPolicy)
            {
                if($executionPolicy->getName() === $name)
                {
                    return $executionPolicy;
                }
            }

            throw new InvalidArgumentException('Execution policy \'' . $name . '\' does not exist');
        }

        /**
         * @param ExecutionPolicy $policy
         * @param bool $overwrite
         * @return void
         * @throws ConfigurationException
         */
        public function addExecutionPolicy(ExecutionPolicy $policy, bool $overwrite=true): void
        {
            foreach($this->execution_policies as $execution_policy)
            {
                if($execution_policy->getName() === $policy->getName())
                {
                    if($overwrite)
                    {
                        $this->removeExecutionPolicy($execution_policy->getName());
                    }
                    else
                    {
                        throw new ConfigurationException('An execution policy with the name \'' . $policy->getName() . '\' already exists');
                    }
                }
            }

            $this->execution_policies[] = $policy;
        }

        /**
         * @param string $name
         * @return void
         */
        public function removeExecutionPolicy(string $name): void
        {
            foreach($this->execution_policies as $key => $executionPolicy)
            {
                if($executionPolicy->getName() === $name)
                {
                    unset($this->execution_policies[$key]);
                    return;
                }
            }
        }

        /**
         * Runs a check on the project configuration and determines what policies are required
         *
         * @param string $build_configuration
         * @return string[]
         * @throws ConfigurationException
         */
        public function getRequiredExecutionPolicies(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            if(count($this->execution_policies) === 0)
            {
                return [];
            }

            $defined_polices = [];
            $required_policies = [];

            /** @var ExecutionPolicy $execution_policy */
            foreach($this->execution_policies as $execution_policy)
            {
                $defined_polices[] = $execution_policy->getName();
            }

            // Check the installer by batch
            if($this->installer !== null)
            {
                /** @var string[] $value */
                foreach($this->installer->toArray() as $key => $value)
                {
                    if($value === null || count($value) === 0)
                    {
                        continue;
                    }

                    foreach($value as $unit)
                    {
                        if(!in_array($unit, $defined_polices, true))
                        {
                            throw new ConfigurationException('The property \'' . $key . '\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                        }

                        if(!in_array($unit, $required_policies, true))
                        {
                            $required_policies[] = $unit;
                        }
                    }
                }
            }

            foreach($this->build->getPostBuild() as $unit)
            {
                if(!in_array($unit, $defined_polices, true))
                {
                    throw new ConfigurationException('The property \'build.pre_build\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                }

                if(!in_array($unit, $required_policies, true))
                {
                    $required_policies[] = $unit;
                }
            }

            foreach($this->build->getPreBuild() as $unit)
            {
                if(!in_array($unit, $defined_polices, true))
                {
                    throw new ConfigurationException('The property \'build.pre_build\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                }

                if(!in_array($unit, $required_policies, true))
                {
                    $required_policies[] = $unit;
                }
            }

            if($this->build->getMain() !== null)
            {
                if(!in_array($this->build->getMain(), $defined_polices, true))
                {
                    throw new ConfigurationException('The property \'build.main\' in the project configuration calls for an undefined execution policy \'' . $this->build->getMain() . '\'');
                }

                if(!in_array($this->build->getMain(), $required_policies, true))
                {
                    $required_policies[] = $this->build->getMain();
                }
            }

            if($build_configuration === BuildConfigurationValues::ALL)
            {
                /** @var BuildConfiguration $configuration */
                foreach($this->build->getBuildConfigurations() as $configuration)
                {
                    foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                    {
                        if(!in_array($policy, $required_policies, true))
                        {
                            $required_policies[] = $policy;
                        }
                    }
                }
            }
            else
            {
                $configuration = $this->build->getBuildConfiguration($build_configuration);
                foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                {
                    if(!in_array($policy, $required_policies, true))
                    {
                        $required_policies[] = $policy;
                    }
                }
            }

            foreach($required_policies as $policy)
            {
                $execution_policy = $this->getExecutionPolicy($policy);

                if($execution_policy?->getExitHandlers()?->getSuccess()?->getRun() !== null)
                {
                    if(!in_array($execution_policy?->getExitHandlers()?->getSuccess()?->getRun(), $defined_polices, true))
                    {
                        throw new ConfigurationException('The execution policy \'' . $execution_policy?->getName() . '\' Success exit handler points to a undefined execution policy \'' . $execution_policy?->getExitHandlers()?->getSuccess()?->getRun() . '\'');
                    }

                    if(!in_array($execution_policy?->getExitHandlers()?->getSuccess()?->getRun(), $required_policies, true))
                    {
                        $required_policies[] = $execution_policy?->getExitHandlers()?->getSuccess()?->getRun();
                    }
                }

                if($execution_policy?->getExitHandlers()?->getWarning()?->getRun() !== null)
                {
                    if(!in_array($execution_policy?->getExitHandlers()?->getWarning()?->getRun(), $defined_polices, true))
                    {
                        throw new ConfigurationException('The execution policy \'' . $execution_policy?->getName() . '\' Warning exit handler points to a undefined execution policy \'' . $execution_policy?->getExitHandlers()?->getWarning()?->getRun() . '\'');
                    }

                    if(!in_array($execution_policy?->getExitHandlers()?->getWarning()?->getRun(), $required_policies, true))
                    {
                        $required_policies[] = $execution_policy?->getExitHandlers()?->getWarning()?->getRun();
                    }
                }

                if($execution_policy?->getExitHandlers()?->getError()?->getRun() !== null)
                {
                    if(!in_array($execution_policy?->getExitHandlers()?->getError()?->getRun(), $defined_polices, true))
                    {
                        throw new ConfigurationException('The execution policy \'' . $execution_policy?->getName() . '\' Error exit handler points to a undefined execution policy \'' . $execution_policy?->getExitHandlers()?->getError()?->getRun() . '\'');
                    }

                    if(!in_array($execution_policy?->getExitHandlers()?->getError()?->getRun(), $required_policies, true))
                    {
                        $required_policies[] = $execution_policy?->getExitHandlers()?->getError()?->getRun();
                    }
                }
            }

            return $required_policies;
        }

        /**
         * @return Installer|null
         */
        public function getInstaller(): ?Installer
        {
            return $this->installer;
        }

        /**
         * @param Installer|null $installer
         */
        public function setInstaller(?Installer $installer): void
        {
            $this->installer = $installer;
        }

        /**
         * @return Build
         */
        public function getBuild(): Build
        {
            return $this->build;
        }

        /**
         * @param Build $build
         */
        public function setBuild(Build $build): void
        {
            $this->build = $build;
        }

        /**
         * @inheritDoc
         */
        public function validate(): void
        {
            $this->project->validate();
            $this->assembly->validate();
            $this->build->validate();

            if($this->build->getMain() !== null)
            {
                if($this->execution_policies === null || count($this->execution_policies) === 0)
                {
                    throw new ConfigurationException(sprintf('Build configuration build.main uses an execution policy "%s" but no policies are defined', $this->build->getMain()));
                }


                $found = false;
                foreach($this->execution_policies as $policy)
                {
                    if($policy->getName() === $this->build->getMain())
                    {
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                {
                    throw new ConfigurationException(sprintf('Build configuration build.main points to a undefined execution policy "%s"', $this->build->getMain()));
                }

                if($this->build->getMain() === BuildConfigurationValues::ALL)
                {
                    throw new ConfigurationException(sprintf('Build configuration build.main cannot be set to "%s"', BuildConfigurationValues::ALL));
                }
            }
        }

        /**
         * Writes a json representation of the object to a file
         *
         * @param string $path
         * @param bool $bytecode
         * @return void
         * @throws IOException
         */
        public function toFile(string $path, bool $bytecode=false): void
        {
            if(!$bytecode)
            {
                Functions::encodeJsonFile(
                    Functions::cleanArray($this->toArray($bytecode)), $path,
                    Functions::FORCE_ARRAY | Functions::PRETTY | Functions::ESCAPE_UNICODE
                );
                return;
            }

            Functions::encodeJsonFile(Functions::cleanArray($this->toArray($bytecode)), $path, Functions::FORCE_ARRAY);
        }

        /**
         * Loads the object from a file representation
         *
         * @param string $path
         * @return ProjectConfiguration
         * @throws ConfigurationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public static function fromFile(string $path): ProjectConfiguration
        {
            return self::fromArray(Functions::loadJsonFile($path, Functions::FORCE_ARRAY));
        }

        /**
         * @param BuildConfiguration $configuration
         * @param array $defined_polices
         * @return array
         * @throws ConfigurationException
         */
        private function processBuildPolicies(BuildConfiguration $configuration, array $defined_polices): array
        {
            $required_policies = [];

            if (count($configuration->getPreBuild()) > 0)
            {
                foreach ($configuration->getPreBuild() as $unit)
                {
                    if (!in_array($unit, $defined_polices, true))
                    {
                        throw new ConfigurationException(sprintf("The property 'pre_build' in the build configuration '%s' calls for an undefined execution policy '%s'", $configuration->getName(), $unit));
                    }

                    $required_policies[] = $unit;
                }
            }

            if (count($configuration->getPostBuild()) > 0)
            {
                foreach ($configuration->getPostBuild() as $unit)
                {
                    if (!in_array($unit, $defined_polices, true))
                    {
                        throw new ConfigurationException(sprintf("The property 'post_build' in the build configuration '%s' calls for an undefined execution policy '%s'", $configuration->getName(), $unit));
                    }

                    $required_policies[] = $unit;
                }
            }

            return $required_policies;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode=false): array
        {
            $results = [];

            if($this->project !== null)
            {
                $results[($bytecode ? Functions::cbc('project') : 'project')] = $this->project->toArray($bytecode);
            }

            if($this->assembly !== null)
            {
                $results[($bytecode ? Functions::cbc('assembly') : 'assembly')] = $this->assembly->toArray($bytecode);
            }

            if($this->build !== null)
            {
                $results[($bytecode ? Functions::cbc('build') : 'build')] = $this->build->toArray($bytecode);
            }

            if($this->installer !== null)
            {
                $results[($bytecode ? Functions::cbc('installer') : 'installer')] = $this->installer->toArray($bytecode);
            }

            if(count($this->execution_policies) > 0)
            {
                $execution_policies = [];

                foreach($this->execution_policies as $executionPolicy)
                {
                    $execution_policies[] = $executionPolicy->toArray($bytecode);
                }

                $results[($bytecode ? Functions::cbc('execution_policies') : 'execution_policies')] = $execution_policies;
            }

            return $results;
        }

        /**
         * @inheritDoc
         * @param array $data
         * @return ProjectConfiguration
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public static function fromArray(array $data): ProjectConfiguration
        {
            $project = Functions::array_bc($data, 'project');
            if($project !== null)
            {
                $project = Project::fromArray($project);
            }
            else
            {
                throw new ConfigurationException('The project configuration is missing the required property "project" in the root of the configuration');
            }

            $assembly = Functions::array_bc($data, 'assembly');
            if($assembly !== null)
            {
                $assembly = Assembly::fromArray($assembly);
            }
            else
            {
                throw new ConfigurationException('The project configuration is missing the required property "assembly" in the root of the configuration');
            }

            $build = Functions::array_bc($data, 'build');
            if($build !== null)
            {
                $build = Build::fromArray($build);
            }
            else
            {
                throw new ConfigurationException('The project configuration is missing the required property "build" in the root of the configuration');
            }

            $object = new self($project, $assembly, $build);

            $object->installer = Functions::array_bc($data, 'installer');
            if($object->installer !== null)
            {
                $object->installer = Installer::fromArray($object->installer);
            }

            $execution_policies = Functions::array_bc($data, 'execution_policies');
            if(!is_null($execution_policies))
            {
                $object->execution_policies = array_map(static function($policy) {
                    return ExecutionPolicy::fromArray($policy);
                }, $execution_policies);
            }

            return $object;
        }
    }