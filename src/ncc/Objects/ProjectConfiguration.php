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
    use ncc\Enums\Options\BuildConfigurationValues;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Objects\ProjectConfiguration\Build;
    use ncc\Objects\ProjectConfiguration\Build\BuildConfiguration;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\Objects\ProjectConfiguration\Installer;
    use ncc\Objects\ProjectConfiguration\Project;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class ProjectConfiguration implements BytecodeObjectInterface
    {
        /**
         * The project configuration
         *
         * @var Project
         */
        public $project;

        /**
         * Assembly information for the build output
         *
         * @var Assembly
         */
        public $assembly;

        /**
         * An array of execution policies
         *
         * @var ExecutionPolicy[]
         */
        public $execution_policies;

        /**
         * Execution Policies to execute by the NCC installer
         *
         * @var Installer|null
         */
        public $installer;

        /**
         * Build configuration for the project
         *
         * @var Build
         */
        public $build;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->project = new Project();
            $this->assembly = new Assembly();
            $this->execution_policies = [];
            $this->build = new Build();
        }

        /**
         * Validates the object for any errors
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!$this->project->validate($throw_exception))
            {
                return false;
            }

            if(!$this->assembly->validate($throw_exception))
            {
                return false;
            }

            if(!$this->build->validate($throw_exception))
            {
                return false;
            }


            try
            {
                $this->getRequiredExecutionPolicies(BuildConfigurationValues::ALL);
            }
            catch(Exception $e)
            {
                if($throw_exception)
                {
                    throw $e;
                }

                return false;
            }

            if($this->build->main !== null)
            {
                if($this->execution_policies === null || count($this->execution_policies) === 0)
                {
                    if($throw_exception)
                    {
                        throw new ConfigurationException(sprintf('Build configuration build.main uses an execution policy "%s" but no policies are defined', $this->build->main));
                    }

                    return false;
                }


                $found = false;
                foreach($this->execution_policies as $policy)
                {
                    if($policy->name === $this->build->main)
                    {
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                {
                    if($throw_exception)
                    {
                        throw new ConfigurationException(sprintf('Build configuration build.main points to a undefined execution policy "%s"', $this->build->main));
                    }
                    return false;
                }

                if($this->build->main === BuildConfigurationValues::ALL)
                {
                    if($throw_exception)
                    {
                        throw new ConfigurationException(sprintf('Build configuration build.main cannot be set to "%s"', BuildConfigurationValues::ALL));
                    }

                    return false;
                }
            }

            return true;
        }

        /**
         * @param string $name
         * @return ExecutionPolicy|null
         */
        private function getExecutionPolicy(string $name): ?ExecutionPolicy
        {
            foreach($this->execution_policies as $executionPolicy)
            {
                if($executionPolicy->name === $name)
                {
                    return $executionPolicy;
                }
            }

            return null;
        }

        /**
         * Runs a check on the project configuration and determines what policies are required
         *
         * @param string $build_configuration
         * @return array
         * @throws ConfigurationException
         */
        public function getRequiredExecutionPolicies(string $build_configuration=BuildConfigurationValues::DEFAULT): array
        {
            if($this->execution_policies === null || count($this->execution_policies) === 0)
            {
                return [];
            }

            $defined_polices = [];
            $required_policies = [];

            /** @var ExecutionPolicy $execution_policy */
            foreach($this->execution_policies as $execution_policy)
            {
                $defined_polices[] = $execution_policy->name;
                //$execution_policy->validate();
            }

            // Check the installer by batch
            if($this->installer !== null)
            {
                $array_rep = $this->installer->toArray();
                /** @var string[] $value */
                foreach($array_rep as $key => $value)
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

            if($this->build->pre_build !== null && count($this->build->post_build) > 0)
            {
                foreach($this->build->post_build as $unit)
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
            }

            if($this->build->post_build !== null && count($this->build->post_build) > 0)
            {
                foreach($this->build->post_build as $unit)
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
            }

            /** @noinspection DegradedSwitchInspection */
            switch($build_configuration)
            {
                case BuildConfigurationValues::ALL:
                    /** @var BuildConfiguration $configuration */
                    foreach($this->build->build_configurations as $configuration)
                    {
                        foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                        {
                            if(!in_array($policy, $required_policies, true))
                            {
                                $required_policies[] = $policy;
                            }
                        }
                    }
                    break;

                default:
                    $configuration = $this->build->getBuildConfiguration($build_configuration);
                    foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                    {
                        if(!in_array($policy, $required_policies, true))
                        {
                            $required_policies[] = $policy;
                        }
                    }
                    break;
            }

            foreach($required_policies as $policy)
            {
                $execution_policy = $this->getExecutionPolicy($policy);
                if($execution_policy?->exit_handlers !== null)
                {
                    if(
                        $execution_policy?->exit_handlers->success !== null &&
                        $execution_policy?->exit_handlers->success->run !== null
                    )
                    {
                        if(!in_array($execution_policy?->exit_handlers->success->run, $defined_polices, true))
                        {
                            throw new ConfigurationException('The execution policy \'' . $execution_policy?->name . '\' Success exit handler points to a undefined execution policy \'' . $execution_policy?->exit_handlers->success->run . '\'');
                        }

                        if(!in_array($execution_policy?->exit_handlers->success->run, $required_policies, true))
                        {
                            $required_policies[] = $execution_policy?->exit_handlers->success->run;
                        }
                    }

                    if($execution_policy?->exit_handlers->warning !== null && $execution_policy?->exit_handlers->warning->run !== null)
                    {
                        if(!in_array($execution_policy?->exit_handlers->warning->run, $defined_polices, true))
                        {
                            throw new ConfigurationException('The execution policy \'' . $execution_policy?->name . '\' Warning exit handler points to a undefined execution policy \'' . $execution_policy?->exit_handlers->warning->run . '\'');
                        }

                        if(!in_array($execution_policy?->exit_handlers->warning->run, $required_policies, true))
                        {
                            $required_policies[] = $execution_policy?->exit_handlers->warning->run;
                        }
                    }

                    if($execution_policy?->exit_handlers->error !== null && $execution_policy?->exit_handlers->error->run !== null)
                    {
                        if(!in_array($execution_policy?->exit_handlers->error->run, $defined_polices, true))
                        {
                            throw new ConfigurationException('The execution policy \'' . $execution_policy?->name . '\' Error exit handler points to a undefined execution policy \'' . $execution_policy?->exit_handlers->error->run . '\'');
                        }

                        if(!in_array($execution_policy?->exit_handlers->error->run, $required_policies, true))
                        {
                            $required_policies[] = $execution_policy?->exit_handlers->error->run;
                        }
                    }
                }

            }

            return $required_policies;
        }

        /**
         * Writes a json representation of the object to a file
         *
         * @param string $path
         * @param bool $bytecode
         * @return void
         * @throws MalformedJsonException
         * @noinspection PhpUnused
         */
        public function toFile(string $path, bool $bytecode=false): void
        {
            if(!$bytecode)
            {
                Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY | Functions::PRETTY | Functions::ESCAPE_UNICODE);
                return;
            }

            Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY);
        }

        /**
         * Loads the object from a file representation
         *
         * @param string $path
         * @return ProjectConfiguration
         * @throws IOException
         * @throws MalformedJsonException
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

            if ($configuration->pre_build !== null && count($configuration->pre_build) > 0)
            {
                foreach ($configuration->pre_build as $unit)
                {
                    if (!in_array($unit, $defined_polices, true))
                    {
                        throw new ConfigurationException(sprintf("The property 'pre_build' in the build configuration '%s' calls for an undefined execution policy '%s'", $configuration->name, $unit));
                    }

                    $required_policies[] = $unit;
                }
            }

            if ($configuration->post_build !== null && count($configuration->post_build) > 0)
            {
                foreach ($configuration->post_build as $unit)
                {
                    if (!in_array($unit, $defined_polices, true))
                    {
                        throw new ConfigurationException(sprintf("The property 'post_build' in the build configuration '%s' calls for an undefined execution policy '%s'", $configuration->name, $unit));
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
            $execution_policies = null;
            if($this->execution_policies !== null)
            {
                $execution_policies = [];
                foreach($this->execution_policies as $executionPolicy)
                {
                    $execution_policies[$executionPolicy->name] = $executionPolicy->toArray($bytecode);
                }
            }

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

            if($execution_policies !== null && count($execution_policies) > 0)
            {
                $results[($bytecode ? Functions::cbc('execution_policies') : 'execution_policies')] = $execution_policies;
            }

            return $results;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): ProjectConfiguration
        {
            $object = new self();

            $object->project = Project::fromArray(Functions::array_bc($data, 'project'));
            $object->assembly = Assembly::fromArray(Functions::array_bc($data, 'assembly'));
            $object->build = Build::fromArray(Functions::array_bc($data, 'build'));
            $object->installer = Installer::fromArray(Functions::array_bc($data, 'installer'));

            $execution_policies = Functions::array_bc($data, 'execution_policies');
            if(!is_null($execution_policies))
            {
                $object->execution_policies = [];
                foreach(Functions::array_bc($data, 'execution_policies') as $execution_policy)
                {
                    $object->execution_policies[] = ExecutionPolicy::fromArray($execution_policy);
                }
            }

            return $object;
        }
    }