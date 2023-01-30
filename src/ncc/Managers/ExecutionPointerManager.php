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
    use ncc\Abstracts\Runners;
    use ncc\Abstracts\Scopes;
    use ncc\Classes\BashExtension\BashRunner;
    use ncc\Classes\LuaExtension\LuaRunner;
    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Classes\PerlExtension\PerlRunner;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Classes\PythonExtension\Python2Runner;
    use ncc\Classes\PythonExtension\Python3Runner;
    use ncc\Classes\PythonExtension\PythonRunner;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NoAvailableUnitsException;
    use ncc\Exceptions\RunnerExecutionException;
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

    class ExecutionPointerManager
    {
        /**
         * The path for where all the runners are located
         *
         * @var string
         */
        private $RunnerPath;

        /**
         * An array of temporary unit names to destroy once the object is destroyed
         *
         * @var string[]
         */
        private $TemporaryUnits;

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
         * @throws InvalidScopeException
         */
        public function __construct()
        {
            $this->RunnerPath = PathFinder::getRunnerPath(Scopes::System);
            $this->TemporaryUnits = [];
        }

        /**
         * Deletes all temporary files and directories
         *
         * @return void
         */
        public function cleanTemporaryUnits(): void
        {
            if(count($this->TemporaryUnits) == 0)
                return;

            Console::outVerbose('Cleaning temporary units...');

            try
            {
                foreach($this->TemporaryUnits as $datum)
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
         * @throws FileNotFoundException
         */
        public function getEntryPointPath(string $package, string $version, string $name): string
        {
            $package_id = $this->getPackageId($package, $version);
            $package_bin_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id;
            $entry_point_path = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $name) . '.entrypoint';

            if(!file_exists($entry_point_path))
                throw new FileNotFoundException('Cannot find entry point for ' . $package . '=' . $version . '.' . $name);

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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws RunnerExecutionException
         * @noinspection PhpUnused
         */
        public function addUnit(string $package, string $version, ExecutionUnit $unit, bool $temporary=false): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot add new ExecutionUnit \'' . $unit->ExecutionPolicy->Name .'\' for ' . $package . ', insufficient permissions');

            Console::outVerbose(sprintf('Adding new ExecutionUnit \'%s\' for %s', $unit->ExecutionPolicy->Name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id;
            $entry_point_path = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $unit->ExecutionPolicy->Name) . '.entrypoint';

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

            $bin_file = $package_bin_path . DIRECTORY_SEPARATOR . hash('haval128,4', $unit->ExecutionPolicy->Name);
            $bin_file .= match ($unit->ExecutionPolicy->Runner)
            {
                Runners::bash => BashRunner::getFileExtension(),
                Runners::php => PhpRunner::getFileExtension(),
                Runners::perl => PerlRunner::getFileExtension(),
                Runners::python => PythonRunner::getFileExtension(),
                Runners::python2 => Python2Runner::getFileExtension(),
                Runners::python3 => Python3Runner::getFileExtension(),
                Runners::lua => LuaRunner::getFileExtension(),
                default => throw new RunnerExecutionException('The runner \'' . $unit->ExecutionPolicy->Runner . '\' is not supported'),
            };

            Console::outDebug(sprintf('bin_file=%s', $bin_file));

            if($filesystem->exists($bin_file) && $temporary)
                return;

            if(!$filesystem->exists($package_bin_path))
                $filesystem->mkdir($package_bin_path);

            if($filesystem->exists($bin_file))
                $filesystem->remove($bin_file);

            IO::fwrite($bin_file, $unit->Data);
            $execution_pointers->addUnit($unit, $bin_file);
            IO::fwrite($package_config_path, ZiProto::encode($execution_pointers->toArray(true)));

            $entry_point = sprintf("#!%s\nncc exec --package=\"%s\" --exec-version=\"%s\" --exec-unit=\"%s\" --exec-args \"$@\"",
                '/bin/bash',
                $package, $version, $unit->ExecutionPolicy->Name
            );

            if(file_exists($entry_point_path))
                $filesystem->remove($entry_point_path);
            IO::fwrite($entry_point_path, $entry_point);
            chmod($entry_point_path, 0755);

            if($temporary)
            {
                Console::outVerbose(sprintf('Adding temporary ExecutionUnit \'%s\' for %s', $unit->ExecutionPolicy->Name, $package));
                $this->TemporaryUnits[] = [
                    'package' => $package,
                    'version' => $version,
                    'unit' => $unit->ExecutionPolicy->Name
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         */
        public function removeUnit(string $package, string $version, string $name): bool
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot remove ExecutionUnit \'' . $name .'\' for ' . $package . ', insufficient permissions');

            Console::outVerbose(sprintf('Removing ExecutionUnit \'%s\' for %s', $name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id;

            Console::outDebug(sprintf('package_id=%s', $package_id));
            Console::outDebug(sprintf('package_config_path=%s', $package_config_path));
            Console::outDebug(sprintf('package_bin_path=%s', $package_bin_path));

            $filesystem = new Filesystem();
            if(!$filesystem->exists($package_config_path))
                return false;
            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $unit = $execution_pointers->getUnit($name);
            if($unit == null)
                return false;
            $results = $execution_pointers->deleteUnit($name);

            // Delete everything if there are no execution pointers configured
            if(count($execution_pointers->getPointers()) == 0)
            {
                $filesystem->remove($package_config_path);
                $filesystem->remove($package_bin_path);

                return $results;
            }

            // Delete the single execution pointer file
            if($filesystem->exists($unit->FilePointer))
                $filesystem->remove($unit->FilePointer);

            return $results;
        }

        /**
         * Returns an array of configured units for a package version
         *
         * @param string $package
         * @param string $version
         * @return array
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @noinspection PhpUnused
         */
        public function getUnits(string $package, string $version): array
        {
            Console::outVerbose(sprintf('getting execution units for %s', $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';

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
                Console::outDebug(sprintf('unit %s', $pointer->ExecutionPolicy->Name));
                $results[] = $pointer->ExecutionPolicy->Name;
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
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         */
        public function executeUnit(string $package, string $version, string $name, array $args=[]): int
        {
            Console::outVerbose(sprintf('executing unit %s for %s', $name, $package));

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';

            if(!file_exists($package_config_path))
                throw new NoAvailableUnitsException('There is no available units for \'' . $package . '=' .$version .'\'');

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $unit = $execution_pointers->getUnit($name);

            if($unit == null)
                throw new RunnerExecutionException('The execution unit \'' . $name . '\' was not found for \'' . $package . '=' .$version .'\'');

            Console::outDebug(sprintf('unit=%s', $unit->ExecutionPolicy->Name));
            Console::outDebug(sprintf('runner=%s', $unit->ExecutionPolicy->Runner));
            Console::outDebug(sprintf('file=%s', $unit->FilePointer));
            Console::outDebug(sprintf('pass_thru_args=%s', json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

            // Handle the arguments
            if($unit->ExecutionPolicy->Execute->Options !== null && count($unit->ExecutionPolicy->Execute->Options) > 0)
            {
                $args = array_merge($args, $unit->ExecutionPolicy->Execute->Options);

                foreach($unit->ExecutionPolicy->Execute->Options as $option)
                {
                    $args[] = ConstantCompiler::compileRuntimeConstants($option);
                }
            }

            $process = new Process(array_merge(
                [PathFinder::findRunner(strtolower($unit->ExecutionPolicy->Runner)), $unit->FilePointer], $args)
            );

            if($unit->ExecutionPolicy->Execute->WorkingDirectory !== null)
            {
                $process->setWorkingDirectory(ConstantCompiler::compileRuntimeConstants($unit->ExecutionPolicy->Execute->WorkingDirectory));
            }

            if($unit->ExecutionPolicy->Execute->Timeout !== null)
            {
                $process->setTimeout((float)$unit->ExecutionPolicy->Execute->Timeout);
            }

            if($unit->ExecutionPolicy->Execute->Silent)
            {
                $process->disableOutput();
                $process->setTty(false);
            }
            elseif($unit->ExecutionPolicy->Execute->Tty)
            {
                $process->enableOutput();
                $process->setTty(true);
            }
            else
            {
                $process->enableOutput();
            }

            if($process->isTty() && !Functions::isTtyMode())
            {
                Console::outWarning('The process is configured to use a TTY, but the current environment does not support it');
                $process->setTty(false);
            }

            Console::outDebug(sprintf('working_directory=%s', $process->getWorkingDirectory()));
            Console::outDebug(sprintf('timeout=%s', ($process->getTimeout() ?? 0)));
            Console::outDebug(sprintf('silent=%s', ($unit->ExecutionPolicy->Execute->Silent ? 'true' : 'false')));
            Console::outDebug(sprintf('tty=%s', ($unit->ExecutionPolicy->Execute->Tty ? 'true' : 'false')));
            Console::outDebug(sprintf('options=%s', implode(' ', $args)));
            Console::outDebug(sprintf('cmd=%s', $process->getCommandLine()));

            try
            {
                if($unit->ExecutionPolicy->Message !== null)
                    Console::out($unit->ExecutionPolicy->Message);

                $process->run(function ($type, $buffer)
                {
                   Console::out($buffer);
                });

                $process->wait();
            }
            catch(Exception $e)
            {
                unset($e);
                $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Error);
            }

            Console::outDebug(sprintf('exit_code=%s', $process->getExitCode()));

            if($unit->ExecutionPolicy->ExitHandlers !== null)
            {
                if($process->isSuccessful() && $unit->ExecutionPolicy->ExitHandlers->Success !== null)
                {
                    $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Success);
                }
                elseif($process->isSuccessful() && $unit->ExecutionPolicy->ExitHandlers->Error !== null)
                {
                    $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Error);
                }
                else
                {
                    $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Success, $process);
                    $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Warning, $process);
                    $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Error, $process);
                }
            }

            return $process->getExitCode();
        }

        /**
         * Temporarily executes a
         *
         * @param Package $package
         * @param string $unit_name
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         */
        public function temporaryExecute(Package $package, string $unit_name): void
        {
            // First get the execution unit from the package.
            $unit = $package->getExecutionUnit($unit_name);

            // Get the required units
            $required_units = [];
            if($unit->ExecutionPolicy->ExitHandlers !== null)
            {
                $required_unit = $unit->ExecutionPolicy?->ExitHandlers?->Success?->Run;
                if($required_unit !== null)
                    $required_units[] = $required_unit;

                $required_unit = $unit->ExecutionPolicy?->ExitHandlers?->Warning?->Run;
                if($required_unit !== null)
                    $required_units[] = $required_unit;

                $required_unit = $unit->ExecutionPolicy?->ExitHandlers?->Error?->Run;
                if($required_unit !== null)
                    $required_units = $required_unit;
            }

            // Install the units temporarily
            $this->addUnit($package->Assembly->Package, $package->Assembly->Version, $unit, true);
            foreach($required_units as $r_unit)
            {
                $this->addUnit($package->Assembly->Package, $package->Assembly->Version, $r_unit, true);
            }

            $this->executeUnit($package->Assembly->Package, $package->Assembly->Version, $unit_name);
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
         * @param ExitHandle $exitHandle
         * @param Process|null $process
         * @return bool
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         */
        public function handleExit(string $package, string $version, ExitHandle $exitHandle, ?Process $process=null): bool
        {
            if($exitHandle->Message !== null)
                Console::out($exitHandle->Message);

            if($process !== null && !$exitHandle->EndProcess)
            {
                if($exitHandle->ExitCode !== $process->getExitCode())
                    return false;
            }
            elseif($exitHandle->EndProcess)
            {
                Console::outDebug(sprintf('exit_code=%s', $process->getExitCode()));
                exit($exitHandle->ExitCode);
            }

            if($exitHandle->Run !== null)
            {
                Console::outVerbose('Running unit \'' . $exitHandle->Run . '\'');
                $this->executeUnit($package, $version, $exitHandle->Run);
            }

            return true;
        }

    }