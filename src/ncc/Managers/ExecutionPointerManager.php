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

    namespace ncc\Managers;

    use Exception;
    use ncc\Enums\Runners;
    use ncc\Enums\Scopes;
    use ncc\Classes\BashExtension\BashRunner;
    use ncc\Classes\LuaExtension\LuaRunner;
    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Classes\PerlExtension\PerlRunner;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Classes\PythonExtension\Python2Runner;
    use ncc\Classes\PythonExtension\Python3Runner;
    use ncc\Classes\PythonExtension\PythonRunner;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\ExecutionPointers;
    use ncc\Objects\Package;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\ExitHandle;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\ZiProto\ZiProto;
    use RuntimeException;

    class ExecutionPointerManager
    {
        /**
         * The path for where all the runners are located
         *
         * @var string
         */
        private $runner_path;

        /**
         * An array of temporary unit names to destroy once the object is destroyed
         *
         * @var string[]
         */
        private $temporary_units;

        /**
         * Deletes all the temporary files on destruct
         */
        public function __destruct()
        {
            try
            {
                $this->cleanTemporaryUnits();
            }
            catch(Exception $e)
            {
                unset($e);
            }
        }

        /**
         * ExecutionPointerManager constructor.
         */
        public function __construct()
        {
            $this->runner_path = PathFinder::getRunnerPath(Scopes::SYSTEM);
            $this->temporary_units = [];
        }

        /**
         * Deletes all temporary files and directories
         *
         * @return void
         */
        public function cleanTemporaryUnits(): void
        {
            if(count($this->temporary_units) === 0)
            {
                return;
            }

            Console::outVerbose('Cleaning temporary units...');

            try
            {
                foreach($this->temporary_units as $datum)
                {
                    Console::outDebug(sprintf('deleting unit %s=%s.%s', $datum['package'], $datum['version'], $datum['name']));
                    $this->removeUnit($datum['package'], $datum['version'], $datum['name']);
                }
            }
            catch(Exception $e)
            {
                unset($e);
            }
        }

        /**
         * Calculates the Package ID for the execution pointers
         *
         * @param string $package
         * @param string $version
         * @return string
         */
        private function getPackageId(string $package, string $version): string
        {
            Console::outDebug(sprintf('calculating package id for %s=%s', $package, $version));
            return hash('haval128,4', $package . $version);
        }

        /**
         * Returns the path to the execution pointer file
         *
         * @param string $package
         * @param string $version
         * @param string $name
         * @return string
         */
        public function getEntryPointPath(string $package, string $version, string $name): string
        {
            $package_id = $this->getPackageId($package, $version);
            $package_bin_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id;
            $entry_point_path = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $name) . '.entrypoint';

            if(!file_exists($entry_point_path))
            {
                throw new RuntimeException('Cannot find entry point for ' . $package . '=' . $version . '.' . $name);
            }

            return $entry_point_path;
        }

        /**
         * Adds a new Execution Unit to the
         *
         * @param string $package
         * @param string $version
         * @param ExecutionUnit $unit
         * @param bool $temporary
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws PathNotFoundException
         */
        public function addUnit(string $package, string $version, ExecutionUnit $unit, bool $temporary=false): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Cannot add new ExecutionUnit \'' . $unit->execution_policy->name .'\' for ' . $package . ', insufficient permissions');
            }

            Console::outVerbose(sprintf('Adding new ExecutionUnit \'%s\' for %s', $unit->execution_policy->name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id;
            $entry_point_path = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $unit->execution_policy->name) . '.entrypoint';

            Console::outDebug(sprintf('package_id=%s', $package_id));
            Console::outDebug(sprintf('package_config_path=%s', $package_config_path));
            Console::outDebug(sprintf('package_bin_path=%s', $package_bin_path));
            Console::outDebug(sprintf('entry_point_path=%s', $entry_point_path));

            $filesystem = new Filesystem();

            // Either load or create the pointers file
            if(!$filesystem->exists($package_config_path))
            {
                $execution_pointers = new ExecutionPointers($package, $version);
            }
            else
            {
                $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            }

            $bin_file = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $unit->execution_policy->name);
            $bin_file .= match ($unit->execution_policy->runner)
            {
                Runners::BASH => BashRunner::getFileExtension(),
                Runners::PHP => PhpRunner::getFileExtension(),
                Runners::PERL => PerlRunner::getFileExtension(),
                Runners::PYTHON => PythonRunner::getFileExtension(),
                Runners::PYTHON_2 => Python2Runner::getFileExtension(),
                Runners::PYTHON_3 => Python3Runner::getFileExtension(),
                Runners::LUA => LuaRunner::getFileExtension(),
                default => throw new NotSupportedException('The runner \'' . $unit->execution_policy->runner . '\' is not supported'),
            };

            Console::outDebug(sprintf('bin_file=%s', $bin_file));

            if($temporary && $filesystem->exists($bin_file))
            {
                return;
            }

            if(!$filesystem->exists($package_bin_path))
            {
                $filesystem->mkdir($package_bin_path);
            }

            if($filesystem->exists($bin_file))
            {
                $filesystem->remove($bin_file);
            }

            IO::fwrite($bin_file, $unit->Data);
            $execution_pointers->addUnit($unit, $bin_file);
            IO::fwrite($package_config_path, ZiProto::encode($execution_pointers->toArray(true)));

            $entry_point = sprintf("#!%s\nncc exec --package=\"%s\" --exec-version=\"%s\" --exec-unit=\"%s\" --exec-args \"$@\"",
                '/bin/bash',
                $package, $version, $unit->execution_policy->name
            );

            if(file_exists($entry_point_path))
            {
                $filesystem->remove($entry_point_path);
            }

            IO::fwrite($entry_point_path, $entry_point);
            chmod($entry_point_path, 0755);

            if($temporary)
            {
                Console::outVerbose(sprintf('Adding temporary ExecutionUnit \'%s\' for %s', $unit->execution_policy->name, $package));
                $this->temporary_units[] = [
                    'package' => $package,
                    'version' => $version,
                    'unit' => $unit->execution_policy->name
                ];
            }
        }

        /**
         * Deletes and removes the installed unit
         *
         * @param string $package
         * @param string $version
         * @param string $name
         * @return bool
         * @throws AuthenticationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function removeUnit(string $package, string $version, string $name): bool
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Cannot remove ExecutionUnit \'' . $name .'\' for ' . $package . ', insufficient permissions');
            }

            Console::outVerbose(sprintf('Removing ExecutionUnit \'%s\' for %s', $name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id;

            Console::outDebug(sprintf('package_id=%s', $package_id));
            Console::outDebug(sprintf('package_config_path=%s', $package_config_path));
            Console::outDebug(sprintf('package_bin_path=%s', $package_bin_path));

            $filesystem = new Filesystem();

            if(!$filesystem->exists($package_config_path))
            {
                return false;
            }

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $unit = $execution_pointers->getUnit($name);

            if($unit === null)
            {
                return false;
            }

            $results = $execution_pointers->deleteUnit($name);

            // Delete everything if there are no execution pointers configured
            if(count($execution_pointers->getPointers()) === 0)
            {
                $filesystem->remove($package_config_path);
                $filesystem->remove($package_bin_path);

                return $results;
            }

            // Delete the single execution pointer file
            if($filesystem->exists($unit->file_pointer))
            {
                $filesystem->remove($unit->file_pointer);
            }

            return $results;
        }

        /**
         * Returns an array of configured units for a package version
         *
         * @param string $package
         * @param string $version
         * @return array
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function getUnits(string $package, string $version): array
        {
            Console::outVerbose(sprintf('getting execution units for %s', $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id . '.inx';

            Console::outDebug(sprintf('package_id=%s', $package_id));
            Console::outDebug(sprintf('package_config_path=%s', $package_config_path));

            if(!file_exists($package_config_path))
            {
                Console::outWarning(sprintf('Path \'%s\' does not exist', $package_config_path));
                return [];
            }

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $results = [];
            foreach($execution_pointers->getPointers() as $pointer)
            {
                Console::outDebug(sprintf('unit %s', $pointer->execution_policy->name));
                $results[] = $pointer->execution_policy->name;
            }

            return $results;
        }

        /**
         * Executes a unit
         *
         * @param string $package
         * @param string $version
         * @param string $name
         * @param array $args
         * @return int
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public function executeUnit(string $package, string $version, string $name, array $args=[]): int
        {
            Console::outVerbose(sprintf('executing unit %s for %s', $name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->runner_path . DIRECTORY_SEPARATOR . $package_id . '.inx';

            if(!file_exists($package_config_path))
            {
                throw new OperationException('There is no available units for \'' . $package . '=' .$version .'\'');
            }

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $unit = $execution_pointers->getUnit($name);

            if($unit === null)
            {
                throw new OperationException('The execution unit \'' . $name . '\' was not found for \'' . $package . '=' .$version .'\'');
            }

            Console::outDebug(sprintf('unit=%s', $unit->execution_policy->name));
            Console::outDebug(sprintf('runner=%s', $unit->execution_policy->runner));
            Console::outDebug(sprintf('file=%s', $unit->file_pointer));
            Console::outDebug(sprintf('pass_thru_args=%s', implode(' ', $args)));

            // Handle the arguments
            if($unit->execution_policy->execute->options !== null && count($unit->execution_policy->execute->options) > 0)
            {
                $args = array_merge($args, $unit->execution_policy->execute->options);

                foreach($unit->execution_policy->execute->options as $option)
                {
                    $args[] = ConstantCompiler::compileRuntimeConstants($option);
                }
            }

            $process = new Process(array_merge(
                [PathFinder::findRunner(strtolower($unit->execution_policy->runner)), $unit->file_pointer], $args)
            );

            if($unit->execution_policy->execute->working_directory !== null)
            {
                $process->setWorkingDirectory(ConstantCompiler::compileRuntimeConstants($unit->execution_policy->execute->working_directory));
            }

            if($unit->execution_policy->execute->timeout !== null)
            {
                $process->setTimeout((float)$unit->execution_policy->execute->timeout);
            }
            else
            {
                Console::outDebug('timeout is not set, using the default value (forever)');
                $process->setTimeout(null);
            }

            try
            {
                if($unit->execution_policy->execute->silent)
                {
                    $process->disableOutput();
                    $process->setTty(false);
                }
                elseif($unit->execution_policy->execute->tty)
                {
                    $process->enableOutput();
                    $process->setTty(true);
                }
                else
                {
                    $process->enableOutput();
                }
            }
            catch(Exception $e)
            {
                unset($e);
                $process->enableOutput();
                Console::outWarning('The process is configured to use a TTY, but the current environment does not support it');
            }
            finally
            {
                if($process->isTty() && !Functions::isTtyMode())
                {
                    Console::outWarning('The process is configured to use a TTY, but the current environment does not support it');
                    $process->setTty(false);
                }
            }

            Console::outDebug(sprintf('working_directory=%s', $process->getWorkingDirectory()));
            Console::outDebug(sprintf('timeout=%s', (int)$process->getTimeout()));
            Console::outDebug(sprintf('silent=%s', ($unit->execution_policy->execute->silent ? 'true' : 'false')));
            Console::outDebug(sprintf('tty=%s', ($unit->execution_policy->execute->tty ? 'true' : 'false')));
            Console::outDebug(sprintf('options=%s', implode(' ', $args)));
            Console::outDebug(sprintf('cmd=%s', $process->getCommandLine()));

            try
            {
                if($unit->execution_policy->message !== null)
                {
                    Console::out($unit->execution_policy->message);
                }

                $process->run(function ($type, $buffer)
                {
                   Console::out($buffer);
                });

                $process->wait();
            }
            catch(Exception $e)
            {
                if($unit->execution_policy->exit_handlers !== null && $unit->execution_policy->exit_handlers->error !== null)
                {
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->error);
                }

                Console::outException(sprintf('An error occurred while executing the unit \'%s\' for \'%s\' (exit code %s)', $unit->execution_policy->name, $package, $process->getExitCode()), $e);
            }
            finally
            {
                Console::outDebug(sprintf('exit_code=%s', $process->getExitCode()));
            }

            if($unit->execution_policy->exit_handlers !== null)
            {
                if($unit->execution_policy->exit_handlers->success !== null && $process->isSuccessful())
                {
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->success);
                }
                elseif($unit->execution_policy->exit_handlers->error !== null && $process->isSuccessful())
                {
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->error);
                }
                else
                {
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->success, $process);
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->warning, $process);
                    $this->handleExit($package, $version, $unit->execution_policy->exit_handlers->error, $process);
                }
            }

            return $process->getExitCode() ?? 0;
        }

        /**
         * Temporarily executes a
         *
         * @param Package $package
         * @param string $unit_name
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public function temporaryExecute(Package $package, string $unit_name): void
        {
            // First, get the execution unit from the package.
            $unit = $package->getExecutionUnit($unit_name);

            if($unit === null)
            {
                throw new OperationException(sprintf('No execution unit named \'%s\' is available for package \'%s\'', $unit_name, $package->assembly->package));
            }

            // Get the required units
            $required_units = [];
            if($unit->execution_policy->exit_handlers !== null)
            {
                $required_unit = $unit->execution_policy?->exit_handlers?->success?->run;
                if($required_unit !== null)
                {
                    $required_units[] = $required_unit;
                }

                $required_unit = $unit->execution_policy?->exit_handlers?->warning?->run;
                if($required_unit !== null)
                {
                    $required_units[] = $required_unit;
                }

                $required_unit = $unit->execution_policy?->exit_handlers?->error?->run;
                if($required_unit !== null)
                {
                    $required_units = $required_unit;
                }
            }

            // Install the units temporarily
            $this->addUnit($package->assembly->package, $package->assembly->version, $unit, true);
            foreach($required_units as $r_unit)
            {
                $this->addUnit($package->assembly->package, $package->assembly->version, $r_unit, true);
            }

            $this->executeUnit($package->assembly->package, $package->assembly->version, $unit_name);
            $this->cleanTemporaryUnits();
        }

        /**
         * Handles an exit handler object.
         *
         * If Process is Null and EndProcess is true, the method will end the process
         * if Process is not Null the exit handler will only execute if the process' exit code is the same
         *
         * @param string $package
         * @param string $version
         * @param ExitHandle $exit_handler
         * @param Process|null $process
         * @return bool
         * @throws IOException
         * @throws OperationException
         * @throws PathNotFoundException
         */
        public function handleExit(string $package, string $version, ExitHandle $exit_handler, ?Process $process=null): bool
        {
            if($exit_handler->message !== null)
            {
                Console::out($exit_handler->message);
            }

            if($process !== null && !$exit_handler->end_process)
            {
                if($exit_handler->exit_code !== $process->getExitCode())
                {
                    return false;
                }
            }
            elseif($exit_handler->end_process)
            {
                Console::outDebug(sprintf('exit_code=%s', $process->getExitCode()));
                exit($exit_handler->exit_code);
            }

            if($exit_handler->run !== null)
            {
                Console::outVerbose('Running unit \'' . $exit_handler->run . '\'');
                $this->executeUnit($package, $version, $exit_handler->run);
            }

            return true;
        }

    }