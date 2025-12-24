<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\CLI\Commands;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\PackageReader;
    use ncc\Objects\Package\ComponentReference;
    use ncc\Objects\Package\ExecutionUnitReference;
    use ncc\Objects\Package\ResourceReference;

    class InspectCommand extends AbstractCommandHandler
    {
        /**
         * Prints out the help menu for the inspect command
         *
         * @return void
         */
        public static function help(): void
        {
            Console::out('Usage: ncc inspect --path=<package-file>' . PHP_EOL);
            Console::out('Displays detailed information about an ncc package.' . PHP_EOL);
            Console::out('This includes package metadata, version, components, resources,');
            Console::out('execution units, dependencies, and entry points.' . PHP_EOL);
            Console::out('Options:');
            Console::out('  --path=<file>     (Required) Path to the .ncc package file to inspect');
            Console::out(PHP_EOL . 'Example:');
            Console::out('  ncc inspect --path=mypackage.ncc');
        }

        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['help']) || isset($argv['h']))
            {
                self::help();
                return 0;
            }

            $packagePath = null;
            if(isset($argv['path']))
            {
                $packagePath = realpath($argv['path']);
            }
            else
            {
                Console::error('No package path specified, use --path to specify a package file to inspect');
                return 1;
            }

            if(!file_exists($packagePath) || !is_file($packagePath))
            {
                Console::error('The given path must be a ncc package file');
                return 1;
            }

            try
            {
                $packageReader = new PackageReader($packagePath);
            }
            catch(Exception $e)
            {
                Console::error($e->getMessage());
                return 1;
            }

            Console::out('Package Structure Version: ' . $packageReader->getPackageVersion());
            Console::out('Build Number: ' . $packageReader->getHeader()->getBuildNumber());
            if($packageReader->getHeader()->getEntryPoint())
            {
                Console::out('Executable: Yes');
                Console::out('Entry Point: ' . $packageReader->getHeader()->getEntryPoint());
            }
            else
            {
                Console::out('Executable: No');
            }

            Console::out('Package: ' . $packageReader->getAssembly()->getPackage());
            Console::out('Package Name: ' . $packageReader->getAssembly()->getName());
            Console::out('Package Version: ' . $packageReader->getAssembly()->getVersion());

            foreach($packageReader->getAllReferences() as $reference)
            {
                switch(get_class($reference))
                {
                    case ResourceReference::class:
                        /** @var ResourceReference $reference */
                        Console::out('Resource: ' . $reference->getName() . ' (Offset: ' . $reference->getOffset() . ', Size: ' . $reference->getSize() . ' bytes)');
                        break;

                    case ComponentReference::class:
                        /** @var ComponentReference $reference */
                        Console::out('Component: ' . $reference->getName() . ' (Offset: ' . $reference->getOffset() . ', Size: ' . $reference->getSize() . ' bytes)');
                        break;

                    case ExecutionUnitReference::class:
                        /** @var ExecutionUnitReference $reference */
                        Console::out('Execution Unit: ' . $reference->getName() . ' (Offset: ' . $reference->getOffset() . ', Size: ' . $reference->getSize() . ' bytes)');
                        $executionUnit = $packageReader->readExecutionUnit($reference);
                        Console::out('  - Name: ' . $executionUnit->getName());
                        Console::out('  - Type: ' . $executionUnit->getType()->value);
                        Console::out('  - Mode: ' . $executionUnit->getMode()->value);
                        Console::out('  - Entry Point: ' . $executionUnit->getEntryPoint());
                        Console::out('  - Working Directory: ' . $executionUnit->getWorkingDirectory());
                        if(!is_null($executionUnit->getRequiredFiles()))
                        {
                            Console::out('  - Required Files: ' . implode($executionUnit->getRequiredFiles(), ', '));
                        }
                        break;

                    default:
                        Console::out('Unknown Reference Type: ' . get_class($reference));
                        break;
                }
            }

            return 0;
        }
    }