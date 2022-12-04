<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use Exception;
    use ncc\Abstracts\Runners;
    use ncc\Abstracts\Scopes;
    use ncc\Classes\PhpExtension\PhpRunner;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\ExecutionUnitNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NoAvailableUnitsException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Objects\ExecutionPointers;
    use ncc\Objects\Package;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy\ExitHandle;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\Console;
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

            try
            {
                foreach($this->TemporaryUnits as $datum)
                {
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
            return hash('haval128,4', $package . $version);
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
         * @throws UnsupportedRunnerException
         * @noinspection PhpUnused
         */
        public function addUnit(string $package, string $version, ExecutionUnit $unit, bool $temporary=false): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot add new ExecutionUnit \'' . $unit->ExecutionPolicy->Name .'\' for ' . $package . ', insufficient permissions');

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id;

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
            $bin_file .= match ($unit->ExecutionPolicy->Runner) {
                Runners::php => PhpRunner::getFileExtension(),
                default => throw new UnsupportedRunnerException('The runner \'' . $unit->ExecutionPolicy->Runner . '\' is not supported'),
            };

            if($filesystem->exists($bin_file) && $temporary)
                return;

            if(!$filesystem->exists($package_bin_path))
                $filesystem->mkdir($package_bin_path);

            if($filesystem->exists($bin_file))
                $filesystem->remove($bin_file);

            IO::fwrite($bin_file, $unit->Data);
            $execution_pointers->addUnit($unit, $bin_file);
            IO::fwrite($package_config_path, ZiProto::encode($execution_pointers->toArray(true)));

            if($temporary)
            {
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

            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';
            $package_bin_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id;

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
            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';

            if(!file_exists($package_config_path))
                return [];

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $results = [];
            foreach($execution_pointers->getPointers() as $pointer)
            {
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
         * @return void
         * @throws AccessDeniedException
         * @throws ExecutionUnitNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws UnsupportedRunnerException
         * @throws RunnerExecutionException
         */
        public function executeUnit(string $package, string $version, string $name): void
        {
            $package_id = $this->getPackageId($package, $version);
            $package_config_path = $this->RunnerPath . DIRECTORY_SEPARATOR . $package_id . '.inx';

            if(!file_exists($package_config_path))
                throw new NoAvailableUnitsException('There is no available units for \'' . $package . '=' .$version .'\'');

            $execution_pointers = ExecutionPointers::fromArray(ZiProto::decode(IO::fread($package_config_path)));
            $unit = $execution_pointers->getUnit($name);

            if($unit == null)
                throw new ExecutionUnitNotFoundException('The execution unit \'' . $name . '\' was not found for \'' . $package . '=' .$version .'\'');

            $process = match (strtolower($unit->ExecutionPolicy->Runner))
            {
                Runners::php => PhpRunner::prepareProcess($unit),
                default => throw new UnsupportedRunnerException('The runner \'' . $unit->ExecutionPolicy->Runner . '\' is not supported'),
            };

            if($unit->ExecutionPolicy->Execute->WorkingDirectory !== null)
                $process->setWorkingDirectory($unit->ExecutionPolicy->Execute->WorkingDirectory);
            if($unit->ExecutionPolicy->Execute->Timeout !== null)
                $process->setTimeout((float)$unit->ExecutionPolicy->Execute->Timeout);

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

            try
            {
                if($unit->ExecutionPolicy->Message !== null)
                    Console::out($unit->ExecutionPolicy->Message);

                $process->run(function ($type, $buffer) {
                   Console::out($buffer);
                });

                $process->wait();
            }
            catch(Exception $e)
            {
                unset($e);
                $this->handleExit($package, $version, $unit->ExecutionPolicy->ExitHandlers->Error);
            }

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
        }

        /**
         * Temporarily executes a
         *
         * @param Package $package
         * @param string $unit_name
         * @return void
         * @throws AccessDeniedException
         * @throws ExecutionUnitNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         * @throws UnsupportedRunnerException
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
         * @throws ExecutionUnitNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         * @throws UnsupportedRunnerException
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
                exit($exitHandle->ExitCode);
            }

            if($exitHandle->Run !== null)
            {
                $this->executeUnit($package, $version, $exitHandle->Run);
            }

            return true;
        }

    }