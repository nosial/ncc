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
    use ncc\Abstracts\Options\BuildConfigurationValues;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidBuildConfigurationException;
    use ncc\Exceptions\InvalidConstantNameException;
    use ncc\Exceptions\InvalidProjectBuildConfiguration;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\InvalidPropertyValueException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UndefinedExecutionPolicyException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedExtensionVersionException;
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
    class ProjectConfiguration
    {
        /**
         * The project configuration
         *
         * @var Project
         */
        public $Project;

        /**
         * Assembly information for the build output
         *
         * @var Assembly
         */
        public $Assembly;

        /**
         * An array of execution policies
         *
         * @var ExecutionPolicy[]
         */
        public $ExecutionPolicies;

        /**
         * Execution Policies to execute by the NCC installer
         *
         * @var Installer|null
         */
        public $Installer;

        /**
         * Build configuration for the project
         *
         * @var Build
         */
        public $Build;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            $this->Project = new Project();
            $this->Assembly = new Assembly();
            $this->ExecutionPolicies = [];
            $this->Build = new Build();
        }

        /**
         * Validates the object for any errors
         *
         * @param bool $throw_exception
         * @return bool
         * @throws BuildConfigurationNotFoundException
         * @throws InvalidConstantNameException
         * @throws InvalidProjectBuildConfiguration
         * @throws InvalidProjectConfigurationException
         * @throws InvalidPropertyValueException
         * @throws RuntimeException
         * @throws UndefinedExecutionPolicyException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedExtensionVersionException
         * @throws InvalidBuildConfigurationException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if(!$this->Project->validate($throw_exception))
                return false;

            if(!$this->Assembly->validate($throw_exception))
                return false;

            if(!$this->Build->validate($throw_exception))
                return false;

            try
            {
                $this->getRequiredExecutionPolicies(BuildConfigurationValues::AllConfigurations);
            }
            catch(Exception $e)
            {
                if($throw_exception)
                    throw $e;
                return false;
            }

            if($this->Build->Main !== null)
            {
                if($this->ExecutionPolicies == null || count($this->ExecutionPolicies) == 0)
                {
                    if($throw_exception)
                        throw new UndefinedExecutionPolicyException(sprintf('Build configuration build.main uses an execution policy "%s" but no policies are defined', $this->Build->Main));
                    return false;
                }


                $found = false;
                foreach($this->ExecutionPolicies as $policy)
                {
                    if($policy->Name == $this->Build->Main)
                    {
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                {
                    if($throw_exception)
                        throw new UndefinedExecutionPolicyException(sprintf('Build configuration build.main points to a undefined execution policy "%s"', $this->Build->Main));
                    return false;
                }

                if($this->Build->Main == BuildConfigurationValues::AllConfigurations)
                {
                    if($throw_exception)
                        throw new InvalidBuildConfigurationException(sprintf('Build configuration build.main cannot be set to "%s"', BuildConfigurationValues::AllConfigurations));
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
            foreach($this->ExecutionPolicies as $executionPolicy)
            {
                if($executionPolicy->Name == $name)
                    return $executionPolicy;
            }

            return null;
        }

        /**
         * Runs a check on the project configuration and determines what policies are required
         *
         * @param string $build_configuration
         * @return array
         * @throws BuildConfigurationNotFoundException
         * @throws UndefinedExecutionPolicyException
         */
        public function getRequiredExecutionPolicies(string $build_configuration=BuildConfigurationValues::DefaultConfiguration): array
        {
            if($this->ExecutionPolicies == null || count($this->ExecutionPolicies) == 0)
                return [];

            $defined_polices = [];
            $required_policies = [];
            /** @var ExecutionPolicy $execution_policy */
            foreach($this->ExecutionPolicies as $execution_policy)
            {
                $defined_polices[] = $execution_policy->Name;
                //$execution_policy->validate();
            }

            // Check the installer by batch
            if($this->Installer !== null)
            {
                $array_rep = $this->Installer->toArray();
                /** @var string[] $value */
                foreach($array_rep as $key => $value)
                {
                    if($value == null || count($value) == 0)
                        continue;

                    foreach($value as $unit)
                    {
                        if(!in_array($unit, $defined_polices))
                            throw new UndefinedExecutionPolicyException('The property \'' . $key . '\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                        if(!in_array($unit, $required_policies))
                            $required_policies[] = $unit;
                    }
                }
            }

            if($this->Build->PreBuild !== null && count($this->Build->PostBuild) > 0)
            {
                foreach($this->Build->PostBuild as $unit)
                {
                    if(!in_array($unit, $defined_polices))
                        throw new UndefinedExecutionPolicyException('The property \'build.pre_build\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                    if(!in_array($unit, $required_policies))
                        $required_policies[] = $unit;
                }
            }

            if($this->Build->PostBuild !== null && count($this->Build->PostBuild) > 0)
            {
                foreach($this->Build->PostBuild as $unit)
                {
                    if(!in_array($unit, $defined_polices))
                        throw new UndefinedExecutionPolicyException('The property \'build.pre_build\' in the project configuration calls for an undefined execution policy \'' . $unit . '\'');
                    if(!in_array($unit, $required_policies))
                        $required_policies[] = $unit;
                }
            }

            switch($build_configuration)
            {
                case BuildConfigurationValues::AllConfigurations:
                    /** @var BuildConfiguration $configuration */
                    foreach($this->Build->Configurations as $configuration)
                    {
                        foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                        {
                            if(!in_array($policy, $required_policies))
                                $required_policies[] = $policy;
                        }
                    }
                    break;

                default:
                    $configuration = $this->Build->getBuildConfiguration($build_configuration);
                    foreach($this->processBuildPolicies($configuration, $defined_polices) as $policy)
                    {
                        if(!in_array($policy, $required_policies))
                            $required_policies[] = $policy;
                    }
                    break;
            }

            foreach($required_policies as $policy)
            {
                $execution_policy = $this->getExecutionPolicy($policy);
                if($execution_policy->ExitHandlers !== null)
                {
                    if(
                        $execution_policy->ExitHandlers->Success !== null &&
                        $execution_policy->ExitHandlers->Success->Run !== null
                    )
                    {
                        if(!in_array($execution_policy->ExitHandlers->Success->Run, $defined_polices))
                            throw new UndefinedExecutionPolicyException('The execution policy \'' . $execution_policy->Name . '\' Success exit handler points to a undefined execution policy \'' . $execution_policy->ExitHandlers->Success->Run . '\'');

                        if(!in_array($execution_policy->ExitHandlers->Success->Run, $required_policies))
                            $required_policies[] = $execution_policy->ExitHandlers->Success->Run;
                    }

                    if(
                        $execution_policy->ExitHandlers->Warning !== null &&
                        $execution_policy->ExitHandlers->Warning->Run !== null
                    )
                    {
                        if(!in_array($execution_policy->ExitHandlers->Warning->Run, $defined_polices))
                            throw new UndefinedExecutionPolicyException('The execution policy \'' . $execution_policy->Name . '\' Warning exit handler points to a undefined execution policy \'' . $execution_policy->ExitHandlers->Warning->Run . '\'');

                        if(!in_array($execution_policy->ExitHandlers->Warning->Run, $required_policies))
                            $required_policies[] = $execution_policy->ExitHandlers->Warning->Run;
                    }

                    if(
                        $execution_policy->ExitHandlers->Error !== null &&
                        $execution_policy->ExitHandlers->Error->Run !== null
                    )
                    {
                        if(!in_array($execution_policy->ExitHandlers->Error->Run, $defined_polices))
                            throw new UndefinedExecutionPolicyException('The execution policy \'' . $execution_policy->Name . '\' Error exit handler points to a undefined execution policy \'' . $execution_policy->ExitHandlers->Error->Run . '\'');

                        if(!in_array($execution_policy->ExitHandlers->Error->Run, $required_policies))
                            $required_policies[] = $execution_policy->ExitHandlers->Error->Run;
                    }
                }

            }

            return $required_policies;
        }

        /**
         * Returns an array representation of the object
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            $execution_policies = null;
            if($this->ExecutionPolicies !== null)
            {
                $execution_policies = [];
                foreach($this->ExecutionPolicies as $executionPolicy)
                {
                    $execution_policies[$executionPolicy->Name] = $executionPolicy->toArray($bytecode);
                }
            }

            $results = [];
            if($this->Project !== null)
                $results[($bytecode ? Functions::cbc('project') : 'project')] = $this->Project->toArray($bytecode);
            if($this->Assembly !== null)
                $results['assembly'] = $this->Assembly->toArray($bytecode);
            if($this->Build !== null)
                $results[($bytecode ? Functions::cbc('build') : 'build')] = $this->Build->toArray($bytecode);
            if($this->Installer !== null)
                $results[($bytecode ? Functions::cbc('installer') : 'installer')] = $this->Installer->toArray($bytecode);
            if($execution_policies !== null && count($execution_policies) > 0)
                $results[($bytecode ? Functions::cbc('execution_policies') : 'execution_policies')] = $execution_policies;
            return $results;
        }

        /**
         * Writes a json representation of the object to a file
         *
         * @param string $path
         * @param bool $bytecode
         * @return void
         * @throws MalformedJsonException
         * @noinspection PhpMissingReturnTypeInspection
         * @noinspection PhpUnused
         */
        public function toFile(string $path, bool $bytecode=false)
        {
            if(!$bytecode)
            {
                Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY | Functions::PRETTY | Functions::ESCAPE_UNICODE);
                return;
            }

            Functions::encodeJsonFile($this->toArray($bytecode), $path, Functions::FORCE_ARRAY);
        }

        /**
         * Constructs the object from an array representation
         *
         * @param array $data
         * @return ProjectConfiguration
         */
        public static function fromArray(array $data): ProjectConfiguration
        {
            $ProjectConfigurationObject = new ProjectConfiguration();

            if(isset($data['project']))
                $ProjectConfigurationObject->Project = Project::fromArray($data['project']);
            if(isset($data['assembly']))
                $ProjectConfigurationObject->Assembly = Assembly::fromArray($data['assembly']);
            if(isset($data['build']))
                $ProjectConfigurationObject->Build = Build::fromArray($data['build']);
            if(isset($data['installer']))
                $ProjectConfigurationObject->Installer = Installer::fromArray($data['installer']);
            if(isset($data['execution_policies']))
            {
                $ProjectConfigurationObject->ExecutionPolicies = [];
                foreach($data['execution_policies'] as $execution_policy)
                {
                    $ProjectConfigurationObject->ExecutionPolicies[] = ExecutionPolicy::fromArray($execution_policy);
                }
            }

            return $ProjectConfigurationObject;
        }

        /**
         * Loads the object from a file representation
         *
         * @param string $path
         * @return ProjectConfiguration
         * @throws FileNotFoundException
         * @throws MalformedJsonException
         * @throws AccessDeniedException
         * @throws IOException
         * @noinspection PhpUnused
         */
        public static function fromFile(string $path): ProjectConfiguration
        {
            return ProjectConfiguration::fromArray(Functions::loadJsonFile($path, Functions::FORCE_ARRAY));
        }

        /**
         * @param BuildConfiguration $configuration
         * @param array $defined_polices
         * @return array
         * @throws UndefinedExecutionPolicyException
         */
        private function processBuildPolicies(BuildConfiguration $configuration, array $defined_polices): array
        {
            $required_policies = [];

            if ($configuration->PreBuild !== null && count($configuration->PreBuild) > 0)
            {
                foreach ($configuration->PreBuild as $unit)
                {
                    if (!in_array($unit, $defined_polices))
                        throw new UndefinedExecutionPolicyException('The property \'pre_build\' in the build configuration \'' . $configuration->Name . '\' calls for an undefined execution policy \'' . $unit . '\'');
                    $required_policies[] = $unit;
                }
            }

            if ($configuration->PostBuild !== null && count($configuration->PostBuild) > 0)
            {
                foreach ($configuration->PostBuild as $unit)
                {
                    if (!in_array($unit, $defined_polices))
                        throw new UndefinedExecutionPolicyException('The property \'pre_build\' in the build configuration \'' . $configuration->Name . '\' calls for an undefined execution policy \'' . $unit . '\'');
                    $required_policies[] = $unit;
                }
            }

            return $required_policies;
        }
    }