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

    namespace ncc\Classes;

    use Exception;
    use ncc\Enums\ExecutionMode;
    use ncc\Enums\ExecutionUnitType;
    use ncc\Enums\MacroVariable;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\Process\ExecutableFinder;
    use ncc\Libraries\Process\Process;
    use ncc\Objects\Project\ExecutionUnit;
    use RuntimeException;

    class ExecutionUnitRunner
    {
        /**
         * Executes the unit directly using the project's source files
         *
         * @param string $projectPath The project path where the configuration is located.
         * @param ExecutionUnit $unit The execution unit to run.
         * @return int The exit code of the executed unit.
         */
        public static function fromSource(string $projectPath, ExecutionUnit $unit): int
        {
            Logger::getLogger()->debug(sprintf('Executing unit from source: %s (type: %s)', $unit->getName(), $unit->getType()->value));
            
            // Check if all the required files are available
            foreach($unit->getRequiredFiles() as $requiredFile)
            {
                Logger::getLogger()->verbose(sprintf('Checking required file: %s', $requiredFile));
                if(!IO::exists($projectPath . DIRECTORY_SEPARATOR . $requiredFile))
                {
                    throw new OperationException(sprintf('The execution unit %s is missing the required file %s', $unit->getName(), $projectPath . DIRECTORY_SEPARATOR . $requiredFile));
                }
            }

            // If we're executing a PHP unit, we verify if the PHP file exists in the source before executing it.
            if($unit->getType() === ExecutionUnitType::PHP)
            {
                $entryPointPath = $projectPath . DIRECTORY_SEPARATOR . $unit->getEntryPoint();
                Logger::getLogger()->debug(sprintf('PHP execution unit entry point: %s', $entryPointPath));
                
                if(!IO::exists($entryPointPath))
                {
                    throw new OperationException(sprintf('The execution unit %s entrypoint %s does not exist', $unit->getName(), $entryPointPath));
                }

                // We're going to execute the PHP file using the current PHP binary.
                $phpPath = self::findBin('php'); // We assume 'php' is in the system PATH since we're running this script. (Wow, such confidence!)
                Logger::getLogger()->verbose(sprintf('Using PHP binary: %s', $phpPath));
                $process = new Process(array_merge([$phpPath, $entryPointPath], $unit->getArguments() ?? []));
            }
            // Otherwise, if it's a system unit, we look for the binary in the system PATH.
            elseif($unit->getType() === ExecutionUnitType::SYSTEM)
            {
                Logger::getLogger()->debug(sprintf('Looking for system binary: %s', $unit->getEntryPoint()));
                // Find the binary in the system PATH.
                $entryPointPath = self::findBin($unit->getEntryPoint());
                if($entryPointPath === null)
                {
                    // Binary not found, throw an exception.
                    throw new OperationException(sprintf('The execution unit %s entrypoint %s could not be found in system PATH', $unit->getName(), $unit->getEntryPoint()));
                }
                
                Logger::getLogger()->verbose(sprintf('Found system binary at: %s', $entryPointPath));

                // Create the process with the found binary and arguments.
                $process = new Process(array_merge([$entryPointPath], $unit->getArguments() ?? []));
            }
            else
            {
                // In every other case, we throw an exception since we don't know how to handle it :(
                throw new OperationException(sprintf('Cannot execute unit type %s', $unit->getType()->value));
            }

            // If all goes well, we apply the configuration from the unit to the process.
            Logger::getLogger()->debug('Applying process configuration');
            $process = self::applyProcessConfig($process, $unit);

            try
            {
                Logger::getLogger()->verbose(sprintf('Executing unit %s...', $unit->getName()));
                $process->run();
            }
            catch(RuntimeException $e)
            {
                Logger::getLogger()->error(sprintf('Execution unit %s failed to execute: %s', $unit->getName(), $e->getMessage()));
            }
            finally
            {
                Logger::getLogger()->verbose(sprintf('Execution unit %s finished with exit code %d.', $unit->getName(), $process->getExitCode()));
                return $process->getExitCode();
            }
        }

        public static function executeFromDistribution(ExecutionUnit $unit, PackageReader $packageReader): int
        {
            // TODO: Complete this method to execute from distribution packages.
            throw new Exception('The method is not yet implemented');
        }

        /**
         * Applies the configuration from the ExecutionUnit to the Process instance.
         *
         * @param Process $process The process to configure.
         * @param ExecutionUnit $unit The execution unit containing the configuration.
         * @return Process The configured process.
         */
        private static function applyProcessConfig(Process $process, ExecutionUnit $unit): Process
        {
            Logger::getLogger()->debug(sprintf('Configuring process for unit: %s (mode: %s)', $unit->getName(), $unit->getMode()->value));
            
            // Set environment variables
            $env = $unit->getEnvironment();
            if($env !== null)
            {
                Logger::getLogger()->verbose(sprintf('Setting %d environment variables', count($env)));
                $process->setEnv($env);
            }

            // Set working directory
            $workingDirectory = MacroVariable::fromInput($unit->getWorkingDirectory());
            if(!empty($workingDirectory))
            {
                Logger::getLogger()->verbose(sprintf('Setting working directory: %s', $workingDirectory));
                $process->setWorkingDirectory($workingDirectory);
            }

            switch($unit->getMode())
            {
                case ExecutionMode::TTY:
                    if(!Process::isTtySupported())
                    {
                        Logger::getLogger()->warning(sprintf('The execution unit %s requested TTY mode, but it is not supported on this platform. Falling back to PIPE mode.', $unit->getName()));
                        $process->setTty(false);
                    }
                    else
                    {
                        $process->setTty(true);
                    }
                    break;

                case ExecutionMode::PTY:
                    if(!Process::isPtySupported())
                    {
                        Logger::getLogger()->warning(sprintf('The execution unit %s requested PTY mode, but it is not supported on this platform. Falling back to PIPE mode.', $unit->getName()));
                        $process->setPty(false);
                    }
                    else
                    {
                        $process->setPty(true);
                    }
                    break;

                case ExecutionMode::AUTO:
                    // AUTO mode tries to use TTY/PTY if available, otherwise falls back to PIPE.
                    if(Process::isTtySupported())
                    {
                        $process->setTty(true);
                    }
                    elseif(Process::isPtySupported())
                    {
                        $process->setPty(true);
                    }
                    else
                    {
                        $process->setTty(false);
                        $process->setPty(false);
                    }
                    break;
            }

            return $process;
        }

        /**
         * Finds the full path of an executable in the system PATH.
         *
         * @param string $name The name of the executable.
         * @return string|null The full path to the executable, or null if not found.
         */
        private static function findBin(string $name): ?string
        {
            return (new ExecutableFinder())->find($name);
        }

    }