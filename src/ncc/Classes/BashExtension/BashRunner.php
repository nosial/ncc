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

    namespace ncc\Classes\BashExtension;

    use Exception;
    use InvalidArgumentException;
    use ncc\Classes\ExecutionUnitRunner;
    use ncc\Enums\Runners;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Exceptions\OperationException;
    use ncc\Interfaces\RunnerInterface;
    use ncc\Objects\Package\ExecutionUnit;
    use ncc\ThirdParty\Symfony\Process\ExecutableFinder;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;

    class BashRunner implements RunnerInterface
    {
        /**
         * @inheritDoc
         * @throws IOException
         * @throws OperationException
         */
        public static function executeUnit(ExecutionUnit $unit, array $args=[], bool $local=true): int
        {
            $tmp = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . hash('sha1', $unit->getData()) . '.bash';
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
                throw new OperationException(sprintf('There was an error executing the bash execution unit %s: %s', $unit->getExecutionPolicy()->getName(), $e->getMessage()), $e);
            }
            finally
            {
                unlink($tmp);
            }

            return $process->getExitCode();
        }
    }