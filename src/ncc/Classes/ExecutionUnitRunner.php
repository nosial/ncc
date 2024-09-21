<?php

    /** @noinspection PhpMissingFieldTypeInspection */

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

    namespace ncc\Classes;

    use Exception;
    use ncc\Classes\BashExtension\BashRunner;
    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Enums\Runners;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\IO;

    class ExecutionUnitRunner
    {
        /**
         * Constructs and returns a process object based off the Execution Unit
         *
         * @param ExecutionUnit $unit
         * @param array $args
         * @return Process
         * @throws NotSupportedException
         */
        public static function constructProcess(ExecutionUnit $unit, array $args=[]): Process
        {
            $bin = match($unit->getExecutionPolicy()->getRunner())
            {
                Runners::PHP->value => (new ExecutableFinder())->find('php'),
                Runners::BASH->value => (new ExecutableFinder())->find('bash'),
                Runners::PYTHON->value => (new ExecutableFinder())->find('python'),
                Runners::LUA->value => (new ExecutableFinder())->find('lua'),
                Runners::PERL->value => (new ExecutableFinder())->find('perl'),

                default => throw new NotSupportedException(sprintf('The execution policy %s is not supported because it uses the %s runner', $unit->getExecutionPolicy()->getName(), $unit->getExecutionPolicy()->getRunner()))
            };

            $process = new Process(array_merge([$bin], $args, $unit->getExecutionPolicy()->getExecute()->getOptions()));
            $process->setWorkingDirectory(ConstantCompiler::compileRuntimeConstants($unit->getExecutionPolicy()->getExecute()->getWorkingDirectory()));
            $process->setEnv($unit->getExecutionPolicy()->getExecute()->getEnvironmentVariables());

            if($unit->getExecutionPolicy()->getExecute()->isTty())
            {
                $process->setTty(true);
            }

            if($unit->getExecutionPolicy()->getExecute()->getTimeout() !== null)
            {
                $process->setTimeout($unit->getExecutionPolicy()->getExecute()->getTimeout());
            }
            else
            {
                $process->setTimeout(null);
            }

            if($unit->getExecutionPolicy()->getExecute()->getIdleTimeout() !== null)
            {
                $process->setIdleTimeout($unit->getExecutionPolicy()->getExecute()->getIdleTimeout());
            }
            else
            {
                $process->setIdleTimeout(null);
            }

            return $process;
        }

        /**
         * Executes a ExecutionUnit locally on the system
         *
         * @param string $package_path
         * @param string $policy_name
         * @param array $args
         * @return int
         * @throws IOException
         * @throws OperationException
         */
        public static function executeFromSystem(string $package_path, string $policy_name, array $args=[]): int
        {
            $unit_path = $package_path . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $policy_name . '.unit';

            if(!is_file($unit_path))
            {
                throw new IOException(sprintf('The execution policy %s does not exist in the package %s (%s)', $policy_name, $package_path, $unit_path));
            }

            try
            {
                $execution_unit = ExecutionUnit::fromArray(ZiProto::decode(IO::fread($unit_path)));
                return match ($execution_unit->getExecutionPolicy()->getRunner())
                {
                    Runners::PHP->value => PhpRunner::executeUnit($execution_unit, $args),
                    Runners::BASH->value => BashRunner::executeUnit($execution_unit, $args),
                    default => throw new NotSupportedException(sprintf('The execution policy %s is not supported because it uses the %s runner', $execution_unit->getExecutionPolicy()->getName(), $execution_unit->getExecutionPolicy()->getRunner())),
                };
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('There was an error executing the execution policy %s: %s', $policy_name, $e->getMessage()), $e);
            }
        }

        /**
         * Executes the execution policy directly from a package (if supported) and returns the exit code
         *
         * @param PackageReader $package_reader
         * @param string $policy_name
         * @param array $args
         * @return int
         * @throws ConfigurationException
         * @throws OperationException
         */
        public static function executeFromPackage(PackageReader $package_reader, string $policy_name, array $args=[]): int
        {
            $execution_unit = $package_reader->getExecutionUnit($policy_name);

            try
            {
                return match ($execution_unit->getExecutionPolicy()->getRunner())
                {
                    Runners::PHP->value => PhpRunner::executeUnit($execution_unit, $args, false),
                    Runners::BASH->value => BashRunner::executeUnit($execution_unit, $args),
                    default => throw new NotSupportedException(sprintf('The execution policy %s is not supported because it uses the %s runner', $execution_unit->getExecutionPolicy()->getName(), $execution_unit->getExecutionPolicy()->getRunner())),
                };
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('There was an error executing the execution policy from a package %s: %s', $policy_name, $e->getMessage()), $e);
            }
        }
    }