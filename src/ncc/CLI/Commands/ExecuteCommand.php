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
    use ncc\Runtime;

    class ExecuteCommand extends AbstractCommandHandler
    {
        public static function handle(array $argv): int
        {
            $package = $argv['package'] ?? $argv['p'] ?? null;
            $version = $argv['version'] ?? $argv['v'] ?? 'latest';
            $executionUnit = $argv['unit'] ?? $argv['u'] ?? null;

            // Parse arguments to pass to the executed package
            // Everything after --args should be passed as arguments
            $arguments = [];
            global $argv;
            $argsIndex = array_search('--args', $argv, true);
            
            if($argsIndex !== false && $argsIndex < count($argv) - 1)
            {
                // Get all arguments after --args
                $arguments = array_slice($argv, $argsIndex + 1);
            }

            if($package === null)
            {
                Console::error('No package specified. Use --package or -p to specify a package to execute.');
                return 1;
            }

            try
            {
                $result = Runtime::execute($package, $version, $executionUnit, $arguments);
                
                // Convert result to exit code
                // For PHP scripts, the return value might not be an integer
                // For system commands, it's already an exit code
                if(is_int($result))
                {
                    return $result;
                }
                
                // If the result is a boolean, convert to exit code (false = 1, true = 0)
                if(is_bool($result))
                {
                    return $result ? 0 : 1;
                }
                
                // For any other return type, consider it successful
                return 0;
            }
            catch(Exception $e)
            {
                Console::error(sprintf('Failed to execute package: %s', $e->getMessage()));
                return 1;
            }
        }
    }