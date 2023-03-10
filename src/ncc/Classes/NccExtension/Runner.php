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

namespace ncc\Classes\NccExtension;

    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\NoAvailableUnitsException;
    use ncc\Exceptions\RunnerExecutionException;
    use ncc\Managers\ExecutionPointerManager;
    use ncc\Objects\Package\ExecutionUnit;
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
         * @throws FileNotFoundException
         * @throws IOException
         * @throws NoAvailableUnitsException
         * @throws RunnerExecutionException
         */
        public static function temporaryExecute(string $package, string $version, ExecutionUnit $unit): void
        {
            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot temporarily execute a unit with insufficient permissions');

            $ExecutionPointerManager = new ExecutionPointerManager();
            $ExecutionPointerManager->addUnit($package, $version, $unit, true);
            $ExecutionPointerManager->executeUnit($package, $version, $unit->ExecutionPolicy->Name);
            $ExecutionPointerManager->cleanTemporaryUnits();
        }
    }