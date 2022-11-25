<?php

    namespace ncc\Classes\PhpExtension;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Interfaces\RunnerInterface;
    use ncc\Objects\ExecutionPointers\ExecutionPointer;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Objects\ProjectConfiguration\ExecutionPolicy;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\Base64;
    use ncc\Utilities\IO;

    class Runner implements RunnerInterface
    {
        /**
         * @param string $path
         * @param ExecutionPolicy $policy
         * @return ExecutionUnit
         * @throws FileNotFoundException
         * @throws AccessDeniedException
         * @throws IOException
         */
        public static function processUnit(string $path, ExecutionPolicy $policy): ExecutionUnit
        {
            $execution_unit = new ExecutionUnit();
            $target_file = $path;
            if(!file_exists($target_file) && !is_file($target_file))
                throw new FileNotFoundException($target_file);
            $policy->Execute->Target = null;
            $execution_unit->ExecutionPolicy = $policy;
            $execution_unit->Data = Base64::encode(IO::fread($target_file));

            return $execution_unit;
        }

        /**
         * Returns the file extension to use for the target file
         *
         * @return string
         */
        public static function getFileExtension(): string
        {
            return '.php';
        }

        /**
         * @param ExecutionPointer $pointer
         * @return Process
         * @throws RunnerExecutionException
         */
        public static function prepareProcess(ExecutionPointer $pointer): Process
        {
            $php_bin = new ExecutableFinder();
            $php_bin = $php_bin->find('php');
            if($php_bin == null)
                throw new RunnerExecutionException('Cannot locate PHP executable');

            if($pointer->ExecutionPolicy->Execute->Options !== null && count($pointer->ExecutionPolicy->Execute->Options) > 0)
                return new Process(array_merge([$php_bin, $pointer->FilePointer], $pointer->ExecutionPolicy->Execute->Options));
            return new Process([$php_bin, $pointer->FilePointer]);
        }
    }