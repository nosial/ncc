<?php

    namespace ncc\Classes\NccExtension;

    use ncc\Abstracts\Runners;
    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\ExecutionUnitNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NoAvailableUnitsException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Exceptions\UnsupportedRunnerException;
    use ncc\Managers\ExecutionPointerManager;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;

    class Runner
    {
        /**
         * Temporarily executes an execution unit, removes it once it is executed
         *
         * @param string $package
         * @param string $version
         * @param ExecutionUnit $unit
         * @return void
         * @throws AccessDeniedException
         * @throws UnsupportedRunnerException
         * @throws ExecutionUnitNotFoundException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         */
        public static function temporaryExecute(string $package, string $version, ExecutionUnit $unit)
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot temporarily execute a unit with insufficent permissions');

            $ExecutionPointerManager = new ExecutionPointerManager();
            $ExecutionPointerManager->addUnit($package, $version, $unit, true);
            $ExecutionPointerManager->executeUnit($package, $version, $unit->ExecutionPolicy->Name);
            $ExecutionPointerManager->cleanTemporaryUnits();;
        }
    }