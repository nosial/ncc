<?php

    namespace ncc\Interfaces;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\ThirdParty\Symfony\Process\Process;

    interface RunnerInterface
    {
        /**
         * Processes the ExecutionPolicy
         *
         * @param string $path
         * @param ExecutionPolicy $policy
         * @return ExecutionUnit
         * @throws FileNotFoundException
         * @throws AccessDeniedException
         * @throws IOException
         */
        public static function processUnit(string $path, ExecutionPolicy $policy): ExecutionUnit;

        /**
         * Returns the file extension to use for the target file
         *
         * @return string
         */
        public static function getFileExtension(): string;

        /**
         * Prepares a process object for the execution pointer
         *
         * @param ExecutionPointer $pointer
         * @return Process
         * @throws RunnerExecutionException
         */
        public static function prepareProcess(ExecutionPointer $pointer): Process;
    }