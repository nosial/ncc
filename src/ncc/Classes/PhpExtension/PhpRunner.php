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

    namespace ncc\Classes\PhpExtension;

    use Exception;
    use InvalidArgumentException;
    use ncc\Classes\ExecutionUnitRunner;
    use ncc\Enums\Runners;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Interfaces\RunnerInterface;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;

    class PhpRunner implements RunnerInterface
    {
        /**
         * @inheritDoc
         * @throws IOException
         * @throws NotSupportedException
         * @throws OperationException
         */
        public static function executeUnit(ExecutionUnit $unit, array $args=[], bool $local=true): int
        {
            if($unit->getExecutionPolicy()->getRunner() !== Runners::PHP)
            {
                throw new InvalidArgumentException(sprintf('The execution unit %s is not a php execution unit', $unit->getExecutionPolicy()->getName()));
            }

            if($local)
            {
                return self::executeLocally($unit, $args);
            }

            return self::executeInMemory($unit);
        }

        /**
         * Executes the php unit from a memory buffer
         *
         * @param ExecutionUnit $unit
         * @return int
         * @throws OperationException
         */
        private static function executeInMemory(ExecutionUnit $unit): int
        {
            try
            {
                $return_code = eval(preg_replace('/^<\?(php)?|\?>$/i', '', $unit->getData()));
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('There was an error executing the PHP execution unit %s: %s', $unit->getExecutionPolicy()->getName(), $e->getMessage()), $e);
            }

            return is_int($return_code) ? $return_code : 0;
        }

        /**
         * Executes the php unit locally from disk
         *
         * @param ExecutionUnit $unit
         * @param array $args
         * @return int
         * @throws IOException
         * @throws OperationException
         * @throws NotSupportedException
         */
        private static function executeLocally(ExecutionUnit $unit, array $args=[]): int
        {
            $tmp = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . hash('sha1', $unit->getData()) . '.php';
            IO::fwrite($tmp, $unit->getData(), 0777);

            try
            {
                $process = ExecutionUnitRunner::constructProcess($unit, array_merge([$tmp], $args));
                $process->run(static function($type, $buffer) use ($unit)
                {
                    if(!$unit->getExecutionPolicy()->getExecute()->isSilent())
                    {
                        print($buffer);
                    }
                });
            }
            catch(Exception $e)
            {
                throw new OperationException(sprintf('There was an error executing the PHP execution unit %s: %s', $unit->getExecutionPolicy()->getName(), $e->getMessage()), $e);
            }
            finally
            {
                unlink($tmp);
            }

            return $process->getExitCode();
        }
    }